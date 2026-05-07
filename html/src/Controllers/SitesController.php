<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\ArrayPager;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\DataGrid;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Infrastructure\Organization\OrganizationEncryptionService;
use PayCal\Domain\RequestGuard;
use PayCal\Domain\Response;
use PayCal\Domain\Money;
use PayCal\Domain\SiteFields;
use PayCal\Domain\Enums\SiteStatus;
use PayCal\Domain\Sites;
use PayCal\Domain\SitesService;
use PayCal\Domain\Strings;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Security\CorrelationBroker;
use PayCal\Domain\Security\CorrelationContext;
use PayCal\Domain\User;
use PayCal\Domain\Work;
use PayCal\Domain\WorkEntry;
use PayCal\Observability\Lens;

/**
 * SitesController.php
 *
 * Purpose: Route layer for site CRUD, datagrid responses, and delegated
 * organization-aware site operations.
 *
 * Developer notes:
 * - This controller owns HTTP concerns, response shaping, and permission
 *   gating; business rules should live in SitesService and related domain
 *   helpers.
 * - Owner override flows are sensitive. Never permit delegated mutation unless
 *   OrganizationDiscoveryService explicitly authorizes the actor.
 * - Site/work correlation output is privileged metadata and must remain behind
 *   correlation guards.
 * - Grid handlers should preserve stable payload shape because the frontend
 *   datagrid JS expects predictable keys and paging semantics.
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
 * Sites API surface.
 *
 * Responsibilities:
 * - Resolve the effective site owner for self and delegated org actions.
 * - Return list/grid payloads used by the Sites page.
 * - Coordinate writes through service/domain layers so validation, lock, and
 *   audit rules stay centralized.
 */
final class SitesController
{
  private const GRID_PAGE_SIZE = 10;
  private const STATUS_ENTRY_LOCKED = 'ENTRY_LOCKED';

  /**
   * Handles resolveSiteOwnerUUIDForMutation operation.
   */
  private function resolveSiteOwnerUUIDForMutation(): ?string
  {
    $actorUUID = User::currentUUID();

    $ownerRaw = InputSanitizer::postString('owner_uuid');
    if ('' === $ownerRaw && isset($_SERVER['HTTP_X_OWNER_UUID']) && is_scalar($_SERVER['HTTP_X_OWNER_UUID'])) {
      $ownerRaw = (string) $_SERVER['HTTP_X_OWNER_UUID'];
    }

    $ownerUUID = InputSanitizer::sanitizeString('' !== $ownerRaw ? $ownerRaw : $actorUUID);
    if ('' === $ownerUUID) {
      Response::error('[Sites] Invalid owner UUID.', [], HttpStatus::HTTP_BAD_REQUEST);
      return null;
    }

    if ($ownerUUID === $actorUUID) {
      return $ownerUUID;
    }

    $orgDiscovery = new OrganizationDiscoveryService();
    if (!$orgDiscovery->canMutateSitesForOwner($actorUUID, $ownerUUID)) {
      Response::error('[Sites] Insufficient organization scope for delegated site mutation.', [], HttpStatus::HTTP_FORBIDDEN);
      return null;
    }

    return $ownerUUID;
  }

