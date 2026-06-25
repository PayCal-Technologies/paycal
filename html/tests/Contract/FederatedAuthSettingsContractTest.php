<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('contract')]
final class FederatedAuthSettingsContractTest extends TestCase
{
  public function testSecurityFederatedPartialUsesI18nKeys(): void
  {
    $partial = $this->readProjectFile('settings/_partials/panel_security_federated.php');

    $this->assertStringContainsString("settings_index_i18n('SETTINGS_FEDERATED_SECTION_TITLE')", $partial);
    $this->assertStringContainsString("settings_index_i18n('SETTINGS_FEDERATED_SECTION_NOTE')", $partial);
    $this->assertStringNotContainsString('Connect Google to sign in', $partial);
  }

  public function testSettingsJsSupportsGoogleAndAppleFederatedProviders(): void
  {
    $settingsJs = $this->readProjectFile('js/settings/index.php');

    $this->assertStringContainsString("const federatedProviderIds = ['google', 'apple'];", $settingsJs);
    $this->assertStringContainsString('SETTINGS_FEDERATED_CONNECT_FMT', $settingsJs);
    $this->assertStringContainsString('SETTINGS_FEDERATED_DISCONNECT_FMT', $settingsJs);
    $this->assertStringContainsString('/api/v1/auth/federated/start/${encodeURIComponent(providerId)}?mode=link', $settingsJs);
  }

  public function testSigninJsUsesLocalizedFederatedButtonLabels(): void
  {
    $signinJs = $this->readProjectFile('js/signin/index.php');

    $this->assertStringContainsString('AUTH_FEDERATED_CONTINUE_APPLE', $signinJs);
    $this->assertStringContainsString('federatedButtonLabel', $signinJs);
    $this->assertStringContainsString('button_label_key', $signinJs);
  }

  public function testFederatedAuthControllerRegistersApplePostCallback(): void
  {
    $controller = $this->readProjectFile('src/Controllers/FederatedAuthController.php');

    $this->assertStringContainsString("private const SUPPORTED_PROVIDERS = ['google', 'apple'];", $controller);
    $this->assertStringContainsString("#[Route('auth/federated/callback/{provider}', ['POST'])]", $controller);
    $this->assertStringContainsString('claimsForAppleCode', $controller);
  }

  private function readProjectFile(string $relativePath): string
  {
    $path = dirname(__DIR__, 2) . '/' . ltrim($relativePath, '/');
    $this->assertFileExists($path);

    return (string) file_get_contents($path);
  }
}
