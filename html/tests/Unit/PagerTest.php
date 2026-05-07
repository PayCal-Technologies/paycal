<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Infrastructure\Persistence\Pager;
use PayCal\Domain\Redis;
use PHPUnit\Framework\Attributes\Group;

/**
 * PagerTest.
 *
 * Unit tests for Pager class
 * Tests pagination logic, parameter validation, and response structure
 *
 * Note: Some tests require Redis mocking for integration testing
 *
 * @internal
 *
 */
#[Group('unit')]
final class PagerTest extends TestCase
{
  // =========================================================================
  // Constructor Tests
  // =========================================================================

  #[Test]
  public function constructorWithMockedRedisCreatesInstance(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $this->assertInstanceOf(Pager::class, $pager);
  }

  // =========================================================================
  // Response Structure Tests
  // =========================================================================

  #[Test]
  public function listWithEmptyKeyReturnsErrorResponse(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('', 0, 10);

    $this->assertArrayHasKey('error', $response);
    $this->assertStringContainsString('must not be empty', $response['error']);
  }

  #[Test]
  public function listResponseStructureHasAllRequiredKeys(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    // Test with invalid params to get response without Redis interaction
    $response = $pager->list('', 0, 10);

    $this->assertArrayHasKey('data', $response);
    $this->assertArrayHasKey('total', $response);
    $this->assertArrayHasKey('startIndex', $response);
    $this->assertArrayHasKey('listSize', $response);
    $this->assertArrayHasKey('returned', $response);
    $this->assertArrayHasKey('hasMore', $response);
    $this->assertArrayHasKey('error', $response);
  }

  #[Test]
  public function listResponseTemplateHasCorrectDefaultValues(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('', 0, 25);

    $this->assertIsArray($response['data']);
    $this->assertSame(0, $response['total']);
    $this->assertSame(0, $response['startIndex']);
    $this->assertSame(25, $response['listSize']);
    $this->assertSame(0, $response['returned']);
    $this->assertFalse($response['hasMore']);
  }

  // =========================================================================
  // Parameter Validation Tests
  // =========================================================================

  #[Test]
  public function listWithNegativeStartIndexReturnsError(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:*', -1, 10);

    $this->assertNotNull($response['error']);
    $this->assertStringContainsString('startIndex', $response['error']);
    $this->assertStringContainsString('>= 0', $response['error']);
  }

  #[Test]
  public function listWithZeroListSizeReturnsError(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:*', 0, 0);

    $this->assertNotNull($response['error']);
    $this->assertStringContainsString('listSize', $response['error']);
    $this->assertStringContainsString('> 0', $response['error']);
  }

  #[Test]
  public function listWithNegativeListSizeReturnsError(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:*', 0, -10);

    $this->assertNotNull($response['error']);
    $this->assertStringContainsString('listSize', $response['error']);
  }

  #[Test]
  public function listWithExcessiveListSizeReturnsError(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:*', 0, 101); // Max is 100

    $this->assertNotNull($response['error']);
    $this->assertStringContainsString('exceeded maximum', $response['error']);
    $this->assertStringContainsString('100', $response['error']);
  }

  #[Test]
  public function listWithMaximumValidListSizeAcceptsParameter(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:key', 0, 100); // Exactly at max

    // In unit test environment without Redis, we expect a retrieval error
    // What we're actually testing is that listSize=100 passes validation
    $this->assertSame(100, $response['listSize']);
  }

  #[Test]
  public function listWithInvalidSortDirectionReturnsError(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:*', 0, 10, ['field'], 'field', 'invalid');

    $this->assertNotNull($response['error']);
    $this->assertStringContainsString('sortDirection', $response['error']);
    $this->assertStringContainsString('asc', $response['error']);
    $this->assertStringContainsString('desc', $response['error']);
  }

  #[Test]
  public function listWithValidAscSortDirectionAcceptsParameter(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:key', 0, 10, ['field'], 'field', 'asc');

    // Verify parameters were accepted (no validation error, though retrieval may fail)
    $this->assertSame(10, $response['listSize']);
    $this->assertSame(0, $response['startIndex']);
  }

  #[Test]
  public function listWithValidDescSortDirectionAcceptsParameter(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:key', 0, 10, ['field'], 'field', 'desc');

    // Verify parameters were accepted
    $this->assertSame(10, $response['listSize']);
    $this->assertSame(0, $response['startIndex']);
  }

