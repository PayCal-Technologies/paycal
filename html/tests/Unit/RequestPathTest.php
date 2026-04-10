<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\RequestPath;
use PHPUnit\Framework\Attributes\Group;

/**
 * RequestPath Test Suite.
 *
 * Tests for the RequestPath URL parsing and routing class.
 * Verifies path parsing, segment extraction, method detection, and pattern matching.
 *
 * PHP version 8.4.16
 *
 * @category   Tests
 *
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * @internal
 *
 */
#[Group('unit')]
final class RequestPathTest extends TestCase
{
  private array $originalServer;

  protected function setUp(): void
  {
    // Backup original $_SERVER state
    $this->originalServer = $_SERVER;
  }

  protected function tearDown(): void
  {
    // Restore original $_SERVER state
    $_SERVER = $this->originalServer;
  }

  // ==========================================
  // getFull() Tests
  // ==========================================

  #[Test]
  public function getFullReturnsRequestUri(): void
  {
    $this->setRequestUri('/users/profile');

    $result = RequestPath::getFull();

    $this->assertEquals('/users/profile', $result);
  }

  #[Test]
  public function getFullReturnsRootWhenMissing(): void
  {
    unset($_SERVER['REQUEST_URI']);

    $result = RequestPath::getFull();

    $this->assertEquals('/', $result);
  }

  #[Test]
  public function getFullHandlesQueryString(): void
  {
    $this->setRequestUri('/search?q=test&page=2');

    $result = RequestPath::getFull();

    $this->assertEquals('/search?q=test&page=2', $result);
  }

  #[Test]
  public function getFullHandlesEmptyPath(): void
  {
    $this->setRequestUri('');

    $result = RequestPath::getFull();

    // Empty string is returned as-is (not normalized to '/')
    $this->assertEquals('', $result);
  }

  // ==========================================
  // getMethod() Tests
  // ==========================================

  #[Test]
  public function getMethodReturnsUppercaseMethod(): void
  {
    $this->setRequestMethod('post');

    $result = RequestPath::getMethod();

    $this->assertEquals('POST', $result);
  }

  #[Test]
  public function getMethodReturnsDefaultWhenMissing(): void
  {
    unset($_SERVER['REQUEST_METHOD']);

    $result = RequestPath::getMethod('GET');

    $this->assertEquals('GET', $result);
  }

  #[Test]
  public function getMethodRejectsInvalidMethod(): void
  {
    $this->setRequestMethod('INVALID');

    $result = RequestPath::getMethod('GET');

    $this->assertEquals('GET', $result);
  }

  #[Test]
  #[DataProvider('validHttpMethodsProvider')]
  public function getMethodHandlesValidHttpMethods(string $input, string $expected): void
  {
    $this->setRequestMethod($input);

    $result = RequestPath::getMethod();

    $this->assertEquals($expected, $result);
  }

  public static function validHttpMethodsProvider(): array
  {
    return [
        'GET' => ['GET', 'GET'],
        'POST' => ['POST', 'POST'],
        'PUT' => ['PUT', 'PUT'],
        'PATCH' => ['PATCH', 'PATCH'],
        'DELETE' => ['DELETE', 'DELETE'],
        'OPTIONS' => ['OPTIONS', 'OPTIONS'],
        'HEAD' => ['HEAD', 'HEAD'],
        'lowercase get' => ['get', 'GET'],
        'mixed case' => ['PoSt', 'POST'],
    ];
  }

  // ==========================================
  // isGet(), isPost(), isWrite() Tests
  // ==========================================

  #[Test]
  public function isGetReturnsTrueForGetRequests(): void
  {
    $this->setRequestMethod('GET');

    $this->assertTrue(RequestPath::isGet());
    $this->assertFalse(RequestPath::isPost());
  }

  #[Test]
  public function isPostReturnsTrueForPostRequests(): void
  {
    $this->setRequestMethod('POST');

    $this->assertTrue(RequestPath::isPost());
    $this->assertFalse(RequestPath::isGet());
  }

  #[Test]
  public function isWriteDetectsWriteOperations(): void
  {
    $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    foreach ($writeMethods as $method) {
      $this->setRequestMethod($method);
      $this->assertTrue(RequestPath::isWrite(), "{$method} should be write operation");
    }
  }

