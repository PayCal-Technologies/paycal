<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * KEK.php
 *
 * Purpose: Key Encryption Key (KEK) lifecycle model: identity, ownership, and
 *          rotation contracts that remain independent of user identity.
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
 * KEK - Key Encryption Key Management.
 *
 * Formal ownership model that decouples KEK identity from user identity.
 * Runtime KEK persistence is handled by KekController and user hash fields.
 */
class KEK
{
  public const STATUS_ACTIVE = 'active';
  public const STATUS_ROTATED = 'rotated';
  public const STATUS_REVOKED = 'revoked';
}
