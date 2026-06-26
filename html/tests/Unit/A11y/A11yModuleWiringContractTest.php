<?php declare(strict_types=1);

use PayCal\Domain\Render;
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

    $this->assertStringContainsString("Render::jsStaticURL('js/core/a11y.js')", $coreJs);
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
      'isInvokerOpenControl',
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
    $coreJs = (string) file_get_contents($htmlRoot . '/js/core/index.php');
    $settingsJs = (string) file_get_contents($htmlRoot . '/js/settings/index.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="modal_signout" data-dialog-invoker-bridge', $header);
    $this->assertStringContainsString('data-dialog-open-tts', $header);
    $this->assertStringContainsString('data-dialog-close="modal_signout" commandfor="modal_signout" command="close"', $header);
    $this->assertStringContainsString('id="call_signout_modal"', $header);
    $this->assertStringContainsString('data-dialog-open="modal_signout"', $header);
    $this->assertStringContainsString('commandfor="modal_signout"', $header);
    $this->assertStringContainsString('command="show-modal"', $header);
    $this->assertStringNotContainsString('href="/signout/" id="call_signout_modal"', $header);
    $this->assertStringNotContainsString('addClickAndEnterListener("call_signout_modal"', $coreJs);
    $this->assertStringNotContainsString("addClickAndEnterListener('call_signout_modal'", $settingsJs);
    $this->assertStringContainsString('applyModalOpenEffects', $a11yJs);
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
    $this->assertStringContainsString('data-dialog-open-tts="GoldMaster"', $goldmasterPage);
    $this->assertStringContainsString('data-dialog-close="goldmaster_dialog" commandfor="goldmaster_dialog" command="close"', $goldmasterPage);
    $this->assertStringContainsString('data-dialog-open="goldmaster_dialog" commandfor="goldmaster_dialog" command="show-modal"', $goldmasterPage);
    $this->assertStringContainsString('applyModalOpenEffects', $a11yJs);
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

  #[Test]
  public function businessMembersInfoDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $membersPage = (string) file_get_contents($htmlRoot . '/business/members/index.php');
    $contextHeader = (string) file_get_contents($htmlRoot . '/business/_context_header.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="business_members_info_dialog"', $membersPage);
    $this->assertStringContainsString('data-dialog-invoker-bridge', $membersPage);
    $this->assertStringContainsString('data-dialog-open-tts', $membersPage);
    $this->assertStringContainsString('data-dialog-close-tts', $membersPage);
    $this->assertStringContainsString('data-dialog-close="business_members_info_dialog" commandfor="business_members_info_dialog" command="close"', $membersPage);
    $this->assertStringContainsString('data-dialog-open="business_members_info_dialog"', $contextHeader);
    $this->assertStringContainsString('commandfor="business_members_info_dialog"', $contextHeader);
    $this->assertStringContainsString('command="show-modal"', $contextHeader);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function businessConsentRevokeDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $modals = (string) file_get_contents($htmlRoot . '/settings/_partials/modals.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="modal_business_consent_revoke" data-dialog-invoker-bridge', $modals);
    $this->assertStringContainsString('data-dialog-close-tts', $modals);
    $this->assertStringContainsString('data-dialog-close="modal_business_consent_revoke" commandfor="modal_business_consent_revoke" command="close"', $modals);
    $this->assertStringContainsString('id="business_consent_revoke_cancel_btn"', $modals);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function importConfirmDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $modals = (string) file_get_contents($htmlRoot . '/settings/_partials/modals.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="modal_import_confirm" data-dialog-invoker-bridge', $modals);
    $this->assertStringContainsString('data-dialog-close-tts', $modals);
    $this->assertStringContainsString('data-dialog-close="modal_import_confirm" commandfor="modal_import_confirm" command="close"', $modals);
    $this->assertStringContainsString('id="import_confirm_cancel_btn"', $modals);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function earningsTeamMemberDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $earningsDialog = (string) file_get_contents($htmlRoot . '/reports/_partials/team_earnings_dialog.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="earnings_team_member_dialog"', $earningsDialog);
    $this->assertStringContainsString('data-dialog-invoker-bridge', $earningsDialog);
    $this->assertStringContainsString('data-dialog-close-tts', $earningsDialog);
    $this->assertStringContainsString('data-dialog-close="earnings_team_member_dialog" commandfor="earnings_team_member_dialog" command="close"', $earningsDialog);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function calendarWeekPickerDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $projectRoot = dirname($htmlRoot);
    $weekPickerDialog = (string) file_get_contents($projectRoot . '/templates/calendar-week-picker-dialog.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="modal_cal_week_picker" data-dialog-invoker-bridge', $weekPickerDialog);
    $this->assertStringContainsString('data-dialog-close-tts="__MODAL_TITLE__"', $weekPickerDialog);
    $this->assertStringContainsString('data-dialog-close="modal_cal_week_picker" commandfor="modal_cal_week_picker" command="close"', $weekPickerDialog);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function changeEmailDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $modals = (string) file_get_contents($htmlRoot . '/settings/_partials/modals.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="modal_change_email" data-dialog-invoker-bridge', $modals);
    $this->assertStringContainsString('data-dialog-close-tts', $modals);
    $this->assertStringContainsString('data-dialog-close="modal_change_email" commandfor="modal_change_email" command="close"', $modals);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function payPeriodPreviewDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $modals = (string) file_get_contents($htmlRoot . '/settings/_partials/modals.php');
    $renderPhp = (string) file_get_contents($htmlRoot . '/src/Domain/Render.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString("'id' => 'modal_pay_period_preview'", $modals);
    $this->assertStringContainsString("'invokerBridge' => true", $modals);
    $this->assertStringContainsString("'closeTts' => settings_index_i18n('PAY_PERIOD')", $modals);
    $this->assertStringContainsString('data-dialog-close="modal_pay_period_preview" commandfor="modal_pay_period_preview" command="close"', $modals);
    $this->assertStringContainsString('invokerBridge', $renderPhp);
    $this->assertStringContainsString('closeTts', $renderPhp);
    $this->assertStringContainsString('data-dialog-invoker-bridge', $renderPhp);
    $this->assertStringContainsString('commandfor="', $renderPhp);

    $rendered = Render::dialog([
      'id' => 'modal_pay_period_preview',
      'title' => 'Pay Period',
      'invokerBridge' => true,
      'closeTts' => 'Pay Period',
    ]);
    $this->assertStringContainsString('id="modal_pay_period_preview"', $rendered);
    $this->assertStringContainsString('data-dialog-invoker-bridge', $rendered);
    $this->assertStringContainsString('data-dialog-close-tts="Pay Period"', $rendered);
    $this->assertStringContainsString('data-dialog-close="modal_pay_period_preview" commandfor="modal_pay_period_preview" command="close"', $rendered);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function createSiteDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $siteEditorDialogs = (string) file_get_contents($htmlRoot . '/sites/_partials/site_editor_dialogs.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString("id='modal_create_site' data-dialog-invoker-bridge", $siteEditorDialogs);
    $this->assertStringContainsString('data-dialog-close-tts', $siteEditorDialogs);
    $this->assertStringContainsString("data-dialog-close='modal_create_site' commandfor='modal_create_site' command='close'", $siteEditorDialogs);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function connectionsPersonManageDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $connectPanel = (string) file_get_contents($htmlRoot . '/business/_partials/profile_connect_panel.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertStringContainsString('id="connections_person_manage_dialog" data-dialog-invoker-bridge', $connectPanel);
    $this->assertStringContainsString('data-dialog-close-tts', $connectPanel);
    $this->assertStringContainsString('data-dialog-close="connections_person_manage_dialog" commandfor="connections_person_manage_dialog" command="close"', $connectPanel);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }

  #[Test]
  public function businessesDefinitionsDialogUsesInvokerBridgeGoldenPattern(): void
  {
    $htmlRoot = dirname(__DIR__, 3);
    $dialogs = (string) file_get_contents($htmlRoot . '/business/_partials/dialogs.php');
    $governancePanel = (string) file_get_contents($htmlRoot . '/business/_partials/governance_panel.php');
    $a11yJs = (string) file_get_contents($htmlRoot . '/js/core/a11y.js');
    $capabilitiesJs = (string) file_get_contents($htmlRoot . '/js/core/capabilities.js');

    $this->assertMatchesRegularExpression('/id="businesses_definitions_dialog"[^>]*data-dialog-invoker-bridge/', $dialogs);
    $this->assertStringContainsString('data-dialog-open-tts', $dialogs);
    $this->assertStringContainsString('data-dialog-close-tts', $dialogs);
    $this->assertStringContainsString('data-dialog-close="businesses_definitions_dialog" commandfor="businesses_definitions_dialog" command="close"', $dialogs);
    $this->assertStringContainsString('id="businesses_definitions_help_button"', $governancePanel);
    $this->assertStringContainsString('data-dialog-open="businesses_definitions_dialog"', $governancePanel);
    $this->assertStringContainsString('commandfor="businesses_definitions_dialog"', $governancePanel);
    $this->assertStringContainsString('command="show-modal"', $governancePanel);
    $this->assertStringContainsString('bindDialogInvokerBridge', $a11yJs);
    $this->assertStringContainsString('invokerCommands: detectInvokerCommands()', $capabilitiesJs);
  }
}
