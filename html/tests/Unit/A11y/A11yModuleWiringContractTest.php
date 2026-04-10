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
      'a11y.openModal(id, text);',
      'a11y.closeModal(id, text);',
    ];

    foreach ($expectedDelegates as $delegateSnippet) {
      $this->assertStringContainsString($delegateSnippet, $coreJs);
    }

    $this->assertStringNotContainsString('function ensureDialogAria(', $coreJs);
    $this->assertStringNotContainsString('function ensureDialogCloseButton(', $coreJs);
  }
}
