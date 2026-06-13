<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

$teamReportsBaseUrl = $teamReportsBaseUrl ?? '/business/reports/';
$isLensMode = (bool) ($isLensMode ?? false);

$teamPanelI18n = static function (string $key, string $fallback = '') use ($i18n): string {
  if (isset($i18n) && is_array($i18n) && array_key_exists($key, $i18n)) {
    $value = trim((string) $i18n[$key]);
    if ($value !== '' && $value !== $key) {
      return $value;
    }
  }

  return $fallback !== '' ? $fallback : $key;
};

?>
  <div class="earnings_team_panel">

    <?php
      $selectedOrgName = '';
      foreach ($activeOrgs as $o) {
        if ($o['org_id'] === $selectedOrgId) {
          $selectedOrgName = $o['name'];
          break;
        }
      }
    ?>

    <div class="earnings_team_year_row">
      <?php foreach ([date('Y') - 1, (int) date('Y')] as $yr): ?>
        <a href="<?php echo htmlspecialchars($teamReportsBaseUrl . '?org=' . rawurlencode($selectedOrgId) . '&year=' . $yr, ENT_QUOTES, 'UTF-8'); ?>"
           class="earnings_team_year_link<?php echo $yr === $teamEarningsYear ? ' active' : ''; ?>">
          <?php echo (int) $yr; ?>
        </a>
      <?php endforeach; ?>
      <button type="button" class="et_export_btn et_export_btn--report" data-team-export-format="pdf">&#128438; <?php echo htmlspecialchars($teamPanelI18n('EARNINGS_PRINT_REPORT', 'Print report'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>

    <?php
      $teamDroppedUnlinkedCount = (int) ($teamSiteMatchStats['dropped_unlinked'] ?? 0);
      $teamDroppedWarn = $teamDroppedUnlinkedCount >= $teamSiteDropWarnThreshold;
      $teamMatchOwnerAndSite = (int) ($teamSiteMatchStats['match_owner_and_site'] ?? 0);
      $teamMatchUniqueSiteId = (int) ($teamSiteMatchStats['match_unique_site_id'] ?? 0);
      $teamMatchSiteName = (int) ($teamSiteMatchStats['match_site_name'] ?? 0);
      $teamMatchedTotal = $teamMatchOwnerAndSite + $teamMatchUniqueSiteId + $teamMatchSiteName;
      $teamEvaluatedTotal = $teamMatchedTotal + $teamDroppedUnlinkedCount;
      $teamFallbackTotal = $teamMatchUniqueSiteId + $teamMatchSiteName;
      $teamFallbackRatio = $teamEvaluatedTotal > 0
        ? round(($teamFallbackTotal / $teamEvaluatedTotal) * 100, 1)
        : 0.0;
      $teamFallbackWarn = $teamFallbackRatio >= $teamSiteFallbackWarnThreshold;
    ?>
    <?php if ($isLensMode): ?>
    <aside class="earnings_site_resolve_summary<?php echo $teamFallbackWarn ? ' earnings_site_resolve_summary--warning' : ''; ?>" role="status" aria-live="polite" aria-atomic="true" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_RESOLVE_SUMMARY_ARIA', 'Team site resolution summary'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="earnings_site_resolve_summary_header">
        <h3 class="earnings_site_resolve_summary_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_RESOLVE_TITLE', 'Team site resolution summary'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <span class="earnings_site_resolve_summary_subtitle"><?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_TEAM_SITE_RESOLVE_ROWS_EVALUATED_FMT', '{count} rows evaluated', ['count' => (string) $teamEvaluatedTotal]), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <dl class="earnings_site_resolve_summary_grid">
        <div class="earnings_site_resolve_stat">
          <dt><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_RESOLVE_EXACT_OWNER_SITE', 'Exact owner+site'), ENT_QUOTES, 'UTF-8'); ?></dt>
          <dd><?php echo $teamMatchOwnerAndSite; ?></dd>
        </div>
        <div class="earnings_site_resolve_stat">
          <dt><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_RESOLVE_UNIQUE_SITE_ID', 'Unique site ID fallback'), ENT_QUOTES, 'UTF-8'); ?></dt>
          <dd><?php echo $teamMatchUniqueSiteId; ?></dd>
        </div>
        <div class="earnings_site_resolve_stat">
          <dt><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_RESOLVE_SITE_NAME', 'Site name fallback'), ENT_QUOTES, 'UTF-8'); ?></dt>
          <dd><?php echo $teamMatchSiteName; ?></dd>
        </div>
        <div class="earnings_site_resolve_stat<?php echo $teamDroppedWarn ? ' earnings_site_resolve_stat--warning' : ''; ?>">
          <dt><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_RESOLVE_DROPPED_UNLINKED', 'Dropped unlinked'), ENT_QUOTES, 'UTF-8'); ?></dt>
          <dd><?php echo $teamDroppedUnlinkedCount; ?></dd>
        </div>
      </dl>
      <p class="earnings_site_resolve_summary_note<?php echo $teamFallbackWarn ? ' earnings_site_resolve_summary_note--warning' : ''; ?>">
        <?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_TEAM_SITE_RESOLVE_FALLBACK_RATIO_FMT', 'Fallback ratio: {ratio}%', ['ratio' => number_format($teamFallbackRatio, 1)]), ENT_QUOTES, 'UTF-8'); ?>
        <?php if ($teamFallbackWarn): ?>
          <?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_TEAM_SITE_RESOLVE_WARNING_THRESHOLD_FMT', '(warning threshold: {threshold}%)', ['threshold' => number_format($teamSiteFallbackWarnThreshold, 1)]), ENT_QUOTES, 'UTF-8'); ?>
        <?php endif; ?>
      </p>
    </aside>
    <?php endif; ?>
    <?php if ($isLensMode && $teamDroppedUnlinkedCount > 0): ?>
    <aside class="earnings_site_diag<?php echo $teamDroppedWarn ? ' earnings_site_diag--warning' : ''; ?>" role="status" aria-live="polite" aria-atomic="true" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_LINK_DIAG_ARIA', 'Team site link diagnostics'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="earnings_site_diag_header">
        <h3 class="earnings_site_diag_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_LINK_DIAG_TITLE', 'Team site-link diagnostics'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <span class="earnings_site_diag_count"><?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_TEAM_SITE_LINK_DIAG_DROPPED_FMT', '{count} dropped', ['count' => (string) $teamDroppedUnlinkedCount]), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <p class="earnings_site_diag_summary">
        <?php if ($teamDroppedWarn): ?>
          <?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_TEAM_SITE_LINK_DIAG_WARN_FMT', 'Warning: dropped rows exceeded threshold ({threshold}). Team totals may be under-counted.', ['threshold' => (string) $teamSiteDropWarnThreshold]), ENT_QUOTES, 'UTF-8'); ?>
        <?php else: ?>
          <?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_LINK_DIAG_SUMMARY', 'Some rows were excluded because their site ownership reference did not match business-linked sites.'), ENT_QUOTES, 'UTF-8'); ?>
        <?php endif; ?>
      </p>
      <?php if (count($teamSiteDropSamples) > 0): ?>
      <details class="earnings_site_diag_details">
        <summary><?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_TEAM_SITE_LINK_DIAG_SHOW_SAMPLES_FMT', 'Show sampled dropped rows ({count})', ['count' => (string) count($teamSiteDropSamples)]), ENT_QUOTES, 'UTF-8'); ?></summary>
        <div class="earnings_site_diag_table_wrap">
          <table class="earnings_site_diag_table">
            <thead>
              <tr>
                <th scope="col"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_LINK_DIAG_COL_MEMBER', 'Member'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th scope="col"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_LINK_DIAG_COL_SITE_ID', 'Site ID'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th scope="col"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_LINK_DIAG_COL_SITE_OWNER', 'Site Owner'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th scope="col"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_SITE_LINK_DIAG_COL_SITE_NAME', 'Site Name'), ENT_QUOTES, 'UTF-8'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($teamSiteDropSamples as $dropSample): ?>
              <tr>
                <td><?php echo htmlspecialchars($dropSample['member_uuid'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($dropSample['site_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($dropSample['site_owner_uuid'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($dropSample['site_name'] !== '' ? $dropSample['site_name'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </details>
      <?php endif; ?>
    </aside>
    <?php endif; ?>

    <?php if (count($teamEarningsRows) === 0): ?>
      <div class="skeleton" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_EMPTY_TITLE', 'No payroll data yet'), ENT_QUOTES, 'UTF-8'); ?>" aria-live="polite">

        <?php if ($teamUnlinkedOnlyWarn): ?>
        <aside class="et_empty_guard et_empty_guard--warning" role="alert" aria-live="assertive" aria-atomic="true">
          <?php echo htmlspecialchars(
            earnings_i18n_fmt(
              'EARNINGS_TEAM_UNLINKED_ONLY_GUARD',
              'Detected {count} work entries that were excluded because their sites are not linked to this business. Link the missing site(s) in Businesses to restore team totals.',
              ['count' => (string) $teamUnlinkedOnlyCount]
            ),
            ENT_QUOTES,
            'UTF-8'
          ); ?>
        </aside>
        <?php endif; ?>

        <!-- ── Onboarding message ── -->
        <div class="et_empty_state">
          <span class="et_empty_icon" aria-hidden="true">📊</span>
          <h2 class="et_empty_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_EMPTY_TITLE', 'No payroll data yet'), ENT_QUOTES, 'UTF-8'); ?> &mdash; <?php echo $teamEarningsYear; ?></h2>
          <p class="et_empty_body"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_EMPTY_BODY', 'Once members log work entries through linked sites, payroll analytics, forecasting and workforce intelligence will appear here.'), ENT_QUOTES, 'UTF-8'); ?></p>
          <ol class="et_empty_steps">
            <li><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_EMPTY_STEP_SITES', 'Link one or more sites to this business in the Businesses editor.'), ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_EMPTY_STEP_WORK', 'Members log work entries on those sites via the Calendar.'), ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TEAM_EMPTY_STEP_BACK', 'Check back here after the first entries are saved.'), ENT_QUOTES, 'UTF-8'); ?></li>
          </ol>
        </div>

        <!-- ── Skeleton exec snapshot ── -->
        <div class="et_skeleton_exec" aria-hidden="true">
          <?php for ($__si = 0; $__si < 5; $__si++): ?>
          <div class="et_skeleton_exec_item">
            <span class="sk-line sk-box reports_sk_exec_value"></span>
            <span class="sk-line sk-line--sm"></span>
            <span class="sk-line sk-line--md reports_sk_exec_sub"></span>
          </div>
          <?php endfor; ?>
        </div>

        <!-- ── Skeleton: Payroll line chart ── -->
        <div class="et_skeleton_figure" aria-hidden="true">
          <div class="et_skeleton_header">
            <span class="sk-line sk-line--sm reports_sk_figure_title"></span>
            <span class="sk-line sk-line--md reports_sk_figure_subtitle"></span>
          </div>
          <div class="et_skeleton_body">
            <div class="et_skeleton_line_path sk-line sk-box"></div>
            <span class="sk-line sk-line--sm reports_sk_caption_line"></span>
          </div>
        </div>

        <!-- ── Skeleton: Hours bar chart ── -->
        <div class="et_skeleton_figure" aria-hidden="true">
          <div class="et_skeleton_header">
            <span class="sk-line sk-line--sm reports_sk_figure_title reports_sk_figure_title_wide"></span>
            <span class="sk-line sk-line--md reports_sk_figure_subtitle"></span>
          </div>
          <div class="et_skeleton_body">
            <div class="et_skeleton_bars" aria-hidden="true">
              <?php foreach ([55, 80, 40, 95, 65, 50, 75, 30, 88, 60, 45, 70] as $__bh): ?>
              <span class="sk-chart-bar sk-box reports_sk_bar reports_sk_bar_h_<?php echo $__bh; ?> reports_sk_bar_primary"></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- ── Skeleton: Workforce headcount bars ── -->
        <div class="et_skeleton_figure" aria-hidden="true">
          <div class="et_skeleton_header">
            <span class="sk-line sk-line--sm reports_sk_figure_title reports_sk_figure_title_narrow"></span>
            <span class="sk-line sk-line--md reports_sk_figure_subtitle"></span>
          </div>
          <div class="et_skeleton_body">
            <div class="et_skeleton_bars" aria-hidden="true">
              <?php foreach ([60, 60, 75, 75, 75, 90, 90, 90, 80, 80, 70, 70] as $__hh): ?>
              <span class="sk-chart-bar sk-box reports_sk_bar reports_sk_bar_h_<?php echo $__hh; ?> reports_sk_bar_secondary"></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- ── Skeleton: Member list grid ── -->
        <div class="earnings_team_grid" aria-hidden="true">
          <div class="earnings_team_grid_header">
            <?php foreach ([null, null, null, null, null] as $_): ?>
            <span><span class="sk-line sk-line--md reports_sk_grid_head_cell"></span></span>
            <?php endforeach; ?>
          </div>
          <?php for ($__ri = 0; $__ri < 5; $__ri++): ?>
          <div class="et_skeleton_grid_row">
            <span class="sk-line sk-line--lg"></span>
            <span class="sk-line sk-line--sm"></span>
            <span class="sk-line sk-line--sm"></span>
            <span class="sk-line sk-line--sm"></span>
            <span class="sk-line sk-line--sm"></span>
          </div>
          <?php endfor; ?>
        </div>

      </div><!-- /.skeleton -->
    <?php else: ?>

    <?php
      Lens::timeStart('Team Earnings: panel chart aggregates');
      // ── Aggregate per-org monthly totals for YTD chart ───────────────────────
      /** @var array<string, array{label: string, reg: float, ot: float, gross: float, net: float, headcount: int, avg_gross: float, members: array<string, bool>}> $orgChartData */
      $orgChartData = [];
      foreach ($teamEarningsRows as $cr) {
        foreach ($cr['months'] as $cm) {
          $mo = $cm['month'];
          if (!isset($orgChartData[$mo])) {
            $orgChartData[$mo] = ['label' => $cm['label'], 'reg' => 0.0, 'ot' => 0.0, 'gross' => 0.0, 'net' => 0.0, 'members' => []];
          }
          $orgChartData[$mo]['reg']                  += $cm['reg_hours'];
          $orgChartData[$mo]['ot']                   += $cm['ot_hours'];
          $orgChartData[$mo]['gross']                += $cm['gross'];
          $orgChartData[$mo]['net']                  += $cm['net'];
          $orgChartData[$mo]['members'][$cr['uuid']] = true;
        }
      }
      ksort($orgChartData);
      $orgCumGross_ = 0.0;
      foreach ($orgChartData as $mo => $d) {
        $hc            = count($d['members']);
        $orgCumGross_ += $d['gross'];
        $moTotal_      = $d['reg'] + $d['ot'];
        // Working days (Mon-Fri) in this calendar month
        // Guard: skip months that couldn't be parsed to YYYY-MM format
        if (!str_contains($mo, '-')) {
          unset($orgChartData[$mo]);
          continue;
        }
        [$moYr_, $moMn_] = explode('-', $mo, 2);
        $moDays_ = (int) date('t', mktime(0, 0, 0, (int) $moMn_, 1, (int) $moYr_));
        $moWdays_ = 0;
        for ($di_ = 1; $di_ <= $moDays_; $di_++) {
          if ((int) date('N', mktime(0, 0, 0, (int) $moMn_, $di_, (int) $moYr_)) <= 5) {
            $moWdays_++;
          }
        }
        $moExpH_ = $hc > 0 ? (float) ($hc * $moWdays_ * 8) : 1.0;
        $orgChartData[$mo]['headcount']   = $hc;
        $orgChartData[$mo]['avg_gross']   = $hc > 0 ? round($d['gross'] / $hc, 2) : 0.0;
        $orgChartData[$mo]['cum_gross']   = round($orgCumGross_, 2);
        $orgChartData[$mo]['ot_ratio']    = $moTotal_ > 0.0
          ? round(($d['ot'] / $moTotal_) * 100, 1) : 0.0;
        $orgChartData[$mo]['utilization'] = round(
          min(($moTotal_ / $moExpH_) * 100, 100.0), 1
        );
        unset($orgChartData[$mo]['members']);
      }
      $cData = array_map(
        fn(string $ym, array $d): array => array_merge($d, ['ym' => $ym]),
        array_keys($orgChartData),
        array_values($orgChartData)
      );
      $cN = count($cData);
      Lens::timeEnd('Team Earnings: panel chart aggregates');
    ?>

    <?php if ($cN >= 1): ?>
    <?php
      Lens::timeStart('Team Earnings: panel insights and rankings');
      // ── Shared chart constants & helpers ────────────────────────────────────
      $svgW_ = 900; $padT_ = 20; $padB_ = 36; $ticks_ = 5;

      $niceMax_ = function(float $v) use ($ticks_): float {
        if ($v <= 0.0) { return (float) $ticks_; }
        $step = $v / $ticks_;
        $mag  = (float) pow(10, floor(log10($step)));
        $norm = $step / $mag;
        $nice = $norm <= 1.0 ? 1.0 : ($norm <= 2.0 ? 2.0 : ($norm <= 5.0 ? 5.0 : 10.0));
        return $nice * $mag * $ticks_;
      };

      /** @param list<float> $vals @param callable $xFn @param callable $yFn @return string */
      $buildPts_ = function(array $vals, callable $xFn, callable $yFn): string {
        $pts = [];
        foreach ($vals as $i => $v) {
          $pts[] = round($xFn($i), 1) . ',' . round($yFn((float) $v), 1);
        }
        return implode(' ', $pts);
      };

      $grossVals_   = array_map(fn($d) => $d['gross'],             $cData);
      $netVals_     = array_map(fn($d) => $d['net'],               $cData);
      $avgVals_     = array_map(fn($d) => $d['avg_gross'],         $cData);
      $cumVals_     = array_map(fn($d) => $d['cum_gross'],         $cData);
      $regVals_     = array_map(fn($d) => $d['reg'],               $cData);
      $otVals_      = array_map(fn($d) => $d['ot'],                $cData);
      $otRatioVals_ = array_map(fn($d) => $d['ot_ratio'],          $cData);
      $headVals_    = array_map(fn($d) => (float) $d['headcount'], $cData);
      $utilVals_    = array_map(fn($d) => $d['utilization'],       $cData);

      // Member ranking by gross descending
      $memberRanked_ = $teamEarningsRows;
      usort($memberRanked_, fn($a, $b) => $b['gross'] <=> $a['gross']);

      // ── ANALYTICS: Forecasting ────────────────────────────────────────────
      $today_      = new \DateTimeImmutable('today');
      $currentYm_  = $today_->format('Y-m');
      $currentMon_ = (int) $today_->format('n');
      $currentDay_ = (int) $today_->format('j');
      $daysInMon_  = (int) $today_->format('t');

      $completedCData_ = array_values(array_filter($cData, fn($d) => $d['ym'] < $currentYm_));
      $currentCData_   = array_values(array_filter($cData, fn($d) => $d['ym'] === $currentYm_));
      $cmEntry_        = $currentCData_[0] ?? null;
      $completedCount_ = count($completedCData_);

      if ($completedCount_ > 0) {
        $avgMonthly_ = array_sum(array_column($completedCData_, 'gross')) / $completedCount_;
      } elseif ($cmEntry_ !== null && $currentDay_ > 0) {
        $avgMonthly_ = ($cmEntry_['gross'] / $currentDay_) * $daysInMon_;
      } else {
        $avgMonthly_ = 0.0;
      }

      $cmProjected_   = ($cmEntry_ !== null && $currentDay_ > 0)
        ? ($cmEntry_['gross'] / $currentDay_) * $daysInMon_
        : $avgMonthly_;
      $ytdSpend_      = $cData[count($cData) - 1]['cum_gross'] ?? 0.0;
      $ytdExCurrent_  = $ytdSpend_ - ($cmEntry_['gross'] ?? 0.0);
      $remainingMons_ = 12 - $currentMon_;
      $eoyForecast_   = $ytdExCurrent_ + $cmProjected_ + ((float) $remainingMons_ * $avgMonthly_);

      // ── ANALYTICS: Org-level budget from site settings (aggregate across linked sites) ─
      // Pull site settings for all linked sites and sum configured budgets that overlap the current year.
      $siteSettingsList_ = [];
      $orgBudgetTotal_   = 0.0;
      $cachedSiteSettings = BusinessWorkspaceCache::getSiteSettings($selectedOrgId, $teamEarningsYear);
      if ($cachedSiteSettings !== null) {
        Lens::add('Team Earnings: site settings source', ['source' => 'workspace_cache']);
        $siteSettingsList_ = $cachedSiteSettings['site_settings_by_ref'];
      } else {
        Lens::add('Team Earnings: site settings source', ['source' => 'live_fetch']);
        $siteRefs_       = Database::smembers(Keys::BUSINESS_SITE . ':' . $selectedOrgId);
        $siteSettingsKeys_ = [];
        foreach ($siteRefs_ as $sRef_) {
          $sRef_ = (string) $sRef_;
          if ($sRef_ === '') {
            continue;
          }
          $siteSettingsKeys_[$sRef_] = Keys::BUSINESS_SITE_SETTINGS . ':' . $selectedOrgId . ':' . $sRef_;
        }
        $siteSettingsHashes_ = $siteSettingsKeys_ !== []
          ? Database::pipelineHgetall(array_values($siteSettingsKeys_))
          : [];
        foreach ($siteSettingsKeys_ as $sRef_ => $settingsKey_) {
          $ss_ = $siteSettingsHashes_[$settingsKey_] ?? [];
          if ($ss_ !== []) {
            $siteSettingsList_[$sRef_] = $ss_;
          }
        }

        $annualBudgetField = (string) (Database::hget(Keys::BUSINESS . ':' . $selectedOrgId, 'annual_budget') ?? '');
        BusinessWorkspaceCache::putSiteSettings($selectedOrgId, $teamEarningsYear, $siteSettingsList_, $annualBudgetField);
      }

      foreach ($siteSettingsList_ as $sRef_ => $ss_) {
        $amt_ = (float) ($ss_['budget_amount'] ?? 0);
        if ($amt_ <= 0) { continue; }
        // Include in org total if budget window covers the current year or is unset
        $bStart_ = $ss_['budget_start'] ?? '';
        $bEnd_   = $ss_['budget_end']   ?? '';
        $yearStr_ = (string) $teamEarningsYear;
        if ($bStart_ === '' || $bEnd_ === ''
          || (substr($bStart_, 0, 4) <= $yearStr_ && substr($bEnd_, 0, 4) >= $yearStr_)) {
          $orgBudgetTotal_ += $amt_;
        }
      }
      // Fall back to legacy annual_budget field if no site-level budgets configured
      $annualBudget_    = $orgBudgetTotal_ > 0
        ? $orgBudgetTotal_
        : (float) (is_array($cachedSiteSettings) && ($cachedSiteSettings['business_annual_budget'] ?? '') !== ''
          ? $cachedSiteSettings['business_annual_budget']
          : (Database::hget(Keys::BUSINESS . ':' . $selectedOrgId, 'annual_budget') ?? 0));
      $budgetRemaining_ = $annualBudget_ > 0 ? $annualBudget_ - $eoyForecast_ : null;

      // Budget status for the status panel (uses YTD spend vs annual budget)
      $budgetPct_    = $annualBudget_ > 0 ? ($ytdSpend_ / $annualBudget_) * 100.0 : null;
      // Derive aggregate warn/critical thresholds (average across configured sites, default 80/95)
      $threshWarn_   = 80.0;
      $threshCrit_   = 95.0;
      if (!empty($siteSettingsList_)) {
        $wSum_ = $cSum_ = 0.0;
        $tCnt_ = 0;
        foreach ($siteSettingsList_ as $ss_) {
          $wSum_ += (float) ($ss_['warn_threshold'] ?? 80);
          $cSum_ += (float) ($ss_['critical_threshold'] ?? 95);
          $tCnt_++;
        }
        $threshWarn_ = $tCnt_ > 0 ? $wSum_ / $tCnt_ : 80.0;
        $threshCrit_ = $tCnt_ > 0 ? $cSum_ / $tCnt_ : 95.0;
      }
      $budgetStatus_ = null;
      if ($budgetPct_ !== null) {
        if ($budgetPct_ >= $threshCrit_)   { $budgetStatus_ = ['label' => earnings_i18n('EARNINGS_CRITICAL', 'Critical'), 'cls' => 'critical', 'icon' => '🔴']; }
        elseif ($budgetPct_ >= $threshWarn_) { $budgetStatus_ = ['label' => earnings_i18n('EARNINGS_WARNING', 'Warning'),  'cls' => 'warning',  'icon' => '🟠']; }
        else                                { $budgetStatus_ = ['label' => earnings_i18n('EARNINGS_VERDICT_ON_TRACK', 'On Track'),  'cls' => 'ok',       'icon' => '🟢']; }
      }
      $budgetVariance_   = $annualBudget_ > 0 ? $eoyForecast_ - $annualBudget_ : null;
      $forecastBasis_   = $completedCount_ > 0
        ? earnings_i18n_fmt('EARNINGS_FORECAST_BASIS_MONTH_AVG', '{months}-mo avg (${amount}/mo)', ['months' => (string) $completedCount_, 'amount' => number_format($avgMonthly_, 0)])
        : earnings_i18n('EARNINGS_FORECAST_BASIS_RUN_RATE', 'current-month run-rate');

      // ── ANALYTICS: Site ranking (used by alerts + cost drivers + site chart) ─
      uasort($orgSiteData_, fn($a, $b) => $b['gross'] <=> $a['gross']);
      $topSites_       = array_slice($orgSiteData_, 0, 8, true);
      $totalSiteGross_ = (float) array_sum(array_column(array_values($orgSiteData_), 'gross'));

      // ── ANALYTICS: Variance & Trend Alerts (severity-graded cards) ─────
      $insights_ = [];
      // Severity classifier: converts % magnitude to a severity label
      $fnSev_    = static function(float $absPct): string {
        if ($absPct >= 50.0) { return 'critical'; }
        if ($absPct >= 25.0) { return 'warning'; }
        if ($absPct >= 10.0) { return 'notice'; }
        return 'normal';
      };
      // Build per-month member presence map for callout attribution
      // membersByMonth_[YYYY-MM][uuid] = display name
      $membersByMonth_ = [];
      foreach ($teamEarningsRows as $mRow_) {
        foreach ($mRow_['months'] as $mEntry_) {
          $membersByMonth_[$mEntry_['month']][$mRow_['uuid']] = $mRow_['name'];
        }
      }

      if (count($cData) >= 2) {
        $prevMo_    = $cData[count($cData) - 2];
        $lastMo_    = $cData[count($cData) - 1];
        $prevLabel_ = $prevMo_['label'];
        $lastLabel_ = $lastMo_['label'];

        // Workers present in prev month but absent in current = "lost / inactive"
        // Workers present in current but absent in prev = "added / joined"
        $prevMonMembers_ = $membersByMonth_[$prevMo_['ym']] ?? [];
        $lastMonMembers_ = $membersByMonth_[$lastMo_['ym']] ?? [];
        $lostNames_      = array_values(array_diff_key($prevMonMembers_, $lastMonMembers_));
        $gainedNames_    = array_values(array_diff_key($lastMonMembers_, $prevMonMembers_));


        // ── FINANCIAL: Payroll ─────────────────────────────────────────────
        if ($prevMo_['gross'] > 0) {
          $gChgPct_ = (($lastMo_['gross'] - $prevMo_['gross']) / $prevMo_['gross']) * 100.0;
          $gAbsPct_ = abs($gChgPct_);
          $gIsPos_  = $gChgPct_ >= 0;
          $gDir_    = $gIsPos_ ? '▲' : '▼';
          $gSev_    = $gIsPos_ ? 'positive' : $fnSev_($gAbsPct_);
          // Cause: derive from headcount + hours shift
          $hcDiff_  = $lastMo_['headcount'] - $prevMo_['headcount'];
          $phTot_   = $prevMo_['reg'] + $prevMo_['ot'];
          $lhTot_   = $lastMo_['reg']  + $lastMo_['ot'];
          $hrPct_   = $phTot_ > 0 ? (($lhTot_ - $phTot_) / $phTot_) * 100.0 : 0.0;
          if ($hcDiff_ < 0 && abs($hrPct_) > 5) {
            $ahcg_   = abs($hcDiff_);
            $hrStrg_ = (string) abs((int) round($hrPct_));
            $gCause_ = $ahcg_ === 1
              ? earnings_i18n_fmt('EARNINGS_ONE_WORKER_INACTIVE_HOURS_DECLINED', '1 worker became inactive and total hours declined {pct}%.', ['pct' => $hrStrg_])
              : earnings_i18n_fmt('EARNINGS_WORKERS_INACTIVE_HOURS_DECLINED', '{count} workers became inactive and total hours declined {pct}%.', ['count' => (string) $ahcg_, 'pct' => $hrStrg_]);
          } elseif ($hcDiff_ < 0) {
            $ahcg_   = abs($hcDiff_);
            $gCause_ = $ahcg_ === 1
              ? earnings_i18n('EARNINGS_ONE_WORKER_INACTIVE', '1 worker became inactive.')
              : earnings_i18n_fmt('EARNINGS_WORKERS_INACTIVE', '{count} workers became inactive.', ['count' => (string) $ahcg_]);
          } elseif (abs($hrPct_) > 10) {
            $hrStr2g_ = (string) abs((int) round($hrPct_));
            $hrDir2g_ = $hrPct_ < 0
              ? earnings_i18n('EARNINGS_DECLINED', 'declined')
              : earnings_i18n('EARNINGS_INCREASED', 'increased');
            $gCause_  = earnings_i18n_fmt('EARNINGS_TOTAL_HOURS_CHANGED_WITH_SAME_WORKFORCE', 'Total hours {direction} {percent}% with the same workforce.', ['direction' => $hrDir2g_, 'percent' => $hrStr2g_]);
          } else {
            $gCause_  = $gIsPos_
              ? earnings_i18n('EARNINGS_HIGHER_RATES_OR_PROJECT_MIX', 'Higher billing rates or improved project mix this month.')
              : earnings_i18n('EARNINGS_BILLING_MIX_RATE_CHANGES', 'Billing mix or rate changes between periods.');
          }
          $gRec_ = null;
          if (!$gIsPos_) {
            if ($gSev_ === 'critical') {
              $gRec_ = earnings_i18n('EARNINGS_REVIEW_STAFFING_IMMEDIATELY', 'Review staffing levels and upcoming project assignments immediately.');
            } elseif ($gSev_ === 'warning') {
              $gRec_ = earnings_i18n('EARNINGS_REVIEW_ACTIVE_ASSIGNMENTS_AVAILABILITY', 'Review active project assignments and workforce availability.');
            }
          }
          if ($gAbsPct_ >= 5.0) {
            $insights_[] = [
              'category' => 'financial', 'severity' => $gSev_, 'title' => earnings_i18n('EARNINGS_PAYROLL', 'Payroll'),
              'change_label' => $gDir_ . ' ' . number_format($gAbsPct_, 0) . '%',
              'context'      => earnings_fmt_money($prevMo_['gross']) . ' → ' . earnings_fmt_money($lastMo_['gross']),
              'cause'        => $gCause_, 'recommendation' => $gRec_,
              'names'        => $hcDiff_ < 0 ? $lostNames_ : ($hcDiff_ > 0 ? $gainedNames_ : []),
            ];
          }
        }

        // ── FINANCIAL: Budget on track (positive signal) ───────────────────
        if ($annualBudget_ > 0 && $budgetRemaining_ !== null && $budgetRemaining_ > 0) {
          $insights_[] = [
            'category' => 'financial', 'severity' => 'positive', 'title' => earnings_i18n('EARNINGS_BUDGET_ON_TRACK', 'Budget On Track'),
            'change_label' => earnings_i18n_fmt('EARNINGS_BUDGET_REMAINING_FMT', '{amount} remaining', ['amount' => earnings_fmt_money($budgetRemaining_)]),
            'context'      => earnings_i18n_fmt('EARNINGS_FORECAST_VS_BUDGET_FMT', 'Forecast {forecast} vs {budget} budget', ['forecast' => earnings_fmt_money($eoyForecast_), 'budget' => earnings_fmt_money($annualBudget_)]),
            'cause'        => earnings_i18n('EARNINGS_FORECAST_WITHIN_BUDGET', 'Year-end payroll forecast is within the annual budget.'),
            'recommendation' => null, 'names' => [],
          ];
        }

        // ── WORKFORCE: Headcount ───────────────────────────────────────────
        $hcDiff2_ = $lastMo_['headcount'] - $prevMo_['headcount'];
        $ahc2_    = abs($hcDiff2_);
        if ($hcDiff2_ !== 0) {
          $hcIsPos_ = $hcDiff2_ > 0;
          $hcSev_   = $hcIsPos_ ? 'positive' : ($ahc2_ >= 3 ? 'warning' : 'notice');
          $hcVerb_  = $hcIsPos_ ? 'added' : 'lost';
          $hcDir_   = $hcIsPos_ ? '+' : '-';
          $insights_[] = [
            'category' => 'workforce', 'severity' => $hcSev_, 'title' => earnings_i18n('EARNINGS_WORKFORCE', 'Workforce'),
            'change_label' => earnings_i18n_fmt('EARNINGS_WORKFORCE_MEMBER_DELTA', '{dir}{count} {memberLabel}', ['dir' => $hcDir_, 'count' => (string) $ahc2_, 'memberLabel' => $ahc2_ === 1 ? earnings_i18n('EARNINGS_MEMBER', 'Member') : earnings_i18n('EARNINGS_MEMBERS', 'Members')]),
            'context'      => earnings_i18n_fmt('EARNINGS_HEADCOUNT_WORKERS_ARROW', '{prev} -> {next} workers', ['prev' => (string) $prevMo_['headcount'], 'next' => (string) $lastMo_['headcount']]),
            'cause'        => earnings_i18n_fmt('EARNINGS_WORKFORCE_VERB_COUNT_BETWEEN_MONTHS', '{verb} {count} {workerLabel} between {prev} and {last}.', ['verb' => ucfirst($hcVerb_), 'count' => (string) $ahc2_, 'workerLabel' => $ahc2_ === 1 ? earnings_i18n('EARNINGS_WORKER', 'worker') : earnings_i18n('EARNINGS_WORKERS', 'workers'), 'prev' => $prevLabel_, 'last' => $lastLabel_]),
            'recommendation' => (!$hcIsPos_ && $ahc2_ >= 2) ? earnings_i18n('EARNINGS_CHECK_PROJECT_ROSTERS_FOR_GAPS', 'Check upcoming project rosters for coverage gaps.') : null,
            'names'        => $hcIsPos_ ? $gainedNames_ : $lostNames_,
          ];
        } else {
          $insights_[] = [
            'category' => 'workforce', 'severity' => 'positive', 'title' => earnings_i18n('EARNINGS_WORKFORCE_STABILITY', 'Workforce Stability'),
            'change_label' => earnings_i18n('EARNINGS_NO_CHURN', 'No Churn'),
            'context'      => earnings_i18n_fmt('EARNINGS_WORKERS_NO_CHANGE', '{count} workers - no change', ['count' => (string) $lastMo_['headcount']]),
            'cause'        => earnings_i18n_fmt('EARNINGS_WORKFORCE_STABLE_AT', 'Active workforce remained stable at {count} workers.', ['count' => (string) $lastMo_['headcount']]),
            'recommendation' => null, 'names' => [],
          ];
        }

        // ── WORKFORCE: Utilization ─────────────────────────────────────────
        if ($prevMo_['utilization'] > 0) {
          $uChgPct_ = (($lastMo_['utilization'] - $prevMo_['utilization']) / $prevMo_['utilization']) * 100.0;
          $uAbsPct_ = abs($uChgPct_);
          if ($uAbsPct_ >= 8.0) {
            $uIsPos_ = $uChgPct_ >= 0;
            $uDir_   = $uIsPos_ ? '▲' : '▼';
            $uSev_   = $uIsPos_ ? 'positive' : $fnSev_($uAbsPct_);
            $hcD3_   = $lastMo_['headcount'] - $prevMo_['headcount'];
            $uCause_ = $hcD3_ < 0
              ? earnings_i18n_fmt('EARNINGS_ACTIVE_WORKFORCE_DECLINED_FROM_TO', 'Active workforce declined from {prev} to {next} workers.', ['prev' => (string) $prevMo_['headcount'], 'next' => (string) $lastMo_['headcount']])
              : ($uIsPos_ ? earnings_i18n('EARNINGS_MORE_HOURS_PER_WORKER_LAST_MONTH', 'More hours recorded per worker compared to last month.')
                          : earnings_i18n('EARNINGS_HOURS_DECLINED_VS_CAPACITY', 'Hours worked declined relative to workforce capacity.'));
            $insights_[] = [
              'category' => 'workforce', 'severity' => $uSev_, 'title' => earnings_i18n('EARNINGS_UTILIZATION', 'Utilization'),
              'change_label' => $uDir_ . ' ' . number_format($uAbsPct_, 0) . '%',
              'context'      => number_format($prevMo_['utilization'], 0) . '% → ' . number_format($lastMo_['utilization'], 0) . '%',
              'cause'        => $uCause_,
              'recommendation' => (!$uIsPos_ && $uAbsPct_ >= 25) ? earnings_i18n('EARNINGS_ENSURE_ACTIVE_ASSIGNMENTS', 'Ensure workers have active site assignments.') : null,
              'names'        => ($hcD3_ < 0 ? $lostNames_ : []),
            ];
          }
        }

        // ── OPERATIONS: Overtime ratio ─────────────────────────────────────
        $otRChg_  = $prevMo_['ot_ratio'] > 0
          ? (($lastMo_['ot_ratio'] - $prevMo_['ot_ratio']) / $prevMo_['ot_ratio']) * 100.0 : 0.0;
        $otRAbs_  = abs($otRChg_);
        $otRIsUp_ = $otRChg_ >= 0;
        if ($lastMo_['ot_ratio'] > 20.0 || $otRAbs_ >= 15.0) {
          if ($otRIsUp_) {
            $otRSev_   = $lastMo_['ot_ratio'] > 35.0 ? 'warning' : 'notice';
            $otRCause_ = earnings_i18n_fmt('EARNINGS_OT_REPRESENTS_PERCENT_INCREASING_PREMIUM', 'OT represents {pct}% of all hours - increasing labour cost premium.', ['pct' => number_format($lastMo_['ot_ratio'], 1)]);
            $otRRec_   = $lastMo_['ot_ratio'] > 30.0
              ? earnings_i18n('EARNINGS_REDUCE_OVERTIME_EXPOSURE', 'Review schedules to reduce overtime exposure and labour cost premium.') : null;
          } else {
            $otRSev_   = 'positive';
            $otRCause_ = earnings_i18n_fmt('EARNINGS_OT_SHARE_REDUCED_TO_PERCENT', 'OT share reduced to {pct}% - labour cost improving.', ['pct' => number_format($lastMo_['ot_ratio'], 1)]);
            $otRRec_   = null;
          }
          $otRDir_ = $otRIsUp_ ? '▲' : '▼';
          $insights_[] = [
            'category' => 'operations', 'severity' => $otRSev_, 'title' => earnings_i18n('EARNINGS_OVERTIME_RATIO', 'Overtime Ratio'),
            'change_label' => $otRDir_ . ' ' . number_format($otRAbs_, 0) . '%',
            'context'      => number_format($prevMo_['ot_ratio'], 1) . '% → ' . number_format($lastMo_['ot_ratio'], 1) . '% of hours',
            'cause'        => $otRCause_, 'recommendation' => $otRRec_, 'names' => [],
          ];
        }
      }

      // ── OPERATIONS: Site concentration ────────────────────────────────────
      if ($totalSiteGross_ > 0 && !empty($topSites_)) {
        $topSiteShare_ = (int) round((array_values($topSites_)[0]['gross'] / $totalSiteGross_) * 100);
        $topSiteName_  = (string) array_key_first($topSites_);
        $activeSites_  = count($orgSiteData_);
        if ($topSiteShare_ >= 50) {
          $scSev_  = $topSiteShare_ >= 85 ? 'critical' : ($topSiteShare_ >= 65 ? 'warning' : 'notice');
          $scStr_  = $activeSites_ === 1
            ? earnings_i18n('EARNINGS_ONLY_ONE_ACTIVE_REVENUE_SITE', ' Only 1 active revenue-generating site.')
            : earnings_i18n_fmt('EARNINGS_SITES_TOTAL_SUFFIX', ' {count} sites total.', ['count' => (string) $activeSites_]);
          $insights_[] = [
            'category' => 'operations', 'severity' => $scSev_, 'title' => earnings_i18n('EARNINGS_SITE_DEPENDENCY_RISK', 'Site Dependency Risk'),
            'change_label' => earnings_i18n_fmt('EARNINGS_PERCENT_CONCENTRATION', '{pct}% concentration', ['pct' => (string) $topSiteShare_]),
            'context'      => '"' . $topSiteName_ . '" — ' . earnings_fmt_money(array_values($topSites_)[0]['gross']),
            'cause'        => earnings_i18n_fmt('EARNINGS_PERCENT_PAYROLL_FROM_SITE', '{pct}% of payroll originates from "{site}".', ['pct' => (string) $topSiteShare_, 'site' => $topSiteName_]) . $scStr_,
            'recommendation' => $topSiteShare_ >= 85 ? earnings_i18n('EARNINGS_DIVERSIFY_PROJECT_SITES', 'Diversify active project sites to reduce revenue concentration risk.') : null,
            'names' => [],
          ];
        } elseif ($activeSites_ >= 3) {
          $insights_[] = [
            'category' => 'operations', 'severity' => 'positive', 'title' => earnings_i18n('EARNINGS_SITE_DIVERSIFICATION', 'Site Diversification'),
            'change_label' => earnings_i18n_fmt('EARNINGS_ACTIVE_SITES_COUNT', '{count} active sites', ['count' => (string) $activeSites_]),
            'context'      => earnings_i18n_fmt('EARNINGS_TOP_SITE_PERCENT_PAYROLL', 'Top site: {pct}% of payroll', ['pct' => (string) $topSiteShare_]),
            'cause'        => earnings_i18n_fmt('EARNINGS_PAYROLL_SPREAD_ACROSS_SITES', 'Payroll is spread across {count} sites - healthy revenue diversification.', ['count' => (string) $activeSites_]),
            'recommendation' => null, 'names' => [],
          ];
        }
      }

      // ── ANALYTICS: Workforce Health ──────────────────────────────────────
      $totalMembersH_ = count($teamEarningsRows);
      $totalHoursAll_ = $teamEarningsTotals['reg_hours'] + $teamEarningsTotals['ot_hours'];
      $otPctAll_      = $totalHoursAll_ > 0 ? ($teamEarningsTotals['ot_hours'] / $totalHoursAll_) * 100.0 : 0.0;
      $dayOfYear_     = (int) $today_->format('z') + 1;
      $weeksElapsed_  = max(1.0, (float) $dayOfYear_ / 7.0);
      $avgWeeklyH_    = $totalMembersH_ > 0 ? ($totalHoursAll_ / $totalMembersH_ / $weeksElapsed_) : 0.0;

      $workersOver60h_ = 0;
      foreach ($memberWeeklyH_ as $wHUuid => $wkMap_) {
        foreach ($wkMap_ as $wkHrs_) {
          if ($wkHrs_ > 60.0) { $workersOver60h_++; break; }
        }
      }
      $workersConsec14_ = 0;
      foreach ($memberDays_ as $dUuid => $dList_) {
        $uDays_ = array_unique($dList_);
        sort($uDays_);
        $maxCon_ = 1; $con_ = 1;
        for ($dIdx_ = 1, $dLen_ = count($uDays_); $dIdx_ < $dLen_; $dIdx_++) {
          $ddiff_ = (int) ((new \DateTimeImmutable($uDays_[$dIdx_]))->diff(
            new \DateTimeImmutable($uDays_[$dIdx_ - 1]))->days);
          if ($ddiff_ === 1) { $con_++; if ($con_ > $maxCon_) { $maxCon_ = $con_; } } else { $con_ = 1; }
        }
        if ($maxCon_ >= 14) { $workersConsec14_++; }
      }
      $healthStatus_ = 'normal';
      if ($otPctAll_ > 40.0 || $workersOver60h_ > 0 || $workersConsec14_ > 0) {
        $healthStatus_ = 'risk';
      } elseif ($otPctAll_ > 20.0 || $avgWeeklyH_ > 50.0) {
        $healthStatus_ = 'watch';
      }
      $statusOtPct_  = $otPctAll_   > 40 ? 'risk' : ($otPctAll_   > 20 ? 'watch' : 'normal');
      $statusAvgH_   = $avgWeeklyH_ > 60 ? 'risk' : ($avgWeeklyH_ > 50 ? 'watch' : 'normal');
      $statusOver60_ = $workersOver60h_  > 0 ? 'risk' : 'normal';
      $statusConsec_ = $workersConsec14_ > 0 ? 'risk' : 'normal';

      // Health score (100 = perfect, deduct for each risk factor)
      $healthScore_  = 100;
      if ($workersOver60h_  > 0) { $healthScore_ -= min(30, $workersOver60h_  * 15); }
      if ($workersConsec14_ > 0) { $healthScore_ -= min(40, $workersConsec14_ * 20); }
      if ($otPctAll_ > 40.0)      { $healthScore_ -= 20; }
      elseif ($otPctAll_ > 20.0)  { $healthScore_ -= 10; }
      if ($avgWeeklyH_ > 60.0)    { $healthScore_ -= 10; }
      elseif ($avgWeeklyH_ > 50.0){ $healthScore_ -= 5;  }
      $healthScore_       = max(0, min(100, $healthScore_));
      $healthScoreStatus_ = $healthScore_ >= 90 ? 'normal' : ($healthScore_ >= 75 ? 'watch' : ($healthScore_ >= 50 ? 'concern' : 'risk'));
      $healthScoreLabel_  = match($healthScoreStatus_) {
        'normal'  => earnings_i18n('EARNINGS_HEALTH_LABEL_HEALTHY', '🟢 Healthy'),
        'watch' => earnings_i18n('EARNINGS_HEALTH_LABEL_WATCH', '🟡 Watch'),
        'concern' => earnings_i18n('EARNINGS_HEALTH_LABEL_CONCERN', '🟠 Concern'),
        default => earnings_i18n('EARNINGS_HEALTH_LABEL_RISK', '🔴 Risk')
      };

      // Cause bullets surfaced under the badge
      $healthRisks_ = [];
      if ($workersOver60h_  > 0) {
        $healthRisks_[] = $workersOver60h_ === 1
          ? earnings_i18n('EARNINGS_ONE_WORKER_EXCEEDED_60H_WEEK', '1 worker exceeded 60 hours in a single week')
          : earnings_i18n_fmt('EARNINGS_WORKERS_EXCEEDED_60H_WEEK', '{count} workers exceeded 60 hours in a single week', ['count' => (string) $workersOver60h_]);
      }
      if ($workersConsec14_ > 0) {
        $healthRisks_[] = $workersConsec14_ === 1
          ? earnings_i18n('EARNINGS_ONE_WORKER_EXCEEDED_14D', '1 worker exceeded 14 consecutive days')
          : earnings_i18n_fmt('EARNINGS_WORKERS_EXCEEDED_14D', '{count} workers exceeded 14 consecutive days', ['count' => (string) $workersConsec14_]);
      }
      if ($otPctAll_ > 40.0)     { $healthRisks_[] = earnings_i18n('EARNINGS_OT_EXCEEDS_40_PERCENT', 'Overtime exceeds 40% of total hours'); }
      elseif ($otPctAll_ > 20.0) { $healthRisks_[] = earnings_i18n('EARNINGS_OT_EXCEEDS_20_PERCENT', 'Overtime exceeds 20% of total hours'); }

      // Month-over-month trends for health card metrics (requires >= 2 months)
      $avgHrsTrend_ = null;
      $otPctTrend_  = null;
      if (isset($prevMo_, $lastMo_)) {
        $prevMonHpW_ = $prevMo_['headcount'] > 0 ? ($prevMo_['reg'] + $prevMo_['ot']) / $prevMo_['headcount'] : 0.0;
        $lastMonHpW_ = $lastMo_['headcount'] > 0 ? ($lastMo_['reg'] + $lastMo_['ot']) / $lastMo_['headcount'] : 0.0;
        if ($prevMonHpW_ > 0 && abs(($lastMonHpW_ - $prevMonHpW_) / $prevMonHpW_ * 100.0) >= 3.0) {
          $avgHrsChgT_ = ($lastMonHpW_ - $prevMonHpW_) / $prevMonHpW_ * 100.0;
          $avgHrsTrend_ = ['dir' => $avgHrsChgT_ >= 0 ? '▲' : '▼', 'pct' => abs((int) round($avgHrsChgT_))];
        }
        if ($prevMo_['ot_ratio'] > 0 && abs(($lastMo_['ot_ratio'] - $prevMo_['ot_ratio']) / $prevMo_['ot_ratio'] * 100.0) >= 5.0) {
          $otPctChgT_ = ($lastMo_['ot_ratio'] - $prevMo_['ot_ratio']) / $prevMo_['ot_ratio'] * 100.0;
          $otPctTrend_ = ['dir' => $otPctChgT_ >= 0 ? '▲' : '▼', 'pct' => abs((int) round($otPctChgT_))];
        }
      }

      // Forecast confidence level
      $forecastConf_ = $completedCount_ >= 6
        ? earnings_i18n('EARNINGS_CONFIDENCE_HIGH', 'High')
        : ($completedCount_ >= 3
          ? earnings_i18n('EARNINGS_CONFIDENCE_MEDIUM', 'Medium')
          : earnings_i18n('EARNINGS_CONFIDENCE_LOW', 'Low'));
      $forecastConfDesc_ = $completedCount_ > 0
        ? earnings_i18n_fmt('EARNINGS_FORECAST_CONFIDENCE_DESC', '{months}-month avg - Confidence: {confidence}', ['months' => (string) $completedCount_, 'confidence' => $forecastConf_])
        : earnings_i18n('EARNINGS_FORECAST_CONFIDENCE_DESC_RUN_RATE_LOW', 'Current-month run-rate - Confidence: Low');

      // Budget verdict (assessment label shown prominently in the forecast card)
      $budgetVerdict_ = null;
      if ($annualBudget_ > 0 && $budgetRemaining_ !== null) {
        if ($budgetRemaining_ >= 0) {
          $budgetVerdict_ = ['status' => 'ok', 'label' => earnings_i18n('EARNINGS_VERDICT_ON_TRACK', 'On Track'),
            'detail' => earnings_i18n_fmt('EARNINGS_UNDER_BUDGET_FMT', '{amount} under budget', ['amount' => earnings_fmt_money($budgetRemaining_)])];
        } else {
          $overAmt_ = abs($budgetRemaining_);
          $overPct_ = round(($overAmt_ / $annualBudget_) * 100, 1);
          $budgetVerdict_ = ['status' => 'over', 'label' => earnings_i18n('EARNINGS_VERDICT_OVER_BUDGET', 'Over Budget'),
            'detail' => earnings_i18n_fmt('EARNINGS_OVER_BUDGET_DETAIL_FMT', '+{amount} (+{percent}%)', ['amount' => earnings_fmt_money($overAmt_), 'percent' => (string) $overPct_])];
        }
      }

      // ── ANALYTICS: Cost Drivers ──────────────────────────────────────────
      $topCostMembers_ = array_slice($memberRanked_, 0, 5);
      $topDriverNames_ = array_slice(array_keys($topSites_), 0, 5);
      $topDriverSites_ = array_slice(array_values($topSites_), 0, 5);
      $totalOtH_       = $teamEarningsTotals['ot_hours'];
      $totalHrsAll2_   = $teamEarningsTotals['reg_hours'] + $totalOtH_;
      $impliedRate_    = $totalHrsAll2_ > 0 ? $teamEarningsTotals['gross'] / $totalHrsAll2_ : 0.0;
      $otPremiumEst_   = $totalOtH_ * $impliedRate_ * 0.5;
      $totalLoa_       = (float) array_sum($memberLoaTotals_);

      // ── ANALYTICS: Health score trend (prev month score for comparison) ──
      $prevHealthScore_  = null;
      $healthScoreTrend_ = null;
      if (isset($prevMo_)) {
        $prevScore_ = 100;
        // Use prev-month aggregate metrics approximated from cData
        $prevTotH_  = $prevMo_['reg'] + $prevMo_['ot'];
        $prevOtPct_ = $prevTotH_ > 0 ? ($prevMo_['ot'] / $prevTotH_) * 100.0 : 0.0;
        if ($prevOtPct_ > 40.0)       { $prevScore_ -= 20; }
        elseif ($prevOtPct_ > 20.0)   { $prevScore_ -= 10; }
        $prevScore_         = max(0, min(100, $prevScore_));
        $prevHealthScore_   = $prevScore_;
        $hsDiff_            = $healthScore_ - $prevScore_;
        if (abs($hsDiff_) >= 2) {
          $healthScoreTrend_ = ['dir' => $hsDiff_ >= 0 ? '▲' : '▼', 'pts' => abs((int) $hsDiff_)];
        }
      }

      // ── ANALYTICS: Payroll composition ───────────────────────────────────
      $totalGrossAll_  = $teamEarningsTotals['gross'];
      $regPayEst_      = $totalGrossAll_ - $otPremiumEst_ - $totalLoa_;
      // Travel: sum hours * implied rate  (work entries carry travel_hours)
      $totalTravelH_   = 0.0;
      foreach ($memberDays_ as $ignored) {} // already iterated; travel is in work entries
      // We don't have travel_hours summed yet — derive from Red is work hash if present.
      // For now compute as residual after reg/ot/loa (safe approximation).
      $regPayPct_      = $totalGrossAll_ > 0 ? round($regPayEst_ / $totalGrossAll_ * 100)  : 0;
      $otPremPct_      = $totalGrossAll_ > 0 ? round($otPremiumEst_ / $totalGrossAll_ * 100) : 0;
      $loaPct_         = $totalGrossAll_ > 0 ? round($totalLoa_ / $totalGrossAll_ * 100)    : 0;
      $otherPct_       = max(0, 100 - $regPayPct_ - $otPremPct_ - $loaPct_);

      // ── ANALYTICS: Risk register (flat list sorted by severity) ──────────
      $sevOrder_ = ['critical' => 0, 'warning' => 1, 'notice' => 2, 'positive' => 3, 'normal' => 4];
      $riskItems_  = [];
      foreach ($insights_ as $ins_) {
        if (in_array($ins_['severity'], ['critical', 'warning', 'notice'], true)) {
          $riskItems_[] = $ins_;
        }
      }
      usort($riskItems_, fn($a, $b) => ($sevOrder_[$a['severity']] ?? 9) <=> ($sevOrder_[$b['severity']] ?? 9));

      // Workforce health risks as additional risk items
      if ($workersOver60h_ > 0) {
        $riskItems_[] = [
          'category' => 'workforce', 'severity' => 'warning', 'title' => earnings_i18n('EARNINGS_WORKER_FATIGUE', 'Worker Fatigue'),
          'change_label' => earnings_i18n_fmt('EARNINGS_WORKER_COUNT_OVER_60H_PER_WEEK', '{count} {workerLabel} >60h/wk', ['count' => (string) $workersOver60h_, 'workerLabel' => $workersOver60h_ === 1 ? earnings_i18n('EARNINGS_WORKER', 'worker') : earnings_i18n('EARNINGS_WORKERS', 'workers')]),
          'cause' => earnings_i18n_fmt('EARNINGS_WORKER_COUNT_EXCEEDED_60H_WEEK_WITH_PERIOD', '{count} {workerLabel} exceeded 60 hours in a single week.', ['count' => (string) $workersOver60h_, 'workerLabel' => $workersOver60h_ === 1 ? earnings_i18n('EARNINGS_WORKER', 'worker') : earnings_i18n('EARNINGS_WORKERS', 'workers')]),
          'recommendation' => earnings_i18n('EARNINGS_REVIEW_WEEKLY_SCHEDULE_ASSIGNMENTS', 'Review weekly schedule assignments to prevent burnout and liability.'),
          'names' => [], 'context' => '',
        ];
      }
      if ($workersConsec14_ > 0) {
        $riskItems_[] = [
          'category' => 'workforce', 'severity' => 'warning', 'title' => earnings_i18n('EARNINGS_CONSECUTIVE_DAYS', 'Consecutive Days'),
          'change_label' => earnings_i18n_fmt('EARNINGS_WORKER_COUNT_OVER_14D', '{count} {workerLabel} >=14 days', ['count' => (string) $workersConsec14_, 'workerLabel' => $workersConsec14_ === 1 ? earnings_i18n('EARNINGS_WORKER', 'worker') : earnings_i18n('EARNINGS_WORKERS', 'workers')]),
          'cause' => earnings_i18n_fmt('EARNINGS_WORKER_COUNT_WORKED_14D_OR_MORE', '{count} {workerLabel} worked 14 or more consecutive days.', ['count' => (string) $workersConsec14_, 'workerLabel' => $workersConsec14_ === 1 ? earnings_i18n('EARNINGS_WORKER', 'worker') : earnings_i18n('EARNINGS_WORKERS', 'workers')]),
          'recommendation' => earnings_i18n('EARNINGS_ENSURE_MANDATORY_REST_DAYS', 'Ensure mandatory rest days are scheduled before the next pay period.'),
          'names' => [], 'context' => '',
        ];
      }
      usort($riskItems_, fn($a, $b) => ($sevOrder_[$a['severity']] ?? 9) <=> ($sevOrder_[$b['severity']] ?? 9));

      // ── ANALYTICS: Positive insights for recommendations ─────────────────
      $positiveItems_ = array_values(array_filter($insights_, fn($i) => $i['severity'] === 'positive'));

      // ── ANALYTICS: Auto-generated recommendations ────────────────────────
      $recommendations_ = [];
      foreach ($riskItems_ as $ri_) {
        if ($ri_['recommendation'] !== null) {
          $recommendations_[] = ['priority' => $ri_['severity'], 'text' => $ri_['recommendation'], 'source' => $ri_['title']];
        }
      }
      if (empty($recommendations_)) {
        $recommendations_[] = [
          'priority' => 'normal',
          'text' => earnings_i18n('EARNINGS_NO_CRITICAL_ACTIONS', 'No critical actions required at this time. Continue monitoring trends.'),
          'source' => earnings_i18n('SYSTEM', 'System'),
        ];
      }

      // ── ANALYTICS: Executive snapshot ────────────────────────────────────
      $criticalCount_  = count(array_filter($riskItems_, fn($i) => $i['severity'] === 'critical'));
      $warningCount_   = count(array_filter($riskItems_, fn($i) => $i['severity'] === 'warning'));
      $positiveCount_  = count($positiveItems_);
      $topRisk_        = !empty($riskItems_) ? $riskItems_[0] : null;
      Lens::timeEnd('Team Earnings: panel insights and rankings');
      Lens::timeStart('Team Earnings: panel HTML render');
    ?>

    <!-- ═══════════ EXECUTIVE SNAPSHOT ═══════════ -->
    <div class="et_exec_snapshot" role="region" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_EXEC_SUMMARY_ARIA', 'Executive summary'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="et_exec_snapshot_item et_exec_snapshot_item--primary">
        <span class="et_exec_snapshot_value"><?php echo htmlspecialchars(earnings_fmt_money($eoyForecast_), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="et_exec_snapshot_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_FORECAST_YEAR_END', 'Forecast Year End'), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($budgetVerdict_ !== null): ?>
        <span class="et_exec_snapshot_sub et_exec_snapshot_sub--<?php echo $budgetVerdict_['status']; ?>"><?php echo htmlspecialchars($budgetVerdict_['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </div>
      <div class="et_exec_snapshot_item">
        <span class="et_exec_snapshot_value et_exec_snapshot_value--<?php echo $healthScoreStatus_; ?>"><?php echo $healthScore_; ?><span class="et_exec_snapshot_value_denom">/100</span></span>
        <span class="et_exec_snapshot_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_WORKFORCE_HEALTH', 'Workforce Health'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="et_exec_snapshot_sub"><?php echo htmlspecialchars($healthScoreLabel_, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="et_exec_snapshot_item">
        <span class="et_exec_snapshot_value et_exec_snapshot_value--risk"><?php echo $criticalCount_; ?></span>
        <span class="et_exec_snapshot_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_CRITICAL_ISSUES', 'Critical Issues'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="et_exec_snapshot_sub"><?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_WARNING_COUNT_FMT', '{count} warning(s)', ['count' => (string) $warningCount_]), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="et_exec_snapshot_item">
        <span class="et_exec_snapshot_value et_exec_snapshot_value--positive"><?php echo $positiveCount_; ?></span>
        <span class="et_exec_snapshot_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_POSITIVE_TRENDS', 'Positive Trends'), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($topRisk_ !== null): ?>
        <span class="et_exec_snapshot_sub et_exec_snapshot_sub--muted"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TOP_RISK_PREFIX', 'Top risk:'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($topRisk_['title'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </div>
      <div class="et_exec_snapshot_item">
        <span class="et_exec_snapshot_value"><?php echo htmlspecialchars(earnings_fmt_money($ytdSpend_), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="et_exec_snapshot_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_YTD_SPEND', 'YTD Spend'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="et_exec_snapshot_sub"><?php echo htmlspecialchars(earnings_fmt_money($avgMonthly_), ENT_QUOTES, 'UTF-8'); ?><?php echo htmlspecialchars(earnings_i18n('EARNINGS_PER_MONTH_AVG_SUFFIX', '/mo avg'), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>

    <!-- ═══════════ BUDGET STATUS ═══════════ -->
    <?php if ($annualBudget_ > 0): ?>
    <figure class="earnings_ytd_figure et_budget_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_BUDGET_STATUS_FOR', 'Budget status for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_BUDGET', 'Budget'), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if ($budgetStatus_ !== null): ?>
        <span class="et_budget_badge et_budget_badge--<?php echo $budgetStatus_['cls']; ?>">
          <?php echo $budgetStatus_['icon']; ?> <?php echo htmlspecialchars($budgetStatus_['label'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <?php endif; ?>
      </header>
      <div class="et_budget_body">
        <div class="et_budget_stat_row">
          <div class="et_budget_stat">
            <span class="et_budget_stat_value"><?php echo htmlspecialchars(earnings_fmt_money($annualBudget_), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_budget_stat_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_BUDGET', 'Budget'), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="et_budget_stat">
            <span class="et_budget_stat_value"><?php echo htmlspecialchars(earnings_fmt_money($ytdSpend_), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_budget_stat_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_ACTUAL_YTD', 'Actual YTD'), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="et_budget_stat">
            <span class="et_budget_stat_value et_budget_stat_value--<?php echo $budgetStatus_['cls'] ?? 'ok'; ?>"><?php echo $budgetPct_ !== null ? number_format($budgetPct_, 1) . '%' : '—'; ?></span>
            <span class="et_budget_stat_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_USED', 'Used'), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="et_budget_stat">
            <span class="et_budget_stat_value"><?php echo htmlspecialchars(earnings_fmt_money($eoyForecast_), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_budget_stat_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_FORECAST', 'Forecast'), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <?php if ($budgetVariance_ !== null): ?>
          <div class="et_budget_stat">
            <span class="et_budget_stat_value et_budget_stat_value--<?php echo $budgetVariance_ > 0 ? 'critical' : 'ok'; ?>">
              <?php echo ($budgetVariance_ > 0 ? '+' : '') . htmlspecialchars(earnings_fmt_money($budgetVariance_), ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <span class="et_budget_stat_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_FORECAST_VARIANCE', 'Forecast Variance'), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($budgetPct_ !== null): ?>
        <div class="et_budget_bar_wrap" aria-hidden="true">
          <svg class="et_budget_bar_svg" viewBox="0 0 100 1" preserveAspectRatio="none">
            <rect class="et_budget_bar_bg" x="0" width="100" height="1"></rect>
            <rect class="et_budget_bar_fill et_budget_bar_fill--<?php echo $budgetStatus_['cls'] ?? 'ok'; ?>"
                  x="0" width="<?php echo min(100, round($budgetPct_, 1)); ?>" height="1"></rect>
            <rect class="et_budget_bar_marker" x="<?php echo round($threshWarn_, 1); ?>" width="0.4" height="1"></rect>
            <rect class="et_budget_bar_marker et_budget_bar_marker--crit" x="<?php echo round($threshCrit_, 1); ?>" width="0.4" height="1"></rect>
          </svg>
          <div class="et_budget_bar_labels">
            <span class="et_budget_bar_label_warn"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_WARN', 'Warn'), ENT_QUOTES, 'UTF-8'); ?> <?php echo round($threshWarn_); ?>%</span>
            <span class="et_budget_bar_label_crit"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_CRITICAL', 'Critical'), ENT_QUOTES, 'UTF-8'); ?> <?php echo round($threshCrit_); ?>%</span>
          </div>
        </div>
        <?php endif; ?>
        <p class="et_budget_hint">
          <?php if (count($siteSettingsList_) > 0): ?>
          <?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_BUDGET_DRAWN_FROM_FMT', 'Budget drawn from {count} configured site(s).', ['count' => (string) count($siteSettingsList_)]), ENT_QUOTES, 'UTF-8'); ?>
          <?php else: ?>
          <?php echo htmlspecialchars(earnings_i18n('EARNINGS_BUDGET_NONE_CONFIGURED', 'No site-level budgets configured.'), ENT_QUOTES, 'UTF-8'); ?>
          <a href="/business/"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_BUDGET_SETUP_LINK', 'Set up budgets in Business Site Controls →'), ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endif; ?>
        </p>
      </div>
    </figure>
    <?php endif; ?>

    <!-- ═══════════ INTELLIGENCE ROW ═══════════ -->
    <div class="et_intel_row">

      <!-- Payroll Forecast -->
      <figure class="et_intel_card et_intel_card--forecast" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_PAYROLL_FORECAST_FOR', 'Payroll forecast for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
        <header class="et_intel_header">
          <span class="et_intel_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_PAYROLL_FORECAST', 'Payroll Forecast'), ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="et_intel_subtitle"><?php echo htmlspecialchars($forecastConfDesc_, ENT_QUOTES, 'UTF-8'); ?></span>
        </header>

        <!-- Hero: the most important number on the panel -->
        <div class="et_forecast_hero">
          <div class="et_forecast_hero_amount"><?php echo htmlspecialchars(earnings_fmt_money($eoyForecast_), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="et_forecast_hero_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_FORECAST_YEAR_END', 'Forecast Year End'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <?php if ($budgetVerdict_ !== null): ?>
        <!-- Assessment verdict -->
        <div class="et_forecast_verdict et_forecast_verdict--<?php echo htmlspecialchars($budgetVerdict_['status'], ENT_QUOTES, 'UTF-8'); ?>">
          <span class="et_forecast_verdict_label"><?php echo htmlspecialchars($budgetVerdict_['label'], ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="et_forecast_verdict_detail"><?php echo htmlspecialchars($budgetVerdict_['detail'], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php endif; ?>

        <dl class="et_forecast_dl">
          <div class="et_forecast_row">
            <dt class="et_forecast_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_YTD_SPEND', 'YTD Spend'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_forecast_value"><?php echo htmlspecialchars(earnings_fmt_money($ytdSpend_), ENT_QUOTES, 'UTF-8'); ?></dd>
          </div>
          <?php if ($cmEntry_ !== null): ?>
          <div class="et_forecast_row">
            <dt class="et_forecast_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_CURRENT_MONTH_PROJECTED', 'Current Month (proj.)'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_forecast_value"><?php echo htmlspecialchars(earnings_fmt_money($cmProjected_), ENT_QUOTES, 'UTF-8'); ?></dd>
          </div>
          <?php endif; ?>
          <?php if ($annualBudget_ > 0): ?>
          <div class="et_forecast_row">
            <dt class="et_forecast_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_ANNUAL_BUDGET', 'Annual Budget'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_forecast_value"><?php echo htmlspecialchars(earnings_fmt_money($annualBudget_), ENT_QUOTES, 'UTF-8'); ?></dd>
          </div>
          <?php endif; ?>
          <div class="et_forecast_row et_forecast_row--muted">
            <dt class="et_forecast_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_AVG_PER_MONTH', 'Avg / Month'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_forecast_value"><?php echo htmlspecialchars(earnings_fmt_money($avgMonthly_), ENT_QUOTES, 'UTF-8'); ?></dd>
          </div>
        </dl>
      </figure>

      <!-- Workforce Health -->
      <figure class="et_intel_card et_intel_card--health" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_WORKFORCE_HEALTH_FOR', 'Workforce health for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
        <header class="et_intel_header">
          <span class="et_intel_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_WORKFORCE_HEALTH', 'Workforce Health'), ENT_QUOTES, 'UTF-8'); ?></span>
        </header>

        <!-- Score + status badge + trend -->
        <div class="et_health_score_row">
          <div class="et_health_score_num et_health_score_num--<?php echo $healthScoreStatus_; ?>">
            <?php echo $healthScore_; ?><span class="et_health_score_denom">/100</span>
          </div>
          <div class="et_health_score_meta">
            <span class="et_health_badge et_health_badge--<?php echo $healthScoreStatus_; ?>">
              <?php echo htmlspecialchars($healthScoreLabel_, ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php if ($healthScoreTrend_ !== null): ?>
            <span class="et_health_score_trend"><?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_HEALTH_SCORE_TREND_FMT', '{dir} {pts} pts vs last month', ['dir' => $healthScoreTrend_['dir'], 'pts' => (string) $healthScoreTrend_['pts']]), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($healthRisks_)): ?>
        <!-- Cause: why the score is what it is -->
        <ul class="et_health_risks">
          <?php foreach ($healthRisks_ as $hr_): ?>
          <li><?php echo htmlspecialchars($hr_, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <dl class="et_health_dl">
          <div class="et_health_row">
            <span class="et_health_emoji" aria-hidden="true"><?php echo $statusAvgH_ === 'normal' ? '🟢' : ($statusAvgH_ === 'watch' ? '🟡' : '🔴'); ?></span>
            <dt class="et_health_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_HEALTH_AVG_WEEKLY_HRS', 'Avg Weekly Hrs / Worker'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_health_value">
              <?php echo htmlspecialchars(number_format($avgWeeklyH_, 1) . 'h', ENT_QUOTES, 'UTF-8'); ?>
              <?php if ($avgHrsTrend_ !== null): ?>
              <span class="et_health_trend"><?php echo htmlspecialchars($avgHrsTrend_['dir'] . ' ' . $avgHrsTrend_['pct'] . '%', ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </dd>
          </div>
          <div class="et_health_row">
            <span class="et_health_emoji" aria-hidden="true"><?php echo $statusOtPct_ === 'normal' ? '🟢' : ($statusOtPct_ === 'watch' ? '🟡' : '🔴'); ?></span>
            <dt class="et_health_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_HEALTH_OVERTIME_PERCENT', 'Overtime %'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_health_value">
              <?php echo htmlspecialchars(number_format($otPctAll_, 1) . '%', ENT_QUOTES, 'UTF-8'); ?>
              <?php if ($otPctTrend_ !== null): ?>
              <span class="et_health_trend"><?php echo htmlspecialchars($otPctTrend_['dir'] . ' ' . $otPctTrend_['pct'] . '%', ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </dd>
          </div>
          <div class="et_health_row">
            <span class="et_health_emoji" aria-hidden="true"><?php echo $workersOver60h_ > 0 ? '🔴' : '🟢'; ?></span>
            <dt class="et_health_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_HEALTH_WORKERS_OVER_60H_WEEK', 'Workers > 60h / Week'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_health_value"><?php echo htmlspecialchars((string) $workersOver60h_, ENT_QUOTES, 'UTF-8'); ?></dd>
          </div>
          <div class="et_health_row">
            <span class="et_health_emoji" aria-hidden="true"><?php echo $workersConsec14_ > 0 ? '🔴' : '🟢'; ?></span>
            <dt class="et_health_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_HEALTH_WORKERS_OVER_14D', 'Workers > 14 Consecutive Days'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_health_value"><?php echo htmlspecialchars((string) $workersConsec14_, ENT_QUOTES, 'UTF-8'); ?></dd>
          </div>
          <div class="et_health_row et_health_row--meta">
            <span class="et_health_emoji" aria-hidden="true">🟢</span>
            <dt class="et_health_label"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_HEALTH_TOTAL_MEMBERS', 'Total Members'), ENT_QUOTES, 'UTF-8'); ?></dt>
            <dd class="et_health_value"><?php echo htmlspecialchars((string) $totalMembersH_, ENT_QUOTES, 'UTF-8'); ?></dd>
          </div>
        </dl>
      </figure>

    </div><!-- .et_intel_row -->

    <!-- ═══════════ VARIANCE & TREND ALERTS ═══════════ -->
    <?php
      $p1H = 220; $p1L = 74; $p1R = 16;
      $p1W = $svgW_ - $p1L - $p1R;
      $p1PlotH = $p1H - $padT_ - $padB_;
      $p1MaxD  = $niceMax_((float) max(max($grossVals_), max($cumVals_)));
      $p1xFor  = fn(int $i): float => (float) $p1L + ($cN > 1 ? ($i / ($cN - 1)) * $p1W : $p1W / 2.0);
      $p1yD    = fn(float $v): float => (float) $padT_ + $p1PlotH - ($v / $p1MaxD) * $p1PlotH;
      $p1ptGross = $buildPts_($grossVals_, $p1xFor, $p1yD);
      $p1ptNet   = $buildPts_($netVals_,   $p1xFor, $p1yD);
      $p1ptAvg   = $buildPts_($avgVals_,   $p1xFor, $p1yD);
      $p1ptCum   = $buildPts_($cumVals_,   $p1xFor, $p1yD);
    ?>
    <?php if (!empty($insights_)):
      $insGroups_ = [];
      foreach ($insights_ as $ins_) { $insGroups_[$ins_['category']][] = $ins_; }
      $catMeta_  = [
        'financial'  => [earnings_i18n('EARNINGS_FINANCIAL', 'Financial'),  earnings_i18n('EARNINGS_FINANCIAL_SUBTITLE', 'Month-over-month payroll, budget &amp; earnings trends')],
        'workforce'  => [earnings_i18n('EARNINGS_WORKFORCE', 'Workforce'),  earnings_i18n('EARNINGS_WORKFORCE_SUBTITLE', 'Headcount, utilization &amp; availability changes')],
        'operations' => [earnings_i18n('EARNINGS_OPERATIONS', 'Operations'), earnings_i18n('EARNINGS_OPERATIONS_SUBTITLE', 'Overtime, site concentration &amp; efficiency signals')],
      ];
      $sevEmoji_ = ['critical' => '🔴', 'warning' => '🟠', 'notice' => '🟡', 'normal' => '🟢', 'positive' => '✓'];
      $sevLabel_ = [
        'critical' => earnings_i18n('EARNINGS_CRITICAL', 'Critical'),
        'warning' => earnings_i18n('EARNINGS_WARNING', 'Warning'),
        'notice' => earnings_i18n('EARNINGS_NOTICE', 'Notice'),
        'normal' => earnings_i18n('EARNINGS_NORMAL', 'Normal'),
        'positive' => earnings_i18n('EARNINGS_POSITIVE', 'Positive'),
      ];
    ?>
      <?php
        $renderInsightsFigure = static function (string $catKey_, string $catLabel_, string $catSub_) use ($insGroups_, $sevEmoji_, $sevLabel_): void {
          if (empty($insGroups_[$catKey_])) {
            return;
          }
          ?>
    <figure class="earnings_ytd_figure et_alerts_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_INSIGHTS_FOR_CATEGORY', '{category} insights', ['category' => $catLabel_]), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars($catLabel_, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo $catSub_; ?></span>
      </header>
      <div class="et_alerts_cards">
          <?php foreach ($insGroups_[$catKey_] as $ins_): ?>
        <article class="et_alert_card et_alert_card--<?php echo htmlspecialchars($ins_['severity'], ENT_QUOTES, 'UTF-8'); ?>"
                 aria-label="<?php echo htmlspecialchars($ins_['title'] . ': ' . $ins_['change_label'], ENT_QUOTES, 'UTF-8'); ?>">
          <header class="et_alert_card_header">
            <span class="et_alert_card_title"><?php echo htmlspecialchars($ins_['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_alert_sev et_alert_sev--<?php echo htmlspecialchars($ins_['severity'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars(($sevEmoji_[$ins_['severity']] ?? '') . ' ' . ($sevLabel_[$ins_['severity']] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </header>
          <p class="et_alert_card_change"><?php echo htmlspecialchars($ins_['change_label'], ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="et_alert_card_context"><?php echo htmlspecialchars($ins_['context'], ENT_QUOTES, 'UTF-8'); ?></p>
          <p class="et_alert_card_cause"><?php echo htmlspecialchars($ins_['cause'], ENT_QUOTES, 'UTF-8'); ?></p>
          <?php if ($ins_['recommendation'] !== null): ?>
          <p class="et_alert_card_rec">
            <span class="et_alert_card_rec_arrow" aria-hidden="true">→</span>
            <?php echo htmlspecialchars($ins_['recommendation'], ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <?php endif; ?>
          <?php if (!empty($ins_['names'])): ?>
          <div class="et_alert_names">
            <?php foreach ($ins_['names'] as $nIdx_ => $nName_):
              $nLabel_ = count($ins_['names']) === 1
                ? $nName_
                : (($nIdx_ + 1) . '. ' . $nName_);
            ?>
            <span class="et_alert_names_item"><?php echo htmlspecialchars($nLabel_, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </article>
          <?php endforeach; ?>
      </div>
    </figure>
          <?php
        };
        [$finLabel_, $finSub_] = $catMeta_['financial'];
        [$wfLabel_, $wfSub_] = $catMeta_['workforce'];
        [$opsLabel_, $opsSub_] = $catMeta_['operations'];
        $hasFinRow_ = !empty($insGroups_['financial']) || !empty($insGroups_['workforce']);
        $hasOpsRow_ = !empty($insGroups_['operations']);
      ?>
      <?php if ($hasFinRow_): ?>
    <div class="et_reports_panel_row">
        <?php $renderInsightsFigure('financial', $finLabel_, $finSub_); ?>
        <?php $renderInsightsFigure('workforce', $wfLabel_, $wfSub_); ?>
    </div>
      <?php endif; ?>
    <div class="et_reports_panel_row">
        <?php if ($hasOpsRow_): ?>
        <?php $renderInsightsFigure('operations', $opsLabel_, $opsSub_); ?>
        <?php endif; ?>

    <!-- ═══════════ PANEL 1: PAYROLL ═══════════ -->
    <?php else: ?>
    <!-- ═══════════ PANEL 1: PAYROLL (no insight rows) ═══════════ -->
    <?php endif; ?>
    <figure class="earnings_ytd_figure et_reports_panel_payroll" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_PAYROLL_OVERVIEW_FOR', 'Payroll overview for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_PAYROLL', 'Payroll'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_PAYROLL_OVERVIEW_SUBTITLE', 'Monthly gross, net, avg per member & cumulative YTD spend'), ENT_QUOTES, 'UTF-8'); ?></span>
      </header>
      <div class="earnings_ytd_body">
        <svg viewBox="0 0 <?php echo $svgW_; ?> <?php echo $p1H; ?>"
             class="earnings_ytd_svg" role="img" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <?php for ($t = 0; $t <= $ticks_; $t++): $gy = round($padT_ + ($t / $ticks_) * $p1PlotH, 1); ?>
          <line x1="<?php echo $p1L; ?>" y1="<?php echo $gy; ?>" x2="<?php echo $p1L + $p1W; ?>" y2="<?php echo $gy; ?>" class="ytd_grid" />
          <?php endfor; ?>
          <?php foreach ($cData as $vi => $_): $vx = round($p1xFor($vi), 1); ?>
          <line x1="<?php echo $vx; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $vx; ?>" y2="<?php echo $padT_ + $p1PlotH; ?>" class="ytd_grid ytd_grid--vert" />
          <?php endforeach; ?>
          <?php for ($t = 0; $t <= $ticks_; $t++):
            $val = ($p1MaxD / $ticks_) * ($ticks_ - $t);
            $gy  = round($padT_ + ($t / $ticks_) * $p1PlotH + 4, 1);
            $lbl = $val >= 1000 ? '$' . number_format($val / 1000, 1) . 'k' : '$' . number_format($val, 0);
          ?>
          <text x="<?php echo $p1L - 8; ?>" y="<?php echo $gy; ?>" class="ytd_axis_label ytd_axis_label--left"><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></text>
          <?php endfor; ?>
          <text x="<?php echo $p1L - 8; ?>" y="18" class="ytd_axis_label ytd_axis_label--left ytd_axis_tag">$</text>
          <?php foreach ($cData as $xi => $d): $lx = round($p1xFor($xi), 1); ?>
          <text x="<?php echo $lx; ?>" y="<?php echo $padT_ + $p1PlotH + 16; ?>" class="ytd_axis_label ytd_axis_label--x"><?php echo htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8'); ?></text>
          <?php endforeach; ?>
          <line x1="<?php echo $p1L; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $p1L; ?>" y2="<?php echo $padT_ + $p1PlotH; ?>" class="ytd_axis_border" />
          <line x1="<?php echo $p1L; ?>" y1="<?php echo $padT_ + $p1PlotH; ?>" x2="<?php echo $p1L + $p1W; ?>" y2="<?php echo $padT_ + $p1PlotH; ?>" class="ytd_axis_border" />
          <g data-series="gross">
            <polyline points="<?php echo $p1ptGross; ?>" fill="none" class="ytd_line ytd_line--gross" />
            <?php foreach ($cData as $pi => $d): $px = round($p1xFor($pi), 1); ?>
            <circle cx="<?php echo $px; ?>" cy="<?php echo round($p1yD($d['gross']), 1); ?>" r="4" class="ytd_dot ytd_dot--gross"><title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_GROSS', '{label}: Gross {amount}', ['label' => $d['label'], 'amount' => earnings_fmt_money((float) $d['gross'])]); ?></title></circle>
            <?php endforeach; ?>
          </g>
          <g data-series="net">
            <polyline points="<?php echo $p1ptNet; ?>" fill="none" class="ytd_line ytd_line--net" />
            <?php foreach ($cData as $pi => $d): $px = round($p1xFor($pi), 1); ?>
            <circle cx="<?php echo $px; ?>" cy="<?php echo round($p1yD($d['net']), 1); ?>" r="4" class="ytd_dot ytd_dot--net"><title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_NET', '{label}: Net {amount}', ['label' => $d['label'], 'amount' => earnings_fmt_money((float) $d['net'])]); ?></title></circle>
            <?php endforeach; ?>
          </g>
          <g data-series="avg">
            <polyline points="<?php echo $p1ptAvg; ?>" fill="none" class="ytd_line ytd_line--avg" />
            <?php foreach ($cData as $pi => $d): $px = round($p1xFor($pi), 1); ?>
            <circle cx="<?php echo $px; ?>" cy="<?php echo round($p1yD($d['avg_gross']), 1); ?>" r="4" class="ytd_dot ytd_dot--avg"><title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_AVG_MEMBER', '{label}: Avg/Member {amount}', ['label' => $d['label'], 'amount' => earnings_fmt_money((float) $d['avg_gross'])]); ?></title></circle>
            <?php endforeach; ?>
          </g>
          <g data-series="cum">
            <polyline points="<?php echo $p1ptCum; ?>" fill="none" class="ytd_line ytd_line--cum" />
            <?php foreach ($cData as $pi => $d): $px = round($p1xFor($pi), 1); ?>
            <circle cx="<?php echo $px; ?>" cy="<?php echo round($p1yD($d['cum_gross']), 1); ?>" r="4" class="ytd_dot ytd_dot--cum"><title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_YTD_BURN', '{label}: YTD Burn {amount}', ['label' => $d['label'], 'amount' => earnings_fmt_money((float) $d['cum_gross'])]); ?></title></circle>
            <?php endforeach; ?>
          </g>
        </svg>
        <div class="earnings_ytd_controls" role="group" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_TOGGLE_PAYROLL_SERIES', 'Toggle payroll series'), ENT_QUOTES, 'UTF-8'); ?>">
          <?php foreach ([
            ['key' => 'gross', 'label' => earnings_i18n('GROSS', 'Gross'),      'sub' => '($)'],
            ['key' => 'net',   'label' => earnings_i18n('NET', 'Net'),           'sub' => '($)'],
            ['key' => 'avg',   'label' => earnings_i18n('EARNINGS_AVG_PER_MEMBER', 'Avg/Member'),  'sub' => '($)'],
            ['key' => 'cum',   'label' => earnings_i18n('EARNINGS_YTD_BURN', 'YTD Burn'),          'sub' => '($)'],
          ] as $sd_): ?>
          <label class="ytd_legend_item ytd_legend_item--<?php echo $sd_['key']; ?>">
            <input type="checkbox" class="ytd_legend_checkbox" data-series="<?php echo $sd_['key']; ?>" checked>
            <span class="ytd_legend_swatch"></span>
            <?php echo htmlspecialchars($sd_['label'], ENT_QUOTES, 'UTF-8'); ?><?php if ($sd_['sub'] !== ''): ?> <small><?php echo htmlspecialchars($sd_['sub'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </figure>
    <?php if (!empty($insights_)): ?>
    </div><!-- .et_reports_panel_row (operations + payroll) -->
    <?php endif; ?>

    <!-- ═══════════ PANEL 2: HOURS DISTRIBUTION ═══════════ -->
    <?php
      $p2H     = 220; $p2L = 60; $p2R = 52;
      $p2W     = $svgW_ - $p2L - $p2R;
      $p2PlotH = $p2H - $padT_ - $padB_;
      $p2totArr  = array_map(fn($d) => $d['reg'] + $d['ot'], $cData);
      $p2MaxH    = $niceMax_((float) max(max($p2totArr), 1.0));
      $p2SlotW   = $p2W / max($cN, 1);
      $p2BarW    = round($p2SlotW * 0.65, 1);
      $p2xBarC   = fn(int $i): float => (float) $p2L + ($i + 0.5) * $p2SlotW;
      $p2xBarL   = fn(int $i): float => round($p2xBarC($i) - $p2BarW / 2.0, 1);
      $p2yH      = fn(float $v): float => (float) $padT_ + $p2PlotH - ($v / $p2MaxH) * $p2PlotH;
      $p2yPct    = fn(float $v): float => (float) $padT_ + $p2PlotH - ($v / 100.0) * $p2PlotH;
      $p2ptRatio = $buildPts_($otRatioVals_, $p2xBarC, $p2yPct);
    ?>
    <figure class="earnings_ytd_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_HOURS_DISTRIBUTION_FOR', 'Hours distribution for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_HOURS_DISTRIBUTION', 'Hours Distribution'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_HOURS_DISTRIBUTION_SUBTITLE', 'Regular vs. overtime by month - OT efficiency ratio (right axis)'), ENT_QUOTES, 'UTF-8'); ?></span>
      </header>
      <div class="earnings_ytd_body">
        <svg viewBox="0 0 <?php echo $svgW_; ?> <?php echo $p2H; ?>"
             class="earnings_ytd_svg" role="img" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <?php for ($t = 0; $t <= $ticks_; $t++): $gy = round($padT_ + ($t / $ticks_) * $p2PlotH, 1); ?>
          <line x1="<?php echo $p2L; ?>" y1="<?php echo $gy; ?>" x2="<?php echo $p2L + $p2W; ?>" y2="<?php echo $gy; ?>" class="ytd_grid" />
          <?php endfor; ?>
          <?php for ($t = 0; $t <= $ticks_; $t++):
            $val = ($p2MaxH / $ticks_) * ($ticks_ - $t);
            $gy  = round($padT_ + ($t / $ticks_) * $p2PlotH + 4, 1);
            $lbl = number_format($val, 0) . 'h';
          ?>
          <text x="<?php echo $p2L - 8; ?>" y="<?php echo $gy; ?>" class="ytd_axis_label ytd_axis_label--left"><?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?></text>
          <?php endfor; ?>
          <text x="<?php echo $p2L - 8; ?>" y="18" class="ytd_axis_label ytd_axis_label--left ytd_axis_tag">h</text>
          <?php for ($t = 0; $t <= $ticks_; $t++):
            $gy  = round($padT_ + ($t / $ticks_) * $p2PlotH + 4, 1);
            $pct = (int) ((1.0 - $t / $ticks_) * 100);
          ?>
          <text x="<?php echo $p2L + $p2W + 6; ?>" y="<?php echo $gy; ?>" class="ytd_axis_label ytd_axis_label--right ytd_axis_label--pct"><?php echo $pct; ?>%</text>
          <?php endfor; ?>
          <text x="<?php echo $p2L + $p2W + 6; ?>" y="18" class="ytd_axis_label ytd_axis_label--right ytd_axis_label--pct ytd_axis_tag">%</text>
          <?php foreach ($cData as $xi => $d): $lx = round($p2xBarC($xi), 1); ?>
          <text x="<?php echo $lx; ?>" y="<?php echo $padT_ + $p2PlotH + 16; ?>" class="ytd_axis_label ytd_axis_label--x"><?php echo htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8'); ?></text>
          <?php endforeach; ?>
          <line x1="<?php echo $p2L; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $p2L; ?>" y2="<?php echo $padT_ + $p2PlotH; ?>" class="ytd_axis_border" />
          <line x1="<?php echo $p2L + $p2W; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $p2L + $p2W; ?>" y2="<?php echo $padT_ + $p2PlotH; ?>" class="ytd_axis_border ytd_axis_border--pct" />
          <line x1="<?php echo $p2L; ?>" y1="<?php echo $padT_ + $p2PlotH; ?>" x2="<?php echo $p2L + $p2W; ?>" y2="<?php echo $padT_ + $p2PlotH; ?>" class="ytd_axis_border" />
          <!-- Reg bars (bottom of stack) -->
          <g data-series="reg">
            <?php foreach ($cData as $p2i => $p2d):
              $p2regH = round(($p2d['reg'] / $p2MaxH) * $p2PlotH, 1);
              $p2otH  = round(($p2d['ot']  / $p2MaxH) * $p2PlotH, 1);
              $p2bY   = $padT_ + $p2PlotH;
            ?>
            <rect x="<?php echo $p2xBarL($p2i); ?>" y="<?php echo $p2bY - $p2regH; ?>"
                  width="<?php echo $p2BarW; ?>" height="<?php echo max($p2regH, 0); ?>"
                  class="ytd_bar--reg">
              <title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_REG_HRS', '{label}: Reg {hours} hrs', ['label' => $p2d['label'], 'hours' => earnings_fmt_hours((float) $p2d['reg'])]); ?></title>
            </rect>
            <?php endforeach; ?>
          </g>
          <!-- OT bars (stacked above reg) -->
          <g data-series="ot">
            <?php foreach ($cData as $p2i => $p2d):
              $p2regH = round(($p2d['reg'] / $p2MaxH) * $p2PlotH, 1);
              $p2otH  = round(($p2d['ot']  / $p2MaxH) * $p2PlotH, 1);
              $p2bY   = $padT_ + $p2PlotH;
            ?>
            <rect x="<?php echo $p2xBarL($p2i); ?>" y="<?php echo $p2bY - $p2regH - $p2otH; ?>"
                  width="<?php echo $p2BarW; ?>" height="<?php echo max($p2otH, 0); ?>"
                  class="ytd_bar--ot">
              <title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_OT_HRS', '{label}: OT {hours} hrs', ['label' => $p2d['label'], 'hours' => earnings_fmt_hours((float) $p2d['ot'])]); ?></title>
            </rect>
            <?php endforeach; ?>
          </g>
          <!-- OT Ratio overlay line -->
          <g data-series="ot_ratio">
            <polyline points="<?php echo $p2ptRatio; ?>" fill="none" class="ytd_line ytd_line--ot_ratio" />
            <?php foreach ($cData as $pi => $d): $px = round($p2xBarC($pi), 1); ?>
            <circle cx="<?php echo $px; ?>" cy="<?php echo round($p2yPct($d['ot_ratio']), 1); ?>" r="3" class="ytd_dot ytd_dot--ot_ratio"><title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_OT_RATIO', '{label}: OT Ratio {pct}', ['label' => $d['label'], 'pct' => earnings_fmt_percent((float) $d['ot_ratio'])]); ?></title></circle>
            <?php endforeach; ?>
          </g>
        </svg>
        <div class="earnings_ytd_controls" role="group" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_TOGGLE_HOURS_SERIES', 'Toggle hours series'), ENT_QUOTES, 'UTF-8'); ?>">
          <?php foreach ([
            ['key' => 'reg',      'label' => earnings_i18n('EARNINGS_BREAKDOWN_REG_HRS', 'Reg Hrs'),  'sub' => ''],
            ['key' => 'ot',       'label' => earnings_i18n('EARNINGS_BREAKDOWN_OT_HRS', 'OT Hrs'),   'sub' => ''],
            ['key' => 'ot_ratio', 'label' => earnings_i18n('EARNINGS_OVERTIME_RATIO', 'OT Ratio'), 'sub' => '(%)'],
          ] as $sd_): ?>
          <label class="ytd_legend_item ytd_legend_item--<?php echo $sd_['key']; ?>">
            <input type="checkbox" class="ytd_legend_checkbox" data-series="<?php echo $sd_['key']; ?>" checked>
            <span class="ytd_legend_swatch"></span>
            <?php echo htmlspecialchars($sd_['label'], ENT_QUOTES, 'UTF-8'); ?><?php if ($sd_['sub'] !== ''): ?> <small><?php echo htmlspecialchars($sd_['sub'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </figure>

    <!-- ═══════════ PANEL 3: WORKFORCE ═══════════ -->
    <?php
      $p3H     = 200; $p3L = 50; $p3R = 52;
      $p3W     = $svgW_ - $p3L - $p3R;
      $p3PlotH = $p3H - $padT_ - $padB_;
      $p3MaxHC = $niceMax_((float) max(max($headVals_), 1.0));
      $p3SlotW = $p3W / max($cN, 1);
      $p3BarW  = round($p3SlotW * 0.65, 1);
      $p3xBarC = fn(int $i): float => (float) $p3L + ($i + 0.5) * $p3SlotW;
      $p3xBarL = fn(int $i): float => round($p3xBarC($i) - $p3BarW / 2.0, 1);
      $p3yHC   = fn(float $v): float => (float) $padT_ + $p3PlotH - ($v / $p3MaxHC) * $p3PlotH;
      $p3yPct  = fn(float $v): float => (float) $padT_ + $p3PlotH - ($v / 100.0) * $p3PlotH;
      $p3ptUtil = $buildPts_($utilVals_, $p3xBarC, $p3yPct);
    ?>
    <figure class="earnings_ytd_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_WORKFORCE_OVERVIEW_FOR', 'Workforce overview for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_WORKFORCE', 'Workforce'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_WORKFORCE_OVERVIEW_SUBTITLE', 'Active headcount by month - workforce utilization rate (right axis)'), ENT_QUOTES, 'UTF-8'); ?></span>
      </header>
      <div class="earnings_ytd_body">
        <svg viewBox="0 0 <?php echo $svgW_; ?> <?php echo $p3H; ?>"
             class="earnings_ytd_svg" role="img" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <?php for ($t = 0; $t <= $ticks_; $t++): $gy = round($padT_ + ($t / $ticks_) * $p3PlotH, 1); ?>
          <line x1="<?php echo $p3L; ?>" y1="<?php echo $gy; ?>" x2="<?php echo $p3L + $p3W; ?>" y2="<?php echo $gy; ?>" class="ytd_grid" />
          <?php endfor; ?>
          <?php for ($t = 0; $t <= $ticks_; $t++):
            $val = ($p3MaxHC / $ticks_) * ($ticks_ - $t);
            $gy  = round($padT_ + ($t / $ticks_) * $p3PlotH + 4, 1);
          ?>
          <text x="<?php echo $p3L - 8; ?>" y="<?php echo $gy; ?>" class="ytd_axis_label ytd_axis_label--left"><?php echo number_format($val, 0); ?></text>
          <?php endfor; ?>
          <text x="<?php echo $p3L - 8; ?>" y="18" class="ytd_axis_label ytd_axis_label--left ytd_axis_tag">#</text>
          <?php for ($t = 0; $t <= $ticks_; $t++):
            $gy  = round($padT_ + ($t / $ticks_) * $p3PlotH + 4, 1);
            $pct = (int) ((1.0 - $t / $ticks_) * 100);
          ?>
          <text x="<?php echo $p3L + $p3W + 6; ?>" y="<?php echo $gy; ?>" class="ytd_axis_label ytd_axis_label--right ytd_axis_label--pct"><?php echo $pct; ?>%</text>
          <?php endfor; ?>
          <text x="<?php echo $p3L + $p3W + 6; ?>" y="18" class="ytd_axis_label ytd_axis_label--right ytd_axis_label--pct ytd_axis_tag">%</text>
          <?php foreach ($cData as $xi => $d): $lx = round($p3xBarC($xi), 1); ?>
          <text x="<?php echo $lx; ?>" y="<?php echo $padT_ + $p3PlotH + 16; ?>" class="ytd_axis_label ytd_axis_label--x"><?php echo htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8'); ?></text>
          <?php endforeach; ?>
          <line x1="<?php echo $p3L; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $p3L; ?>" y2="<?php echo $padT_ + $p3PlotH; ?>" class="ytd_axis_border" />
          <line x1="<?php echo $p3L + $p3W; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $p3L + $p3W; ?>" y2="<?php echo $padT_ + $p3PlotH; ?>" class="ytd_axis_border ytd_axis_border--pct" />
          <line x1="<?php echo $p3L; ?>" y1="<?php echo $padT_ + $p3PlotH; ?>" x2="<?php echo $p3L + $p3W; ?>" y2="<?php echo $padT_ + $p3PlotH; ?>" class="ytd_axis_border" />
          <g data-series="headcount">
            <?php foreach ($cData as $p3i => $p3d):
              $p3hcH = round(((float) $p3d['headcount'] / $p3MaxHC) * $p3PlotH, 1);
              $p3bY  = $padT_ + $p3PlotH;
            ?>
            <rect x="<?php echo $p3xBarL($p3i); ?>" y="<?php echo $p3bY - $p3hcH; ?>"
                  width="<?php echo $p3BarW; ?>" height="<?php echo max($p3hcH, 0); ?>"
                  class="ytd_bar--headcount">
              <title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_ACTIVE_MEMBERS', '{label}: {count} active members', ['label' => $p3d['label'], 'count' => (string) $p3d['headcount']]); ?></title>
            </rect>
            <?php endforeach; ?>
          </g>
          <g data-series="utilization">
            <polyline points="<?php echo $p3ptUtil; ?>" fill="none" class="ytd_line ytd_line--util" />
            <?php foreach ($cData as $pi => $d): $px = round($p3xBarC($pi), 1); ?>
            <circle cx="<?php echo $px; ?>" cy="<?php echo round($p3yPct($d['utilization']), 1); ?>" r="3" class="ytd_dot ytd_dot--util"><title><?php echo earnings_chart_hover('EARNINGS_YTD_HOVER_UTILIZATION', '{label}: Utilization {pct}', ['label' => $d['label'], 'pct' => earnings_fmt_percent((float) $d['utilization'])]); ?></title></circle>
            <?php endforeach; ?>
          </g>
        </svg>
        <div class="earnings_ytd_controls" role="group" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_TOGGLE_WORKFORCE_SERIES', 'Toggle workforce series'), ENT_QUOTES, 'UTF-8'); ?>">
          <?php foreach ([
            ['key' => 'headcount',   'label' => earnings_i18n('EARNINGS_HEADCOUNT', 'Headcount'),   'sub' => '(#)'],
            ['key' => 'utilization', 'label' => earnings_i18n('EARNINGS_UTILIZATION', 'Utilization'), 'sub' => '(%)'],
          ] as $sd_): ?>
          <label class="ytd_legend_item ytd_legend_item--<?php echo $sd_['key']; ?>">
            <input type="checkbox" class="ytd_legend_checkbox" data-series="<?php echo $sd_['key']; ?>" checked>
            <span class="ytd_legend_swatch"></span>
            <?php echo htmlspecialchars($sd_['label'], ENT_QUOTES, 'UTF-8'); ?><?php if ($sd_['sub'] !== ''): ?> <small><?php echo htmlspecialchars($sd_['sub'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </figure>

    <!-- ═══════════ COST DRIVERS ═══════════ -->
    <figure class="earnings_ytd_figure et_cost_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_COST_DRIVERS_FOR', 'Cost drivers for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_COST_DRIVERS', 'Cost Drivers'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_COST_DRIVERS_SUBTITLE', 'Top contributors by member, site, OT premium & allowances'), ENT_QUOTES, 'UTF-8'); ?></span>
      </header>
      <div class="et_cost_body">
        <!-- Top Members -->
        <div class="et_cost_col">
          <h3 class="et_cost_col_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TOP_MEMBERS', 'Top Members'), ENT_QUOTES, 'UTF-8'); ?></h3>
          <?php foreach ($topCostMembers_ as $cdm_): ?>
          <div class="et_cost_row">
            <span class="et_cost_name"><?php echo htmlspecialchars($cdm_['name'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_cost_amount"><?php echo htmlspecialchars(earnings_fmt_money($cdm_['gross']), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Top Sites -->
        <div class="et_cost_col">
          <h3 class="et_cost_col_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_TOP_SITES', 'Top Sites'), ENT_QUOTES, 'UTF-8'); ?></h3>
          <?php foreach ($topDriverNames_ as $tdi_ => $tdn_): ?>
          <div class="et_cost_row">
            <span class="et_cost_name"><?php echo htmlspecialchars($tdn_, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_cost_amount"><?php echo htmlspecialchars(earnings_fmt_money($topDriverSites_[$tdi_]['gross']), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <!-- Impact Breakdown -->
        <div class="et_cost_col">
          <h3 class="et_cost_col_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_IMPACT_BREAKDOWN', 'Impact Breakdown'), ENT_QUOTES, 'UTF-8'); ?></h3>
          <div class="et_cost_row">
            <span class="et_cost_name"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_OT_PREMIUM_EST', 'OT Premium (est.)'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_cost_amount et_cost_amount--impact">+<?php echo htmlspecialchars(earnings_fmt_money($otPremiumEst_), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <?php if ($totalLoa_ > 0): ?>
          <div class="et_cost_row">
            <span class="et_cost_name"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_LOA_ALLOWANCES', 'LOA Allowances'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_cost_amount et_cost_amount--impact">+<?php echo htmlspecialchars(earnings_fmt_money($totalLoa_), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <?php endif; ?>
          <div class="et_cost_row">
            <span class="et_cost_name"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_OT_PERCENT_OF_HOURS', 'OT as % of Hours'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_cost_amount"><?php echo htmlspecialchars(number_format($otPctAll_, 1) . '%', ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="et_cost_row">
            <span class="et_cost_name"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_IMPLIED_RATE', 'Implied Rate'), ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_cost_amount"><?php echo htmlspecialchars('$' . number_format($impliedRate_, 2) . '/hr', ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </div>
      </div>
    </figure>

    <!-- ═══════════ SITE PAYROLL COST ═══════════ -->
    <?php if (!empty($topSites_)):
      $siteN_     = min(count($topSites_), 8);
      $siteSvgH_  = $padT_ + $siteN_ * 30 + $padB_;
      $sitePadL_  = 130; $sitePadR_ = 90;
      $sitePlotW_ = $svgW_ - $sitePadL_ - $sitePadR_;
      $siteMaxG_  = max(array_map(fn($s) => (float) $s['gross'], array_values($topSites_)));
      if ($siteMaxG_ <= 0.0) { $siteMaxG_ = 1.0; }
      $siteSlice_ = array_slice(array_keys($topSites_), 0, 8);
    ?>
    <?php $_siteRows_ = array_map(fn($sn) => ['site' => $sn, 'gross' => round((float)$topSites_[$sn]['gross'], 2), 'members' => count($topSites_[$sn]['members']), 'reg_hrs' => round((float)$topSites_[$sn]['reg'], 2), 'ot_hrs' => round((float)$topSites_[$sn]['ot'], 2)], $siteSlice_); ?>
    <figure class="earnings_ytd_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_SITE_PAYROLL_COST_FOR', 'Site payroll cost for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>"
      data-team-type="sites" data-team-year="<?php echo $teamEarningsYear; ?>" data-team-org="<?php echo htmlspecialchars($selectedOrgName, ENT_QUOTES, 'UTF-8'); ?>"
      data-team-rows="<?php echo htmlspecialchars(json_encode($_siteRows_, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_SITE_PAYROLL_COST', 'Site Payroll Cost'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_SITE_PAYROLL_COST_SUBTITLE', 'YTD gross by worksite - hover for cost per hour & OT %'), ENT_QUOTES, 'UTF-8'); ?></span>
        <div class="et_export_group">
          <button type="button" class="et_export_btn" data-team-export-format="csv"><?php echo htmlspecialchars(earnings_i18n('CSV', 'CSV'), ENT_QUOTES, 'UTF-8'); ?></button>
          <button type="button" class="et_export_btn" data-team-export-format="txt"><?php echo htmlspecialchars(earnings_i18n('TXT', 'TXT'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
      </header>
      <div class="earnings_ytd_body earnings_ytd_body--rank">
        <svg viewBox="0 0 <?php echo $svgW_; ?> <?php echo $siteSvgH_; ?>"
             class="earnings_ytd_svg earnings_ytd_svg--rank" role="img" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <?php for ($sk_ = 1; $sk_ <= $ticks_; $sk_++): $sgx_ = round($sitePadL_ + ($sk_ / $ticks_) * $sitePlotW_, 1); ?>
          <line x1="<?php echo $sgx_; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $sgx_; ?>" y2="<?php echo $padT_ + $siteN_ * 30; ?>" class="ytd_grid ytd_grid--vert" />
          <?php endfor; ?>
          <line x1="<?php echo $sitePadL_; ?>" y1="<?php echo $padT_; ?>" x2="<?php echo $sitePadL_; ?>" y2="<?php echo $padT_ + $siteN_ * 30; ?>" class="ytd_axis_border" />
          <?php foreach ($siteSlice_ as $sri_ => $sn_):
            $sd_       = $topSites_[$sn_];
            $sBW_      = round(($sd_['gross'] / $siteMaxG_) * $sitePlotW_, 1);
            $sRowY_    = $padT_ + $sri_ * 30;
            $sHC_      = count($sd_['members']);
            $sTotH_    = $sd_['reg'] + $sd_['ot'];
            $sCphR_    = $sTotH_ > 0 ? round($sd_['gross'] / $sTotH_, 2) : 0.0;
            $sOtPct_   = $sTotH_ > 0 ? round(($sd_['ot'] / $sTotH_) * 100, 0) : 0;
            $sMemberLabel_ = $sHC_ === 1
              ? earnings_i18n('EARNINGS_MEMBER', 'member')
              : earnings_i18n('EARNINGS_MEMBERS', 'members');
          ?>
          <rect x="<?php echo $sitePadL_; ?>" y="<?php echo $sRowY_ + 6; ?>"
                width="<?php echo $sBW_; ?>" height="18"
                class="ytd_site_bar">
            <title><?php echo earnings_chart_hover(
              'EARNINGS_SITE_BAR_HOVER',
              '{site}: {amount} | {count} {memberLabel} | {rate}/hr | {otPct}% OT',
              [
                'site' => $sn_,
                'amount' => earnings_fmt_money((float) $sd_['gross']),
                'count' => (string) $sHC_,
                'memberLabel' => $sMemberLabel_,
                'rate' => earnings_fmt_money($sCphR_),
                'otPct' => (string) $sOtPct_,
              ],
            ); ?></title>
          </rect>
          <text x="<?php echo $sitePadL_ - 8; ?>" y="<?php echo $sRowY_ + 19; ?>"
                class="ytd_axis_label ytd_axis_label--left ytd_rank_name"><?php echo htmlspecialchars($sn_, ENT_QUOTES, 'UTF-8'); ?></text>
          <text x="<?php echo $sitePadL_ + $sBW_ + 8; ?>" y="<?php echo $sRowY_ + 19; ?>"
                class="ytd_axis_label ytd_rank_value"><?php echo htmlspecialchars('$' . number_format($sd_['gross'], 0), ENT_QUOTES, 'UTF-8'); ?></text>
          <?php endforeach; ?>
        </svg>
      </div>
    </figure>
    <?php endif; // topSites_ ?>

    <!-- ═══════════ PAYROLL COMPOSITION ═══════════ -->
    <?php if ($totalGrossAll_ > 0 && $totalHrsAll2_ > 0): ?>
    <figure class="earnings_ytd_figure et_composition_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_PAYROLL_COMPOSITION_FOR', 'Payroll composition for {org}', ['org' => $selectedOrgName]), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_PAYROLL_COMPOSITION', 'Payroll Composition'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_PAYROLL_COMPOSITION_SUBTITLE', 'Where payroll dollars actually go'), ENT_QUOTES, 'UTF-8'); ?></span>
      </header>
      <div class="et_composition_body">
        <?php
          $compItems_ = [
            ['label' => earnings_i18n('EARNINGS_REGULAR_PAY', 'Regular Pay'),  'pct' => $regPayPct_,  'amount' => max(0.0, $regPayEst_),    'cls' => 'reg'],
            ['label' => earnings_i18n('EARNINGS_OT_PREMIUM', 'OT Premium'),   'pct' => $otPremPct_,  'amount' => $otPremiumEst_,            'cls' => 'ot'],
            ['label' => earnings_i18n('LOA', 'LOA'),          'pct' => $loaPct_,     'amount' => $totalLoa_,                'cls' => 'loa'],
            ['label' => earnings_i18n('OTHER', 'Other'),        'pct' => $otherPct_,   'amount' => $totalGrossAll_ * $otherPct_ / 100, 'cls' => 'other'],
          ];
        ?>
        <!-- Bar track — SVG geometry attrs, not inline styles, so CSP-safe -->
        <svg class="et_comp_bar_svg" viewBox="0 0 100 1" preserveAspectRatio="none" role="img" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_PAYROLL_COMPOSITION_BAR_ARIA', 'Payroll composition bar'), ENT_QUOTES, 'UTF-8'); ?>">
          <?php $xOff_ = 0; foreach ($compItems_ as $ci_): if ($ci_['pct'] <= 0) { continue; } ?>
          <rect class="et_comp_bar_rect--<?php echo $ci_['cls']; ?>"
                x="<?php echo $xOff_; ?>" width="<?php echo $ci_['pct']; ?>" height="1">
            <title><?php echo earnings_chart_hover('EARNINGS_COMPOSITION_SEGMENT_HOVER', '{label}: {pct}%', ['label' => $ci_['label'], 'pct' => (string) $ci_['pct']]); ?></title>
          </rect>
          <?php $xOff_ += $ci_['pct']; endforeach; ?>
        </svg>
        <!-- Legend -->
        <ul class="et_comp_legend">
          <?php foreach ($compItems_ as $ci_): if ($ci_['pct'] <= 0) { continue; } ?>
          <li class="et_comp_legend_item">
            <span class="et_comp_swatch et_comp_swatch--<?php echo $ci_['cls']; ?>" aria-hidden="true"></span>
            <span class="et_comp_legend_label"><?php echo htmlspecialchars($ci_['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_comp_legend_pct"><?php echo $ci_['pct']; ?>%</span>
            <span class="et_comp_legend_amt"><?php echo htmlspecialchars(earnings_fmt_money($ci_['amount']), ENT_QUOTES, 'UTF-8'); ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </figure>
    <?php endif; ?>

    <!-- ═══════════ RISK REGISTER ═══════════ -->
    <?php if (!empty($riskItems_)):
      $sevIcon_  = ['critical' => '🔴', 'warning' => '🟠', 'notice' => '🟡'];
      $riskSevLabel_ = [
        'critical' => earnings_i18n('EARNINGS_CRITICAL', 'Critical'),
        'warning' => earnings_i18n('EARNINGS_WARNING', 'Warning'),
        'notice' => earnings_i18n('EARNINGS_NOTICE', 'Notice'),
      ];
    ?>
    <?php $_riskRows_ = array_map(fn($rk) => ['severity' => $rk['severity'], 'title' => $rk['title'], 'cause' => $rk['cause'], 'action' => $rk['recommendation'] ?? ''], $riskItems_); ?>
    <figure class="earnings_ytd_figure et_risk_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_RISK_REGISTER', 'Risk register'), ENT_QUOTES, 'UTF-8'); ?>"
      data-team-type="risks" data-team-year="<?php echo $teamEarningsYear; ?>" data-team-org="<?php echo htmlspecialchars($selectedOrgName, ENT_QUOTES, 'UTF-8'); ?>"
      data-team-rows="<?php echo htmlspecialchars(json_encode($_riskRows_, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_RISK_REGISTER', 'Risk register'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n_fmt('EARNINGS_RISK_REGISTER_SUBTITLE_FMT', '{count} active risk(s) requiring attention', ['count' => (string) count($riskItems_)]), ENT_QUOTES, 'UTF-8'); ?></span>
        <div class="et_export_group">
          <button type="button" class="et_export_btn" data-team-export-format="csv"><?php echo htmlspecialchars(earnings_i18n('CSV', 'CSV'), ENT_QUOTES, 'UTF-8'); ?></button>
          <button type="button" class="et_export_btn" data-team-export-format="txt"><?php echo htmlspecialchars(earnings_i18n('TXT', 'TXT'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
      </header>
      <div class="et_risk_body">
        <?php foreach ($riskItems_ as $rk_): ?>
        <div class="et_risk_item et_risk_item--<?php echo htmlspecialchars($rk_['severity'], ENT_QUOTES, 'UTF-8'); ?>">
          <span class="et_risk_icon" aria-hidden="true"><?php echo $sevIcon_[$rk_['severity']] ?? '⚪'; ?></span>
          <div class="et_risk_content">
            <div class="et_risk_title_row">
              <span class="et_risk_title"><?php echo htmlspecialchars($rk_['title'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="et_risk_sev et_risk_sev--<?php echo htmlspecialchars($rk_['severity'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($riskSevLabel_[$rk_['severity']] ?? $rk_['severity'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <p class="et_risk_cause"><?php echo htmlspecialchars($rk_['cause'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if (!empty($rk_['recommendation'])): ?>
            <p class="et_risk_rec">→ <?php echo htmlspecialchars($rk_['recommendation'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </figure>
    <?php endif; ?>

    <!-- ═══════════ RECOMMENDATIONS ═══════════ -->
    <?php $_recRows_ = array_map(fn($rec) => ['priority' => $rec['priority'], 'text' => $rec['text'], 'source' => $rec['source']], $recommendations_); ?>
    <figure class="earnings_ytd_figure et_rec_figure" aria-label="<?php echo htmlspecialchars(earnings_i18n('EARNINGS_RECOMMENDED_ACTIONS', 'Recommended actions'), ENT_QUOTES, 'UTF-8'); ?>"
      data-team-type="recommendations" data-team-year="<?php echo $teamEarningsYear; ?>" data-team-org="<?php echo htmlspecialchars($selectedOrgName, ENT_QUOTES, 'UTF-8'); ?>"
      data-team-rows="<?php echo htmlspecialchars(json_encode($_recRows_, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>">
      <header class="earnings_ytd_header">
        <span class="earnings_ytd_title"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_RECOMMENDED_ACTIONS', 'Recommended actions'), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="earnings_ytd_subtitle"><?php echo htmlspecialchars(earnings_i18n('EARNINGS_RECOMMENDED_ACTIONS_SUBTITLE', 'Auto-generated from this period\'s data'), ENT_QUOTES, 'UTF-8'); ?></span>
        <div class="et_export_group">
          <button type="button" class="et_export_btn" data-team-export-format="csv"><?php echo htmlspecialchars(earnings_i18n('CSV', 'CSV'), ENT_QUOTES, 'UTF-8'); ?></button>
          <button type="button" class="et_export_btn" data-team-export-format="txt"><?php echo htmlspecialchars(earnings_i18n('TXT', 'TXT'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
      </header>
      <ol class="et_rec_list">
        <?php foreach ($recommendations_ as $rci_ => $rec_): ?>
        <li class="et_rec_item et_rec_item--<?php echo htmlspecialchars($rec_['priority'], ENT_QUOTES, 'UTF-8'); ?>">
          <span class="et_rec_num"><?php echo $rci_ + 1; ?></span>
          <div class="et_rec_content">
            <span class="et_rec_text"><?php echo htmlspecialchars($rec_['text'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="et_rec_source"><?php echo htmlspecialchars($rec_['source'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
    </figure>

    <?php
      Lens::timeEnd('Team Earnings: panel HTML render');
    endif; // $cN >= 1 ?>

    <?php endif; ?>

  </div><!-- .earnings_team_panel -->
