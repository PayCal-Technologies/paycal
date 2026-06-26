<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class CompressionConfigContractTest extends TestCase
{
  #[Test]
  public function nginxCompressionSnippetEnablesGzipForTextResponses(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $nginxConf = (string) file_get_contents($projectRoot . '/docs/nginx/compression.conf');

    $this->assertStringContainsString('gzip on;', $nginxConf);
    $this->assertStringContainsString('gzip_vary on;', $nginxConf);
    $this->assertStringContainsString('gzip_min_length 256;', $nginxConf);
    $this->assertStringContainsString('text/html', $nginxConf);
    $this->assertStringContainsString('text/css', $nginxConf);
    $this->assertStringContainsString('application/javascript', $nginxConf);
    $this->assertStringContainsString('application/json', $nginxConf);
    $this->assertStringContainsString('Vary: Cookie', $nginxConf);
    $this->assertStringContainsString('zlib.output_compression', $nginxConf);
  }
}
