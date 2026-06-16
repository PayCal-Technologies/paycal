<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\Constants\Keys;

$currentPage = 'PAGE_INDEX';

require_once 'config.php';

if (function_exists('html_index_i18n') === false) {
  function html_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

if (function_exists('calendar_scalar_string') === false) {
	function calendar_scalar_string(mixed $value): string
	{
		return is_scalar($value) ? trim((string) $value) : '';
	}
}

if (function_exists('calendar_viewable_members_for_actor') === false) {
	/**
	 * @return array<string, array{uuid: string, full_name: string, email: string}>
	 */
	function calendar_viewable_members_for_actor(string $actorUUID): array
	{
		$viewable = [];
		if ($actorUUID === '') {
			return $viewable;
		}

		$actor = UserRepository::getByUUID($actorUUID);
		if ($actor !== null) {
			$viewable[$actorUUID] = [
				'uuid' => $actorUUID,
				'full_name' => calendar_scalar_string($actor->full_name ?? ''),
				'email' => calendar_scalar_string($actor->email ?? ''),
			];
		}

		if (User::isAdmin()) {
			foreach (Database::scanKeys(Keys::USER . ':*') as $userKey) {
				$userData = Database::hgetall($userKey);
				$userUUID = calendar_scalar_string($userData['user_uuid'] ?? '');
				if ($userUUID === '') {
					continue;
				}

				$viewable[$userUUID] = [
					'uuid' => $userUUID,
					'full_name' => calendar_scalar_string($userData['full_name'] ?? ''),
					'email' => calendar_scalar_string($userData['email'] ?? ''),
				];
			}

			return $viewable;
		}

		foreach (Database::smembers(Keys::BUSINESS_USER . ':' . $actorUUID) as $orgIdRaw) {
			$orgId = calendar_scalar_string($orgIdRaw);
			if ($orgId === '') {
				continue;
			}

			$org = Database::hgetall(Keys::BUSINESS . ':' . $orgId);
			if (empty($org)) {
				continue;
			}

			$ownerUUID = calendar_scalar_string($org['owner_uuid'] ?? '');
			$actorRelationship = Database::hgetall(Keys::BUSINESS_RELATIONSHIP . ':' . $orgId . ':' . $actorUUID);
			$actorStatus = calendar_scalar_string($actorRelationship['status'] ?? '');
			$actorRole = strtolower(calendar_scalar_string($actorRelationship['role'] ?? ''));
			$isOwner = $ownerUUID !== '' && $ownerUUID === $actorUUID;
					 $isManager = $actorStatus === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $actorRole === 'coordinator';
					 if (!$isOwner && !$isManager) {
				continue;
			}

			foreach (Database::smembers(Keys::BUSINESS_MEMBERS . ':' . $orgId) as $memberUUIDRaw) {
				$memberUUID = calendar_scalar_string($memberUUIDRaw);
				if ($memberUUID === '') {
					continue;
				}

				$memberRelationship = Database::hgetall(Keys::BUSINESS_RELATIONSHIP . ':' . $orgId . ':' . $memberUUID);
				$memberStatus = calendar_scalar_string($memberRelationship['status'] ?? '');
				if ($memberStatus !== BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
					continue;
				}

				$member = UserRepository::getByUUID($memberUUID);
				if ($member === null) {
					continue;
				}

				$viewable[$memberUUID] = [
					'uuid' => $memberUUID,
					'full_name' => calendar_scalar_string($member->full_name ?? ''),
					'email' => calendar_scalar_string($member->email ?? ''),
				];
			}
		}

		return $viewable;
	}
}

if (function_exists('calendar_member_label') === false) {
	/** @param array{uuid: string, full_name: string, email: string} $member */
	function calendar_member_label(array $member): string
	{
		$name = calendar_scalar_string($member['full_name']);
		$email = calendar_scalar_string($member['email']);
		if ($name !== '' && $email !== '') {
			return $name . ' (' . $email . ')';
		}
		if ($name !== '') {
			return $name;
		}
		if ($email !== '') {
			return $email;
		}

		return calendar_scalar_string($member['uuid']);
	}
}

if (function_exists('calendar_parse_date_param') === false) {
	function calendar_parse_date_param(mixed $raw, \DateTimeZone $zone): ?\DateTimeImmutable
	{
		if (!is_scalar($raw)) {
			return null;
		}

		$value = trim((string) $raw);
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
			return null;
		}

		$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $zone);

		return ($date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value) ? $date : null;
	}
}

if (function_exists('calendar_week_start_for_date') === false) {
	function calendar_week_start_for_date(\DateTimeImmutable $date, User $user): \DateTimeImmutable
	{
		$weekStartIndex = UserPreferenceDefaults::calendarWeekStartDay($user);
		$weekDayOffset = (((int) $date->format('w')) - $weekStartIndex + 7) % 7;

		return $date->modify('-' . $weekDayOffset . ' days');
	}
}

if (function_exists('calendar_format_week_heading') === false) {
	function calendar_format_week_heading(\DateTimeImmutable $start, \DateTimeImmutable $end, ?string $locale = null): string
	{
		$startLabel = Strings::formatLocalizedMediumDate($start, $locale);
		$endLabel = Strings::formatLocalizedMediumDate($end, $locale);

		return $startLabel . ' – ' . $endLabel;
	}
}

if (function_exists('calendar_default_anchor_for_month') === false) {
	function calendar_default_anchor_for_month(string $monthYm, \DateTimeZone $zone): \DateTimeImmutable
	{
		if (!preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
			return new \DateTimeImmutable('today', $zone);
		}

		$today = new \DateTimeImmutable('today', $zone);
		if ($today->format('Y-m') === $monthYm) {
			return $today;
		}

		return new \DateTimeImmutable($monthYm . '-01', $zone);
	}
}

if (function_exists('calendar_hydrate_rows_for_dates') === false) {
	/**
	 * @param array<string, array<string, mixed>> $rowsByDate
	 * @param list<string> $dateIds
	 */
	function calendar_hydrate_rows_for_dates(array &$rowsByDate, array $dateIds, User $user): void
	{
		$missing = [];
		foreach ($dateIds as $dateId) {
			if (!isset($rowsByDate[$dateId])) {
				$missing[] = $dateId;
			}
		}

		if ($missing === []) {
			return;
		}

		sort($missing);
		$zone = new \DateTimeZone(trim((string) ($user->timezone ?? '')) ?: 'America/Edmonton');
		$start = new \DateTimeImmutable($missing[0], $zone);
		$end = new \DateTimeImmutable($missing[count($missing) - 1], $zone);
		$workByDay = [];

		foreach (Work::getWorkInRange($start, $end->modify('+1 day'), $user->user_uuid) as $data) {
			if (!is_array($data)) {
				continue;
			}

			$ymd = calendar_scalar_string($data['date'] ?? '');
			if ($ymd === '') {
				continue;
			}

			$workByDay[$ymd][] = $data;
		}

		foreach ($missing as $dateId) {
			$entries = $workByDay[$dateId] ?? [];
			$totalHours = 0.0;
			$workEntries = [];

			foreach ($entries as $work) {
				if (!is_array($work)) {
					continue;
				}

				$regular = is_numeric($work['regular_hours'] ?? $work['regular'] ?? $work['r'] ?? null) ? (float) ($work['regular_hours'] ?? $work['regular'] ?? $work['r']) : 0.0;
				$overtime = is_numeric($work['overtime_hours'] ?? $work['overtime'] ?? $work['o'] ?? null) ? (float) ($work['overtime_hours'] ?? $work['overtime'] ?? $work['o']) : 0.0;
				$totalHours += ($regular + $overtime);
				$siteIdValue = $work['site_id'] ?? $work['s'] ?? '';
				$siteNameValue = $work['site_name'] ?? $work['n'] ?? '';
				$livingOutValue = $work['living_out_allowance'] ?? $work['living_out'] ?? $work['loa'] ?? $work['l'] ?? 0;
				$travelHoursValue = $work['travel_hours'] ?? $work['travel'] ?? $work['t'] ?? 0;
				$hoursValue = $work['hours'] ?? $work['h'] ?? 0;
				$wageValue = $work['wage'] ?? $work['w'] ?? 0;
				$siteColorValue = $work['site_color'] ?? '';

				$workEntries[] = [
					'site_id' => is_scalar($siteIdValue) ? (string) $siteIdValue : '',
					'site_name' => is_scalar($siteNameValue) ? (string) $siteNameValue : '',
					'site_color' => is_scalar($siteColorValue) ? strtoupper((string) $siteColorValue) : '',
					'hours' => is_numeric($hoursValue) ? (float) $hoursValue : 0.0,
					'regular_hours' => $regular,
					'overtime_hours' => $overtime,
					'living_out_allowance' => is_numeric($livingOutValue) ? (float) $livingOutValue : 0.0,
					'travel_hours' => is_numeric($travelHoursValue) ? (float) $travelHoursValue : 0.0,
					'wage' => is_numeric($wageValue) ? (float) $wageValue : 0.0,
				];
			}

			$rowsByDate[$dateId] = [
				'id' => $dateId,
				'date' => $dateId,
				'entry_count' => count($workEntries),
				'total_hours' => number_format($totalHours, 2, '.', ''),
				'adjacent' => 0,
				'work_entries' => $workEntries,
			];
		}
	}
}

