<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * SiteFields.php
 *
 * Purpose: String-backed field name enum for site-related request parameters
 *          and Redis hash keys.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */
enum SiteFields: string
{
  case status = 'status';
  case bulk_action = 'bulk_action';
  case id = 'id';

  case sites = 'sites';
  case site_ids = 'site_ids';
}