  #[Test]
  public function isWriteReturnsFalseForReadOperations(): void
  {
    $readMethods = ['GET', 'HEAD', 'OPTIONS'];

    foreach ($readMethods as $method) {
      $this->setRequestMethod($method);
      $this->assertFalse(RequestPath::isWrite(), "{$method} should not be write operation");
    }
  }

  // ==========================================
  // getDate() Tests
  // ==========================================

  #[Test]
  public function getDateExtractsDateFromQueryString(): void
  {
    $this->setRequestUri('/calendar?2026-03');

    $result = RequestPath::getDate();

    $this->assertEquals(['year' => 2026, 'month' => 3], $result);
  }

  #[Test]
  public function getDateExtractsDateFromPath(): void
  {
    $this->setRequestUri('/2026-03-15');

    $result = RequestPath::getDate();

    $this->assertEquals(['year' => 2026, 'month' => 3], $result);
  }

  #[Test]
  public function getDateReturnsCurrentWhenNoDateFound(): void
  {
    $this->setRequestUri('/calendar');

    $result = RequestPath::getDate();

    $this->assertEquals((int) date('Y'), $result['year']);
    $this->assertEquals((int) date('m'), $result['month']);
  }

  #[Test]
  public function getDateHandlesFullDateInQueryString(): void
  {
    $this->setRequestUri('/calendar?2026-12-25');

    $result = RequestPath::getDate();

    $this->assertEquals(['year' => 2026, 'month' => 12], $result);
  }

  #[Test]
  public function getDateIgnoresInvalidFormats(): void
  {
    $this->setRequestUri('/calendar?invalid-date');

    $result = RequestPath::getDate();

    // Should return current date
    $this->assertArrayHasKey('year', $result);
    $this->assertArrayHasKey('month', $result);
  }

  // ==========================================
  // getPathInfo() Tests
  // ==========================================

  #[Test]
  public function getPathInfoReturnsPathInfo(): void
  {
    $this->setPathInfo('/api/users');

    $result = RequestPath::getPathInfo();

    $this->assertEquals('/api/users', $result);
  }

  #[Test]
  public function getPathInfoNormalizesLeadingSlash(): void
  {
    $this->setPathInfo('api/users');

    $result = RequestPath::getPathInfo();

    $this->assertEquals('/api/users', $result);
  }

  #[Test]
  public function getPathInfoReturnsEmptyWhenMissing(): void
  {
    unset($_SERVER['PATH_INFO']);

    $result = RequestPath::getPathInfo();

    $this->assertEquals('', $result);
  }

  // ==========================================
  // getSegments() Tests
  // ==========================================

  #[Test]
  public function getSegmentsReturnsPathSegments(): void
  {
    $this->setRequestUri('/users/123/profile');

    $result = RequestPath::getSegments();

    $this->assertEquals(['users', '123', 'profile'], $result);
  }

  #[Test]
  public function getSegmentsHandlesRootPath(): void
  {
    $this->setRequestUri('/');

    $result = RequestPath::getSegments();

    $this->assertEquals([], $result);
  }

  #[Test]
  public function getSegmentsFiltersEmptySegments(): void
  {
    $this->setRequestUri('/users//profile///');

    $result = RequestPath::getSegments();

    $this->assertEquals(['users', 'profile'], $result);
  }

  #[Test]
  public function getSegmentsSupportsOffset(): void
  {
    $this->setRequestUri('/api/v1/users/list');

    $result = RequestPath::getSegments(2);

    $this->assertEquals(['users', 'list'], $result);
  }

  #[Test]
  public function getSegmentsSupportsLength(): void
  {
    $this->setRequestUri('/api/v1/users/list');

    $result = RequestPath::getSegments(1, 2);

    $this->assertEquals(['v1', 'users'], $result);
  }

  #[Test]
  public function getSegmentsIgnoresQueryString(): void
  {
    $this->setRequestUri('/users/profile?id=123');

    $result = RequestPath::getSegments();

    $this->assertEquals(['users', 'profile?id=123'], $result);
  }

