<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Named forecast scenario presets for comparison cards.
 */
enum ForecastScenario: string
{
  case Conservative = 'conservative';
  case Normal       = 'normal';
  case Overtime     = 'overtime';
  case Custom       = 'custom';
}
