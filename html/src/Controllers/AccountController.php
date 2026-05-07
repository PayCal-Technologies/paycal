<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Infrastructure\Session\ActivityMonitor;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Database;
use PayCal\Domain\EmailGarum;
use PayCal\Domain\Encryption\EnvelopeFormat;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\ProtectedMode;
use PayCal\Domain\RecoveryKey;
use PayCal\Infrastructure\Resilience\RedisReliabilityService;
use PayCal\Domain\Response;
use PayCal\Infrastructure\Telemetry\SecurityLog;
use PayCal\Domain\User;
use PayCal\Domain\UserFields;

/**
 * AccountController.php
 *
 * Purpose: Authenticated account-management API layer for encryption bootstrap,
 * recovery-material handling, activity views, and protected account actions.
 *
 * Developer notes:
 * - This controller coordinates sensitive account flows; keep encryption and
 *   recovery rules centralized in domain helpers where possible.
 * - Protected-mode gates, reliability checks, and security logging are part of
 *   the behavior contract for these endpoints.
 *
 * Architectural role:
 * - Entry-point controller for request handling, authorization enforcement,
 *   and response or render shaping at the web boundary.
 * - Domain policy, persistence rules, and side-effect orchestration should
 *   stay in collaborators rather than expanding controller state.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @subpackage HTTP
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */



/**
 * Account-management API surface.
 *
 * Responsibilities:
 * - Expose authenticated account endpoints used by the profile/account UI.
 * - Coordinate encryption bootstrap and recovery-key lifecycle actions.
 * - Route account-sensitive operations through audit and reliability helpers.
 */
class AccountController
{
    private const FINGERPRINT_HEX_LENGTH = 16;
    private const ENCRYPTION_SALT_BYTES = 32;

    /**
     * Handles passkeyWrappedDekKey operation.
     */
    private function passkeyWrappedDekKey(string $userUUID): string
    {
        return Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks';
    }

    /**
     * Handles credentialFingerprint operation.
     */
    private function credentialFingerprint(string $credentialId): string
    {
        if ($credentialId === '') {
            return '';
        }

        return substr(hash('sha256', $credentialId), 0, self::FINGERPRINT_HEX_LENGTH);
    }