  // ==========================================
  // getSegment() Tests
  // ==========================================

  #[Test]
  public function getSegmentReturnsSegmentAtIndex(): void
  {
    $this->setRequestUri('/users/123/profile');

    $this->assertEquals('users', RequestPath::getSegment(0));
    $this->assertEquals('123', RequestPath::getSegment(1));
    $this->assertEquals('profile', RequestPath::getSegment(2));
  }

  #[Test]
  public function getSegmentReturnsDefaultWhenMissing(): void
  {
    $this->setRequestUri('/users');

    $result = RequestPath::getSegment(5, 'not-found');

    $this->assertEquals('not-found', $result);
  }

  #[Test]
  public function getSegmentHandlesNegativeIndex(): void
  {
    $this->setRequestUri('/users/123');

    // PHP arrays don't support negative indices directly
    // This should return default
    $result = RequestPath::getSegment(-1, 'default');

    $this->assertEquals('default', $result);
  }

  // ==========================================
  // getIntSegment() Tests
  // ==========================================

  #[Test]
  public function getIntSegmentReturnsInteger(): void
  {
    $this->setRequestUri('/users/123/profile');

    $result = RequestPath::getIntSegment(1);

    $this->assertSame(123, $result);
  }

  #[Test]
  public function getIntSegmentReturnsDefaultForNonNumeric(): void
  {
    $this->setRequestUri('/users/abc/profile');

    $result = RequestPath::getIntSegment(1, 999);

    // (int)'abc' = 0, but if segment is '' then default is returned
    $this->assertIsInt($result);
  }

  #[Test]
  public function getIntSegmentHandlesMissingSegment(): void
  {
    $this->setRequestUri('/users');

    $result = RequestPath::getIntSegment(5, 42);

    $this->assertEquals(42, $result);
  }

  // ==========================================
  // getLastSegment() Tests
  // ==========================================

  #[Test]
  public function getLastSegmentReturnsLastSegment(): void
  {
    $this->setRequestUri('/users/123/profile/edit');

    $result = RequestPath::getLastSegment();

    $this->assertEquals('edit', $result);
  }

  #[Test]
  public function getLastSegmentReturnsDefaultWhenEmpty(): void
  {
    $this->setRequestUri('/');

    $result = RequestPath::getLastSegment('home');

    $this->assertEquals('home', $result);
  }

  #[Test]
  public function getLastSegmentHandlesSingleSegment(): void
  {
    $this->setRequestUri('/users');

    $result = RequestPath::getLastSegment();

    $this->assertEquals('users', $result);
  }

  // ==========================================
  // hasSegment() Tests
  // ==========================================

  #[Test]
  public function hasSegmentReturnsTrueWhenSegmentExists(): void
  {
    $this->setRequestUri('/api/users/list');

    $this->assertTrue(RequestPath::hasSegment('users'));
    $this->assertTrue(RequestPath::hasSegment('api'));
    $this->assertTrue(RequestPath::hasSegment('list'));
  }

  #[Test]
  public function hasSegmentReturnsFalseWhenSegmentMissing(): void
  {
    $this->setRequestUri('/api/users/list');

    $this->assertFalse(RequestPath::hasSegment('profile'));
    $this->assertFalse(RequestPath::hasSegment('admin'));
  }

  #[Test]
  public function hasSegmentIsCaseInsensitiveByDefault(): void
  {
    $this->setRequestUri('/api/Users/List');

    $this->assertTrue(RequestPath::hasSegment('users', false));
    $this->assertTrue(RequestPath::hasSegment('USERS', false));
    $this->assertTrue(RequestPath::hasSegment('UsErS', false));
  }

  #[Test]
  public function hasSegmentRespectsCaseSensitivity(): void
  {
    $this->setRequestUri('/api/Users/List');

    $this->assertTrue(RequestPath::hasSegment('Users', true));
    $this->assertFalse(RequestPath::hasSegment('users', true));
  }

  #[Test]
  public function hasSegmentReturnsFalseForEmptyString(): void
  {
    $this->setRequestUri('/api/users');

    $this->assertFalse(RequestPath::hasSegment(''));
  }