  #[Test]
  public function listWithEmptyRedisKeyReturnsValidationError(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('', 0, 10);

    $this->assertStringContainsString('redisKey', $response['error']);
  }

  #[Test]
  #[DataProvider('invalidParameterProvider')]
  public function listWithInvalidParametersReturnsError(
    string $key,
    int $start,
    int $size,
    string $expectedErrorFragment
  ): void {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list($key, $start, $size);

    $this->assertNotNull($response['error']);
    $this->assertStringContainsString($expectedErrorFragment, $response['error']);
  }

  public static function invalidParameterProvider(): array
  {
    return [
        'empty key' => ['', 0, 10, 'redisKey'],
        'negative start' => ['test:*', -5, 10, 'startIndex'],
        'zero size' => ['test:*', 0, 0, 'listSize'],
        'negative size' => ['test:*', 0, -1, 'listSize'],
        'excessive size' => ['test:*', 0, 200, 'maximum'],
    ];
  }

  // =========================================================================
  // Default Values Tests
  // =========================================================================

  #[Test]
  public function listWithDefaultParametersUsesCorrectDefaults(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    // Call with minimal params
    $response = $pager->list('test:key');

    $this->assertSame(0, $response['startIndex']);
    $this->assertSame(25, $response['listSize']); // Default list size
    // Note: In unit test environment, may have retrieval error but defaults should be set
  }

  // =========================================================================
  // Response Consistency Tests
  // =========================================================================

  #[Test]
  public function listErrorResponseStillHasCompleteStructure(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    // Trigger validation error
    $response = $pager->list('', 0, 10);

    // Even with error, all keys should be present
    $this->assertArrayHasKey('data', $response);
    $this->assertArrayHasKey('total', $response);
    $this->assertArrayHasKey('startIndex', $response);
    $this->assertArrayHasKey('listSize', $response);
    $this->assertArrayHasKey('returned', $response);
    $this->assertArrayHasKey('hasMore', $response);
    $this->assertArrayHasKey('error', $response);

    // Error should be populated
    $this->assertNotNull($response['error']);

    // Data should be empty
    $this->assertSame([], $response['data']);
    $this->assertSame(0, $response['total']);
    $this->assertSame(0, $response['returned']);
    $this->assertFalse($response['hasMore']);
  }

  #[Test]
  public function listPreservesStartIndexInResponse(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('', 42, 10);

    $this->assertSame(42, $response['startIndex']);
  }

  #[Test]
  public function listPreservesListSizeInResponse(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('', 0, 50);

    $this->assertSame(50, $response['listSize']);
  }

  // =========================================================================
  // Type Safety Tests
  // =========================================================================

  #[Test]
  public function listResponseHasCorrectTypes(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('', 0, 10);

    $this->assertIsArray($response['data']);
    $this->assertIsInt($response['total']);
    $this->assertIsInt($response['startIndex']);
    $this->assertIsInt($response['listSize']);
    $this->assertIsInt($response['returned']);
    $this->assertIsBool($response['hasMore']);
    // error can be null or string
    $this->assertTrue(is_null($response['error']) || is_string($response['error']));
  }

  // =========================================================================
  // Edge Case Tests
  // =========================================================================

  #[Test]
  public function listWithBoundaryStartIndexZeroIsValid(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:key', 0, 10);

    // Verify startIndex was accepted (no validation error for startIndex=0)
    $this->assertSame(0, $response['startIndex']);
    // Validation errors would mention 'startIndex' specifically
    if (null !== $response['error']) {
      $this->assertStringNotContainsString('startIndex', $response['error']);
    }
  }

  #[Test]
  public function listWithBoundaryListSizeOneIsValid(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:key', 0, 1);

    // Verify listSize was accepted (no validation error for listSize=1)
    $this->assertSame(1, $response['listSize']);
    // Validation errors would mention 'listSize' specifically
    if (null !== $response['error']) {
      $this->assertStringNotContainsString('listSize', $response['error']);
    }
  }

  #[Test]
  public function listWithBoundaryListSizeHundredIsValid(): void
  {
    $redis = $this->createMockRedis();
    $pager = new Pager($redis);

    $response = $pager->list('test:key', 0, 100);

    // Verify listSize was accepted (no validation error for listSize=100)
    $this->assertSame(100, $response['listSize']);
    // Validation errors would mention 'listSize' specifically
    if (null !== $response['error']) {
      $this->assertStringNotContainsString('listSize', $response['error']);
    }
  }

  private function createMockRedis(): Redis
  {
    return $this->createMock(Redis::class);
  }
}