if (function_exists('calendar_recalculate_month_weeks') === false) {
	function calendar_recalculate_month_weeks(string $userUUID, string $monthYm): void
	{
		if ($userUUID === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
			return;
		}

		try {
			$firstOfMonth = new \DateTimeImmutable($monthYm . '-01');
			$firstWeekday = (int) $firstOfMonth->format('w');
			$gridStart = $firstOfMonth->modify('-' . $firstWeekday . ' days');

			// Month grid is always 42 cells (6 weeks); recalc each visible week immediately.
			for ($weekOffset = 0; $weekOffset < 6; $weekOffset++) {
				$weekAnchor = $gridStart->modify('+' . ($weekOffset * 7) . ' days');
				Work::processWorkWeekContainingDate($userUUID, $weekAnchor->format('Y-m-d'));
			}
		} catch (\Throwable $e) {
			Lens::add('Calendar week recalc skipped', ['error' => $e->getMessage()], 'recalc');
		}
	}
}

$requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/';
$requestPathRaw = parse_url($requestUri, PHP_URL_PATH);
$requestPath = is_string($requestPathRaw) ? $requestPathRaw : '/';
$normalizedRequestPath = '/' . trim($requestPath, '/');

$pathMonthParam = null;
if (preg_match('/^\/(\d{4})-(\d{2})(?:-\d{2})?$/', $normalizedRequestPath, $pathMatches)) {
	$pathMonthParam = $pathMatches[1] . '-' . $pathMatches[2];
}

$allowedRequestPaths = ['/', '/index.php'];
if (null !== $pathMonthParam) {
	$allowedRequestPaths[] = $normalizedRequestPath;
}

// Unknown rewritten paths should be hard 404s, not calendar/auth responses.
if (!in_array($normalizedRequestPath, $allowedRequestPaths, true)) {
	$notFoundPageLanguageRaw = defined('USER_LANGUAGE') ? (string) USER_LANGUAGE : Language::DEFAULT;
	$notFoundPageLanguage = htmlspecialchars(str_replace('_', '-', $notFoundPageLanguageRaw), ENT_QUOTES, 'UTF-8');
	http_response_code(404);
	header('Content-Type: text/html; charset=UTF-8');
	header('X-Robots-Tag: noindex, nofollow');
	Security::sendCoreSecurityHeaders();
	echo '<!doctype html><html lang="' . $notFoundPageLanguage . '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars((string) html_index_i18n('NOT_FOUND_404_TITLE'), ENT_QUOTES, 'UTF-8') . '</title></head><body><main><h1>' . htmlspecialchars((string) html_index_i18n('NOT_FOUND_404_TITLE'), ENT_QUOTES, 'UTF-8') . '</h1><p>' . htmlspecialchars((string) html_index_i18n('NOT_FOUND_404_BODY'), ENT_QUOTES, 'UTF-8') . '</p></main></body></html>';
	exit;
}

Authentication::redirectHomeIfUnauthenticated();
Authentication::redirectUnverifiedToVerificationPage();

$actorUUID = User::currentUUID();
$viewableMembers = calendar_viewable_members_for_actor($actorUUID);
$clearUserViewRaw = InputSanitizer::getString('clear_user_view');
$clearUserView = is_string($clearUserViewRaw) && trim($clearUserViewRaw) !== '';
$requestedCalendarUserUUID = InputSanitizer::getString('user_uuid');
$requestedCalendarUserUUID = is_string($requestedCalendarUserUUID) ? trim($requestedCalendarUserUUID) : '';
$requestedCalendarUserUUID = $clearUserView ? '' : $requestedCalendarUserUUID;
$selectedCalendarUserUUID = isset($viewableMembers[$requestedCalendarUserUUID]) ? $requestedCalendarUserUUID : $actorUUID;
$selectedCalendarUser = $viewableMembers[$selectedCalendarUserUUID] ?? ($viewableMembers[$actorUUID] ?? [
	'uuid' => $actorUUID,
	'full_name' => '',
	'email' => '',
]);
$selectedCalendarUserLabel = calendar_member_label($selectedCalendarUser);
$selectedCalendarUserModel = UserRepository::getByUUID($selectedCalendarUserUUID);
$calendarSubjectUser = $selectedCalendarUserModel ?? User::current();
$isDelegatedCalendarView = $selectedCalendarUserUUID !== '' && $selectedCalendarUserUUID !== $actorUUID;

Lens::boot('calendar-v2-grid');

$monthParam = InputSanitizer::getString('month');
if (!is_string($monthParam) || !preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
	$monthParam = null;
}

$monthParam = $monthParam ?? $pathMonthParam ?? date('Y-m');

$recalcWeekEntriesRaw = InputSanitizer::getString('recalc_week_entries');
$recalcWeekEntries = is_string($recalcWeekEntriesRaw) && trim($recalcWeekEntriesRaw) !== '';
$shouldRecalcWeekEntries = $recalcWeekEntries || $isDelegatedCalendarView;
if ($shouldRecalcWeekEntries) {
	calendar_recalculate_month_weeks($selectedCalendarUserUUID, $monthParam);
}

[$yearParam, $monthNumberParam] = explode('-', $monthParam);

$apiCandidates = [
	Environment::appURL('api/v1/calendar') . '?year=' . rawurlencode($yearParam) . '&month=' . rawurlencode($monthNumberParam) . '&user_uuid=' . rawurlencode($selectedCalendarUserUUID),
	Environment::appURL('api/calendar') . '?year=' . rawurlencode($yearParam) . '&month=' . rawurlencode($monthNumberParam) . '&user_uuid=' . rawurlencode($selectedCalendarUserUUID),
	Environment::appURL('api/v1/data/calendar/month/get') . '?month=' . rawurlencode($monthParam) . '&user_uuid=' . rawurlencode($selectedCalendarUserUUID),
	Environment::appURL('api/data/calendar/month/get') . '?month=' . rawurlencode($monthParam) . '&user_uuid=' . rawurlencode($selectedCalendarUserUUID),
];

$cookieHeader = [];
if (isset($_COOKIE['PAYCAL_AUTH']) && is_string($_COOKIE['PAYCAL_AUTH']) && '' !== $_COOKIE['PAYCAL_AUTH']) {
        $cookieHeader[] = 'PAYCAL_AUTH=' . $_COOKIE['PAYCAL_AUTH'];
}
if (isset($_COOKIE['PHPSESSID']) && is_string($_COOKIE['PHPSESSID']) && '' !== $_COOKIE['PHPSESSID']) {
	$cookieHeader[] = 'PHPSESSID=' . $_COOKIE['PHPSESSID'];
}

$httpHeaders = [
	'Accept: application/json',
];

if (!empty($cookieHeader)) {
	$httpHeaders[] = 'Cookie: ' . implode('; ', $cookieHeader);
}

$context = stream_context_create([
	'http' => [
		'method' => 'GET',
		'timeout' => 5,
		'header' => implode("\r\n", $httpHeaders),
	],
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
	],
]);

$decoded = null;

