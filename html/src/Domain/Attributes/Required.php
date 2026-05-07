<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * Required.php
 *
 * Purpose: Validation metadata attribute indicating that a bound property
 * must be present and non-empty.
 *
 * Developer notes:
 * - Used by reflection-driven validation paths.
 * - Keep semantics simple and predictable for field-validation tooling.
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
 * Required attribute.
 *
 * Responsibilities:
 * - Mark fields that cannot be null/empty in validated payloads.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Required
{
}

