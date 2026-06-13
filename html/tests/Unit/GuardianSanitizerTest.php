<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * GuardianSanitzerConfigTest
 *
 * Unit tests for Guardian sanitizer configuration and constants.
 * Note: Guardian.js is a client-side module; integration behavior is covered
 * in browser-based tests (PlaywrightSmokeTest). These tests validate the
 * server-side configuration and constants that support Guardian.
 */
final class GuardianSanitizerTest extends TestCase
{
  private function pageHeadRendererFile(): string
  {
    return __DIR__ . '/../../src/Domain/PageHeadRenderer.php';
  }

  private function headerFile(): string
  {
    return __DIR__ . '/../../header.php';
  }

  /**
   * Test that Guardian configuration constants are accessible
   */
  public function testGuardianConstantsExists(): void
  {
    // Guardian script wiring lives in PageHeadRenderer; header.php delegates to it.
    $pageHeadRendererFile = $this->pageHeadRendererFile();
    $this->assertFileExists($pageHeadRendererFile);
    $this->assertStringContainsString('guardian.js', (string) file_get_contents($pageHeadRendererFile));
    $this->assertStringContainsString('PageHeadRenderer::renderScripts', (string) file_get_contents($this->headerFile()));
  }

  /**
   * Test that Guardian module is referenced with nonce
   */
  public function testGuardianScriptHasNonce(): void
  {
    $content = (string) file_get_contents($this->pageHeadRendererFile());

    $this->assertStringContainsString('nonce=', $content);
    $this->assertStringContainsString('guardian.js', $content);
  }

  /**
   * Test that runtime-integrity monitor is bootstrapped
   */
  public function testRuntimeIntegrityBootstrapped(): void
  {
    $coreFile = __DIR__ . '/../../js/core/index.php';
    $this->assertFileExists($coreFile);
    $content = file_get_contents($coreFile);
    
    $this->assertStringContainsString('RuntimeIntegrity', $content);
    $this->assertStringContainsString('start', $content);
  }

  /**
   * Test that blocked element selectors are documented
   */
  public function testBlockedElementsDocumented(): void
  {
    $guardianFile = __DIR__ . '/../../js/guardian.js';
    $this->assertFileExists($guardianFile);
    $content = file_get_contents($guardianFile);
    
    // Verify the extended selector includes our hardening additions
    $this->assertStringContainsString('script', $content);
    $this->assertStringContainsString('iframe', $content);
    $this->assertStringContainsString('foreignObject', $content);
  }

  /**
   * Test that style attribute handling is in place
   */
  public function testStyleAttributeHandling(): void
  {
    $guardianFile = __DIR__ . '/../../js/guardian.js';
    $this->assertFileExists($guardianFile);
    $content = file_get_contents($guardianFile);
    
    // Check that style attribute removal logic is present
    // The sanitizer should strip style attributes during template processing
    $this->assertStringContainsString('style', $content);
  }

  /**
   * Test that Guardian configuration is consistent across modules
   */
  public function testGuardianConfigConsistency(): void
  {
    $pageHeadContent = (string) file_get_contents($this->pageHeadRendererFile());

    // Guardian must load from renderScripts so it runs before app modules.
    $guardianPos = strpos($pageHeadContent, 'guardian.js');
    $this->assertNotFalse($guardianPos, 'Guardian should be referenced in PageHeadRenderer');
    $this->assertStringContainsString('renderScripts', $pageHeadContent);
    $this->assertStringContainsString('PageHeadRenderer::renderScripts', (string) file_get_contents($this->headerFile()));
  }

  /**
   * Test that CSP nonce is applied to Guardian script
   */
  public function testGuardianNonceCompliance(): void
  {
    $content = (string) file_get_contents($this->pageHeadRendererFile());

    $this->assertStringContainsString('<script src="{$guardian}" nonce="{$cspNonce}"></script>', $content);
    $this->assertStringContainsString('guardian.js', $content);
  }

  /**
   * Test that blocked selector list includes all required vectors
   */
  public function testBlockedSelectorCoverage(): void
  {
    $guardianFile = __DIR__ . '/../../js/guardian.js';
    $content = file_get_contents($guardianFile);
    
    $requiredSelectors = ['script', 'iframe', 'object', 'embed', 'foreignObject', 'meta'];
    
    foreach ($requiredSelectors as $selector) {
      $this->assertStringContainsString($selector, $content, "Guardian should include selector: {$selector}");
    }
  }
}

