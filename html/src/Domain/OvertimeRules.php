<?php declare(strict_types=1);

/**
 * PayCal OvertimeRules.php
 *
 * Jurisdiction rules for daily and weekly overtime.
 * Thresholds and sources live in OvertimeRulesData.json.
 *
 * @package PayCal
 * @author  Chris Langtiw <chris@paycal.app>
 * @license PROPRIETARY
 */

namespace PayCal\Domain;

use InvalidArgumentException;
use RuntimeException;

/**
 * Standard overtime thresholds for Canadian jurisdictions.
 *
 * Methods follow the published general rule. Averaging agreements and
 * industry permits are described on the rule and are not applied here.
 */
final class OvertimeRules
{
	public const LEGACY = 'LEGACY';

	/** @var array<string, array<string, mixed>>|null */
	private static ?array $rules = null;

	/** Prevent construction of the static rule service. */
	private function __construct()
	{
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array
	{
		if (self::$rules === null) {
			$path = __DIR__ . '/OvertimeRulesData.json';
			$decoded = json_decode((string) file_get_contents($path), true);
			if (!is_array($decoded) || !isset($decoded['rules']) || !is_array($decoded['rules'])) {
				throw new RuntimeException('Overtime rules data is missing or invalid.');
			}
			self::$rules = [];
			foreach ($decoded['rules'] as $code => $row) {
				if (!is_string($code)) { throw new RuntimeException('Invalid jurisdiction key.'); }
				self::$rules[$code] = PayRuleData::object($row);
			}
		}

		return self::$rules;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function forCode(string $code): array
	{
		$normalized = strtoupper(trim($code));
		$rules = self::all();
		if (!isset($rules[$normalized])) {
			throw new InvalidArgumentException('Unknown overtime jurisdiction: ' . $code);
		}

		return $rules[$normalized];
	}

	/**
	 * Resolve a stored province, territory, or federal code.
	 * An empty value keeps the historical 8/40 calculation.
	 *
	 * @return array<string, mixed>
	 */
	public static function resolve(?string $code): array
	{
		$normalized = strtoupper(trim((string) $code));
		if ($normalized === '') {
			return self::forCode(self::LEGACY);
		}

		$aliases = [
			'ALBERTA' => 'AB',
			'BRITISH COLUMBIA' => 'BC',
			'MANITOBA' => 'MB',
			'NEW BRUNSWICK' => 'NB',
			'NEWFOUNDLAND' => 'NL',
			'NEWFOUNDLAND AND LABRADOR' => 'NL',
			'NOVA SCOTIA' => 'NS',
			'NORTHWEST TERRITORIES' => 'NT',
			'NUNAVUT' => 'NU',
			'ONTARIO' => 'ON',
			'PRINCE EDWARD ISLAND' => 'PE',
			'PEI' => 'PE',
			'QUEBEC' => 'QC',
			'QUÉBEC' => 'QC',
			'SASKATCHEWAN' => 'SK',
			'YUKON' => 'YT',
			'FEDERAL' => 'CA',
			'CANADA' => 'CA',
		];
		$normalized = $aliases[$normalized] ?? $normalized;

		return self::forCode($normalized);
	}

	/** Statutory ordinary overtime floor; a higher contracted rate is a separate input. */
	public static function hourlyOvertimeRateCents(string $code, int $regularRateCents, \DateTimeImmutable $date): int
	{
		$rule = self::resolve($code);
		if (($rule['rate_basis'] ?? '') !== 'minimum_wage') {
			return (int) round($regularRateCents * 1.5);
		}
		$minimum = null;
		foreach (PayRuleData::entries($rule['minimum_wage_history_cents']) as $effective => $cents) {
			if ((string) $effective <= $date->format('Y-m-d')) {
				$minimum = (int) PayRuleData::number($cents);
			}
		}
		if ($minimum === null) {
			// Preserve historical behavior before the supported rate history.
			return (int) round($regularRateCents * 1.5);
		}
		return max($regularRateCents, (int) round($minimum * 1.5));
	}

	/**
	 * @return list<string>
	 */
	public static function jurisdictionCodes(): array
	{
		return array_values(array_filter(
			array_keys(self::all()),
			static fn (string $code): bool => $code !== self::LEGACY
		));
	}

	/**
	 * Split one week of worked hours into regular, time-and-a-half, and double time.
	 *
	 * @param list<float> $dailyHours Hours worked on each day of the week, in order
	 * @return array{
	 *   regular: float,
	 *   overtime: float,
	 *   doubletime: float,
	 *   daily: list<array{regular: float, overtime: float, doubletime: float}>
	 * }
	 */
	public static function allocateWeek(array $dailyHours, string $code, int $publicHolidaysInWeek = 0, ?\DateTimeImmutable $asOf = null): array
	{
		$rule = self::resolve($code);
		$method = PayRuleData::text($rule['method'] ?? '');
		$weekly = PayRuleData::number($rule['weekly_hours']);
		if (($rule['code'] ?? '') === 'PE' && $asOf !== null && $asOf->format('Y-m-d') < '2026-06-30') {
			$weekly = 48.0;
		}
		$reduction = PayRuleData::number($rule['weekly_reduction_per_holiday'] ?? 0);
		if ($publicHolidaysInWeek > 0 && $reduction > 0) {
			$weekly = max(0.0, $weekly - ($reduction * $publicHolidaysInWeek));
		}

		return match ($method) {
			'weekly_only' => self::allocateWeeklyOnly($dailyHours, $weekly),
			'daily_then_weekly' => self::allocateDailyThenWeekly(
				$dailyHours,
				PayRuleData::number($rule['daily_hours']),
				$weekly
			),
			'greater_of' => self::allocateGreaterOf(
				$dailyHours,
				PayRuleData::number($rule['daily_hours']),
				$weekly
			),
			'bc_daily_double' => self::allocateBc($dailyHours),
			default => throw new RuntimeException('Unknown overtime method: ' . $method),
		};
	}

	/**
	 * Allocate a Sunday based week using holidays that have verified dates.
	 * Holiday hours in Saskatchewan are paid separately at the premium rate
	 * and excluded from the reduced weekly threshold.
	 * @param list<float> $dailyHours
	 * @param list<string>|null $eligibleHolidayDates Explicit qualifying dates for conditional holiday rules; null applies only the general SK adjustment
	 * @return array{regular: float, overtime: float, doubletime: float, daily: list<array{regular: float, overtime: float, doubletime: float}>}
	 */
	public static function allocateDatedWeek(array $dailyHours, string $code, \DateTimeImmutable $start, ?array $eligibleHolidayDates = null): array
	{
		$resolved = PayRuleData::text(self::resolve($code)['code']);
		$holidays = $resolved === self::LEGACY ? [] : StatHolidayEngine::between($resolved, $start, $start->modify('+7 days'));
		$holidays = array_values(array_filter($holidays, static fn (array $holiday): bool => $eligibleHolidayDates === null ? $resolved === 'SK' : in_array($holiday['date'], $eligibleHolidayDates, true)));
		if (PayRuleData::number(self::resolve($resolved)['weekly_reduction_per_holiday'] ?? 0) <= 0) { $holidays = []; }
		$holidayHours = [];
		foreach ($holidays as $holiday) {
			$index = (int) $start->diff(new \DateTimeImmutable($holiday['date'], $start->getTimezone()))->format('%a');
			$holidayHours[$index] = PayRuleData::number($dailyHours[$index] ?? 0);
			$dailyHours[$index] = 0.0;
		}
		$split = self::allocateWeek(array_values($dailyHours), $resolved, count($holidays), $start);
		foreach ($holidayHours as $index => $hours) {
			if (!isset($split['daily'][$index])) { continue; }
			$split['daily'][$index]['overtime'] = $hours;
			$split['overtime'] += $hours;
		}
		return $split;
	}

	/**
	 * @param list<float> $dailyHours
	 * @return array{regular: float, overtime: float, doubletime: float, daily: list<array{regular: float, overtime: float, doubletime: float}>}
	 */
	private static function allocateWeeklyOnly(array $dailyHours, float $weeklyHours): array
	{
		$daily = [];
		$regularRemaining = $weeklyHours;
		$totalRegular = 0.0;
		$totalOvertime = 0.0;

		foreach ($dailyHours as $hours) {
			$hours = self::hours($hours);
			$regular = min($hours, max(0.0, $regularRemaining));
			$overtime = $hours - $regular;
			$regularRemaining -= $regular;
			$totalRegular += $regular;
			$totalOvertime += $overtime;
			$daily[] = ['regular' => $regular, 'overtime' => $overtime, 'doubletime' => 0.0];
		}

		return [
			'regular' => $totalRegular,
			'overtime' => $totalOvertime,
			'doubletime' => 0.0,
			'daily' => $daily,
		];
	}

	/**
	 * @param list<float> $dailyHours
	 * @return array{regular: float, overtime: float, doubletime: float, daily: list<array{regular: float, overtime: float, doubletime: float}>}
	 */
	private static function allocateDailyThenWeekly(array $dailyHours, float $dailyLimit, float $weeklyHours): array
	{
		$capped = [];
		$dailyOvertime = [];
		foreach ($dailyHours as $hours) {
			$hours = self::hours($hours);
			$regularCandidate = min($hours, $dailyLimit);
			$capped[] = $regularCandidate;
			$dailyOvertime[] = $hours - $regularCandidate;
		}

		$weekly = self::allocateWeeklyOnly($capped, $weeklyHours);
		$daily = [];
		$totalOvertime = 0.0;
		foreach ($weekly['daily'] as $index => $row) {
			$overtime = $row['overtime'] + $dailyOvertime[$index];
			$totalOvertime += $overtime;
			$daily[] = ['regular' => $row['regular'], 'overtime' => $overtime, 'doubletime' => 0.0];
		}

		return [
			'regular' => $weekly['regular'],
			'overtime' => $totalOvertime,
			'doubletime' => 0.0,
			'daily' => $daily,
		];
	}

	/**
	 * Pay the greater of daily-threshold overtime and weekly-threshold overtime.
	 *
	 * @param list<float> $dailyHours
	 * @return array{regular: float, overtime: float, doubletime: float, daily: list<array{regular: float, overtime: float, doubletime: float}>}
	 */
	private static function allocateGreaterOf(array $dailyHours, float $dailyLimit, float $weeklyHours): array
	{
		$dailyOnly = self::allocateDailyThenWeekly($dailyHours, $dailyLimit, PHP_FLOAT_MAX);
		$weeklyOnly = self::allocateWeeklyOnly($dailyHours, $weeklyHours);
		$chosen = $dailyOnly['overtime'] >= $weeklyOnly['overtime'] ? $dailyOnly : $weeklyOnly;

		return $chosen;
	}

	/**
	 * British Columbia s. 40: 1.5x after 8 and up to 12 in a day, 2x after 12,
	 * and 1.5x after 40 counted from the first 8 hours of each day.
	 *
	 * @param list<float> $dailyHours
	 * @return array{regular: float, overtime: float, doubletime: float, daily: list<array{regular: float, overtime: float, doubletime: float}>}
	 */
	private static function allocateBc(array $dailyHours): array
	{
		$firstEight = [];
		$timeAndHalf = [];
		$double = [];
		foreach ($dailyHours as $hours) {
			$hours = self::hours($hours);
			$first = min($hours, 8.0);
			$middle = min(max(0.0, $hours - 8.0), 4.0);
			$rest = max(0.0, $hours - 12.0);
			$firstEight[] = $first;
			$timeAndHalf[] = $middle;
			$double[] = $rest;
		}

		$weekly = self::allocateWeeklyOnly($firstEight, 40.0);
		$daily = [];
		$totalOvertime = 0.0;
		$totalDouble = 0.0;
		foreach ($weekly['daily'] as $index => $row) {
			$overtime = $row['overtime'] + $timeAndHalf[$index];
			$totalOvertime += $overtime;
			$totalDouble += $double[$index];
			$daily[] = [
				'regular' => $row['regular'],
				'overtime' => $overtime,
				'doubletime' => $double[$index],
			];
		}

		return [
			'regular' => $weekly['regular'],
			'overtime' => $totalOvertime,
			'doubletime' => $totalDouble,
			'daily' => $daily,
		];
	}

	/** Validate a nonnegative finite number of worked hours. */
	private static function hours(mixed $value): float
	{
		$hours = PayRuleData::number($value);
		if ($hours < 0) {
			throw new InvalidArgumentException('Hours cannot be negative.');
		}

		return $hours;
	}

	/**
	 * Short sentence for help and tool pages.
	 */
	public static function summary(string $code): string
	{
		$rule = self::resolve($code);
		$french = defined('USER_LANGUAGE') && strtolower(PayRuleData::text(constant('USER_LANGUAGE'))) === 'fr';
		$daily = $rule['daily_hours'];
		$weekly = PayRuleData::number($rule['weekly_hours']);
		$multiplier = PayRuleData::number($rule['multiplier']);
		$rate = rtrim(rtrim(number_format($multiplier, 1, '.', ''), '0'), '.');

		if (PayRuleData::text($rule['method'] ?? '') === 'bc_daily_double') {
			return $french
				? 'Après 8 heures dans une journée, le taux est de ' . $rate . ' fois jusqu’à 12 heures, puis le taux double. Après 40 heures dans une semaine, le taux est aussi de ' . $rate . ' fois, en comptant seulement les 8 premières heures de chaque journée.'
				: 'After 8 hours in a day, pay is ' . $rate . ' times up to 12 hours and double time after 12. Hours after 40 in a week are also ' . $rate . ' times, counting only the first 8 hours of each day.';
		}

		if (($rule['rate_basis'] ?? '') === 'minimum_wage') {
			return $french
				? 'Le seuil général est de ' . self::hourLabel($weekly, true) . ' par semaine. Le taux minimum des heures supplémentaires est fondé sur le salaire minimum provincial, et non sur le salaire habituel. Un contrat peut prévoir davantage.'
				: 'The general overtime threshold is ' . self::hourLabel($weekly) . ' per week. The statutory overtime minimum is based on provincial minimum wage. Your contract may pay more.';
		}

		if ($daily === null) {
			return $french
				? 'Le temps supplémentaire commence après ' . self::hourLabel($weekly, true) . ' dans une semaine, à ' . $rate . ' fois le taux régulier. Il n’y a pas de seuil quotidien général.'
				: 'Overtime starts after ' . self::hourLabel($weekly) . ' in a week, at ' . $rate . ' times the regular rate. There is no general daily overtime threshold.';
		}

		$method = PayRuleData::text($rule['method']);
		if ($french) {
			$joiner = $method === 'greater_of'
				? 'Le temps supplémentaire est le plus élevé des heures au-delà de '
				: 'Le temps supplémentaire commence après ';

			return $joiner . self::hourLabel(PayRuleData::number($daily), true) . ' dans une journée ou de ' . self::hourLabel($weekly, true) . ' dans une semaine, à ' . $rate . ' fois le taux régulier.';
		}

		$joiner = $method === 'greater_of'
			? 'Overtime is the greater of hours over '
			: 'Overtime starts after ';

		return $joiner . self::hourLabel(PayRuleData::number($daily)) . ' in a day or ' . self::hourLabel($weekly) . ' in a week, at ' . $rate . ' times the regular rate.';
	}

	/** Format an hour threshold for the selected language. */
	private static function hourLabel(float $hours, bool $french = false): string
	{
		$formatted = rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.');
		if ($french) {
			return $formatted . ($hours === 1.0 ? ' heure' : ' heures');
		}

		return $formatted . ' hour' . ($hours === 1.0 ? '' : 's');
	}
}
