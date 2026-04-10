<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\UserRepository;

require_once __DIR__.'/../../tests/bootstrap.php';

#[Group('integration')]
final class SiteCalendarFlowE2ETest extends TestCase
{
  private string $userUUID;
  private string $sessionHash;
  private string $dateID;

  /** @var array<int, string> */
  private array $tempScripts = [];

  protected function setUp(): void
  {
    parent::setUp();

    $this->userUUID = 'U' . substr(bin2hex(random_bytes(8)), 0, 9);
    $this->sessionHash = bin2hex(random_bytes(16));
    $this->dateID = date('Y-m-d');

    UserRepository::setUser(
      $this->userUUID,
      password_hash('test-password', PASSWORD_DEFAULT),
      'site-calendar-e2e-' . substr($this->userUUID, 1) . '@example.test',
      AuthLevel::USER,
      'Site Calendar E2E User',
      $this->sessionHash,
      '555-111-0000'
    );

    Database::hset(Keys::USER . ':' . $this->userUUID, [
      'email_verified' => '1',
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

  public function testCanCreateSiteThenSaveCalendarEntry(): void
  {
    $createSiteScript = '<?php\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../bootstrap/constants.php"), true) . ';\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../bootstrap/Classes.php"), true) . ';\n'
      . 'require_once ' . var_export(realpath(__DIR__ . "/../../src/Controllers/SitesController.php"), true) . ';\n'
      . '\\PayCal\\Domain\\Authentication::setSession(' . var_export($this->sessionHash, true) . ', ' . var_export($this->userUUID, true) . ');\n'
      . '$_COOKIE["PAYCAL_AUTH"] = ' . var_export($this->sessionHash, true) . ';\n'
      . '$_SERVER["REQUEST_METHOD"] = "POST";\n'
      . '$_POST = [\n'
      . '  "site_name" => "E2E Site",\n'
      . '  "wage" => "35",\n'
      . '  "living_out_allowance" => "0",\n'
      . '  "travel_hours" => "0",\n'
      . '  "province" => "AB",\n'
      . '  "status" => "active"\n'
      . '];\n'
      . '$c = new \\PayCal\\Controllers\\SitesController();\n'
      . '$c->createSite();\n';

    $siteResponse = $this->runScript($createSiteScript);
    $this->assertSame(0, $siteResponse['exit'], $siteResponse['output']);
    $this->assertIsArray($siteResponse['json']);
    $this->assertSame('success', $siteResponse['json']['status'] ?? null, $siteResponse['output']);

    $siteId = (string) (($siteResponse['json']['id'] ?? '') ?: ($siteResponse['json']['data']['id'] ?? ''));
    $this->assertNotSame('', $siteId, 'Site create should return an id.');

    $entries = [[
      'date' => $this->dateID,
      'site_id' => $siteId,
      'site_name' => 'E2E Site',
      'encrypted_blob' => base64_encode(json_encode([
        'version' => 1,
        'ciphertext' => base64_encode('site-calendar-e2e-ciphertext'),
        'nonce' => base64_encode('site-calendar-e2e-nonce'),
        'aad' => $siteId,
      ])),
    ]];

    $calendarScript = '<?php\n'
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

    $calendarResponse = $this->runScript($calendarScript);
    $this->assertSame(0, $calendarResponse['exit'], $calendarResponse['output']);
    $this->assertIsArray($calendarResponse['json']);
    $this->assertSame('success', $calendarResponse['json']['status'] ?? null, $calendarResponse['output']);

    $workKey = Keys::WORK . ':' . $this->userUUID . ':' . $this->dateID . ':' . $siteId;
    $saved = Database::hgetall($workKey);
    $this->assertNotEmpty($saved, 'Work entry should exist after site create + calendar save flow.');
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
    $tmpScript = tempnam(sys_get_temp_dir(), 'site_calendar_e2e_');
    file_put_contents($tmpScript, str_replace('\\n', PHP_EOL, $content));

    return $tmpScript;
  }
}
