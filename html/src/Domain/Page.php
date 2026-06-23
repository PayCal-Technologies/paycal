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
  case REPORTS = 'PAGE_REPORTS';
  case SITES = 'PAGE_SITES';
  case PROFILE = 'PAGE_PROFILE';
  case CONNECTIONS = 'PAGE_CONNECTIONS';
  case BUSINESSES = 'PAGE_BUSINESSES';
  case BUSINESS_DASHBOARD = 'PAGE_BUSINESS_DASHBOARD';
  case BUSINESS_DETAILS = 'PAGE_BUSINESS_DETAILS';
  case BUSINESS_MEMBERS = 'PAGE_BUSINESS_MEMBERS';
  case BUSINESS_GROUPS = 'PAGE_BUSINESS_GROUPS';
  case BUSINESS_SITES = 'PAGE_BUSINESS_SITES';
  case BUSINESS_PAYROLL = 'PAGE_BUSINESS_PAYROLL';
  case BUSINESS_AUDIT = 'PAGE_BUSINESS_AUDIT';
  case BUSINESS_REPORTS = 'PAGE_BUSINESS_REPORTS';
  case ADMIN = 'PAGE_ADMIN';
}