  /**
   * Handles numericFloat operation.
   */
  private static function numericFloat(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

  /**
   * Handles scalarString operation.
   */
  private static function scalarString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * @param array<string,mixed> $row
   * @return array<string,mixed>|null
   */
  private static function decryptWorkRowIfNeeded(array $row, string $userUUID): ?array
  {
    $blob = self::scalarString($row['encrypted_blob'] ?? '');
    if ($blob === '') {
      return null;
    }

    $sessionHash = Authentication::getSessionHashFromCookie();
    if ($sessionHash === null) {
      return null;
    }

    $sessionKey = Keys::SESSION . ':' . $sessionHash;
    $credentialId = self::scalarString(Database::hget($sessionKey, 'credential_id'));
    $user = User::current();
    $saltB64 = self::scalarString($user->encryption_salt);
    if ($saltB64 === '') {
      return null;
    }

    $dek = self::resolveDekForEnvelope($blob, $userUUID, $credentialId, $saltB64);
    if ($dek === null) {
      return null;
    }

    $decryptedJson = self::decryptWorkBlob($blob, $dek);
    if ($decryptedJson === null) {
      return null;
    }

    $decoded = json_decode($decryptedJson, true);
    if (!is_array($decoded)) {
      return null;
    }

    $decodedWithStringKeys = [];
    foreach ($decoded as $field => $value) {
      if (is_string($field)) {
        $decodedWithStringKeys[$field] = $value;
      }
    }

    $normalized = WorkEntry::normalizeWorkEntryPayload($decodedWithStringKeys);
    $merged = $row;
    foreach ($normalized as $k => $v) {
      $merged[(string) $k] = $v;
    }

    if ((!isset($merged['hours']) || !is_numeric($merged['hours']))
      && isset($merged['regular_hours'], $merged['overtime_hours'])
      && is_numeric($merged['regular_hours'])
      && is_numeric($merged['overtime_hours'])) {
      $merged['hours'] = (float) $merged['regular_hours'] + (float) $merged['overtime_hours'];
    }

    $hasGrossAfterMerge = isset($merged['gross']) || isset($merged['g']);
    if (!$hasGrossAfterMerge) {
      $regularHours = self::numericFloat($merged['regular_hours'] ?? $merged['r'] ?? 0);
      $overtimeHours = self::numericFloat($merged['overtime_hours'] ?? $merged['o'] ?? 0);
      $travelHours = self::numericFloat($merged['travel_hours'] ?? $merged['t'] ?? 0);
      $loa = self::numericFloat($merged['living_out_allowance'] ?? $merged['l'] ?? 0);

      $wage = null;
      if (isset($merged['wage']) && is_numeric($merged['wage'])) {
        $wage = (string) $merged['wage'];
      } elseif (isset($merged['w']) && is_numeric($merged['w'])) {
        $wage = (string) $merged['w'];
      }

      $grossCents = Money::dollarsToCents((string) $loa);
      if ($wage !== null) {
        $grossCents += Money::calculateGross($regularHours, $overtimeHours, $wage);
        if ($travelHours > 0) {
          $travelPay = $travelHours * (float) $wage;
          $grossCents += Money::dollarsToCents((string) $travelPay);
        }
      }

      if ($grossCents > 0) {
        $merged['gross'] = Money::centsToDollars($grossCents);
      }
    }

    return $merged;
  }

  /**
   * Resolve the correct DEK wrapper for either personal or organization envelopes.
   */
  private static function resolveDekForEnvelope(string $blob, string $ownerUUID, string $credentialId, string $saltB64): ?string
  {
    $orgMeta = self::parseOrganizationEnvelopeMetadata($blob);
    if (is_array($orgMeta)) {
      $actorUUID = User::currentUUID();
      if ($actorUUID === '' || $credentialId === '') {
        self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'denied', 'missing_actor_or_credential');
        return null;
      }

      $wrap = (new OrganizationEncryptionService())->resolveActiveWrapForUnwrap(
        $orgMeta['org_id'],
        $orgMeta['segment'],
        $orgMeta['key_version'],
        $actorUUID,
        $credentialId,
        '',
        $orgMeta['dek_id']
      );
      if (!$wrap['success']) {
        self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'denied', 'wrap_resolution_failed');
        return null;
      }

      $wrappedDek = self::scalarString($wrap['data']['wrapped_dek'] ?? '');
      if ($wrappedDek === '') {
        self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'denied', 'missing_wrapped_dek');
        return null;
      }

      self::appendOrganizationWorkReadAudit($orgMeta, $actorUUID, $ownerUUID, 'success', 'wrap_resolved');

