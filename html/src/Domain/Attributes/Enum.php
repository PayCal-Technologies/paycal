<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * Enum.php
 *
 * Purpose: Validation metadata attribute declaring that a property must map
 * to a supported enum class/case.
 *
 * Developer notes:
 * - Enum class names stored here should remain fully-qualified and stable.
 *
 * @category   Attributes
 * @package    PayCal\Domain\Attributes
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Enum attribute.
 *
 * Responsibilities:
 * - Declare allowed enum class constraints for validated fields.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
/**
 * Enum metadata value object.
 */
class Enum
{
  /**
   * @param string $enumClass Fully-qualified class name of the target enum.
   */
  public function __construct(public string $enumClass)
  {
  }
}

