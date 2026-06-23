<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class AdminTestRunnerOutputContractTest extends TestCase
{
  public function testRunnerDoesNotPersistRawPhpunitOutput(): void
  {
    $runApi = (string) file_get_contents(__DIR__ . '/../../api/tests/run.php');
    $wsEndpoint = (string) file_get_contents(__DIR__ . '/../../ws/index.php');

    $this->assertStringNotContainsString("'output' => \$output", $runApi);
    $this->assertStringNotContainsString("'output' => \$fullOutput", $wsEndpoint);
    $this->assertStringContainsString("'rawOutputStored' => false", $runApi);
    $this->assertStringContainsString("'rawOutputStored' => false", $wsEndpoint);
  }

  public function testStoredResultsAreSanitizedBeforeDisplayOrDownload(): void
  {
    $resultsApi = (string) file_get_contents(__DIR__ . '/../../api/tests/results.php');
    $pageController = (string) file_get_contents(__DIR__ . '/../../src/Controllers/TestsPageController.php');
    $testsJs = (string) file_get_contents(__DIR__ . '/../../js/tests/index.php');

    $this->assertStringContainsString("unset(\$lastRun['output']);", $resultsApi);
    $this->assertStringContainsString("unset(\$lastRun['output']);", $pageController);
    $this->assertStringNotContainsString('class="test_output"', $pageController);
    $this->assertStringNotContainsString('TESTS_DASHBOARD_VIEW_OUTPUT', $pageController);
    $this->assertStringNotContainsString('lastRun.output', $testsJs);
  }
}
