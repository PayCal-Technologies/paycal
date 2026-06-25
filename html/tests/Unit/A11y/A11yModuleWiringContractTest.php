<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class A11yModuleWiringContractTest extends TestCase
{
  #[Test]
  public function coreModuleImportsAndInstantiatesA11yModule(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $coreJs = (string) file_get_contents($htmlRoot . '/js/core/index.php');

    $this->assertStringContainsString("import A11yModule from '/js/core/a11y.js';", $coreJs);
    $this->assertStringContainsString('const a11y = A11yModule(state, getElement, query, queryAll, textToSpeech, config);', $coreJs);
  }

  #[Test]
  public function coreModalAndFocusHelpersDelegateToA11yModule(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $coreJs = (string) file_get_contents($htmlRoot . '/js/core/index.php');

    $expectedDelegates = [
      'return a11y.getFocusableElements(container);',
      'return a11y.trapFocusWithin(container, event);',
      'a11y.addAudioFocusListener(el, prefix, suffix);',
      'function ensureDialogChrome(dialog) { a11y.ensureDialogChrome(dialog); }',
      'function ensureAllDialogsChrome() { a11y.ensureAllDialogsChrome(); }',
      'function bindAllDialogInvokerBridges() { a11y.bindAllDialogInvokerBridges(); }',
      'a11y.openModal(id, text);',
      'a11y.closeModal(id, text);',
    ];

    foreach ($expectedDelegates as $delegateSnippet) {
      $this->assertStringContainsString($delegateSnippet, $coreJs);
    }

    $this->assertStringNotContainsString('function ensureDialogAria(', $coreJs);
    $this->assertStringNotContainsString('function ensureDialogCloseButton(', $coreJs);
  }

  #[Test]
  public function signOutDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $header = (string) file_get_contents($htmlRoot . '/header.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="modal_signout" data-dialog-invoker-bridge', $header);
    $this->assertStringContainsString('data-dialog-close="modal_signout" commandfor="modal_signout" command="close"', $header);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function sessionTimeoutDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $footer = (string) file_get_contents($htmlRoot . '/footer.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="modal_session_timeout" data-dialog-invoker-bridge', $footer);
    $this->assertStringContainsString('data-dialog-close="modal_session_timeout" commandfor="modal_session_timeout" command="close"', $footer);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function goldmasterDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $goldmasterPage = (string) file_get_contents($htmlRoot . '/admin/goldmaster/index.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="goldmaster_dialog" data-dialog-invoker-bridge', $goldmasterPage);
    $this->assertStringContainsString('data-dialog-close="goldmaster_dialog" commandfor="goldmaster_dialog" command="close"', $goldmasterPage);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function helpDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $projectRoot = dirname($htmlRoot);
    $shortcutModal = (string) file_get_contents($projectRoot . '/templates/keyboard-shortcuts.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString("id='modal_help' data-dialog-invoker-bridge", $shortcutModal);
    $this->assertStringContainsString("data-dialog-close='modal_help' commandfor='modal_help' command='close'", $shortcutModal);
    $this->assertStringContainsString('data-dialog-close="modal_help" commandfor="modal_help" command="close"', $shortcutModal);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }
}
