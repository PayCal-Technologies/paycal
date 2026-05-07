<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * Email.php
 *
 * Purpose: Validation metadata attribute signaling that a property expects
 * email-address format validation.
 *
 * Developer notes:
 * - Keep this as a pure metadata marker; do not embed normalization rules here.
 *
 * @category   Attributes
 * @package    PayCal\Domain\Attributes
 * @subpackage Metadata
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */
/**
 * Email attribute.
 *
 * Responsibilities:
 * - Mark fields that require email-format validation.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Email
{
}

