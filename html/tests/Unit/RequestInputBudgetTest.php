<?php declare(strict_types=1);

use PayCal\Domain\InputSanitizer;
use PayCal\Domain\RequestInputBudget;
use PHPUnit\Framework\TestCase;

/** Regression tests use synthetic input and require no application services. */
final class RequestInputBudgetTest extends TestCase
{
  /** Budget includes all sibling subtrees, including empty containers. */
  public function testWideNestedInputIsRejected(): void
  {
    $input = array_fill(0, 101, array_fill(0, 100, ''));
    $this->expectException(\RuntimeException::class);
    InputSanitizer::sanitizeArray($input);
  }

  /** A bounded array remains accepted at the exact node boundary. */
  public function testExactNodeBudgetIsAccepted(): void
  {
    RequestInputBudget::assertArray(array_fill(0, RequestInputBudget::MAX_NODES, ''));
    $this->addToAssertionCount(1);
  }

  /** Recursive references fail at the depth boundary rather than encoding. */
  public function testRecursiveInputIsRejected(): void
  {
    $input = [];
    $input['self'] = &$input;
    $this->expectException(\RuntimeException::class);
    InputSanitizer::sanitizeArray($input);
  }

  /** Aggregate bytes count keys as well as values before transformation. */
  public function testAggregateBytesAreRejected(): void
  {
    $input = array_fill(0, 300, str_repeat('x', 4000));
    $this->expectException(\RuntimeException::class);
    InputSanitizer::sanitizeArray($input);
  }

  /** Failed encoding must not bypass the existing POST byte policy. */
  public function testInvalidUtf8PostArrayFailsClosed(): void
  {
    $saved = $_POST;
    try {
      $_POST = ['items' => ["\xff"]];
      $this->expectException(\RuntimeException::class);
      InputSanitizer::postArray('items');
    } finally {
      $_POST = $saved;
    }
  }

  /** Preserve top level key collision and nested key normalization contracts. */
  public function testExistingSanitizerBehaviorIsPreserved(): void
  {
    $this->assertSame(
      ['a' => '2', 'nested' => ['<key>' => '3']],
      InputSanitizer::sanitizeArray([' a ' => 1, 'a' => 2, 'nested' => ['<key>' => 3]])
    );
  }

  /** Bodies retain exact bytes for signatures and encrypted envelopes. */
  public function testOrdinaryBodiesAndEscapedDelimitersAreAccepted(): void
  {
    foreach (['', 'a=b&c=d', '{"value":"[{},:]\\\"\\\\","items":[1,2]}', str_repeat('x', RequestInputBudget::MAX_BYTES)] as $body) {
      $before = $body;
      RequestInputBudget::assertBody($body);
      $this->assertSame($before, $body);
    }
  }

  /** Raw size enforcement does not depend on a declared content length. */
  public function testOversizedBodyIsRejected(): void
  {
    $this->expectException(\RuntimeException::class);
    RequestInputBudget::assertBody(str_repeat('x', RequestInputBudget::MAX_BYTES + 1));
  }

  /** Limit nesting before json_decode builds its containers. */
  public function testDeepJsonIsRejected(): void
  {
    $this->expectException(\RuntimeException::class);
    RequestInputBudget::assertBody(str_repeat('[', 33) . '0' . str_repeat(']', 33));
  }

  /** A small wire representation can still imply many allocated buckets. */
  public function testWideJsonIsRejected(): void
  {
    $this->expectException(\RuntimeException::class);
    RequestInputBudget::assertBody('[' . str_repeat('0,', 10000) . '0]');
  }
}
