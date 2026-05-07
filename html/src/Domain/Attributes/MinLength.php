<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * MinLength.php
 *
 * Purpose: Validation metadata attribute defining minimum character-length
 * requirements for string-backed properties.
 *
 * Developer notes:
 * - This attribute only defines metadata; actual enforcement belongs in
 *   validation services.
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
 * MinLength attribute.
 *
 * Responsibilities:
 * - Provide declarative minimum-length constraints for field validators.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class MinLength
{
  /**
   * @param int $length Minimum required string length (number of characters).
   */
  public function __construct(public int $length)
  {
  }
}

