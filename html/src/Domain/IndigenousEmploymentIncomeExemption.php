<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Evaluates the income-tax exemption guideline for registered First Nations
 * employment income connected to reserve work.
 */
final class IndigenousEmploymentIncomeExemption
{
  /**
   * @return array{exemptCents:int,taxableCents:int,fullyExempt:bool,prorated:bool,reason:string}
   */
  public static function evaluate(
    int $incomeCents,
    bool $workerRegisteredOrEntitled,
    int $onReserveDutyPercent,
    bool $employerResidentOnReserve = false,
    bool $workerLivesOnReserve = false
  ): array {
    $incomeCents = max(0, $incomeCents);
    $onReserveDutyPercent = max(0, min(100, $onReserveDutyPercent));

    if (!$workerRegisteredOrEntitled || $incomeCents === 0) {
      return self::result(0, $incomeCents, false, false, 'not_eligible');
    }

    if ($onReserveDutyPercent >= 90) {
      return self::result($incomeCents, 0, true, false, 'guideline_1_90_percent_on_reserve');
    }

    if (
      $onReserveDutyPercent > 50
      && ($employerResidentOnReserve || $workerLivesOnReserve)
    ) {
      return self::result($incomeCents, 0, true, false, 'guideline_3_majority_plus_connecting_factor');
    }

    if ($employerResidentOnReserve && $workerLivesOnReserve) {
      return self::result($incomeCents, 0, true, false, 'guideline_2_employer_and_worker_on_reserve');
    }

    if ($onReserveDutyPercent > 0) {
      $exemptCents = (int) round($incomeCents * $onReserveDutyPercent / 100, 0, PHP_ROUND_HALF_UP);
      return self::result(
        $exemptCents,
        max(0, $incomeCents - $exemptCents),
        false,
        true,
        'guideline_1_prorated_on_reserve_duties'
      );
    }

    return self::result(0, $incomeCents, false, false, 'no_reserve_connection');
  }

  /**
   * @return array{exemptCents:int,taxableCents:int,fullyExempt:bool,prorated:bool,reason:string}
   */
  private static function result(
    int $exemptCents,
    int $taxableCents,
    bool $fullyExempt,
    bool $prorated,
    string $reason
  ): array {
    return [
      'exemptCents' => $exemptCents,
      'taxableCents' => $taxableCents,
      'fullyExempt' => $fullyExempt,
      'prorated' => $prorated,
      'reason' => $reason,
    ];
  }
}