// Try API call via file_get_contents first
foreach ($apiCandidates as $apiURL) {
	$raw = @file_get_contents($apiURL, false, $context);
	if ($raw !== false) {
		$candidate = json_decode($raw, true);
		if (is_array($candidate)) {
			$decoded = $candidate;
			Lens::add('Calendar API success', ['url' => $apiURL, 'has_data' => !!$decoded], 'api_call');
			break;
		}
	}
}

// Fallback: Generate calendar data inline if API failed
if (null === $decoded) {
	try {
		Lens::add('Calendar: Generating data inline', [], 'api_fallback');
		$calendarWeekStart = UserPreferenceDefaults::calendarWeekStartDay($calendarSubjectUser);
		$calendar = Calendar::fromDate(new \DateTime("{$yearParam}-{$monthNumberParam}-01"), $calendarWeekStart, $calendarSubjectUser);
		$generator = new \PayCal\Controllers\CalendarController();
		// Use reflection to call private method generateCalendarData
		$reflector = new \ReflectionClass($generator);
		$method = $reflector->getMethod('generateCalendarData');
		$calendarData = $method->invoke($generator, $calendar, $yearParam, $monthNumberParam, $calendarSubjectUser);
		$decoded = ['success' => true, 'data' => $calendarData];
		Lens::add('Calendar: Inline generation succeeded', [], 'api_fallback');
	} catch (\Throwable $e) {
		Lens::add('Calendar: Inline generation failed', ['error' => $e->getMessage()], 'api_fallback');
	}
}

$payload = [];
if (is_array($decoded)) {
	$payload = is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded;
	$payloadDays = is_array($payload['days'] ?? null) ? $payload['days'] : [];
	Lens::add('Calendar payload received', ['has_days' => !empty($payloadDays), 'days_count' => count($payloadDays)], 'payload');
}

$cells = is_array($payload['cells'] ?? null) ? $payload['cells'] : [];
$workMap = is_array($payload['work'] ?? null) ? $payload['work'] : [];

$rows = [];
foreach ($cells as $cell) {
	if (!is_array($cell)) {
		continue;
	}

	$date = is_string($cell['d'] ?? null) ? $cell['d'] : '';
	if ('' === $date) {
		continue;
	}

	$workIDs = is_array($cell['w'] ?? null) ? $cell['w'] : [];
	$totalHours = 0.0;
	$workEntries = [];

	foreach ($workIDs as $workID) {
		if (!is_string($workID) || '' === $workID) {
			continue;
		}

		$work = $workMap[$workID] ?? null;
		if (!is_array($work)) {
			continue;
		}

		$regular = is_numeric($work['regular'] ?? null) ? (float) $work['regular'] : 0.0;
		$overtime = is_numeric($work['overtime'] ?? null) ? (float) $work['overtime'] : 0.0;
		$totalHours += ($regular + $overtime);
		$siteIdValue = $work['site_id'] ?? $work['s'] ?? '';
		$siteNameValue = $work['site_name'] ?? $work['n'] ?? '';
		$regularHoursValue = $work['regular_hours'] ?? $work['regular'] ?? $work['r'] ?? 0;
		$overtimeHoursValue = $work['overtime_hours'] ?? $work['overtime'] ?? $work['o'] ?? 0;
		$livingOutValue = $work['living_out_allowance'] ?? $work['living_out'] ?? $work['loa'] ?? $work['l'] ?? 0;
		$travelHoursValue = $work['travel_hours'] ?? $work['travel'] ?? $work['t'] ?? 0;
		$hoursValue = $work['hours'] ?? $work['h'] ?? 0;
		$wageValue = $work['wage'] ?? $work['w'] ?? 0;
		$siteColorValue = $work['site_color'] ?? '';
		
		$workEntries[] = [
			'site_id' => is_scalar($siteIdValue) ? (string) $siteIdValue : '',
			'site_name' => is_scalar($siteNameValue) ? (string) $siteNameValue : '',
			'site_color' => is_scalar($siteColorValue) ? strtoupper((string) $siteColorValue) : '',
			'hours' => is_numeric($hoursValue) ? (float) $hoursValue : 0.0,
			'regular_hours' => is_numeric($regularHoursValue) ? (float) $regularHoursValue : 0.0,
			'overtime_hours' => is_numeric($overtimeHoursValue) ? (float) $overtimeHoursValue : 0.0,
			'living_out_allowance' => is_numeric($livingOutValue) ? (float) $livingOutValue : 0.0,
			'travel_hours' => is_numeric($travelHoursValue) ? (float) $travelHoursValue : 0.0,
			'wage' => is_numeric($wageValue) ? (float) $wageValue : 0.0,
		];
	}

	$rows[] = [
			'id' => $date,
			'date' => $date,
			'entry_count' => count($workIDs),
			'total_hours' => number_format($totalHours, 2, '.', ''),
			'adjacent' => is_numeric($cell['a'] ?? null) ? (int) $cell['a'] : 0,
			'work_entries' => $workEntries,
	];
}

if (empty($rows)) {
	$days = is_array($payload['days'] ?? null) ? $payload['days'] : [];
	foreach ($days as $day) {
		if (!is_array($day)) {
			continue;
		}

				$d = $day['date'] ?? $day['id'] ?? null;
				$date = is_string($d) ? $d : '';
		if ('' === $date) {
			continue;
		}

		$workEntries = is_array($day['workEntries'] ?? null) ? $day['workEntries'] : [];
		$totalHours = is_numeric($day['totalHours'] ?? null) ? (float) $day['totalHours'] : 0.0;

		$rows[] = [
			'id' => $date,
			'date' => $date,
			'entry_count' => count($workEntries),
			'total_hours' => number_format($totalHours, 2, '.', ''),
			'adjacent' => !empty($day['isAdjacent']) ? 1 : 0,
			'work_entries' => array_map(static function ($entry) {
				if (!is_array($entry)) {
					return $entry;
				}

								$sid = $entry['site_id'] ?? $entry['s'] ?? null;
								$entry['site_id'] = is_string($sid) ? $sid : '';
								$sn = $entry['site_name'] ?? $entry['n'] ?? null;
								$entry['site_name'] = is_string($sn) ? $sn : '';
								$sc = $entry['site_color'] ?? null;
								$entry['site_color'] = is_string($sc) ? strtoupper($sc) : '';
								$rh = $entry['regular_hours'] ?? $entry['r'] ?? null;
								$entry['regular_hours'] = is_numeric($rh) ? (float) $rh : 0.0;
								$oh = $entry['overtime_hours'] ?? $entry['o'] ?? null;
								$entry['overtime_hours'] = is_numeric($oh) ? (float) $oh : 0.0;
								$loa = $entry['living_out_allowance'] ?? $entry['l'] ?? null;
								$entry['living_out_allowance'] = is_numeric($loa) ? (float) $loa : 0.0;
								$th = $entry['travel_hours'] ?? $entry['t'] ?? null;
								$entry['travel_hours'] = is_numeric($th) ? (float) $th : 0.0;
								$h = $entry['hours'] ?? $entry['h'] ?? null;
								$entry['hours'] = is_numeric($h) ? (float) $h : 0.0;
								$w = $entry['wage'] ?? $entry['w'] ?? null;
								$entry['wage'] = is_numeric($w) ? (float) $w : 0.0;

				return $entry;
		}, $workEntries),
		];
	}
}

if (!empty($rows)) {
	$rowCount = count($rows);
	for ($rowIndex = 0; $rowIndex < $rowCount; ++$rowIndex) {
		$dateId = (string) ($rows[$rowIndex]['id'] ?? '');
		if ($dateId === '') {
			continue;
		}

		$prevDateId = $rowIndex > 0 ? (string) ($rows[$rowIndex - 1]['id'] ?? '') : null;
		$nextDateId = $rowIndex < ($rowCount - 1) ? (string) ($rows[$rowIndex + 1]['id'] ?? '') : null;
		$rows[$rowIndex]['cell_extra_classes'] = CalendarCellDisplay::payPeriodClasses(
			$calendarSubjectUser,
			$dateId,
			$prevDateId !== '' ? $prevDateId : null,
			$nextDateId !== '' ? $nextDateId : null,
		);
	}
}

// Calendar display preferences belong to the signed-in actor, even when viewing
// another member's work entries.
$currentUser = User::current();

