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

		foreach (Database::smembers(Keys::ORGANIZATION_USER . ':' . $actorUUID) as $orgIdRaw) {
			$orgId = calendar_scalar_string($orgIdRaw);
			if ($orgId === '') {
				continue;
			}

			$org = Database::hgetall(Keys::ORGANIZATION . ':' . $orgId);
			if (empty($org)) {
				continue;
			}

			$ownerUUID = calendar_scalar_string($org['owner_uuid'] ?? '');
			$actorRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $actorUUID);
			$actorStatus = calendar_scalar_string($actorRelationship['status'] ?? '');
			$actorRole = strtolower(calendar_scalar_string($actorRelationship['role'] ?? ''));
			$isOwner = $ownerUUID !== '' && $ownerUUID === $actorUUID;
					 $isManager = $actorStatus === OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $actorRole === 'coordinator';
					 if (!$isOwner && !$isManager) {
				continue;
			}

			foreach (Database::smembers(Keys::ORGANIZATION_MEMBERS . ':' . $orgId) as $memberUUIDRaw) {
				$memberUUID = calendar_scalar_string($memberUUIDRaw);
				if ($memberUUID === '') {
					continue;
				}

				$memberRelationship = Database::hgetall(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $memberUUID);
				$memberStatus = calendar_scalar_string($memberRelationship['status'] ?? '');
				if ($memberStatus !== OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
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
	http_response_code(404);
	header('Content-Type: text/html; charset=UTF-8');
	header('X-Robots-Tag: noindex, nofollow');
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: DENY');
	header('Referrer-Policy: strict-origin-when-cross-origin');
	// COEP disabled in dev to allow WebWorker loading
	header('Cross-Origin-Opener-Policy: same-origin');
	header('Cross-Origin-Resource-Policy: same-site');
	header("Permissions-Policy: accelerometer=(), camera=(), microphone=(), geolocation=(), usb=(), unload=()");
	echo '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars((string) html_index_i18n('NOT_FOUND_404_TITLE'), ENT_QUOTES, 'UTF-8') . '</title></head><body><main><h1>' . htmlspecialchars((string) html_index_i18n('NOT_FOUND_404_TITLE'), ENT_QUOTES, 'UTF-8') . '</h1><p>' . htmlspecialchars((string) html_index_i18n('NOT_FOUND_404_BODY'), ENT_QUOTES, 'UTF-8') . '</p></main></body></html>';
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
$isDelegatedRefresh = $selectedCalendarUserUUID !== '' && $selectedCalendarUserUUID !== $actorUUID;
$shouldRecalcWeekEntries = $recalcWeekEntries || $isDelegatedRefresh;
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
$apiError = null;
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
		$calendar = Calendar::fromDate(new \DateTime("{$yearParam}-{$monthNumberParam}-01"), 0, $calendarSubjectUser);
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
		
		$workEntries[] = [
			'site_id' => is_scalar($siteIdValue) ? (string) $siteIdValue : '',
			'site_name' => is_scalar($siteNameValue) ? (string) $siteNameValue : '',
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

// Get user positioning preferences for calendar
$currentUser = $calendarSubjectUser;

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
$userLocale = strtolower((string) ($currentUser->language ?? 'en')) . '_' . strtoupper((string) ($currentUser->language ?? 'en'));
$monthFormatter = new \IntlDateFormatter($userLocale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);
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

$calendarMonthContext = (new \DateTimeImmutable(sprintf('%04d-%02d-01', (int) $yearParam, (int) $monthNumberParam)))->format('F Y');

$grid = new DataGrid([
		'id' => 'calendar-grid',
		'columns' => [
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
					return number_format($hours, 2);
				}],
				['key' => 'adjacent', 'label' => html_index_i18n('CALENDAR_COL_ADJACENT'), 'sortable' => true, 'compute' => function($row, $col) {
					$adjacent = (int) ($row[$col['key']] ?? 0);
					return 1 === $adjacent ? html_index_i18n('CALENDAR_ADJACENT_YES') : html_index_i18n('CALENDAR_ADJACENT_NO');
				}],
		],
		'rows' => $rows,
		'meta' => [
				'layout' => 'month',
				'descriptionId' => 'calendar-grid-instructions calendar-grid-context calendar-month-status',
				'year' => (int) $yearParam,
				'month' => (int) $monthNumberParam,
				'searchEnabled' => false,
				'rowActions' => [],
				// Positioning configuration - uses normalized user preferences
				'dateLabelPosition' => $calendarDateLabelPosition,
				'workEntryPosition' => $calendarWorkEntryPosition,
				'dateAriaFormat' => $calendarAudioLabelFormat,
				// Autofocus preference - first, today (default), or last entry
				'autofocus' => $calendarAutofocus,
				// Lock boundary for historical record locking
				'lockBoundary' => is_scalar($payload['lockBoundary'] ?? null) ? (string) $payload['lockBoundary'] : '',
		],
]);


$message = '&nbsp;';
$pageTitle = 'Calendar - [PayCal]';
$pageLabel = 'CALENDAR';
$pageLanguage = User::current()->language ?? 'en';
$isEmailVerified = User::current()->email_verified ?? false;

require_once Environment::appHome().'html/header.php';
?>

<section
	id="calendar-v2-root"
	class="panel w100"
	role="application"
	aria-labelledby="calendar-landmark-title"
	aria-describedby="calendar-grid-instructions calendar-grid-context calendar-month-status"
	data-email-verified="<?php echo $isEmailVerified ? '1' : '0'; ?>"
	data-calendar-actor-uuid="<?php echo htmlspecialchars($actorUUID, ENT_QUOTES, 'UTF-8'); ?>"
	data-calendar-user-uuid="<?php echo htmlspecialchars($selectedCalendarUserUUID, ENT_QUOTES, 'UTF-8'); ?>"
>
	<h1 id="calendar-landmark-title" class="visually_hidden"><?php echo html_index_i18n('CALENDAR'); ?></h1>
	<?php if (count($viewableMembers) > 1) { ?>
	<div class="calendar_user_selector f_row f_center_y gap8">
		<label for="calendar_user_lookup">View as</label>
		<form id="calendar_user_view_form" method="GET" action="/" class="calendar_user_selector_form" data-self-label="<?php echo htmlspecialchars(calendar_member_label($viewableMembers[$actorUUID] ?? $selectedCalendarUser), ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" name="month" value="<?php echo htmlspecialchars($monthParam, ENT_QUOTES, 'UTF-8'); ?>">
			<input type="hidden" id="calendar_user_uuid_hidden" name="user_uuid" value="<?php echo htmlspecialchars($selectedCalendarUserUUID, ENT_QUOTES, 'UTF-8'); ?>">
			<input id="calendar_user_lookup" type="text" list="calendar_user_lookup_list" autocomplete="off" value="<?php echo htmlspecialchars($selectedCalendarUserLabel, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Select calendar user">
			<datalist id="calendar_user_lookup_list">
				<?php foreach ($viewableMembers as $memberUUID => $member) {
					$label = calendar_member_label($member);
				?>
				<option value="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>" data-user-uuid="<?php echo htmlspecialchars($memberUUID, ENT_QUOTES, 'UTF-8'); ?>"></option>
				<?php } ?>
			</datalist>
			<button id="calendar_user_clear_btn" type="submit" name="clear_user_view" value="1" class="btn btn_secondary calendar_user_clear" aria-label="Clear delegated calendar view" formnovalidate>
				<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
					<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
				</svg>
			</button>
		</form>
	</div>
	<?php } ?>
	<p id="calendar-grid-instructions" class="visually_hidden"><?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_GRID_INSTRUCTIONS'), ENT_QUOTES, 'UTF-8'); ?></p>
	<p id="calendar-grid-context" class="visually_hidden"><?php echo htmlspecialchars($calendarMonthContext, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_GRID_CONTEXT_SUFFIX'), ENT_QUOTES, 'UTF-8'); ?></p>
	<p id="calendar-month-status" class="visually_hidden" role="status" aria-live="polite" aria-atomic="true"></p>
	<?php echo $grid->table(); ?>
</section>

<?php echo $datePickerDialog; ?>

<div id="calendar_day_context_menu" class="hidden" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_DAY_MENU_ARIA'), ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1">
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
	<ul role="menu" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_DAY_ACTIONS_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
		<li tabindex="-1" data-action="copy" role="menuitem"><span><?php echo html_index_i18n('COPY'); ?></span><kbd class="calendar_shortcut" data-shortcut-modifier="primary" data-shortcut-key="C"><span class="calendar_shortcut_mod" aria-hidden="true"><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_mac" focusable="false"><use href="#mod-mac"></use></svg><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_win" focusable="false"><use href="#mod-win"></use></svg></span><span class="calendar_shortcut_sep" aria-hidden="true">+</span><span class="calendar_shortcut_key">C</span></kbd></li>
		<li tabindex="-1" data-action="paste" role="menuitem"><span><?php echo html_index_i18n('PASTE'); ?></span><kbd class="calendar_shortcut" data-shortcut-modifier="primary" data-shortcut-key="V"><span class="calendar_shortcut_mod" aria-hidden="true"><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_mac" focusable="false"><use href="#mod-mac"></use></svg><svg class="svg-icon calendar_shortcut_icon calendar_shortcut_icon_win" focusable="false"><use href="#mod-win"></use></svg></span><span class="calendar_shortcut_sep" aria-hidden="true">+</span><span class="calendar_shortcut_key">V</span></kbd></li>
		<li tabindex="-1" data-action="open" role="menuitem"><span><?php echo html_index_i18n('OPEN'); ?></span><kbd class="calendar_shortcut" data-shortcut-key="Enter" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('ENTER_KEY'), ENT_QUOTES, 'UTF-8'); ?>"><span class="calendar_shortcut_key" aria-hidden="true">↵</span></kbd></li>
		<li tabindex="-1" data-action="delete" role="menuitem"><span><?php echo html_index_i18n('DELETE'); ?></span><kbd class="calendar_shortcut" data-shortcut-key="Delete"><span class="calendar_shortcut_key"><?php echo html_index_i18n('DELETE_KEY'); ?></span></kbd></li>
	</ul>
</div>

<!-- Calendar Entry Modal Dialog -->
<dialog id="calendar-modal" class="calendar_modal" data-dialog-close-on-backdrop="true" aria-labelledby="calendar-modal-date" aria-describedby="calendar-modal-desc">
	<p id="calendar-modal-desc" class="visually_hidden"><?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_MODAL_DESC'), ENT_QUOTES, 'UTF-8'); ?></p>
	<section class="modal_header calendar_modal_header">
		<button type="button" class="btn btn_close calendar_modal_close" data-dialog-close="calendar-modal" aria-label="<?php echo htmlspecialchars((string) html_index_i18n('CLOSE'), ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
		<h2 id="calendar-modal-date"><?php echo htmlspecialchars((string) html_index_i18n('DATE'), ENT_QUOTES, 'UTF-8'); ?></h2>
		<div class="calendar_modal_header_actions">
			<button type="button" class="btn btn_primary calendar_modal_header_add" data-action="add-row"><?php echo htmlspecialchars((string) html_index_i18n('CALENDAR_MODAL_ADD_ENTRY'), ENT_QUOTES, 'UTF-8'); ?></button>
		</div>
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

// Load monolithic calendar.js directly (not the PHP-backed folder which includes PhantomWing)
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
$calendarSriAttribute = Environment::appEnv() === 'prod'
	? Render::sriAttribute('js/calendar/calendar.js')
	: '';
echo '    <script src="' . Environment::appURL('js/calendar/calendar.js') . '?v=' . htmlspecialchars($cacheVersion, ENT_QUOTES, 'UTF-8') . '" nonce="' . htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8') . '"' . $calendarSriAttribute . '></script>' . PHP_EOL;

require_once Environment::appHome().'html/footer.php';