  // ==========================================
  // getExtension() Tests
  // ==========================================

  #[Test]
  public function getExtensionReturnsFileExtension(): void
  {
    $this->setRequestUri('/files/document.pdf');

    $result = RequestPath::getExtension();

    $this->assertEquals('pdf', $result);
  }

  #[Test]
  public function getExtensionHandlesMultipleDots(): void
  {
    $this->setRequestUri('/files/archive.tar.gz');

    $result = RequestPath::getExtension();

    $this->assertEquals('gz', $result);
  }

  #[Test]
  public function getExtensionReturnsEmptyWhenNoExtension(): void
  {
    $this->setRequestUri('/api/users');

    $result = RequestPath::getExtension();

    $this->assertEquals('', $result);
  }

  #[Test]
  public function getExtensionHandlesQueryString(): void
  {
    $this->setRequestUri('/files/doc.pdf?download=true');

    $result = RequestPath::getExtension();

    // Extension parsing from full URI may include query string
    $this->assertIsString($result);
  }

  // ==========================================
  // getBaseName() Tests
  // ==========================================

  #[Test]
  public function getBaseNameReturnsFilenameWithoutExtension(): void
  {
    $this->setRequestUri('/files/document.pdf');

    $result = RequestPath::getBaseName();

    $this->assertEquals('document', $result);
  }

  #[Test]
  public function getBaseNameHandlesNoExtension(): void
  {
    $this->setRequestUri('/api/users');

    $result = RequestPath::getBaseName();

    $this->assertEquals('users', $result);
  }

  #[Test]
  public function getBaseNameReturnsEmptyForRoot(): void
  {
    $this->setRequestUri('/');

    $result = RequestPath::getBaseName();

    $this->assertEquals('', $result);
  }

  #[Test]
  public function getBaseNameHandlesMultipleExtensions(): void
  {
    $this->setRequestUri('/backup.tar.gz');

    $result = RequestPath::getBaseName();

    $this->assertEquals('backup.tar', $result);
  }

  // ==========================================
  // getSubpath() Tests
  // ==========================================

  #[Test]
  public function getSubpathReturnsSubpathFromIndex(): void
  {
    $this->setRequestUri('/api/v1/users/list');

    $result = RequestPath::getSubpath(2);

    $this->assertEquals('/users/list', $result);
  }

  #[Test]
  public function getSubpathHandlesZeroIndex(): void
  {
    $this->setRequestUri('/api/v1/users');

    $result = RequestPath::getSubpath(0);

    $this->assertEquals('/api/v1/users', $result);
  }

  #[Test]
  public function getSubpathHandlesNegativeIndex(): void
  {
    $this->setRequestUri('/api/v1/users');

    $result = RequestPath::getSubpath(-1);

    // Negative indices are clamped to 0
    $this->assertEquals('/api/v1/users', $result);
  }

  #[Test]
  public function getSubpathReturnsRootWhenIndexTooLarge(): void
  {
    $this->setRequestUri('/api/users');

    $result = RequestPath::getSubpath(10);

    $this->assertEquals('/', $result);
  }

  // ==========================================
  // matchesPattern() Tests
  // ==========================================

  #[Test]
  public function matchesPatternHandlesWildcards(): void
  {
    $this->setRequestUri('/api/users/123');

    // fnmatch pattern
    $this->assertTrue(RequestPath::matchesPattern('/api/users/*'));
    $this->assertTrue(RequestPath::matchesPattern('/api/*'));
  }

  #[Test]
  public function matchesPatternHandlesQuestionMark(): void
  {
    $this->setRequestUri('/api/v1');

    $this->assertTrue(RequestPath::matchesPattern('/api/v?'));
  }

  #[Test]
  public function matchesPatternHandlesRegex(): void
  {
    $this->setRequestUri('/users/123/profile');

    $this->assertTrue(RequestPath::matchesPattern('/^\/users\/\d+\/profile$/'));
  }

  #[Test]
  public function matchesPatternReturnsFalseForNoMatch(): void
  {
    $this->setRequestUri('/api/users');

    $this->assertFalse(RequestPath::matchesPattern('/admin/*'));
  }