    /**
     * GET /api/v1/user/account/bootstrap
     * Returns stable per-user encryption salt for KEK derivation.
     */
    #[Route('user/account/bootstrap', ['GET'])]
    /**
     * Handles bootstrap operation.
     */
    public function bootstrap(): void
    {
        Authentication::abortIfUnauthenticated();

        if (Authentication::isCurrentSessionRecoveryPending()) {
            Response::error('[Account] Recovery in progress.', [], HttpStatus::HTTP_FORBIDDEN);
        }

        $user = User::current();

        // Check if salt exists, else generate and persist
        $salt = $user->encryption_salt;
        if (empty($salt)) {
            $rawSalt = random_bytes(self::ENCRYPTION_SALT_BYTES);
            $salt = base64_encode($rawSalt);
            $user->updateSettings([
                UserFields::ENCRYPTION_SALT->value => $salt
            ]);
            \PayCal\Observability\Lens::add('[Bootstrap] Salt generated and persisted', ['user_uuid' => $user->user_uuid]);
        } else {
            \PayCal\Observability\Lens::add('[Bootstrap] Salt already exists', ['user_uuid' => $user->user_uuid]);
        }

        // Fetch wrapped DEK and version if present
        $wrappedDek = $user->wrapped_dek;
        $wrappedDekPasskey = '';
        $dekVersion = $user->dek_version;
        $cryptoVersion = $user->crypto_version > 0 ? $user->crypto_version : 1;
        $passwordOnlyWarning = ProtectedMode::isCurrentSessionPasswordOnly();
        $authStrength = ProtectedMode::getCurrentAuthStrength();
        $passkeyEnabled = ProtectedMode::isPasskeyEnabled($user->user_uuid);
        
        // Fetch session credential_id (if logged in via passkey)
        $sessionCredentialId = '';
        $sessionHash = \PayCal\Domain\Authentication::getSessionHashFromCookie();
        if ($sessionHash) {
            $sessionKey = \PayCal\Domain\Constants\Keys::SESSION . ':' . $sessionHash;
            $sessionCredentialId = (string) \PayCal\Domain\Database::hget($sessionKey, 'credential_id');
        }
        
        $credentialId = '';
        $credentialSource = 'none';
        $credentialSetKey = Keys::webauthnUserCredentials($user->user_uuid);
        $credentialIds = \PayCal\Domain\Database::smembers($credentialSetKey);
        $credentialIds = array_values(array_filter(array_map(
            static fn ($value): string => (string) $value,
            $credentialIds
        ), static fn (string $value): bool => $value !== ''));
        if ($sessionCredentialId !== '') {
            $credentialId = $sessionCredentialId;
            $credentialSource = 'session_credential';
        }

        // Resolve the passkey wrapper for the selected credential from multi-wrapper storage.
        $passkeyWrappedDekKey = $this->passkeyWrappedDekKey($user->user_uuid);
        $wrappedCredentialCount = 0;
        foreach ($credentialIds as $candidateCredentialId) {
            $candidateWrapped = (string) Database::hget($passkeyWrappedDekKey, $candidateCredentialId);
            if ($candidateWrapped !== '') {
                $wrappedCredentialCount++;
            }
        }

        if ($credentialId !== '') {
            $wrappedDekPasskey = (string) Database::hget($passkeyWrappedDekKey, $credentialId);
        }

        $wrappedDekPasskeyMeta = [
            'present' => !empty($wrappedDekPasskey),
            'length' => strlen($wrappedDekPasskey),
            'decodeOk' => false,
            'envelopeVersion' => null,
        ];

        if ($wrappedDekPasskey !== '') {
            $decodedEnvelope = base64_decode($wrappedDekPasskey, true);
            if (!is_string($decodedEnvelope) || $decodedEnvelope === '') {
                // non-decodable envelope; leave meta defaults
            } else {
                $wrappedDekPasskeyMeta['decodeOk'] = true;
                $envelope = json_decode($decodedEnvelope, true);
                if (is_array($envelope)) {
                    $wrappedDekPasskeyMeta['envelopeVersion'] = $envelope['version'] ?? $envelope['v'] ?? null;
                }
            }
        }

        \PayCal\Observability\Lens::add('[Bootstrap] Encryption state', [
            'user_uuid' => $user->user_uuid,
            'hasWrappedDek' => !empty($wrappedDek),
            'hasWrappedDekPasskey' => !empty($wrappedDekPasskey),
            'dekVersion' => $dekVersion,
            'cryptoVersion' => $cryptoVersion,
            'passwordOnlyWarning' => $passwordOnlyWarning,
            'authStrength' => $authStrength,
            'passkeyEnabled' => $passkeyEnabled,
            'hasCredentialId' => !empty($credentialId),
            'sessionCredentialId' => $sessionCredentialId,
            'sessionCredentialIdFp' => $this->credentialFingerprint($sessionCredentialId),
            'selectedCredentialIdFp' => $this->credentialFingerprint($credentialId),
            'selectedCredentialSource' => $credentialSource,
            'credentialSetCount' => count($credentialIds),
            'wrappedCredentialCount' => $wrappedCredentialCount,
            'wrappedDekPasskeyMeta' => $wrappedDekPasskeyMeta,
        ]);

        $authDiagnostics = [
            'userUuid' => $user->user_uuid,
            'emailVerified' => (bool) $user->email_verified,
            'passwordOnlyWarning' => $passwordOnlyWarning,
            'authStrength' => $authStrength,
            'passkeyEnabled' => $passkeyEnabled,
            'sessionCredentialIdPresent' => $sessionCredentialId !== '',
            'sessionCredentialIdFp' => $this->credentialFingerprint($sessionCredentialId),
            'selectedCredentialIdFp' => $this->credentialFingerprint($credentialId),
            'selectedCredentialSource' => $credentialSource,
            'credentialSetCount' => count($credentialIds),
            'wrappedCredentialCount' => $wrappedCredentialCount,
            'hasWrappedDekPassword' => !empty($wrappedDek),
            'hasWrappedDekPasskey' => !empty($wrappedDekPasskey),
            'wrappedDekPasskeyMeta' => $wrappedDekPasskeyMeta,
        ];

        if (Environment::devSecurityDisabled()) {
            $authDiagnostics['lens'] = \PayCal\Observability\Lens::data();
        }

        Response::success('[Account] Bootstrap payload.', [
            'userId' => $user->user_uuid,
            'credentialId' => $credentialId,
            'sessionCredentialId' => $sessionCredentialId,
            'credentialIds' => $credentialIds,
            'credentialSetCount' => count($credentialIds),
            'wrappedCredentialCount' => $wrappedCredentialCount,
            'encryptionSalt' => $salt,
            'wrappedDek' => $wrappedDek,
            'wrappedDekPassword' => $wrappedDek,
            'wrappedDekPasskey' => $wrappedDekPasskey,
            'wrappedDekPasskeyForCredential' => $wrappedDekPasskey,
            'dekVersion' => $dekVersion,
            'cryptoVersion' => $cryptoVersion,
            'passwordOnlyWarning' => $passwordOnlyWarning,
            'authStrength' => $authStrength,
            'passkeyEnabled' => $passkeyEnabled,
            'mutationAllowed' => true,
            'stepUpRequiredForSensitiveActions' => $passwordOnlyWarning,
            'authDiagnostics' => $authDiagnostics,
        ]);
    }