$calendarAutofocus = (string) ($currentUser->calendar_autofocus ?? 'today');
if ('current' === $calendarAutofocus) {
	$calendarAutofocus = 'today';
}
if (!in_array($calendarAutofocus, ['first', 'today', 'last'], true)) {
	$calendarAutofocus = 'today';
}

$calendarDateLabelPosition = (string) ($currentUser->calendar_date_label_position ?? 'left');
if ('center' === $calendarDateLabelPosition) {
	$calendarDateLabelPosition = 'middle';
}
if (!in_array($calendarDateLabelPosition, ['left', 'middle', 'right'], true)) {
	$calendarDateLabelPosition = 'left';
}

$calendarWorkEntryPosition = (string) ($currentUser->calendar_work_entry_position ?? 'left');
if ('center' === $calendarWorkEntryPosition) {
	$calendarWorkEntryPosition = 'middle';
}
if (!in_array($calendarWorkEntryPosition, ['left', 'middle', 'right'], true)) {
	$calendarWorkEntryPosition = 'left';
}

$calendarAudioLabelFormat = (string) ($currentUser->calendar_audio_labels ?? 'number');
if (!in_array($calendarAudioLabelFormat, ['number', 'short', 'long'], true)) {
	$calendarAudioLabelFormat = 'number';
}

$calendarDayNameFormat = (string) ($currentUser->calendar_day_name_format ?? UserPreferenceDefaults::DEFAULT_CALENDAR_DAY_NAME_FORMAT);
if (!in_array($calendarDayNameFormat, ['narrow', 'short', 'long'], true)) {
	$calendarDayNameFormat = UserPreferenceDefaults::DEFAULT_CALENDAR_DAY_NAME_FORMAT;
}
$calendarDayNamePosition = (string) ($currentUser->calendar_day_name_position ?? UserPreferenceDefaults::DEFAULT_CALENDAR_DAY_NAME_POSITION);
if ('center' === $calendarDayNamePosition) {
	$calendarDayNamePosition = 'middle';
}
if (!in_array($calendarDayNamePosition, ['left', 'middle', 'right'], true)) {
	$calendarDayNamePosition = UserPreferenceDefaults::DEFAULT_CALENDAR_DAY_NAME_POSITION;
}
$calendarDisplayLanguage = trim((string) ($currentUser->language ?? ''));
$calendarDisplayLocale = Language::resolveDateLocale(trim((string) ($currentUser->locale ?? '')), $calendarDisplayLanguage);

$calendarShowGrossBadge = !empty($currentUser->calendar_show_gross_badge);
$calendarShowNetBadge = !empty($currentUser->calendar_show_net_badge);
$calendarShowDeductionsBadge = !empty($currentUser->calendar_show_deductions_badge);
$calendarWorkEntryFields = CalendarCellDisplay::workEntryFieldPrefs($currentUser);
$calendarDefaultView = strtolower(trim((string) ($currentUser->calendar_default_view ?? UserPreferenceDefaults::DEFAULT_CALENDAR_DEFAULT_VIEW)));
if (!in_array($calendarDefaultView, ['month', 'week', 'pay_period'], true)) {
	$calendarDefaultView = UserPreferenceDefaults::DEFAULT_CALENDAR_DEFAULT_VIEW;
}
$calendarViewParam = InputSanitizer::getString('view');
if (is_string($calendarViewParam) && in_array(strtolower(trim($calendarViewParam)), ['month', 'week', 'pay_period'], true)) {
	$calendarDefaultView = strtolower(trim($calendarViewParam));
}

$pickerYears = iterator_to_array(Work::getAvailableYears($currentUser->user_uuid));
if (empty($pickerYears)) {
	$pickerYears = [(int) $yearParam];
}
if (!in_array((int) $yearParam, $pickerYears, true)) {
	$pickerYears[] = (int) $yearParam;
	rsort($pickerYears);
}

$selectedCalendarYear = (int) $yearParam;
$pickerYearMap = [];
foreach ($pickerYears as $yearValue) {
	$pickerYearMap[(int) $yearValue] = true;
}

for ($offset = 1; $offset <= 10; ++$offset) {
	$pickerYearMap[$selectedCalendarYear - $offset] = true;
}
$pickerYearMap[(int) $yearParam] = true;

$pickerYearValues = array_map('intval', array_keys($pickerYearMap));
rsort($pickerYearValues);

$yearOptions = '';
foreach ($pickerYearValues as $yearValue) {
	$escapedYear = htmlspecialchars((string) $yearValue, ENT_QUOTES, 'UTF-8');
	$selectedAttr = ((int) $yearParam === $yearValue) ? ' selected' : '';
	$yearOptions .= "<option value=\"{$escapedYear}\"{$selectedAttr}></option>";
}

$minPickerYear = min($pickerYearValues);
$maxPickerYear = max($pickerYearValues);
$selectedYearValue = htmlspecialchars((string) ((int) $yearParam), ENT_QUOTES, 'UTF-8');
$yearPickerMarkup = '<label class="date_picker_year_label visually_hidden" for="cal_year_input">' . htmlspecialchars((string) html_index_i18n('CALENDAR_YEAR_LABEL'), ENT_QUOTES, 'UTF-8') . '</label>'
	. '<input id="cal_year_input" class="date_picker_year_input" type="text" list="cal_year_options" inputmode="numeric" pattern="[0-9]{4}" '
	. 'data-min-year="' . htmlspecialchars((string) $minPickerYear, ENT_QUOTES, 'UTF-8') . '" '
	. 'data-max-year="' . htmlspecialchars((string) $maxPickerYear, ENT_QUOTES, 'UTF-8') . '" '
	. 'value="' . $selectedYearValue . '" aria-label="' . htmlspecialchars((string) html_index_i18n('CALENDAR_YEAR_LABEL'), ENT_QUOTES, 'UTF-8') . '">'
	. '<datalist id="cal_year_options">' . $yearOptions . '</datalist>';