      return self::unwrapDekFromPasskeyWrapper($wrappedDek, $credentialId, $actorUUID, $saltB64);
    }

    $wrappedPasskeyMapKey = Keys::USER . ':' . $ownerUUID . ':passkey_wrapped_deks';
    $wrappedDekPasskey = '';
    if ($credentialId !== '') {
      $wrappedDekPasskey = self::scalarString(Database::hget($wrappedPasskeyMapKey, $credentialId));
    }

    if ($credentialId === '') {
      return null;
    }
    if ($wrappedDekPasskey === '') {
      return null;
    }

    return self::unwrapDekFromPasskeyWrapper($wrappedDekPasskey, $credentialId, $ownerUUID, $saltB64);
  }

  /** @param array{org_id: string, segment: string, key_version: string, dek_id: string} $orgMeta */
  private static function appendOrganizationWorkReadAudit(array $orgMeta, string $actorUUID, string $targetUUID, string $outcome, string $reason): void
  {
    if ($orgMeta['org_id'] === '') {
      return;
    }

    try {
      (new OrganizationDiscoveryService())->appendOrganizationAuditEvent(
        (string) $orgMeta['org_id'],
        'org.work.read',
        $actorUUID !== '' ? $actorUUID : User::currentUUID(),
        [
          'target_user_uuid' => $targetUUID,
          'segment' => $orgMeta['segment'],
          'key_version' => $orgMeta['key_version'],
          'dek_id' => $orgMeta['dek_id'],
          'outcome' => $outcome,
          'reason' => $reason,
        ]
      );
    } catch (\Throwable $e) {
      \PayCal\Domain\Log::debug('[SitesController] org.work.read audit emit failed: ' . $e->getMessage());
    }
  }

  /** @return array{org_id: string, segment: string, key_version: string, dek_id: string}|null */
  private static function parseOrganizationEnvelopeMetadata(string $blob): ?array
  {
    $decodedEnvelope = base64_decode($blob, true);
    if ($decodedEnvelope === false) {
      return null;
    }

    $envelope = json_decode($decodedEnvelope, true);
    if (!is_array($envelope)) {
      return null;
    }

    $metaRaw = $envelope['meta'] ?? null;
    $meta = is_array($metaRaw) ? $metaRaw : [];
    $modeRaw = $meta['encryption_mode'] ?? ($envelope['encryption_mode'] ?? '');
    $mode = is_scalar($modeRaw) ? trim((string) $modeRaw) : '';
    if ($mode !== 'organization') {
      return null;
    }

    $orgIdRaw = $meta['org_id'] ?? ($envelope['org_id'] ?? '');
    $segmentRaw = $meta['segment'] ?? ($envelope['segment'] ?? '');
    $keyVersionRaw = $meta['key_version'] ?? ($envelope['key_version'] ?? '');
    $dekIdRaw = $meta['dek_id'] ?? ($envelope['dek_id'] ?? '');

    $orgId = is_scalar($orgIdRaw) ? trim((string) $orgIdRaw) : '';
    $segment = is_scalar($segmentRaw) ? trim((string) $segmentRaw) : '';
    $keyVersion = is_scalar($keyVersionRaw) ? trim((string) $keyVersionRaw) : '';
    $dekId = is_scalar($dekIdRaw) ? trim((string) $dekIdRaw) : '';

    if ($orgId === '' || $segment === '' || $keyVersion === '' || $dekId === '') {
      return null;
    }

    return [
      'org_id' => $orgId,
      'segment' => $segment,
      'key_version' => $keyVersion,
      'dek_id' => $dekId,
    ];
  }

  /**
   * Handles hkdfPasskeyKek operation.
   */
  private static function hkdfPasskeyKek(string $credentialId, string $saltB64): ?string
  {
    $salt = base64_decode($saltB64, true);
    if ($salt === false) {
      return null;
    }

    return hash_hkdf('sha256', $credentialId, 32, 'paycal-passkey-kek', $salt);
  }

  /**
   * Handles unwrapDekFromPasskeyWrapper operation.
   */
  private static function unwrapDekFromPasskeyWrapper(string $wrappedDekPasskey, string $credentialId, string $userUUID, string $saltB64): ?string
  {
    $decodedEnvelope = base64_decode($wrappedDekPasskey, true);
    if ($decodedEnvelope === false) {
      return null;
    }

    $envelope = json_decode($decodedEnvelope, true);
    if (!is_array($envelope)) {
      return null;
    }

    $nonceB64 = self::scalarString($envelope['nonce'] ?? $envelope['iv'] ?? '');
    $ctB64 = self::scalarString($envelope['ciphertext'] ?? $envelope['ct'] ?? '');
    if ($nonceB64 === '' || $ctB64 === '') {
      return null;
    }

    $nonce = base64_decode($nonceB64, true);
    $ciphertextWithTag = base64_decode($ctB64, true);
    if ($nonce === false || $ciphertextWithTag === false || strlen($ciphertextWithTag) < 17) {
      return null;
    }

    $ciphertext = substr($ciphertextWithTag, 0, -16);
    $tag = substr($ciphertextWithTag, -16);

    $kekCanonical = self::hkdfPasskeyKek($credentialId, $saltB64);
    if (is_string($kekCanonical) && $kekCanonical !== '') {
      $dek = openssl_decrypt($ciphertext, 'aes-256-gcm', $kekCanonical, OPENSSL_RAW_DATA, $nonce, $tag);
      if (is_string($dek) && $dek !== '') {
        return $dek;
      }
    }

    return null;
  }

  /**
   * Handles decryptWorkBlob operation.
   */
  private static function decryptWorkBlob(string $blobBase64Envelope, string $dekRaw): ?string
  {
    $decodedEnvelope = base64_decode($blobBase64Envelope, true);
    if ($decodedEnvelope === false) {
      return null;
    }

    $envelope = json_decode($decodedEnvelope, true);
    if (!is_array($envelope)) {
      return null;
    }

    $nonceB64 = self::scalarString($envelope['nonce'] ?? $envelope['iv'] ?? '');
    $ctB64 = self::scalarString($envelope['ciphertext'] ?? $envelope['ct'] ?? '');
    $aad = self::scalarString($envelope['aad'] ?? '');
    if ($nonceB64 === '' || $ctB64 === '') {
      return null;
    }

    $nonce = base64_decode($nonceB64, true);
    $ciphertextWithTag = base64_decode($ctB64, true);
    if ($nonce === false || $ciphertextWithTag === false || strlen($ciphertextWithTag) < 17) {
      return null;
    }

    $ciphertext = substr($ciphertextWithTag, 0, -16);
    $tag = substr($ciphertextWithTag, -16);

    $plaintext = openssl_decrypt(
      $ciphertext,
      'aes-256-gcm',
      $dekRaw,
      OPENSSL_RAW_DATA,
      $nonce,
      $tag,
      $aad
    );

    return is_string($plaintext) ? $plaintext : null;
  }

  /**
   * Handles correlationContext operation.
   */
  private static function correlationContext(): string
  {
    $raw = InputSanitizer::sanitizeString(InputSanitizer::getString('correlation_context'));
    return $raw === '' ? 'self-service-earnings' : strtolower($raw);
  }

  /** @return array<string, mixed> */
  private static function siteFinancialCorrelationComposeProbe(): array
  {
    $context = new CorrelationContext(
      self::correlationContext(),
      User::currentUUID(),
      User::isAdmin() ? 'security-admin' : 'user',
      'sites-self-service',
      ['site_metadata:financial_payload'],
      'sites_controller'
    );

    return CorrelationBroker::compose(
      ['scope' => 'site_metadata'],
      ['scope' => 'financial_payload'],
      'site_metadata',
      'financial_payload',
      $context
    );
  }

  /**
   * Constructor. Aborts with 401 if the request is not authenticated.
   */
  public function __construct()
  {
    Authentication::abortIfUnauthenticated();
  }

  /**
   * Update sites for the current user.
   */
  #[Route('sites/update', ['POST'])]
  /**
   * Handles updateSites operation.
   */
  public function updateSites(): void
  {
    $ownerUUID = $this->resolveSiteOwnerUUIDForMutation();
    if (null === $ownerUUID) {
      return;
    }

    // Check if this is a single site update (for Edit dialog)
    $id = InputSanitizer::postString('id');
    $sites = InputSanitizer::postArray('sites');
    if (!empty($id) && empty($sites)) {
      $this->updateSingleSite($ownerUUID);

      return;
    }

    // Allowed scalar + array fields for Sites operations
    $allowedStrings = [
        SiteFields::status->value,
        SiteFields::bulk_action->value,
        SiteFields::id->value,
      'owner_uuid',
    ];

    $allowedArrays = [
        SiteFields::sites->value,
        SiteFields::site_ids->value,
        'selected_sites',
    ];

    $filtered = RequestGuard::filterPost($allowedStrings, $allowedArrays);

    if (false === $filtered) {
      Response::error('[SiC] RequestGuard failed.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new SitesService();
    $ok = $service->update($ownerUUID, $filtered);

    if ($ok) {
      $sites = $service->get($ownerUUID);
      Response::success('[SiC] Update success.', ['sites' => $sites], HttpStatus::HTTP_OK);
    } else {
      Response::error('[SiC] Update failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Delete a site by ID and archive its work entries.
   */
  #[Route('sites/delete', ['POST'])]
  /**
   * Handles deleteSite operation.
   */
  public function deleteSite(): void
  {
    $ownerUUID = $this->resolveSiteOwnerUUIDForMutation();
    if (null === $ownerUUID) {
      return;
    }

    // ID may come from POST or header
    $id = $_SERVER['HTTP_X_RESOURCE_ID'] ?? InputSanitizer::postString('id') ?: null;

    /** @var ?string $id */
    $filteredId = RequestGuard::deleteCheck($id);

    if (false === $filteredId) {
      return;
    }

    $service = new SitesService();
    $result = $service->delete($ownerUUID, $filteredId);

    if ($result['success']) {
      Response::success(
        "[Sites] Site deleted and {$result['archived_count']} work entries archived.",
        ['archived_count' => $result['archived_count']],
        HttpStatus::HTTP_OK
      );
    } elseif ($result['locked_entries'] > 0) {
      // Lock enforcement: entries are within grace period
      Response::error(
        "[Sites] Cannot delete site: {$result['locked_entries']} work entries are within the editing grace period. Please wait until the grace period expires.",
        ['locked_entries' => $result['locked_entries']],
        HttpStatus::HTTP_UNPROCESSABLE
      );
    } else {
      Response::error('[Sites] Delete failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Get summary of archived work entries for a site.
   */
  #[Route('sites/archived', ['GET'])]
  /**
   * Handles getArchivedWork operation.
   */
  public function getArchivedWork(): void
  {
    $siteId = InputSanitizer::getString('site_id') ?? '';

    if ('' === $siteId) {
      Response::error('[Sites] Missing site_id parameter.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new SitesService();
    $summary = $service->getArchivedWorkSummary(User::currentUUID(), $siteId);

    Response::success('[Sites] Archived work summary retrieved.', $summary, HttpStatus::HTTP_OK);
  }

  /**
   * Permanently delete archived work entries for a site.
   * This is the "finality delete" - cannot be undone.
   * Only works on archived sites.
   */
  #[Route('sites/permanent-delete', ['DELETE', 'POST'])]
  /**
   * Handles permanentDelete operation.
   */
  public function permanentDelete(): void
  {
    $ownerUUID = $this->resolveSiteOwnerUUIDForMutation();
    if (null === $ownerUUID) {
      return;
    }

    $siteIdRaw = InputSanitizer::postString('site_id') ?: ($_SERVER['HTTP_X_RESOURCE_ID'] ?? InputSanitizer::postString('id'));
    $siteId = is_scalar($siteIdRaw) ? (string) $siteIdRaw : '';

    if ('' === $siteId) {
      Response::error('[Sites] Missing site_id parameter.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new SitesService();
    $result = $service->permanentDelete($ownerUUID, $siteId);

    if ($result['success']) {
      Response::success(
        "[Sites] Permanently deleted site and {$result['deleted_work_count']} archived work entries.",
        ['deleted_work_count' => $result['deleted_work_count']],
        HttpStatus::HTTP_OK
      );
    } elseif ($result['locked_entries'] > 0) {
      Response::error(
        Strings::i18n(self::STATUS_ENTRY_LOCKED),
        [
          'status' => 'ENTRY_LOCKED',
          'locked_entries' => (int) $result['locked_entries'],
        ],
        HttpStatus::HTTP_FORBIDDEN
      );
    } else {
      Response::error('[Sites] Permanent delete failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Get all sites for current user.
   */
  #[Route('sites', ['GET'])]
  /**
   * Handles getUserSites operation.
   */
  public function getUserSites(): void
  {
    Lens::timeStart('SitesService::get');
    $service = new SitesService();
    $sites = $service->get(User::currentUUID());
    Lens::timeEnd('SitesService::get');

    Lens::add('getUserSites Result', [
      'total_sites' => count($sites),
      'site_ids' => array_slice(array_keys($sites), 0, 5),
      'service_class' => get_class($service)
    ]);

    Response::success('[Sites] Retrieved.', ['sites' => $sites], HttpStatus::HTTP_OK);
  }

  /**
   * Get DataGrid HTML for sites.
   */
  #[Route('sites/grid', ['GET'])]
  /**
   * Handles getGrid operation.
   */
  public function getGrid(): void
  {
    Lens::add('SitesController::getGrid() called', [
      'query_params' => $_GET
    ]);
    
    $status = InputSanitizer::getString('status') ?? SiteStatus::ACTIVE->value;
    $page = (int) (InputSanitizer::getString('page') ?? 1);
    $search = InputSanitizer::getString('search') ?? '';
    $sort = InputSanitizer::getString('sort') ?? 'site_name';
    $direction = InputSanitizer::getString('direction') ?? 'asc';

    Lens::add('Query Parameters', [
      'status' => $status,
      'page' => $page,
      'search' => $search,
      'sort' => $sort,
      'direction' => $direction
    ]);

    $i18n = [];
    foreach (['SITES_CREATE'] as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $grid = DataGrid::create("sites-{$status}", 'Sites');
    // Only show Create Site button on Active tab
    if (SiteStatus::ACTIVE->value === $status) {
      $grid->addControl([
          'type' => 'primary',
          'label' => $i18n['SITES_CREATE'],
          'action' => 'create-site',
      ]);
    }
    $grid->enableSearch('Filter sites…');
    $grid->enableSorting();
    $grid->addColumn('site_name', 'Name', true, '3fr');
    $grid->addColumn('wage', 'Wg', true, '1fr');
    $grid->addColumn('living_out_allowance', 'LOA', true, '1fr');
    $grid->addColumn('travel_hours', 'Trv', true, '0.8fr');
    $grid->addColumn('province', 'Prov', true, '1.5fr');
    $grid->addColumn('entries', 'Ent', true, '0.8fr');
    // Use 3D box icon for active sites (archive), trash for archived (permanent delete)
    $actionIcon = SiteStatus::ACTIVE->value === $status ? '📦' : '🗑';
    $grid->addRowAction('delete', $actionIcon);
    $grid->setItemLabel('sites');

    Lens::timeStart('getSitesForGrid');
    $sites = $this->getSitesForGrid($status, $search, $sort, $direction);
    Lens::timeEnd('getSitesForGrid');

    Lens::add('Sites Retrieved', [
      'count' => count($sites),
      'status' => $status,
      'first_site_sample' => !empty($sites) ? array_slice((array)$sites[0], 0, 3) : null
    ]);

      $pager = new ArrayPager($sites, self::GRID_PAGE_SIZE);
    $pager->setPage($page);

    $html = $grid->table($pager);

    // Inject state attributes
    $gridDiv = '<div class="datagrid" data-grid="sites-'.$status.'" data-page="'.$page.'">';
    $start = ($pager->getPage() - 1) * $pager->getPageSize() + 1;
    $end = min($pager->getPage() * $pager->getPageSize(), $pager->getTotal());
    $total = $pager->getTotal();
    $replacement = '<div class="datagrid" data-grid="sites-'.$status.'" data-page="'.$page.'"'
      .' data-search="'.htmlspecialchars($search).'"'
      .' data-sort="'.htmlspecialchars($sort).'"'
      .' data-direction="'.htmlspecialchars($direction).'"'
      .' data-pagination-start="'.$start.'"'
      .' data-pagination-end="'.$end.'"'
      .' data-pagination-total="'.$total.'">';
    $html = str_replace($gridDiv, $replacement, $html);

    Lens::add('Grid Response', [
      'status_code' => 'success',
      'total_sites' => $total,
      'current_page' => $page,
        'page_count' => ceil($total / self::GRID_PAGE_SIZE)
    ]);

    Response::success('[Sites] Grid rendered.', ['html' => $html], HttpStatus::HTTP_OK);
  }

  /**
   * Get site data for a single site.
   */
  #[Route('sites/get', ['GET'])]
  /**
   * Handles getSiteData operation.
   */
  public function getSiteData(): void
  {
    $id = InputSanitizer::getString('id') ?? '';

    Lens::add('getSiteData Request', ['site_id' => $id]);

    if ('' === $id) {
      Lens::add('getSiteData Error', ['error' => 'Missing site ID'], 'error');
      Response::error('[Sites] Missing site ID.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    Lens::timeStart('Sites::getSiteById');
    $site = Sites::getInstance()->getSiteById(User::currentUUID(), $id);
    Lens::timeEnd('Sites::getSiteById');

    if (null === $site) {
      Lens::add('getSiteData Error', ['error' => 'Site not found', 'site_id' => $id], 'error');
      Response::error('[Sites] Site not found.', [], HttpStatus::HTTP_NOT_FOUND);

      return;
    }

    Lens::add('getSiteData Success', [
      'site_id' => $id,
      'site_name' => $site['site_name'] ?? 'unnamed',
      'province' => $site['province'] ?? 'unknown'
    ]);

    Response::success('[Sites] Site data retrieved.', ['site' => $site], HttpStatus::HTTP_OK);
  }

  /**
   * Get earnings breakdown by site for a given year.
   */
  #[Route('sites/earnings', ['GET'])]
  /**
   * Handles getSiteEarnings operation.
   */
  public function getSiteEarnings(): void
  {
    $correlationProbe = self::siteFinancialCorrelationComposeProbe();
    if (($correlationProbe['status'] ?? '') !== 'success') {
      Response::error('[SC] Correlation context denied.', [
        'context' => self::correlationContext(),
        'reason' => 'metadata_correlation_denied',
        'decision' => $correlationProbe['decision'] ?? null,
      ], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $year = (int) (InputSanitizer::getString('year') ?? date('Y'));

    Lens::add('getSiteEarnings Request', ['year' => $year]);

    if ($year < SystemConfig::get('year_min') || $year > SystemConfig::get('year_max')) {
      Lens::add('getSiteEarnings Error', ['error' => "Year {$year} out of range"], 'error');
      Response::error("[Sites] Year {$year} is out of allowed range.", [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $start = new \DateTimeImmutable("{$year}-01-01");
    $end = new \DateTimeImmutable("{$year}-12-31");

    // Get all work entries for the year
    Lens::timeStart('Work::getWorkInRange');
    $workEntriesGen = Work::getWorkInRange($start, $end->modify('+1 day'), User::currentUUID());
    Lens::timeEnd('Work::getWorkInRange');

    // Convert generator to array so we can count and iterate
    $workEntries = iterator_to_array($workEntriesGen);

    Lens::add('Work Entries Retrieved', [
      'year' => $year,
      'entry_count' => count($workEntries)
    ]);

    // Get all sites for user to map IDs to names
    Lens::timeStart('Sites::getSites for earnings');
    $sitesGen = Sites::getInstance()->getSites(User::currentUUID());
    Lens::timeEnd('Sites::getSites for earnings');

    // Convert generator to array
    $sites = iterator_to_array($sitesGen);

    Lens::add('Sites Map Building', [
      'site_count' => count($sites)
    ]);

    $siteMap = [];
    foreach ($sites as $siteId => $siteData) {
      $siteMap[$siteId] = [
          'id' => $siteId,
          'name' => $siteData['site_name'] ?? "Unknown Site ({$siteId})",
          'status' => $siteData['status'] ?? SiteStatus::ACTIVE->value,
      ];

    }

    Lens::add('Site Map Created', [
      'total_sites' => count($siteMap)
    ]);

    // Aggregate earnings by site
    $siteEarnings = [];
    $grandTotals = [
        'earnings' => 0.0,
        'hours' => 0.0,
        'regular_hours' => 0.0,
        'overtime_hours' => 0.0,
        'work_days' => 0,
    ];

    foreach ($workEntries as $key => $workData) {
      $resolved = self::decryptWorkRowIfNeeded($workData, User::currentUUID());
      if (is_array($resolved)) {
        $workData = $resolved;
      }

      $siteId = self::scalarString($workData['site_id'] ?? '');
      if ('' === $siteId) {
        continue;
      }

      // Initialize site entry if not exists
      if (!isset($siteEarnings[$siteId])) {
        $siteEarnings[$siteId] = [
            'site_id' => $siteId,
            'site_name' => $siteMap[$siteId]['name'] ?? "Unknown Site ({$siteId})",
            'site_status' => $siteMap[$siteId]['status'] ?? 'unknown',
            'total_earnings' => 0.0,
            'total_hours' => 0.0,
            'regular_hours' => 0.0,
            'overtime_hours' => 0.0,
            'work_days' => 0,
        ];
      }

      // Aggregate work data
      $gross = self::numericFloat($workData['gross'] ?? $workData['g'] ?? 0);
      $hours = self::numericFloat($workData['hours'] ?? $workData['h'] ?? 0);
      $regular = self::numericFloat($workData['regular_hours'] ?? $workData['r'] ?? 0);
      $overtime = self::numericFloat($workData['overtime_hours'] ?? $workData['o'] ?? 0);

      $siteEarnings[$siteId]['total_earnings'] += $gross;
      $siteEarnings[$siteId]['total_hours'] += $hours;
      $siteEarnings[$siteId]['regular_hours'] += $regular;
      $siteEarnings[$siteId]['overtime_hours'] += $overtime;
      ++$siteEarnings[$siteId]['work_days'];

      $grandTotals['earnings'] += $gross;
      $grandTotals['hours'] += $hours;
      $grandTotals['regular_hours'] += $regular;
      $grandTotals['overtime_hours'] += $overtime;
      ++$grandTotals['work_days'];
    }

    // Convert to indexed array and sort by earnings descending
    $siteList = array_values($siteEarnings);
    usort($siteList, fn ($a, $b) => $b['total_earnings'] <=> $a['total_earnings']);

    $grandTotals['sites_count'] = count($siteList);

    Lens::add('getSiteEarnings Result', [
      'year' => $year,
      'sites_with_earnings' => count($siteList),
      'total_earnings' => $grandTotals['earnings'],
      'total_hours' => $grandTotals['hours'],
      'work_days' => $grandTotals['work_days']
    ]);

    Response::success('[Sites] Earnings data retrieved.', [
        'year' => $year,
        'sites' => $siteList,
        'totals' => $grandTotals,
    ], HttpStatus::HTTP_OK);
  }

  /**
   * Create a new site.
   */
  #[Route('sites/create', ['POST'])]
  /**
   * Handles createSite operation.
   */
  public function createSite(): void
  {
    $ownerUUID = $this->resolveSiteOwnerUUIDForMutation();
    if (null === $ownerUUID) {
      return;
    }

    $data = [
        'site_name' => InputSanitizer::postString('site_name'),
        'wage' => InputSanitizer::postString('wage') ?: '0',
        'living_out_allowance' => InputSanitizer::postString('living_out_allowance') ?: '0',
        'travel_hours' => InputSanitizer::postString('travel_hours') ?: '0',
        'province' => InputSanitizer::postString('province'),
        'status' => InputSanitizer::postString('status') ?: SiteStatus::ACTIVE->value,
    ];

    $service = new SitesService();
    $siteId = $service->create($ownerUUID, $data);

    if ($siteId) {
      Response::success('[Sites] Created.', ['id' => $siteId], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Sites] Create failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Get orphaned work entries (work with no corresponding site).
   */
  #[Route('sites/orphaned', ['GET'])]
  /**
   * Handles getOrphanedWork operation.
   */
  public function getOrphanedWork(): void
  {
    $service = new SitesService();
    $result = $service->findOrphanedWork(User::currentUUID());

    Response::success(
      '[Sites] Orphaned work data retrieved.',
      $result,
      HttpStatus::HTTP_OK
    );
  }

  /**
   * Recover orphaned work by creating a site and binding entries.
   */
  #[Route('sites/recover', ['POST'])]
  /**
   * Handles recoverOrphanedWork operation.
   */
  public function recoverOrphanedWork(): void
  {
    $ownerUUID = $this->resolveSiteOwnerUUIDForMutation();
    if (null === $ownerUUID) {
      return;
    }

    $orphanedSiteId = InputSanitizer::postString('orphaned_site_id');
    $siteName = InputSanitizer::postString('site_name');
    $wage = InputSanitizer::postString('wage');
    $loa = InputSanitizer::postString('living_out_allowance');
    $travel = InputSanitizer::postString('travel_hours');
    $province = InputSanitizer::postString('province') ?: 'AB';

    if ('' === $orphanedSiteId || '' === $siteName) {
      Response::error('[Sites] Missing required parameters.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $siteData = [
        'site_name' => $siteName,
        'wage' => $wage,
        'living_out_allowance' => $loa,
        'travel_hours' => $travel,
        'province' => $province,
        'status' => SiteStatus::ACTIVE->value,
    ];

    $service = new SitesService();
    $result = $service->recoverOrphanedWork($ownerUUID, $orphanedSiteId, $siteData);

    if ($result['success']) {
      Response::success(
        "[Sites] Recovered {$result['bound_count']} orphaned work entries.",
        $result,
        HttpStatus::HTTP_OK
      );
    } else {
      Response::error('[Sites] Recovery failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Update a single site.
   */
  private function updateSingleSite(string $ownerUUID): void
  {
    $id = InputSanitizer::postString('id');
    $data = [
        'site_name' => InputSanitizer::postString('site_name'),
        'wage' => InputSanitizer::postString('wage') ?: '0',
        'living_out_allowance' => InputSanitizer::postString('living_out_allowance') ?: '0',
        'travel_hours' => InputSanitizer::postString('travel_hours') ?: '0',
        'province' => InputSanitizer::postString('province'),
        'status' => InputSanitizer::postString('status') ?: SiteStatus::ACTIVE->value,
    ];

    if ('' === $id) {
      Response::error('[Sites] Missing site ID.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $service = new SitesService();
    $ok = $service->updateSingle($ownerUUID, $id, $data);

    if ($ok) {
      Response::success('[Sites] Updated.', [], HttpStatus::HTTP_OK);
    } else {
      Response::error('[Sites] Update failed.', [], HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Get sites for grid display.
   * Applies search, sorting, and province name formatting.
   *
   * @param string $status    Status filter (active/inactive)
   * @param string $search    Search term
   * @param string $sort      Sort column
   * @param string $direction Sort direction (asc/desc)
   *
   * @return list<array<string, int|string>>
   */
  private function getSitesForGrid(string $status, string $search, string $sort, string $direction): array
  {
    Lens::timeStart('Sites::getSites Call');

    $currentUUID = User::currentUUID();
    Lens::add('Current User UUID', ['uuid' => $currentUUID]);
    
    // Verify we got a valid UUID (not PUBLIC_UUID)
    if ($currentUUID === SystemConfig::PUBLIC_UUID) {
      Lens::add('WARNING: Using PUBLIC_UUID instead of current user', [
        'uuid' => $currentUUID,
        'is_authenticated' => true
      ]);
    }

    $sitesInstance = Sites::getInstance();
    Lens::add('Sites::getInstance Check', ['instance_exists' => true]);

    // Get all sites with the specified status
    $sites = iterator_to_array(Sites::getSites($currentUUID, $status));

    Lens::timeEnd('Sites::getSites Call');

    Lens::add('getSitesForGrid Debug', [
      'requested_status' => $status,
      'sites_returned' => count($sites),
      'site_ids' => array_keys($sites),
      'first_site_sample' => !empty($sites) ? (array)array_values($sites)[0] : []
    ]);

    // Province mapping for display
    $provinces = [
        'AB' => 'Alberta',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
        'NB' => 'New Brunswick',
        'NL' => 'Newfoundland and Labrador',
        'NS' => 'Nova Scotia',
        'ON' => 'Ontario',
        'PE' => 'Prince Edward Island',
        'QC' => 'Quebec',
        'SK' => 'Saskatchewan',
        'NT' => 'Northwest Territories',
        'NU' => 'Nunavut',
        'YT' => 'Yukon',
    ];

    // Convert to indexed array with id field, format province, and count work entries
    $result = [];
    foreach ($sites as $siteId => $siteData) {
      $siteArray = $siteData;
      $siteArray['id'] = $siteId;

      // Set default name for unnamed/detached sites
      if (empty($siteArray['site_name'])) {
        $siteArray['site_name'] = "Unknown Site ({$siteId})";
      }

      // Convert province code to full name for display
      $provinceCode = $siteArray['province'] ?? '';
      $siteArray['province'] = $provinces[$provinceCode] ?? $provinceCode;

      // Count work entries (both active and archived)
      $activePattern = Keys::WORK . ':' . User::currentUUID() . ':*:' . $siteId;
      $archivedPattern = Keys::WORK . ':archived:' . User::currentUUID() . ':*:' . $siteId;
      Lens::timeStart("Database::scanKeys-{$siteId}");
      $activeKeys = Database::scanKeys($activePattern);
      $archivedKeys = Database::scanKeys($archivedPattern);
      Lens::timeEnd("Database::scanKeys-{$siteId}");
      $siteArray['entries'] = count($activeKeys) + count($archivedKeys);
      Lens::increment('sites_processed');

      $result[] = $siteArray;
    }

    // Apply search filter (case-insensitive)
    if ('' !== $search) {
      Lens::timeStart('Search Filter');
      $searchLower = mb_strtolower($search);
      $result = array_filter($result, function ($site) use ($searchLower) {
        $nameLower = mb_strtolower($site['site_name']);
        $provinceLower = mb_strtolower($site['province']);

        return str_contains($nameLower, $searchLower) || str_contains($provinceLower, $searchLower);
      });
      Lens::timeEnd('Search Filter');
      Lens::add('Search Filtered', ['query' => $search, 'results' => count($result)]);
    }

    // Apply sorting
    if ('' !== $sort && count($result) > 0) {
      Lens::timeStart('Sort Results');
      usort($result, function ($a, $b) use ($sort, $direction) {
        $aVal = $a[$sort] ?? '';
        $bVal = $b[$sort] ?? '';

        // Numeric comparison for wage, loa, travel_hours, entries
        if (in_array($sort, ['wage', 'living_out_allowance', 'travel_hours', 'entries'], true)) {
          $aVal = (float) $aVal;
          $bVal = (float) $bVal;
          $cmp = $aVal <=> $bVal;
        } else {
          $cmp = strcasecmp((string)$aVal, (string)$bVal);
        }

        return 'desc' === $direction ? -$cmp : $cmp;
      });
      Lens::timeEnd('Sort Results');
      Lens::add('Sort Applied', ['column' => $sort, 'direction' => $direction]);
    }

    Lens::add('getSitesForGrid Final', [
      'total_returned' => count($result),
      'status' => $status,
      'search_applied' => !empty($search),
      'sort_applied' => !empty($sort)
    ]);

    return array_values($result);
  }
}


