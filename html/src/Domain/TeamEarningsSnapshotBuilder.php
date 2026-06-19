<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Builds team earnings rollup snapshots for business workspace reports.
 */
final class TeamEarningsSnapshotBuilder
{
  /**
   * @return array{
   *   teamEarningsRows: list<array<string, mixed>>,
   *   teamEarningsTotals: array{reg_hours: float, ot_hours: float, gross: float, net: float},
   *   teamSiteMatchStats: array{match_owner_and_site: int, match_unique_site_id: int, match_site_name: int, included_unlinked: int},
   *   teamSiteDropSamples: list<array<string, string>>,
   *   teamSiteFallbackWarn: bool,
   *   teamUnlinkedOnlyWarn: bool,
   *   teamUnlinkedOnlyCount: int,
   *   orgSiteData: array<string, array<string, mixed>>,
   *   memberLoaTotals: array<string, float>,
   *   memberWeeklyH: array<string, array<string, float>>,
   *   memberDays: array<string, list<string>>
   * }
   */
  public static function build(string $businessId, int $year, string $actorUUID = ''): array
  {
    $businessId = trim($businessId);
    $empty = self::emptySnapshot();

    if ($businessId === '') {
      return $empty;
    }

    Lens::timeStart('Team Earnings: org site context');
    $orgSiteLinkContext = BusinessSiteLinkResolver::buildContext($businessId);
    Lens::timeEnd('Team Earnings: org site context');

    Lens::timeStart('Team Earnings: load org members');
    $orgMembers = BusinessMemberRepository::forBusiness($businessId, null, 'active', useCache: false);
    Lens::timeEnd('Team Earnings: load org members');
    Lens::add('Team Earnings: org member count', ['count' => count($orgMembers)]);

    if ($orgMembers === []) {
      return $empty;
    }

    $teamEarningsRows = [];
    $teamEarningsTotals = ['reg_hours' => 0.0, 'ot_hours' => 0.0, 'gross' => 0.0, 'net' => 0.0];
    $teamSiteMatchStats = [
      'match_owner_and_site' => 0,
      'match_unique_site_id' => 0,
      'match_site_name' => 0,
      'included_unlinked' => 0,
    ];
    $teamSiteDropSamples = [];
    $orgSiteData = [];
    $memberLoaTotals = [];
    $memberWeeklyH = [];
    $memberDays = [];

    Lens::timeStart('Team Earnings: member work scans');
    $teamWorkScanCount = 0;
    $teamWorkHgetallCount = 0;
    $memberBatchSize = MemberWorkEntriesFetcher::MEMBER_FETCH_BATCH_SIZE;
    $cachedMemberWork = BusinessWorkspaceCache::getMemberWork($businessId);
    $protectedGate = new BusinessProtectedDataAccess();

    foreach (array_chunk($orgMembers, $memberBatchSize) as $memberBatch) {
      $batchUuids = [];
      foreach ($memberBatch as $memberEntry) {
        $batchUuids[] = (string) $memberEntry['user']->user_uuid;
      }

      $teamWorkScanCount += count($batchUuids) * 2;
      $workEntriesByMember = $actorUUID !== ''
        ? $protectedGate->readMembersWork(
          $actorUUID,
          $businessId,
          $batchUuids,
          $year,
          false,
          'business.team_earnings.snapshot',
        )
        : self::resolveMemberWorkBatch($batchUuids, $year, $cachedMemberWork);

      foreach ($memberBatch as $memberEntry) {
        $memberUser = $memberEntry['user'];
        $memberUUID = (string) $memberUser->user_uuid;
        $memberName = (string) $memberUser->full_name;
        $memberRole = (string) $memberEntry['role'];

        $regHours = 0.0;
        $otHours = 0.0;
        $gross = 0.0;
        $net = 0.0;
        $monthlyBreakdown = [];

        $workKeys = $workEntriesByMember[$memberUUID] ?? [];
        $teamWorkHgetallCount += count($workKeys);

        foreach ($workKeys as $wk => $entry) {
          $keyParts = explode(':', $wk);
          $isArchived = isset($keyParts[1]) && $keyParts[1] === 'archived';
          $siteIdFromKey = $isArchived ? (string) ($keyParts[4] ?? '') : (string) ($keyParts[3] ?? '');
          $date = $isArchived
            ? (isset($keyParts[3]) && strlen((string) $keyParts[3]) >= 10 ? (string) $keyParts[3] : (string) ($entry['date'] ?? ''))
            : (isset($keyParts[2]) && strlen((string) $keyParts[2]) >= 10 ? (string) $keyParts[2] : (string) ($entry['date'] ?? ''));

          $siteOwnerCandidate = (string) ($entry['site_owner_uuid'] ?? $memberUUID);
          $matchStrategy = BusinessSiteLinkResolver::resolveMatchStrategy(
            $orgSiteLinkContext,
            $siteIdFromKey,
            $siteOwnerCandidate,
            (string) ($entry['site_name'] ?? ''),
          );
          if ($matchStrategy === 'no_match') {
            $teamSiteMatchStats['included_unlinked']++;
            if (count($teamSiteDropSamples) < 25) {
              $teamSiteDropSamples[] = [
                'work_key' => $wk,
                'member_uuid' => $memberUUID,
                'site_id' => $siteIdFromKey,
                'site_owner_uuid' => $siteOwnerCandidate,
                'site_name' => (string) ($entry['site_name'] ?? ''),
              ];
            }
            continue;
          } elseif ($matchStrategy === 'owner_and_site') {
            $teamSiteMatchStats['match_owner_and_site']++;
          } elseif ($matchStrategy === 'unique_site_id') {
            $teamSiteMatchStats['match_unique_site_id']++;
          } elseif ($matchStrategy === 'site_name') {
            $teamSiteMatchStats['match_site_name']++;
          }

          $month = strlen($date) >= 7 ? substr($date, 0, 7) : 'unknown';

          $eReg = (float) ($entry['regular_hours'] ?? 0);
          $eOt = (float) ($entry['overtime_hours'] ?? 0);
          $eGross = (float) ($entry['gross'] ?? 0);
          $eNet = (float) ($entry['net'] ?? $eGross);

          $eSiteN = (string) ($entry['site_name'] ?? '');
          if ($eSiteN === '') {
            $eSiteN = $siteIdFromKey !== '' ? $siteIdFromKey : Strings::i18n('EARNINGS_UNKNOWN_SITE');
          }
          $eSiteColor = strtoupper((string) ($entry['site_color'] ?? ''));
          if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $eSiteColor)) {
            $eSiteColor = '';
          }
          $eLoa = (float) ($entry['living_out_allowance'] ?? 0);
          if (!isset($orgSiteData[$eSiteN])) {
            $orgSiteData[$eSiteN] = ['gross' => 0.0, 'reg' => 0.0, 'ot' => 0.0, 'members' => [], 'site_color' => ''];
          }
          $orgSiteData[$eSiteN]['gross'] += $eGross;
          $orgSiteData[$eSiteN]['reg'] += $eReg;
          $orgSiteData[$eSiteN]['ot'] += $eOt;
          $orgSiteData[$eSiteN]['members'][$memberUUID] = true;
          if ($eSiteColor !== '' && (string) $orgSiteData[$eSiteN]['site_color'] === '') {
            $orgSiteData[$eSiteN]['site_color'] = $eSiteColor;
          }
          $memberLoaTotals[$memberUUID] = ($memberLoaTotals[$memberUUID] ?? 0.0) + $eLoa;
          if ($date !== '') {
            $isoWk = (string) date('oW', (int) strtotime($date));
            $memberWeeklyH[$memberUUID][$isoWk] = ($memberWeeklyH[$memberUUID][$isoWk] ?? 0.0)
              + ($eReg + $eOt);
            $memberDays[$memberUUID][] = $date;
          }

          $regHours += $eReg;
          $otHours += $eOt;
          $gross += $eGross;
          $net += $eNet;

          if (!isset($monthlyBreakdown[$month])) {
            $monthlyBreakdown[$month] = ['reg_hours' => 0.0, 'ot_hours' => 0.0, 'gross' => 0.0, 'net' => 0.0];
          }
          $monthlyBreakdown[$month]['reg_hours'] += $eReg;
          $monthlyBreakdown[$month]['ot_hours'] += $eOt;
          $monthlyBreakdown[$month]['gross'] += $eGross;
          $monthlyBreakdown[$month]['net'] += $eNet;
        }

        ksort($monthlyBreakdown);

        $monthsJson = [];
        foreach ($monthlyBreakdown as $ym => $mv) {
          $dt = \DateTimeImmutable::createFromFormat('Y-m', $ym);
          $monthsJson[] = [
            'month' => $ym,
            'label' => $dt !== false
              ? Strings::formatLocalizedShortMonthYear((int) $dt->format('Y'), (int) $dt->format('n'))
              : $ym,
            'reg_hours' => round($mv['reg_hours'], 2),
            'ot_hours' => round($mv['ot_hours'], 2),
            'gross' => round($mv['gross'], 2),
            'net' => round($mv['net'], 2),
          ];
        }

        $teamEarningsRows[] = [
          'name' => $memberName,
          'uuid' => $memberUUID,
          'role' => $memberRole,
          'reg_hours' => $regHours,
          'ot_hours' => $otHours,
          'gross' => $gross,
          'net' => $net,
          'months' => $monthsJson,
          'loa_total' => round($memberLoaTotals[$memberUUID] ?? 0.0, 2),
        ];

        $teamEarningsTotals['reg_hours'] += $regHours;
        $teamEarningsTotals['ot_hours'] += $otHours;
        $teamEarningsTotals['gross'] += $gross;
        $teamEarningsTotals['net'] += $net;
      }
    }

    Lens::timeEnd('Team Earnings: member work scans');
    Lens::add('Team Earnings: redis work scan stats', [
      'scan_calls' => $teamWorkScanCount,
      'hgetall_calls' => $teamWorkHgetallCount,
      'work_keys_total' => $teamWorkHgetallCount,
    ]);
    Lens::increment('earnings.team.work_scan_calls', $teamWorkScanCount);
    Lens::increment('earnings.team.work_hgetall_calls', $teamWorkHgetallCount);

    Lens::timeStart('Team Earnings: post-process totals');
    usort($teamEarningsRows, static fn ($a, $b): int => strcasecmp($a['name'], $b['name']));

    $teamSiteFallbackWarnThreshold = 15.0;
    $teamMatchedTotalForSignal = $teamSiteMatchStats['match_owner_and_site']
      + $teamSiteMatchStats['match_unique_site_id']
      + $teamSiteMatchStats['match_site_name'];
    $teamEvaluatedTotalForSignal = $teamMatchedTotalForSignal + $teamSiteMatchStats['included_unlinked'];
    $teamFallbackTotalForSignal = $teamSiteMatchStats['match_unique_site_id']
      + $teamSiteMatchStats['match_site_name'];
    $teamFallbackRatioForSignal = $teamEvaluatedTotalForSignal > 0
      ? round(($teamFallbackTotalForSignal / $teamEvaluatedTotalForSignal) * 100, 1)
      : 0.0;
    $teamSiteFallbackWarn = $teamFallbackRatioForSignal >= $teamSiteFallbackWarnThreshold;
    $teamUnlinkedOnlyCount = $teamSiteMatchStats['included_unlinked'];
    $teamUnlinkedOnlyWarn = $teamUnlinkedOnlyCount > 0 && $teamMatchedTotalForSignal === 0;

    if ($teamUnlinkedOnlyWarn) {
      Lens::increment('earnings.team.site_resolution.unlinked_only_warn');
      Lens::add('Team Earnings: unlinked-only guard', [
        'dropped_unlinked_rows' => $teamUnlinkedOnlyCount,
        'matched_rows' => $teamMatchedTotalForSignal,
        'selected_org' => $businessId,
        'year' => $year,
        'action' => 'link_site_to_business',
      ], 'warning');
    }

    if ($teamSiteFallbackWarn) {
      Lens::increment('earnings.team.site_resolution.fallback_ratio_warn');
      Lens::add('Team Earnings: fallback ratio warning', [
        'threshold_pct' => $teamSiteFallbackWarnThreshold,
        'fallback_ratio_pct' => $teamFallbackRatioForSignal,
        'evaluated_rows' => $teamEvaluatedTotalForSignal,
        'fallback_rows' => $teamFallbackTotalForSignal,
        'selected_org' => $businessId,
        'year' => $year,
      ], 'warning');
    }

    Lens::add('Team Earnings: site-link diagnostics', $teamSiteMatchStats);
    if (count($teamSiteDropSamples) > 0) {
      Lens::add('Team Earnings: site-link dropped samples', $teamSiteDropSamples);
    }
    Lens::timeEnd('Team Earnings: post-process totals');

    $memberWeeklyHNormalized = self::normalizeMemberWeeklyHours($memberWeeklyH);

    return [
      'teamEarningsRows' => $teamEarningsRows,
      'teamEarningsTotals' => $teamEarningsTotals,
      'teamSiteMatchStats' => $teamSiteMatchStats,
      'teamSiteDropSamples' => $teamSiteDropSamples,
      'teamSiteFallbackWarn' => $teamSiteFallbackWarn,
      'teamUnlinkedOnlyWarn' => $teamUnlinkedOnlyWarn,
      'teamUnlinkedOnlyCount' => $teamUnlinkedOnlyCount,
      'orgSiteData' => $orgSiteData,
      'memberLoaTotals' => $memberLoaTotals,
      'memberWeeklyH' => $memberWeeklyHNormalized,
      'memberDays' => $memberDays,
    ];
  }

  /**
   * @param array<string, array<int|string, float>> $memberWeeklyH
   * @return array<string, array<string, float>>
   */
  private static function normalizeMemberWeeklyHours(array $memberWeeklyH): array
  {
    $normalized = [];
    foreach ($memberWeeklyH as $memberKey => $weekHours) {
      $weekMap = [];
      foreach ($weekHours as $weekKey => $hours) {
        $weekMap[(string) $weekKey] = (float) $hours;
      }
      $normalized[(string) $memberKey] = $weekMap;
    }

    return $normalized;
  }

  /**
   * @param list<string> $batchUuids
   * @param array<string, array<string, array<string, string>>>|null $cachedMemberWork
   * @return array<string, array<string, array<string, string>>>
   */
  private static function resolveMemberWorkBatch(
    array $batchUuids,
    int $year,
    ?array $cachedMemberWork,
  ): array {
    if ($cachedMemberWork !== null) {
      $fromCache = [];
      foreach ($batchUuids as $memberUuid) {
        $entries = $cachedMemberWork[$memberUuid] ?? null;
        if (!is_array($entries)) {
          $fromCache[$memberUuid] = [];
          continue;
        }

        $yearPrefix = (string) $year;
        $filtered = [];
        foreach ($entries as $workKey => $entry) {
          $keyParts = explode(':', $workKey);
          $isArchived = isset($keyParts[1]) && $keyParts[1] === 'archived';
          $date = $isArchived
            ? (isset($keyParts[3]) ? (string) $keyParts[3] : (string) ($entry['date'] ?? ''))
            : (isset($keyParts[2]) ? (string) $keyParts[2] : (string) ($entry['date'] ?? ''));
          if ($date !== '' && str_starts_with($date, $yearPrefix)) {
            $filtered[$workKey] = $entry;
          }
        }
        $fromCache[$memberUuid] = $filtered;
      }

      return $fromCache;
    }

    $empty = [];
    foreach ($batchUuids as $memberUuid) {
      $empty[$memberUuid] = [];
    }

    return $empty;
  }

  /**
   * @return array{
   *   teamEarningsRows: list<array<string, mixed>>,
   *   teamEarningsTotals: array{reg_hours: float, ot_hours: float, gross: float, net: float},
   *   teamSiteMatchStats: array{match_owner_and_site: int, match_unique_site_id: int, match_site_name: int, included_unlinked: int},
   *   teamSiteDropSamples: list<array<string, string>>,
   *   teamSiteFallbackWarn: bool,
   *   teamUnlinkedOnlyWarn: bool,
   *   teamUnlinkedOnlyCount: int,
   *   orgSiteData: array<string, array<string, mixed>>,
   *   memberLoaTotals: array<string, float>,
   *   memberWeeklyH: array<string, array<string, float>>,
   *   memberDays: array<string, list<string>>
   * }
   */
  public static function emptySnapshot(): array
  {
    return [
      'teamEarningsRows' => [],
      'teamEarningsTotals' => ['reg_hours' => 0.0, 'ot_hours' => 0.0, 'gross' => 0.0, 'net' => 0.0],
      'teamSiteMatchStats' => [
        'match_owner_and_site' => 0,
        'match_unique_site_id' => 0,
        'match_site_name' => 0,
        'included_unlinked' => 0,
      ],
      'teamSiteDropSamples' => [],
      'teamSiteFallbackWarn' => false,
      'teamUnlinkedOnlyWarn' => false,
      'teamUnlinkedOnlyCount' => 0,
      'orgSiteData' => [],
      'memberLoaTotals' => [],
      'memberWeeklyH' => [],
      'memberDays' => [],
    ];
  }

  /**
   * Hydrate template variables from a cached or freshly built snapshot.
   *
   * @param array<string, mixed> $snapshot
   */
  public static function applySnapshot(array $snapshot): void
  {
    global $teamEarningsRows, $teamEarningsTotals, $teamSiteMatchStats, $teamSiteDropSamples;
    global $teamSiteFallbackWarn, $teamUnlinkedOnlyWarn, $teamUnlinkedOnlyCount;
    global $orgSiteData_, $memberLoaTotals_, $memberWeeklyH_, $memberDays_;

    $teamEarningsRows = is_array($snapshot['teamEarningsRows'] ?? null) ? $snapshot['teamEarningsRows'] : [];
    $teamEarningsTotals = is_array($snapshot['teamEarningsTotals'] ?? null)
      ? $snapshot['teamEarningsTotals']
      : ['reg_hours' => 0.0, 'ot_hours' => 0.0, 'gross' => 0.0, 'net' => 0.0];
    $teamSiteMatchStats = is_array($snapshot['teamSiteMatchStats'] ?? null)
      ? $snapshot['teamSiteMatchStats']
      : self::emptySnapshot()['teamSiteMatchStats'];
    $teamSiteDropSamples = is_array($snapshot['teamSiteDropSamples'] ?? null) ? $snapshot['teamSiteDropSamples'] : [];
    $teamSiteFallbackWarn = (bool) ($snapshot['teamSiteFallbackWarn'] ?? false);
    $teamUnlinkedOnlyWarn = (bool) ($snapshot['teamUnlinkedOnlyWarn'] ?? false);
    $teamUnlinkedOnlyCountRaw = $snapshot['teamUnlinkedOnlyCount'] ?? 0;
    $teamUnlinkedOnlyCount = is_int($teamUnlinkedOnlyCountRaw) ? $teamUnlinkedOnlyCountRaw : (is_numeric($teamUnlinkedOnlyCountRaw) ? (int) $teamUnlinkedOnlyCountRaw : 0);
    $orgSiteData_ = is_array($snapshot['orgSiteData'] ?? null) ? $snapshot['orgSiteData'] : [];
    $memberLoaTotals_ = is_array($snapshot['memberLoaTotals'] ?? null) ? $snapshot['memberLoaTotals'] : [];
    $memberWeeklyH_ = is_array($snapshot['memberWeeklyH'] ?? null) ? $snapshot['memberWeeklyH'] : [];
    $memberDays_ = is_array($snapshot['memberDays'] ?? null) ? $snapshot['memberDays'] : [];
  }
}