$monthButtons = [];
$monthPickerLocale = Language::resolveDateLocale(
	trim((string) ($currentUser->locale ?? '')),
	(string) ($currentUser->language ?? Language::DEFAULT),
);
$monthFormatter = new \IntlDateFormatter($monthPickerLocale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
$monthFormatter->setPattern('MMM');
for ($monthValue = 1; $monthValue <= 12; ++$monthValue) {
	$monthPretty = (string) $monthFormatter->format((new \DateTimeImmutable(sprintf('2000-%02d-01', $monthValue)))->getTimestamp());
	if ('' === $monthPretty) {
		$monthPretty = (new \DateTimeImmutable(sprintf('2000-%02d-01', $monthValue)))->format('M');
	}

	$monthButtons[] = Render::template('calendar-menu-month-item', [
		'__MONTH_PADDED__' => str_pad((string) $monthValue, 2, '0', STR_PAD_LEFT),
		'__MONTH_PRETTY__' => strtoupper($monthPretty),
		'__SELECTED_CLASS__' => ((int) $monthNumberParam === $monthValue) ? ' cal_menu_selected' : '',
		'__ARIA_PRESSED__' => ((int) $monthNumberParam === $monthValue) ? 'true' : 'false',
		'__SELECT_MONTH_ARIA_PREFIX__' => html_index_i18n('SELECT_MONTH_ARIA_PREFIX'),
	]);
}

$datePickerDialog = Render::template('calendar-date-picker-dialog', [
	'__MODAL_ARIA__' => html_index_i18n('OPEN_DATE_PICKER_WITH'),
	'__MODAL_META__' => html_index_i18n('DATE_PICKER'),
	'__MODAL_TITLE__' => html_index_i18n('DATE_PICKER'),
	'__CAL_MENU_YEARS__' => $yearPickerMarkup,
	'__CAL_MENU_MONTHS__' => implode('', $monthButtons),
	'__GO__' => html_index_i18n('GO'),
	'__CLOSE__' => html_index_i18n('CLOSE'),
	'__CANCEL__' => html_index_i18n('CANCEL'),
	'__DATE_PICKER_ACTIONS_ARIA__' => html_index_i18n('DATE_PICKER_ACTIONS_ARIA'),
	'__YEAR_LOWER__' => html_index_i18n('YEAR_LOWER'),
	'__ARROWS__' => html_index_i18n('ARROWS'),
	'__MONTHS_LOWER__' => html_index_i18n('MONTHS_LOWER'),
	'__ENTER_KEY__' => html_index_i18n('ENTER_KEY'),
	'__VIEW_LOWER__' => html_index_i18n('VIEW_LOWER'),
]);

$calendarMonthContext = Strings::formatLocalizedMonthYear((int) $yearParam, (int) $monthNumberParam);

$calendarLockBoundary = is_scalar($payload['lockBoundary'] ?? null) ? (string) $payload['lockBoundary'] : '';

$rowsByDate = [];
foreach ($rows as $row) {
	$dateKey = (string) ($row['id'] ?? '');
	if ($dateKey !== '') {
		$rowsByDate[$dateKey] = $row;
	}
}

$calendarRowForDate = static function (string $dateId, ?string $prevDateId, ?string $nextDateId) use ($rowsByDate, $calendarSubjectUser): array {
	if (isset($rowsByDate[$dateId])) {
		$row = $rowsByDate[$dateId];
		if (!isset($row['cell_extra_classes'])) {
			$row['cell_extra_classes'] = CalendarCellDisplay::payPeriodClasses($calendarSubjectUser, $dateId, $prevDateId, $nextDateId);
		}

		return $row;
	}

	return [
		'id' => $dateId,
		'date' => $dateId,
		'entry_count' => 0,
		'total_hours' => '0.00',
		'adjacent' => 0,
		'work_entries' => [],
		'cell_extra_classes' => CalendarCellDisplay::payPeriodClasses($calendarSubjectUser, $dateId, $prevDateId, $nextDateId),
	];
};

$calendarUserTimezone = new \DateTimeZone(trim((string) ($calendarSubjectUser->timezone ?? '')) ?: 'America/Edmonton');
$calendarUserLocale = trim((string) ($calendarSubjectUser->language ?? '')) ?: null;
$calendarMonthAnchorDate = calendar_default_anchor_for_month($monthParam, $calendarUserTimezone);
$weekStartParam = calendar_parse_date_param(InputSanitizer::getString('week_start'), $calendarUserTimezone);
$payPeriodStartParam = calendar_parse_date_param(InputSanitizer::getString('pay_period_start'), $calendarUserTimezone);
$weekAnchorDate = $weekStartParam
	?? calendar_parse_date_param(InputSanitizer::getString('week'), $calendarUserTimezone)
	?? $calendarMonthAnchorDate;
$payPeriodAnchorDate = $payPeriodStartParam
	?? calendar_parse_date_param(InputSanitizer::getString('pay_period'), $calendarUserTimezone)
	?? $calendarMonthAnchorDate;
$weekStartDate = calendar_week_start_for_date($weekAnchorDate, $calendarSubjectUser);
$prevWeekStartDate = $weekStartDate->modify('-7 days');
$nextWeekStartDate = $weekStartDate->modify('+7 days');
$weekDateIds = [];
for ($weekIndex = 0; $weekIndex < 7; ++$weekIndex) {
	$weekDateIds[] = $weekStartDate->modify('+' . $weekIndex . ' days')->format('Y-m-d');
}

$weekRows = [];
$payPeriodRows = [];
$payPeriodHeading = (string) html_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_PAY_PERIOD');
$payPeriodPickerOptions = [];
$resolvedPayPeriod = PayPeriodGenerator::resolveForDateOrCompute($calendarSubjectUser, $payPeriodAnchorDate);
$payPeriodInfo = $resolvedPayPeriod->getPayPeriodForDate($payPeriodAnchorDate);
$payPeriodHeading = (string) ($payPeriodInfo['label_short'] ?? $payPeriodInfo['label_full'] ?? $payPeriodHeading);
$periodStart = $payPeriodInfo['start'];
$periodEnd = $payPeriodInfo['end'];
$payPeriodStartDate = $periodStart;
$payPeriodAnchorDate = $payPeriodStartDate;
$prevPayPeriodStartDate = $resolvedPayPeriod->previous()->start();
$nextPayPeriodStartDate = $resolvedPayPeriod->next()->start();
$payPeriodDateIds = [];
for ($cursor = $periodStart; $cursor <= $periodEnd; $cursor = $cursor->modify('+1 day')) {
	$payPeriodDateIds[] = $cursor->format('Y-m-d');
}

calendar_hydrate_rows_for_dates($rowsByDate, $weekDateIds, $calendarSubjectUser);

foreach ($weekDateIds as $weekIndex => $weekDateId) {
	$prevWeekDateId = $weekIndex > 0 ? $weekDateIds[$weekIndex - 1] : null;
	$nextWeekDateId = $weekIndex < 6 ? $weekDateIds[$weekIndex + 1] : null;
	$weekRows[] = $calendarRowForDate($weekDateId, $prevWeekDateId, $nextWeekDateId);
}

$weekHeadingStart = new \DateTimeImmutable($weekDateIds[0], $calendarUserTimezone);
$weekHeadingEnd = new \DateTimeImmutable($weekDateIds[6], $calendarUserTimezone);
$weekHeading = calendar_format_week_heading($weekHeadingStart, $weekHeadingEnd, $calendarUserLocale);

calendar_hydrate_rows_for_dates($rowsByDate, $payPeriodDateIds, $calendarSubjectUser);

$payPeriodCount = count($payPeriodDateIds);
foreach ($payPeriodDateIds as $payPeriodIndex => $payPeriodDateId) {
	$prevPayPeriodDateId = $payPeriodIndex > 0 ? $payPeriodDateIds[$payPeriodIndex - 1] : null;
	$nextPayPeriodDateId = $payPeriodIndex < ($payPeriodCount - 1) ? $payPeriodDateIds[$payPeriodIndex + 1] : null;
	$payPeriodRows[] = $calendarRowForDate($payPeriodDateId, $prevPayPeriodDateId, $nextPayPeriodDateId);
}

$payPeriodPickerCursor = $resolvedPayPeriod;
for ($pickerStep = 0; $pickerStep < 8; ++$pickerStep) {
	$payPeriodPickerCursor = $payPeriodPickerCursor->previous();
}
for ($pickerStep = 0; $pickerStep < 17; ++$pickerStep) {
	$pickerStart = $payPeriodPickerCursor->start();
	$pickerInfo = $payPeriodPickerCursor->getPayPeriodForDate($pickerStart);
	$pickerAnchor = $pickerStart->format('Y-m-d');
	$payPeriodPickerOptions[] = [
		'anchor' => $pickerAnchor,
		'label' => (string) ($pickerInfo['label_short'] ?? $pickerInfo['label_full'] ?? $pickerAnchor),
		'selected' => $pickerAnchor === $payPeriodStartDate->format('Y-m-d'),
	];
	$payPeriodPickerCursor = $payPeriodPickerCursor->next();
}

$payPeriodPickerMarkup = '';
foreach ($payPeriodPickerOptions as $payPeriodPickerOption) {
	$optionAnchor = htmlspecialchars((string) ($payPeriodPickerOption['anchor'] ?? ''), ENT_QUOTES, 'UTF-8');
	$optionLabel = htmlspecialchars((string) ($payPeriodPickerOption['label'] ?? ''), ENT_QUOTES, 'UTF-8');
	$optionSelected = !empty($payPeriodPickerOption['selected']);
	$payPeriodPickerMarkup .= '<button type="button" class="calendar_payperiod_picker_option'
		. ($optionSelected ? ' cal_menu_selected' : '')
		. '" data-pay-period-start="' . $optionAnchor . '" role="option" aria-selected="'
		. ($optionSelected ? 'true' : 'false') . '">' . $optionLabel . '</button>';
}

$weekPickerMinDate = min($pickerYearValues) . '-01-01';
$weekPickerMaxDate = max($pickerYearValues) . '-12-31';
$weekPickerDialog = Render::template('calendar-week-picker-dialog', [
	'__MODAL_ARIA__' => html_index_i18n('CALENDAR_WEEK_PICKER_ARIA'),
	'__MODAL_META__' => html_index_i18n('CALENDAR_WEEK_PICKER_TITLE'),
	'__MODAL_TITLE__' => html_index_i18n('CALENDAR_WEEK_PICKER_TITLE'),
	'__DATE_LABEL__' => html_index_i18n('CALENDAR_WEEK_PICKER_DATE_LABEL'),
	'__CURRENT_DATE__' => htmlspecialchars($weekAnchorDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'),
	'__MIN_DATE__' => htmlspecialchars($weekPickerMinDate, ENT_QUOTES, 'UTF-8'),
	'__MAX_DATE__' => htmlspecialchars($weekPickerMaxDate, ENT_QUOTES, 'UTF-8'),
	'__GO__' => html_index_i18n('GO'),
	'__CLOSE__' => html_index_i18n('CLOSE'),
	'__DATE_PICKER_ACTIONS_ARIA__' => html_index_i18n('DATE_PICKER_ACTIONS_ARIA'),
]);
$payPeriodPickerDialog = Render::template('calendar-payperiod-picker-dialog', [
	'__MODAL_ARIA__' => html_index_i18n('CALENDAR_PAY_PERIOD_PICKER_ARIA'),
	'__MODAL_META__' => html_index_i18n('CALENDAR_PAY_PERIOD_PICKER_TITLE'),
	'__MODAL_TITLE__' => html_index_i18n('CALENDAR_PAY_PERIOD_PICKER_TITLE'),
	'__PAYPERIOD_OPTIONS__' => $payPeriodPickerMarkup,
	'__CLOSE__' => html_index_i18n('CLOSE'),
	'__DATE_PICKER_ACTIONS_ARIA__' => html_index_i18n('DATE_PICKER_ACTIONS_ARIA'),
]);

$calendarGridColumns = [
	['key' => 'date', 'label' => html_index_i18n('CALENDAR_COL_DATE'), 'sortable' => true, 'compute' => function($row, $col) {
		$date = $row[$col['key']] ?? '';
		if ('' === $date) return '';
		try {
			$dt = new \DateTime($date);
			return $dt->format('M d, Y');
		} catch (\Exception $e) {
			return $date;
		}
	}],
	['key' => 'entry_count', 'label' => html_index_i18n('CALENDAR_COL_ENTRIES'), 'sortable' => true],
	['key' => 'total_hours', 'label' => html_index_i18n('CALENDAR_COL_TOTAL_HOURS'), 'sortable' => true, 'compute' => function($row, $col) {
		$hours = (float) ($row[$col['key']] ?? 0);
		return Strings::formatLocalizedNumber($hours, 2, 2);
	}],
	['key' => 'adjacent', 'label' => html_index_i18n('CALENDAR_COL_ADJACENT'), 'sortable' => true, 'compute' => function($row, $col) {
		$adjacent = (int) ($row[$col['key']] ?? 0);
		return 1 === $adjacent ? html_index_i18n('CALENDAR_ADJACENT_YES') : html_index_i18n('CALENDAR_ADJACENT_NO');
	}],
];

$calendarSharedMeta = [
	'layout' => 'month',
	'year' => (int) $yearParam,
	'month' => (int) $monthNumberParam,
	'searchEnabled' => false,
	'rowActions' => [],
	'language' => $calendarDisplayLanguage,
	'locale' => $calendarDisplayLocale,
	'dateLabelPosition' => $calendarDateLabelPosition,
	'workEntryPosition' => $calendarWorkEntryPosition,
	'workEntryFields' => $calendarWorkEntryFields,
	'dateAriaFormat' => $calendarAudioLabelFormat,
	'dayNameFormat' => $calendarDayNameFormat,
	'dayNamePosition' => $calendarDayNamePosition,
	'autofocus' => $calendarAutofocus,
	'lockBoundary' => $calendarLockBoundary,
];

$grid = new DataGrid([
		'id' => 'calendar-grid',
		'columns' => $calendarGridColumns,
		'rows' => $rows,
		'meta' => array_merge($calendarSharedMeta, [
				'descriptionId' => 'calendar-grid-instructions calendar-grid-context calendar-month-status',
				'suppressMonthNavigation' => true,
		]),
]);

$weekGrid = new DataGrid([
		'id' => 'calendar-week-grid',
		'columns' => $calendarGridColumns,
		'rows' => $weekRows,
		'meta' => array_merge($calendarSharedMeta, [
				'descriptionId' => 'calendar-grid-instructions calendar-grid-context',
				'suppressMonthNavigation' => true,
		]),
]);

$payPeriodGrid = new DataGrid([
		'id' => 'calendar-payperiod-grid',
		'columns' => $calendarGridColumns,
		'rows' => $payPeriodRows,
		'meta' => array_merge($calendarSharedMeta, [
				'descriptionId' => 'calendar-grid-instructions calendar-grid-context',
				'suppressMonthNavigation' => true,
		]),
]);


$message = '&nbsp;';
$pageTitle = (string) html_index_i18n('CALENDAR') . ' - [PayCal]';
$pageLabel = html_index_i18n('CALENDAR');
$pageLanguage = User::current()->language ?? 'en';
$isEmailVerified = User::current()->email_verified ?? false;

require_once Environment::appHome().'html/header.php';
?>

<section
	id="calendar-v2-root"
	class="panel w100 calendar_full_bleed"
	role="application"
	aria-labelledby="calendar-landmark-title"
	aria-describedby="calendar-grid-instructions calendar-grid-context calendar-month-status"
	data-email-verified="<?php echo $isEmailVerified ? '1' : '0'; ?>"
	data-calendar-actor-uuid="<?php echo htmlspecialchars($actorUUID, ENT_QUOTES, 'UTF-8'); ?>"
	data-calendar-user-uuid="<?php echo htmlspecialchars($selectedCalendarUserUUID, ENT_QUOTES, 'UTF-8'); ?>"
	data-show-gross-badge="<?php echo $calendarShowGrossBadge ? '1' : '0'; ?>"
	data-show-net-badge="<?php echo $calendarShowNetBadge ? '1' : '0'; ?>"
	data-show-deductions-badge="<?php echo $calendarShowDeductionsBadge ? '1' : '0'; ?>"
	data-work-entry-hours="<?php echo !empty($calendarWorkEntryFields['hours']) ? '1' : '0'; ?>"
	data-work-entry-regular="<?php echo !empty($calendarWorkEntryFields['regular']) ? '1' : '0'; ?>"
	data-work-entry-overtime="<?php echo !empty($calendarWorkEntryFields['overtime']) ? '1' : '0'; ?>"
	data-work-entry-living-out="<?php echo !empty($calendarWorkEntryFields['living_out']) ? '1' : '0'; ?>"
	data-work-entry-travel="<?php echo !empty($calendarWorkEntryFields['travel']) ? '1' : '0'; ?>"
	data-default-view="<?php echo htmlspecialchars($calendarDefaultView, ENT_QUOTES, 'UTF-8'); ?>"
	data-active-view="<?php echo htmlspecialchars($calendarDefaultView, ENT_QUOTES, 'UTF-8'); ?>"
>
	<h1 id="calendar-landmark-title" class="visually_hidden"><?php echo html_index_i18n('CALENDAR'); ?></h1>
	<p id="calendar-grid-instructions" class="visually_hidden"><?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_GRID_INSTRUCTIONS'), ENT_QUOTES, 'UTF-8'); ?></p>
	<p id="calendar-grid-context" class="visually_hidden"><?php echo htmlspecialchars($calendarMonthContext, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_GRID_CONTEXT_SUFFIX'), ENT_QUOTES, 'UTF-8'); ?></p>
	<p id="calendar-month-status" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></p>

	<div class="calendar_control_strip" role="toolbar" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_VIEW_MODE_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
		<div class="radio_group pill_group calendar_view_pills" role="radiogroup" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_VIEW_MODE_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
			<input type="radio" class="radio" id="calendar_view_mode_month" name="calendar_view_mode" value="month"<?php echo $calendarDefaultView === 'month' ? ' checked' : ''; ?>>
			<label for="calendar_view_mode_month"><?php echo htmlspecialchars((string) html_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_MONTH'), ENT_QUOTES, 'UTF-8'); ?></label>
			<input type="radio" class="radio" id="calendar_view_mode_week" name="calendar_view_mode" value="week"<?php echo $calendarDefaultView === 'week' ? ' checked' : ''; ?>>
			<label for="calendar_view_mode_week"><?php echo htmlspecialchars((string) html_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_WEEK'), ENT_QUOTES, 'UTF-8'); ?></label>
			<input type="radio" class="radio" id="calendar_view_mode_pay_period" name="calendar_view_mode" value="pay_period"<?php echo $calendarDefaultView === 'pay_period' ? ' checked' : ''; ?>>
			<label for="calendar_view_mode_pay_period"><?php echo htmlspecialchars((string) html_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_PAY_PERIOD'), ENT_QUOTES, 'UTF-8'); ?></label>
		</div>

		<div class="calendar_range_controls" data-calendar-range-controls="month"<?php echo $calendarDefaultView === 'month' ? '' : ' hidden'; ?>>
			<button
				type="button"
				id="cal_picker_button"
				class="calendar_range_picker"
				data-action="open-month-picker"
				data-year="<?php echo htmlspecialchars((string) $yearParam, ENT_QUOTES, 'UTF-8'); ?>"
				data-month="<?php echo htmlspecialchars((string) $monthNumberParam, ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars($calendarMonthContext, ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="ALT+\\"
				accesskey="\\"
			><?php echo htmlspecialchars($calendarMonthContext, ENT_QUOTES, 'UTF-8'); ?></button>
			<button
				type="button"
				class="calendar_range_button"
				data-action="prev-month"
				data-month="<?php echo htmlspecialchars((string) (((int) $monthNumberParam) === 1 ? 12 : ((int) $monthNumberParam) - 1), ENT_QUOTES, 'UTF-8'); ?>"
				data-year="<?php echo htmlspecialchars((string) (((int) $monthNumberParam) === 1 ? ((int) $yearParam) - 1 : (int) $yearParam), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars((string) html_index_i18n('DATAGRID_PREVIOUS_MONTH_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="[ PageUp"
				accesskey="["
			><span aria-hidden="true">&lt;</span></button>
			<button
				type="button"
				class="calendar_range_button"
				data-action="next-month"
				data-month="<?php echo htmlspecialchars((string) (((int) $monthNumberParam) === 12 ? 1 : ((int) $monthNumberParam) + 1), ENT_QUOTES, 'UTF-8'); ?>"
				data-year="<?php echo htmlspecialchars((string) (((int) $monthNumberParam) === 12 ? ((int) $yearParam) + 1 : (int) $yearParam), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars((string) html_index_i18n('DATAGRID_NEXT_MONTH_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="] PageDown"
				accesskey="]"
			><span aria-hidden="true">&gt;</span></button>
		</div>

		<div class="calendar_range_controls" data-calendar-range-controls="week"<?php echo $calendarDefaultView === 'week' ? '' : ' hidden'; ?>>
			<button
				type="button"
				id="cal_week_picker_button"
				class="calendar_range_picker"
				data-action="open-week-picker"
				data-anchor="<?php echo htmlspecialchars($weekStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars($weekHeading, ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="ALT+\\"
				accesskey="\\"
			><?php echo htmlspecialchars($weekHeading, ENT_QUOTES, 'UTF-8'); ?></button>
			<button
				type="button"
				class="calendar_range_button"
				data-action="prev-week"
				data-anchor="<?php echo htmlspecialchars($prevWeekStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_PREVIOUS_WEEK_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="[ PageUp"
				accesskey="["
			><span aria-hidden="true">&lt;</span></button>
			<button
				type="button"
				class="calendar_range_button"
				data-action="next-week"
				data-anchor="<?php echo htmlspecialchars($nextWeekStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_NEXT_WEEK_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="] PageDown"
				accesskey="]"
			><span aria-hidden="true">&gt;</span></button>
		</div>

		<div class="calendar_range_controls" data-calendar-range-controls="pay_period"<?php echo $calendarDefaultView === 'pay_period' ? '' : ' hidden'; ?>>
			<button
				type="button"
				id="cal_payperiod_picker_button"
				class="calendar_range_picker"
				data-action="open-pay-period-picker"
				data-anchor="<?php echo htmlspecialchars($payPeriodStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars($payPeriodHeading, ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="ALT+\\"
				accesskey="\\"
			><?php echo htmlspecialchars($payPeriodHeading, ENT_QUOTES, 'UTF-8'); ?></button>
			<button
				type="button"
				class="calendar_range_button"
				data-action="prev-pay-period"
				data-anchor="<?php echo htmlspecialchars($prevPayPeriodStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_PREVIOUS_PAY_PERIOD_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="[ PageUp"
				accesskey="["
			><span aria-hidden="true">&lt;</span></button>
			<button
				type="button"
				class="calendar_range_button"
				data-action="next-pay-period"
				data-anchor="<?php echo htmlspecialchars($nextPayPeriodStartDate->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_NEXT_PAY_PERIOD_ARIA'), ENT_QUOTES, 'UTF-8'); ?>"
				aria-keyshortcuts="] PageDown"
				accesskey="]"
			><span aria-hidden="true">&gt;</span></button>
		</div>
	</div>

	<div id="calendar-view-month" class="calendar_view_panel<?php echo $calendarDefaultView === 'month' ? '' : ' hidden'; ?>" data-calendar-view="month"<?php echo $calendarDefaultView === 'month' ? '' : ' aria-hidden="true"'; ?>>
		<?php echo $grid->table(); ?>
	</div>
	<div id="calendar-view-week" class="calendar_view_panel<?php echo $calendarDefaultView === 'week' ? '' : ' hidden'; ?>" data-calendar-view="week"<?php echo $calendarDefaultView === 'week' ? '' : ' aria-hidden="true"'; ?>>
		<?php echo $weekGrid->table(); ?>
	</div>
	<div id="calendar-view-pay-period" class="calendar_view_panel<?php echo $calendarDefaultView === 'pay_period' ? '' : ' hidden'; ?>" data-calendar-view="pay_period"<?php echo $calendarDefaultView === 'pay_period' ? '' : ' aria-hidden="true"'; ?>>
		<?php echo $payPeriodGrid->table(); ?>
	</div>
</section>

<?php echo $datePickerDialog; ?>
<?php echo $weekPickerDialog; ?>
<?php echo $payPeriodPickerDialog; ?>

<div id="calendar_day_context_menu" class="hidden" role="menu" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_DAY_MENU_ARIA'), ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1">
	<div id="calendar_day_context_menu_head" class="centered" aria-hidden="true"></div>
	<svg class="visually_hidden" aria-hidden="true" focusable="false" width="0" height="0">
		<defs>
			<symbol id="mod-mac" viewBox="0 0 16 16">
				<path d="M6.2 2.2C6.2 3.525 5.125 4.6 3.8 4.6C2.475 4.6 1.4 5.675 1.4 7C1.4 8.325 2.475 9.4 3.8 9.4C5.125 9.4 6.2 10.475 6.2 11.8C6.2 13.125 7.275 14.2 8.6 14.2C9.925 14.2 11 13.125 11 11.8C11 10.475 12.075 9.4 13.4 9.4C14.725 9.4 15.8 8.325 15.8 7C15.8 5.675 14.725 4.6 13.4 4.6C12.075 4.6 11 3.525 11 2.2C11 0.875 9.925 -0.2 8.6 -0.2C7.275 -0.2 6.2 0.875 6.2 2.2ZM8.6 5.2C7.605 5.2 6.8 6.005 6.8 7C6.8 7.995 7.605 8.8 8.6 8.8C9.595 8.8 10.4 7.995 10.4 7C10.4 6.005 9.595 5.2 8.6 5.2Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
			</symbol>
			<symbol id="mod-win" viewBox="0 0 20 16">
				<rect x="1.25" y="1.25" width="17.5" height="13.5" rx="3" ry="3" fill="none" stroke="currentColor" stroke-width="1.5"></rect>
				<path d="M6 8h8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
				<path d="M9 5l-3 3 3 3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
			</symbol>
		</defs>
	</svg>
	<ul role="none" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_DAY_ACTIONS_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
		<li role="none"><button type="button" tabindex="-1" data-action="copy" role="menuitem"><span><?php echo html_index_i18n('COPY'); ?></span><kbd class="calendar_shortcut" data-shortcut-modifier="primary" data-shortcut-key="C"><span class="calendar_shortcut_mod" aria-hidden="true"><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_mac" focusable="false"><use href="#mod-mac"></use></svg><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_win" focusable="false"><use href="#mod-win"></use></svg></span><span class="calendar_shortcut_sep" aria-hidden="true">+</span><span class="calendar_shortcut_key">C</span></kbd></button></li>
		<li role="none"><button type="button" tabindex="-1" data-action="paste" role="menuitem"><span><?php echo html_index_i18n('PASTE'); ?></span><kbd class="calendar_shortcut" data-shortcut-modifier="primary" data-shortcut-key="V"><span class="calendar_shortcut_mod" aria-hidden="true"><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_mac" focusable="false"><use href="#mod-mac"></use></svg><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_win" focusable="false"><use href="#mod-win"></use></svg></span><span class="calendar_shortcut_sep" aria-hidden="true">+</span><span class="calendar_shortcut_key">V</span></kbd></button></li>
		<li role="none"><button type="button" tabindex="-1" data-action="open" role="menuitem"><span><?php echo html_index_i18n('OPEN'); ?></span><kbd class="calendar_shortcut" data-shortcut-key="Enter" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('ENTER_KEY'), ENT_QUOTES, 'UTF-8'); ?>"><span class="calendar_shortcut_key" aria-hidden="true">↵</span></kbd></button></li>
		<li role="none"><button type="button" tabindex="-1" data-action="delete" role="menuitem"><span><?php echo html_index_i18n('DELETE'); ?></span><kbd class="calendar_shortcut" data-shortcut-key="Delete"><span class="calendar_shortcut_key"><?php echo html_index_i18n('DELETE_KEY'); ?></span></kbd></button></li>
	</ul>
</div>

<!-- Calendar Entry Modal Dialog -->
	<dialog id="calendar-modal" class="calendar_modal" data-dialog-close-on-backdrop="true" aria-modal="true" aria-labelledby="calendar-modal-date" aria-describedby="calendar-modal-desc">
		<p id="calendar-modal-desc" class="visually_hidden"><?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_MODAL_DESC'), ENT_QUOTES, 'UTF-8'); ?></p>
		<section class="modal_header calendar_modal_header">
			<h2 id="calendar-modal-date"><?php echo htmlspecialchars((string) html_index_i18n('DATE'), ENT_QUOTES, 'UTF-8'); ?></h2>
			<button type="button" class="btn btn_close calendar_modal_close" data-dialog-close="calendar-modal" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CLOSE'), ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
		</section>
	<section class="modal_content calendar_modal_body">
		<div id="calendar-modal-content"><?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_MODAL_EMPTY'), ENT_QUOTES, 'UTF-8'); ?></div>
	</section>
	<section class="modal_footer calendar_modal_footer">
		<button type="button" class="btn btn_primary calendar_modal_action calendar_modal_action_save" data-action="save"><?php echo htmlspecialchars((string) html_index_i18n('SAVE'), ENT_QUOTES, 'UTF-8'); ?></button>
		<button type="button" class="btn btn_cancel calendar_modal_action calendar_modal_action_close" data-dialog-close="calendar-modal"><?php echo htmlspecialchars((string) html_index_i18n('CLOSE'), ENT_QUOTES, 'UTF-8'); ?></button>
	</section>
	</dialog>

<?php
// Load core module for PayCalCore global functions
echo Render::jsScript('core');

$cacheVersion = Environment::appVersion();
if ($cacheVersion === '' || $cacheVersion === 'unknown') {
	$calendarJsPath = Environment::appHome() . 'html/js/calendar/calendar.js';
	$workerJsPath = Environment::appHome() . 'html/js/calendar/crypto-worker.js';
	$calendarMtime = file_exists($calendarJsPath) ? (string) filemtime($calendarJsPath) : (string) time();
	$workerMtime = file_exists($workerJsPath) ? (string) filemtime($workerJsPath) : (string) time();
	$cacheVersion = 'dev-' . $calendarMtime . '-' . $workerMtime;
}
$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = (is_string($cspNonceRaw) && $cspNonceRaw !== '') ? $cspNonceRaw : User::nonce();

	$calendarPageI18nKeys = [
		'CALENDAR_MODAL_ADD',
		'CALENDAR_MODAL_ADD_ENTRY',
	'CALENDAR_MODAL_DESC',
	'CALENDAR_MODAL_EMPTY',
	'CALENDAR_MODAL_SELECT_SITE',
	'DELETE',
	'SAVE',
	'CLOSE',
	'I_WORK_DETAILS',
	'VIEW',
	'DATE_PICKER',
	'GROSS',
	'DEDUCTIONS',
	'NET',
	'CALENDAR_DAYS',
	'CALENDAR_DAY_SINGULAR',
	'CALENDAR_LOCKED_UNVERIFIED',
	'CALENDAR_LOCKED_CANNOT_EDIT',
	'CALENDAR_LOCKED_CANNOT_EDIT_GRACE',
	'CALENDAR_UNLOCK_REQUIRED_EDIT',
	'CALENDAR_WEB_AUTHN_UNSUPPORTED',
	'CALENDAR_EMAIL_VERIFICATION_REQUIRED',
	'CALENDAR_UNLOCK_REQUIRED_SAVE',
	'CALENDAR_ENCRYPTION_REQUIRED',
	'CALENDAR_WORK_ENTRY_LABEL',
	'CALENDAR_ENCRYPTED_DETAILS_UNAVAILABLE',
	'CALENDAR_NO_ENTRIES_TO_COPY_ON',
	'CALENDAR_COPIED_ENTRIES_FROM',
	'CALENDAR_CLIPBOARD_EMPTY_FOR',
	'CALENDAR_PASTING_TO',
	'CALENDAR_PASTED_ENTRIES_TO',
	'CALENDAR_PASTE_FAILED_FOR',
	'CALENDAR_DELETING_FOR',
	'CALENDAR_ENTRIES_DELETED_FOR',
	'CALENDAR_DELETE_FAILED_FOR',
	'CALENDAR_UNKNOWN_ERROR',
	'CALENDAR_MONTH_UPDATED_TO',
	'CALENDAR_SAVING_ELLIPSIS',
	'CALENDAR_SAVING_ENTRIES_FOR',
	'CALENDAR_SAVED_ENTRIES_FOR',
	'CALENDAR_SAVE_FAILED_FOR',
	'CALENDAR_SAVE_FAILED_SHORT',
	'DATAGRID_PREVIOUS_MONTH_ARIA',
	'DATAGRID_NEXT_MONTH_ARIA',
	'CALENDAR_PREVIOUS_WEEK_ARIA',
	'CALENDAR_NEXT_WEEK_ARIA',
	'CALENDAR_WEEK_PICKER_TITLE',
	'CALENDAR_PREVIOUS_PAY_PERIOD_ARIA',
	'CALENDAR_NEXT_PAY_PERIOD_ARIA',
	'CALENDAR_PAY_PERIOD_PICKER_TITLE',
];
$calendarPageI18n = [];
foreach ($calendarPageI18nKeys as $calendarPageI18nKey) {
	$calendarPageI18n[$calendarPageI18nKey] = html_index_i18n($calendarPageI18nKey);
}
echo '    <script type="application/json" id="calendar-page-i18n" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '">'
	. json_encode($calendarPageI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
	. '</script>' . PHP_EOL;

echo '    <script type="module" src="' . Environment::appURL('js/core/binary-codec.js') . '?v=' . htmlspecialchars($cacheVersion, ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"></script>' . PHP_EOL;
echo '    <script type="module" src="' . Environment::appURL('js/core/set-utils.js') . '?v=' . htmlspecialchars($cacheVersion, ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"></script>' . PHP_EOL;

// Load monolithic calendar.js directly (not the PHP-backed folder which includes PhantomWing)
$calendarSriAttribute = Environment::appEnv() === 'prod'
	? Render::sriAttribute('js/calendar/calendar.js')
	: '';
echo '    <script src="' . Environment::appURL('js/calendar/calendar.js') . '?v=' . htmlspecialchars($cacheVersion, ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' . $calendarSriAttribute . '></script>' . PHP_EOL;

require_once Environment::appHome().'html/footer.php';
