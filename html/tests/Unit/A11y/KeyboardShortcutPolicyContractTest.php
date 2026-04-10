<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class KeyboardShortcutPolicyContractTest extends TestCase
{
  #[Test]
  public function globalShortcutHandlerGuardsTypingAndOpenDialogs(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $coreJsPath = $htmlRoot . '/js/core/index.php';
    $coreJs = (string) file_get_contents($coreJsPath);

    $this->assertStringContainsString('const isEditableTarget = (target)', $coreJs);
    $this->assertStringContainsString('const hasOpenDialog = () => !!document.querySelector("dialog[open]")', $coreJs);
    $this->assertStringContainsString('if (isEditableTarget(e.target) || hasOpenDialog()) return;', $coreJs);
  }

  #[Test]
  public function keyboardShortcutDocsStateApplicationExceptionAndSafeguards(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $projectRoot = dirname($htmlRoot);

    $accessibilityPage = (string) file_get_contents($htmlRoot . '/transparency/accessibility/index.php');
    $shortcutModal = (string) file_get_contents($projectRoot . '/templates/keyboard-shortcuts.php');

    $this->assertStringContainsString('Single-key shortcuts (C, R, S, E, A, H, N, P, ?)', $accessibilityPage);
    $this->assertStringContainsString('Shortcuts do not fire while typing in inputs or when dialogs are open', $accessibilityPage);
    $this->assertStringContainsString('Shortcut Safeguards', $shortcutModal);
    $this->assertStringContainsString('Single-key page shortcuts are suppressed while typing in inputs and while dialogs are open.', $shortcutModal);
  }
}
