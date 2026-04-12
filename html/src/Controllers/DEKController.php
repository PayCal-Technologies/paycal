<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Authentication;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Database;
use PayCal\Domain\Encryption\EnvelopeFormat;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\RedisReliabilityService;
use PayCal\Domain\Response;
use PayCal\Domain\User;
use PayCal\Domain\UserFields;

/**
 * DEKController.php
 *
 * Purpose: API layer for Data Encryption Key wrapper retrieval, storage, and
 * compatibility handling during client encryption bootstrap.
 *
 * Developer notes:
 * - DEK wrapper handling is security-sensitive and versioned; preserve the
 *   existing validation and reliability checks around stored wrappers.
 * - This controller should orchestrate transport concerns only, not invent new
 *   crypto semantics outside the domain encryption helpers.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * DEK wrapper API surface.
 *
 * Responsibilities:
 * - Read and persist wrapped DEK material for authenticated users.
 * - Validate wrapper shape, version metadata, and response boundaries.
 * - Coordinate storage helpers used by client-side encryption flows.
 */
class DEKController
{
    private const FINGERPRINT_HEX_LENGTH = 16;
    private const WRAPPED_DEK_MIN_LENGTH = 32;
    private const VERSION_MIN = 1;
    private const VERSION_MAX = 9999;

    /**
     * Handles passkeyWrappedDekKey operation.
     */
    private function passkeyWrappedDekKey(string $userUUID): string
    {
        return Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks';
    }

    /**
     * Resolve the credential ID to use for DEK wrapping.
     * 
     * @param string $userUUID The user's UUID
     * @param array<mixed, mixed> $body The request body
     * @return string The resolved credential ID, or empty string if none found
     */
    private function resolveCredentialIdForUser(string $userUUID, array $body): string
    {
        $credentialSetKey = Keys::webauthnUserCredentials($userUUID);

        $sessionCredentialId = '';
        $sessionHash = Authentication::getSessionHashFromCookie();
        if ($sessionHash) {
            $sessionCredentialId = (string) Database::hget(Keys::SESSION . ':' . $sessionHash, 'credential_id');
        }

        $bodyCredentialId = $body['credentialId'] ?? '';
        if (is_string($bodyCredentialId) && $bodyCredentialId !== '') {
            // If session carries a credential, caller must bind wrapper to that same credential.
            if ($sessionCredentialId !== '' && !hash_equals($sessionCredentialId, $bodyCredentialId)) {
                return '';
            }

            return Database::sismember($credentialSetKey, $bodyCredentialId) === 1
                ? $bodyCredentialId
                : '';
        }

        if ($sessionCredentialId !== '') {
            return Database::sismember($credentialSetKey, $sessionCredentialId) === 1
                ? $sessionCredentialId
                : '';
        }

        return '';
    }

    /**
     * Handles safeFingerprint operation.
     */
    private function safeFingerprint(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return substr(hash('sha256', $value), 0, self::FINGERPRINT_HEX_LENGTH);
    }

    /**
     * GET /api/v1/user/account/wrapped-dek
    * Returns the user's wrapped passkey DEK and versions.
     */
    #[Route('user/account/wrapped-dek', ['GET'])]
    /**
     * Handles getWrappedDek operation.
     */
    public function getWrappedDek(): void
    {
        Authentication::abortIfUnauthenticated();

        $user = User::current();

        $sessionCredentialId = '';
        $sessionHash = Authentication::getSessionHashFromCookie();
        if ($sessionHash) {
            $sessionCredentialId = (string) Database::hget(Keys::SESSION . ':' . $sessionHash, 'credential_id');
        }

        $wrappedDekPasskey = '';
        if ($sessionCredentialId !== '') {
            $wrappedDekPasskey = (string) Database::hget($this->passkeyWrappedDekKey($user->user_uuid), $sessionCredentialId);
        }

        Response::success('[DEK] Wrapped DEK.', [
            'credentialId' => $sessionCredentialId,
            'wrappedDekPassword' => $user->wrapped_dek,
            'wrappedDekPasskey' => $wrappedDekPasskey,
            'dekVersion' => $user->dek_version,
            'cryptoVersion' => $user->crypto_version > 0 ? $user->crypto_version : 1,
        ]);
    }

    /**
     * POST /api/v1/user/crypto/passkey-wrap
     * Stores passkey-wrapped DEK under credential_id in multi-wrapper storage.
     */
    #[Route('user/crypto/passkey-wrap', ['POST'])]
    /**
     * Handles postPasskeyWrap operation.
     */
    public function postPasskeyWrap(): void
    {
        $this->persistPasskeyWrapper();
    }

    /**
     * POST /api/v1/user/account/wrapped-dek
    * Stores a new wrapped passkey DEK (base64-encoded envelope JSON) and versions.
     *
     * Request body:
     * {
    *   "wrappedDekPasskey": "base64...",
    *   "dekVersion": 1,
    *   "cryptoVersion": 1
     * }
     */
    #[Route('user/account/wrapped-dek', ['POST'])]
    /**
     * Handles postWrappedDek operation.
     */
    public function postWrappedDek(): void
    {
        $this->persistPasskeyWrapper();
    }

