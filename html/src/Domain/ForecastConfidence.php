<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Overall confidence tier for a projection window.
 */
enum ForecastConfidence: string
{
  case High   = 'high';
  case Medium = 'medium';
  case Low    = 'low';
}
