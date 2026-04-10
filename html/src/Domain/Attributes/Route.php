<?php declare(strict_types=1);

namespace PayCal\Domain\Attributes;

/**
 * Route.php
 *
 * Purpose: Route metadata attribute used by API controller discovery and
 * dispatch to map methods to path/method combinations.
 *
 * Developer notes:
 * - Route metadata is part of the API contract surface.
 * - Keep path/method semantics declarative and side-effect free.
 *
 * @category   Attributes
 * @package    PayCal\Domain\Attributes
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */



/**
 * Route attribute.
 *
 * Responsibilities:
 * - Annotate controller methods with route path and allowed verbs.
 * - Provide stable metadata consumed by runtime route registration.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
/**
 * Route metadata value object.
 */
class Route
{
  /**
   * Initializes a new instance.
   */
  public function __construct(
    public string $path,
    /** @var array<string> */
    public array $methods = ['GET']
  ) {
  }
}

