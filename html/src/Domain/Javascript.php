<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Javascript.php
 *
 * Purpose: Define the Javascript class for PayCal\Domain.
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



/**
 * Class Javascript.
 *
 * Helper utility for JavaScript asset loading and copyright notice generation.
 * Provides methods for rendering script tags with cache busting and CSP nonce.
 */
class Javascript
{
  /**
   * Output docblock template which includes current year.
   *
   * @return void
   */
  public static function renderDocblock(): void
  {
    echo Render::template('javascript-docblock', [
        '__DATE__' => date('Y'),
    ]);
  }
}
