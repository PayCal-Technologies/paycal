<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Page.php
 *
 * Purpose: Application page identifier enum for route context switching and
 *          analytics page tracking.
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
enum Page: string
{
  case INDEX = 'PAGE_INDEX';
  case EARNINGS = 'PAGE_EARNINGS';
  case SITES = 'PAGE_SITES';
  case PROFILE = 'PAGE_PROFILE';
  case ORGANIZATIONS = 'PAGE_ORGANIZATIONS';
  case ADMIN = 'PAGE_ADMIN';
}