    /**
     * POST user/account/recovery-key
     *
     * Stores the recovery-wrapped DEK and related proof material for the
     * current authenticated user, enabling future account recovery without a password.
     */
    #[Route('user/account/recovery-key', ['POST'])]
    /**
     * Handles createRecoveryKey operation.
     */
    public function createRecoveryKey(): void
    {
        Authentication::abortIfUnauthenticated();

        $mutationGate = RedisReliabilityService::allowMutations();
        if ($mutationGate['allowed'] !== true) {
            Response::error(
                '[Account] Redis reliability guard blocked mutation.',
                ['redis_guard' => $mutationGate],
                HttpStatus::HTTP_SERVICE_UNAVAILABLE
            );
            return;
        }

        $payload = $this->jsonBody();
        $wrappedDekRecovery = $this->scalarString($payload['wrappedDekRecovery'] ?? '');
        $accountRecoverySalt = $this->scalarString($payload['accountRecoverySalt'] ?? '');
        $recoveryProofKey = $this->scalarString($payload['recoveryProofKey'] ?? '');
        $recoveryKey = trim($this->scalarString($payload['recoveryKey'] ?? ''));

        if ($wrappedDekRecovery === '' || $accountRecoverySalt === '' || $recoveryProofKey === '' || $recoveryKey === '') {
            $this->fail('Recovery key payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
        }

        if (!RecoveryKey::validate($recoveryKey)) {
            $this->fail('Recovery key payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
        }

        if (base64_decode($accountRecoverySalt, true) === false || base64_decode($recoveryProofKey, true) === false) {
            $this->fail('Recovery key payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
        }

        $decodedEnvelopeJson = base64_decode($wrappedDekRecovery, true);
        if (!is_string($decodedEnvelopeJson) || $decodedEnvelopeJson === '') {
            $this->fail('Recovery key payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
        }

        try {
            EnvelopeFormat::fromJson($decodedEnvelopeJson);
        } catch (\Throwable) {
            $this->fail('Recovery key payload incomplete.', HttpStatus::HTTP_BAD_REQUEST);
        }

        $user = User::current();
        $emailSent = false;
        try {
            $emailSent = EmailGarum::sendRecoveryKeyEmail($recoveryKey, $user->email, $user->full_name);
        } catch (\Throwable $exception) {
            SecurityLog::log('recovery_key_created_from_settings_email_exception', [
                'user_uuid' => $user->user_uuid,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            $this->fail('Unable to email Recovery Key right now. No changes were saved.', HttpStatus::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$emailSent) {
            SecurityLog::log('recovery_key_created_from_settings_email_failed', [
                'user_uuid' => $user->user_uuid,
                'email' => $user->email,
            ]);

            $this->fail('Unable to email Recovery Key right now. No changes were saved.', HttpStatus::HTTP_SERVICE_UNAVAILABLE);
        }

        $userKey = Keys::USER . ':' . $user->user_uuid;

        Database::hset($userKey, [
            UserFields::ACCOUNT_RECOVERY_SALT->value => $accountRecoverySalt,
            UserFields::WRAPPED_DEK_RECOVERY->value => $wrappedDekRecovery,
            UserFields::RECOVERY_KEY_GENERATED->value => '1',
            UserFields::RECOVERY_PROOF_KEY->value => $recoveryProofKey,
            UserFields::RECOVERY_PROOF_KEY_VERSION->value => '1',
        ]);

        SecurityLog::log('recovery_key_created_from_settings', [
            'user_uuid' => $user->user_uuid,
            'email' => $user->email,
        ]);
        try {
            \PayCal\Infrastructure\Audit\SystemAuditRepository::append('user.recovery_key.created', $user->user_uuid, [
                'method' => 'settings',
            ]);
        } catch (\Throwable) {
        }

        Response::success('Recovery key created and emailed.', ['emailSent' => true]);
    }

    /**
     * GET /api/v1/user/account/activity
     *
     * Returns current login details, active session metadata, and browser info
     * for the authenticated user.
     */
    #[Route('user/account/activity', ['GET'])]
    /**
     * Handles activity operation.
     */
    public function activity(): void
    {
        Authentication::abortIfUnauthenticated();

        $user = User::current();
        $activity = ActivityMonitor::snapshotForUser($user->user_uuid);

        Response::success('[Account] Activity snapshot.', $activity);
    }

    /** @return array<string, mixed> */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Handles scalarString operation.
     */
    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Handles fail operation.
     */
    private function fail(string $message, int $status): never
    {
        Response::error($message, [], $status);
        throw new \RuntimeException($message);
    }
}