    /**
     * Handles persistPasskeyWrapper operation.
     */
    private function persistPasskeyWrapper(): void
    {
        Authentication::abortIfUnauthenticated();

        $mutationGate = RedisReliabilityService::allowMutations();
        if ($mutationGate['allowed'] !== true) {
            Response::error(
                '[DEK] Redis reliability guard blocked mutation.',
                ['redis_guard' => $mutationGate],
                HttpStatus::HTTP_SERVICE_UNAVAILABLE
            );
            return;
        }

        $user = User::current();
        $raw = file_get_contents('php://input');
        $body = json_decode($raw ?: '', true);

        \PayCal\Observability\Lens::add('[DEK] Wrapped DEK request received', [
            'user_uuid' => $user->user_uuid,
            'rawLength' => is_string($raw) ? strlen($raw) : 0,
            'bodyKeys' => is_array($body) ? array_keys($body) : [],
        ]);

        if (!is_array($body)) {
            Response::error('[DEK] Invalid JSON payload.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        $credentialId = $this->resolveCredentialIdForUser($user->user_uuid, $body);
        if ($credentialId === '') {
            Response::error('[DEK] credentialId required for passkey wrapper storage.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        $wrappedDekPasskey = $body['wrappedDekPasskey'] ?? null;
        $dekVersionRaw = $body['dekVersion'] ?? 1;
        $cryptoVersionRaw = $body['cryptoVersion'] ?? 1;

        if (!is_string($wrappedDekPasskey) || strlen($wrappedDekPasskey) < self::WRAPPED_DEK_MIN_LENGTH) {
            Response::error('[DEK] wrappedDekPasskey required.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        if ((is_string($dekVersionRaw) && !ctype_digit($dekVersionRaw)) || (!is_int($dekVersionRaw) && !is_string($dekVersionRaw))) {
            Response::error('[DEK] Invalid dekVersion.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        $dekVersion = (int) $dekVersionRaw;
        if ($dekVersion < self::VERSION_MIN || $dekVersion > self::VERSION_MAX) {
            Response::error('[DEK] Invalid dekVersion range.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        if ((is_string($cryptoVersionRaw) && !ctype_digit($cryptoVersionRaw)) || (!is_int($cryptoVersionRaw) && !is_string($cryptoVersionRaw))) {
            Response::error('[DEK] Invalid cryptoVersion.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        $cryptoVersion = (int) $cryptoVersionRaw;
        if ($cryptoVersion < self::VERSION_MIN || $cryptoVersion > self::VERSION_MAX) {
            Response::error('[DEK] Invalid cryptoVersion range.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        $decodedEnvelopeJson = base64_decode($wrappedDekPasskey, true);
        if (false === $decodedEnvelopeJson || '' === $decodedEnvelopeJson) {
            Response::error('[DEK] wrappedDekPasskey must be valid base64.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        $decodedEnvelope = json_decode($decodedEnvelopeJson, true);
        \PayCal\Observability\Lens::add('[DEK] Wrapped DEK envelope diagnostics', [
            'user_uuid' => $user->user_uuid,
            'wrappedDekPasskeyLen' => strlen($wrappedDekPasskey),
            'wrappedDekPasskeyFp' => $this->safeFingerprint($wrappedDekPasskey),
            'decodedEnvelopeJsonLen' => strlen($decodedEnvelopeJson),
            'decodeEnvelopeIsArray' => is_array($decodedEnvelope),
            'envelopeVersion' => is_array($decodedEnvelope) ? ($decodedEnvelope['version'] ?? $decodedEnvelope['v'] ?? null) : null,
            'hasNonce' => is_array($decodedEnvelope) && (!empty($decodedEnvelope['nonce']) || !empty($decodedEnvelope['iv'])),
            'hasCiphertext' => is_array($decodedEnvelope) && (!empty($decodedEnvelope['ciphertext']) || !empty($decodedEnvelope['ct'])),
        ]);

        try {
            EnvelopeFormat::fromJson($decodedEnvelopeJson);
        } catch (\Throwable $e) {
            Response::error('[DEK] Invalid wrappedDekPasskey envelope.', [], HttpStatus::HTTP_BAD_REQUEST);
            return;
        }

        $updateFields = [];
        $updateFields[UserFields::DEK_VERSION->value] = $dekVersion;
        $updateFields[UserFields::CRYPTO_VERSION->value] = $cryptoVersion;

        \PayCal\Observability\Lens::add('[DEK] Wrapped DEK persisting', [
            'user_uuid' => $user->user_uuid,
            'credentialIdFp' => $this->safeFingerprint($credentialId),
            'dekVersion' => $dekVersion,
            'cryptoVersion' => $cryptoVersion,
            'hasWrappedDekPasskey' => true,
            'wrappedDekPasskeyFp' => $this->safeFingerprint($wrappedDekPasskey),
        ]);

        Database::hset($this->passkeyWrappedDekKey($user->user_uuid), [$credentialId => $wrappedDekPasskey]);
        $user->updateSettings($updateFields);

        // Clear legacy single-wrapper field once multi-wrapper storage is active.
        if (!empty($user->wrapped_dek_passkey)) {
            $user->updateSettings([
                UserFields::WRAPPED_DEK_PASSKEY->value => '',
            ]);
        }

        \PayCal\Observability\Lens::add('[DEK] Wrapped DEK persisted successfully', [
            'user_uuid' => $user->user_uuid,
            'credentialIdFp' => $this->safeFingerprint($credentialId),
            'dekVersion' => $dekVersion,
            'cryptoVersion' => $cryptoVersion,
            'hasWrappedDekPasskey' => true
        ]);

        Response::success('[DEK] Wrapped DEK stored.', ['credentialId' => $credentialId]);
    }
}


