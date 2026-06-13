<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Provenance label for a forecast assumption field.
 */
enum ForecastAssumptionSource: string
{
  case Saved      = 'saved';
  case Scheduled  = 'scheduled';
  case Temporary  = 'temporary';
  case Estimated  = 'estimated';
  case Missing    = 'missing';
}
