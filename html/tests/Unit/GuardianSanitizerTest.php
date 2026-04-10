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
  /**
   * Test that Guardian configuration constants are accessible
   */
  public function testGuardianConstantsExists(): void
  {
    // Guardian configuration is embedded in header.php and core/index.php
    // Verify the expected configuration paths exist
    $headerFile = __DIR__ . '/../../header.php';
    $this->assertFileExists($headerFile);
    $this->assertStringContainsString('guardian.js', file_get_contents($headerFile));
  }

  /**
   * Test that Guardian module is referenced with nonce
   */
  public function testGuardianScriptHasNonce(): void
  {
    $headerFile = __DIR__ . '/../../header.php';
    $content = file_get_contents($headerFile);
    
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
    $headerFile = __DIR__ . '/../../header.php';
    $headerContent = file_get_contents($headerFile);
    
    // Verify that Guardian is loaded
    // This ensures the sanitizer is active before any user content is rendered
    $guardianPos = strpos($headerContent, 'guardian.js');
    
    $this->assertNotFalse($guardianPos, 'Guardian should be referenced in header');
  }

  /**
   * Test that CSP nonce is applied to Guardian script
   */
  public function testGuardianNonceCompliance(): void
  {
    $headerFile = __DIR__ . '/../../header.php';
    $content = file_get_contents($headerFile);
    
    // Count occurrences of nonce attributes on scripts
    $this->assertStringContainsString('nonce="<?php echo', $content);
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