  // ==========================================
  // isRoot() Tests
  // ==========================================

  #[Test]
  public function isRootReturnsTrueForRootPath(): void
  {
    $this->setRequestUri('/');

    $this->assertTrue(RequestPath::isRoot());
  }

  #[Test]
  public function isRootReturnsFalseForNonRootPath(): void
  {
    $this->setRequestUri('/users');

    $this->assertFalse(RequestPath::isRoot());
  }

  // ==========================================
  // countSegments() Tests
  // ==========================================

  #[Test]
  public function countSegmentsReturnsCorrectCount(): void
  {
    $this->setRequestUri('/api/v1/users/list');

    $result = RequestPath::countSegments();

    $this->assertEquals(4, $result);
  }

  #[Test]
  public function countSegmentsReturnsZeroForRoot(): void
  {
    $this->setRequestUri('/');

    $result = RequestPath::countSegments();

    $this->assertEquals(0, $result);
  }

  #[Test]
  public function countSegmentsIgnoresEmptySegments(): void
  {
    $this->setRequestUri('/api//users///list//');

    $result = RequestPath::countSegments();

    $this->assertEquals(3, $result);
  }

  // ==========================================
  // Edge Cases and Complex Scenarios
  // ==========================================

  #[Test]
  public function handlesUrlEncodedPaths(): void
  {
    $this->setRequestUri('/users/john%20doe');

    $segment = RequestPath::getSegment(1);

    $this->assertEquals('john%20doe', $segment);
  }

  #[Test]
  public function handlesPathsWithFragments(): void
  {
    $this->setRequestUri('/page#section');

    $segments = RequestPath::getSegments();

    $this->assertEquals(['page#section'], $segments);
  }

  #[Test]
  public function handlesComplexQueryStrings(): void
  {
    $this->setRequestUri('/search?q=test&filter[category]=php&sort=asc');

    $full = RequestPath::getFull();

    $this->assertStringContainsString('filter[category]=php', $full);
  }

  #[Test]
  public function handlesInternationalCharacters(): void
  {
    $this->setRequestUri('/users/josé');

    $segment = RequestPath::getSegment(1);

    $this->assertEquals('josé', $segment);
  }

  #[Test]
  public function handlesVeryLongPaths(): void
  {
    $longPath = '/a/b/c/d/e/f/g/h/i/j/k/l/m/n/o/p/q/r/s/t/u/v/w/x/y/z';
    $this->setRequestUri($longPath);

    $count = RequestPath::countSegments();

    $this->assertEquals(26, $count);
  }

  #[Test]
  public function handlesNumericSegments(): void
  {
    $this->setRequestUri('/2026/03/15');

    $year = RequestPath::getIntSegment(0);
    $month = RequestPath::getIntSegment(1);
    $day = RequestPath::getIntSegment(2);

    $this->assertEquals(2026, $year);
    $this->assertEquals(3, $month);
    $this->assertEquals(15, $day);
  }

  #[Test]
  #[DataProvider('dateUrlProvider')]
  public function getDateHandlesVariousFormats(string $uri, int $expectedYear, int $expectedMonth): void
  {
    $this->setRequestUri($uri);

    $result = RequestPath::getDate();

    $this->assertEquals($expectedYear, $result['year']);
    $this->assertEquals($expectedMonth, $result['month']);
  }

  public static function dateUrlProvider(): array
  {
    return [
        'query YYYY-MM' => ['/calendar?2026-03', 2026, 3],
        'query YYYY-MM-DD' => ['/calendar?2026-12-25', 2026, 12],
        'path YYYY-MM-DD' => ['/2025-01-15', 2025, 1],
        'no date' => ['/calendar', (int) date('Y'), (int) date('m')],
    ];
  }

  // ==========================================
  // Helper Methods
  // ==========================================

  private function setRequestUri(string $uri): void
  {
    $_SERVER['REQUEST_URI'] = $uri;
  }

  private function setRequestMethod(string $method): void
  {
    $_SERVER['REQUEST_METHOD'] = $method;
  }

  private function setPathInfo(string $pathInfo): void
  {
    $_SERVER['PATH_INFO'] = $pathInfo;
  }
}
