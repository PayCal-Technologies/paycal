<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\UserRepository;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__.'/../../tests/bootstrap.php';

/**
 * CalendarApiEntryLifecycleTest
 */
#[Group('integration')]
final class CalendarApiEntryLifecycleTest extends TestCase
{
  private string $userUUID;
  private string $siteID;
  private string $dateID;
  private string $sessionHash;

  /** @var array<int, string> */
  private array $tempScripts = [];

  private function validBlob(string $siteId): string
  {
    return base64_encode(json_encode([
      'version' => 1,
      'ciphertext' => base64_encode('calendar-api-test-ciphertext'),
      'nonce' => base64_encode('calendar-api-nonce'),
      'aad' => $siteId,
    ]));
  }

  protected function setUp(): void
  {
    parent::setUp();

    $this->userUUID = 'U' . substr(bin2hex(random_bytes(8)), 0, 9);
    $this->siteID = 'S' . substr(bin2hex(random_bytes(8)), 0, 9);
    $this->dateID = date('Y-m-d');
    $this->sessionHash = bin2hex(random_bytes(16));

    UserRepository::setUser(
      $this->userUUID,
      password_hash('test-password', PASSWORD_DEFAULT),
      'calendar-api-' . substr($this->userUUID, 1) . '@example.test',
      AuthLevel::USER,
      'Calendar API Test User',
      $this->sessionHash,
      '555-000-0000'
    );

    Database::hset(Keys::USER . ':' . $this->userUUID, [
      'email_verified' => '1',
    ]);

    Database::hset(Keys::SITE . ':' . $this->userUUID . ':' . $this->siteID, [
      'site_id' => $this->siteID,
      'site_name' => 'Calendar API Test Site',
      'status' => 'active',
      'wage' => '30.00',
    ]);
  }

  protected function tearDown(): void
  {
    Database::del(Keys::WORK . ':' . $this->userUUID . ':*');
    Database::del(Keys::SITE . ':' . $this->userUUID . ':*');
    Database::del(Keys::USER . ':' . $this->userUUID);
    Database::del(Keys::SESSION . ':' . $this->sessionHash);
    Database::del('user:' . $this->userUUID . ':csrf:calendar:*');

    foreach ($this->tempScripts as $tmpScript) {
      @unlink($tmpScript);
    }

    parent::tearDown();
  }

  public function testCalendarApiCanCreateEntryViaUpdateEndpoint(): void
  {
    $entries = [[
      'date' => $this->dateID,
      'site_id' => $this->siteID,
      'site_name' => 'Calendar API Test Site',
      'encrypted_blob' => $this->validBlob($this->siteID),
    ]];

    $script = '<?php\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../bootstrap/constants.php"), true) . ';\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../bootstrap/Classes.php"), true) . ';\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../src/Controllers/CalendarController.php"), true) . ';\n'
      . '\\PayCal\\Domain\\Authentication::setSession(' . var_export($this->sessionHash, true) . ', ' . var_export($this->userUUID, true) . ');\n'
      . '$_COOKIE["PAYCAL_AUTH"] = ' . var_export($this->sessionHash, true) . ';\n'
      . '$_SERVER["REQUEST_METHOD"] = "POST";\n'
      . '$u = \\PayCal\\Domain\\User::current();\n'
      . '$nonce = $u->generateFormNonce("calendar");\n'
      . '$_POST = [\n'
      . '  "d" => ' . var_export($this->dateID, true) . ',\n'
      . '  "entries" => ' . var_export(json_encode($entries, JSON_UNESCAPED_SLASHES), true) . ',\n'
      . '  "cal_work_save_as_default" => "false",\n'
      . '  "csrf_token" => $nonce\n'
      . '];\n'
      . '$c = new \\PayCal\\Controllers\\CalendarController();\n'
      . '$c->updateCalendar();\n';

    $response = $this->runScript($script);

    $this->assertSame(0, $response['exit'], $response['output']);
    $this->assertIsArray($response['json']);
    $this->assertSame('success', $response['json']['status'] ?? null, $response['output']);

    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $this->dateID . ':' . $this->siteID;
    $saved = Database::hgetall($workKey);

    $this->assertNotEmpty($saved, 'Work entry should exist after API create.');
  }

  public function testCalendarApiCanDeleteEntriesViaDeleteEndpoint(): void
  {
    $created = WorkEntry::updateWorkEntry([
      'd' => $this->dateID,
      's' => $this->siteID,
      'encrypted_blob' => $this->validBlob($this->siteID),
    ], $this->userUUID);

    $this->assertTrue($created, 'Precondition: entry must exist before delete API call.');

    $workPattern = Keys::WORK . ':' . $this->userUUID . ':' . $this->dateID . ':*';
    $this->assertTrue(WorkEntry::zwildcardexists($workPattern));

    $script = '<?php\n'
      . 'if (!function_exists("getallheaders")) { function getallheaders(): array { return ["X-Resource-Id" => ' . var_export($this->dateID, true) . ']; } }\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../bootstrap/constants.php"), true) . ';\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../bootstrap/Classes.php"), true) . ';\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../src/Controllers/CalendarController.php"), true) . ';\n'
      . '\\PayCal\\Domain\\Authentication::setSession(' . var_export($this->sessionHash, true) . ', ' . var_export($this->userUUID, true) . ');\n'
      . '$_COOKIE["PAYCAL_AUTH"] = ' . var_export($this->sessionHash, true) . ';\n'
      . '$_SERVER["REQUEST_METHOD"] = "DELETE";\n'
      . '$c = new \\PayCal\\Controllers\\CalendarController();\n'
      . '$c->delete();\n';

    $response = $this->runScript($script);

    $this->assertSame(0, $response['exit'], $response['output']);
    $this->assertIsArray($response['json']);
    $this->assertSame('success', $response['json']['status'] ?? null, $response['output']);

    $this->assertFalse(WorkEntry::zwildcardexists($workPattern), 'Work entries for day should be deleted.');
  }

  /**
   * @return array{exit:int,output:string,json:array<string,mixed>|null}
   */
  private function runScript(string $content): array
  {
    $tmpScript = $this->writeTempScript($content);
    $this->tempScripts[] = $tmpScript;

    $out = [];
    $exit = 1;
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpScript), $out, $exit);

    $output = implode("\n", $out);
    $json = json_decode($output, true);

    return [
      'exit' => $exit,
      'output' => $output,
      'json' => is_array($json) ? $json : null,
    ];
  }

  private function writeTempScript(string $content): string
  {
    $tmpScript = tempnam(sys_get_temp_dir(), 'calendar_api_test_');
    file_put_contents($tmpScript, str_replace('\\n', PHP_EOL, $content));

    return $tmpScript;
  }
}
