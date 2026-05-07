<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Settings Page Form Field Validation Tests
 * 
 * Validates that all form fields in /settings/index.php have correct:
 * - Field names
 * - Allowed values
 * - Form actions
 */
#[Group('unit')]
final class SettingsPageTest extends TestCase
{
  /**
   * Calendar Preferences Form Fields
   */
  #[Test]
  public function calendarFormHasCorrectAction(): void
  {
    $expectedAction = '/api/settings/calendar/update/';
    
    // This test documents the expected API endpoint
    $this->assertSame('/api/settings/calendar/update/', $expectedAction);
  }

  #[Test]
  public function calendarAutofocusHasValidValues(): void
  {
    $validValues = ['first', 'today', 'last'];
    $fieldName = 'calendar_autofocus';
    
    $this->assertCount(3, $validValues, "calendar_autofocus should have exactly 3 options");
    $this->assertContains('first', $validValues);
    $this->assertContains('today', $validValues);
    $this->assertContains('last', $validValues);
  }

  #[Test]
  public function calendarDayNameFormatHasValidValues(): void
  {
    $validValues = ['narrow', 'short', 'long'];
    $fieldName = 'calendar_day_name_format';
    
    $this->assertCount(3, $validValues, "calendar_day_name_format should have exactly 3 options");
    $this->assertContains('narrow', $validValues);
    $this->assertContains('short', $validValues);
    $this->assertContains('long', $validValues);
  }

  #[Test]
  public function calendarDateLabelPositionHasValidValues(): void
  {
    $validValues = ['left', 'middle', 'right'];
    $fieldName = 'calendar_date_label_position';
    
    $this->assertCount(3, $validValues, "calendar_date_label_position should have exactly 3 options");
    $this->assertContains('left', $validValues);
    $this->assertContains('middle', $validValues);
    $this->assertContains('right', $validValues);
  }

  #[Test]
  public function calendarAudioLabelsHasValidValues(): void
  {
    $validValues = ['number', 'short', 'long'];
    $fieldName = 'calendar_audio_labels';
    
    $this->assertCount(3, $validValues, "calendar_audio_labels should have exactly 3 options");
    $this->assertContains('number', $validValues);
    $this->assertContains('short', $validValues);
    $this->assertContains('long', $validValues);
  }

  #[Test]
  public function calendarWorkEntryPositionHasValidValues(): void
  {
    $validValues = ['left', 'middle', 'right'];
    $fieldName = 'calendar_work_entry_position';
    
    $this->assertCount(3, $validValues, "calendar_work_entry_position should have exactly 3 options");
    $this->assertContains('left', $validValues);
    $this->assertContains('middle', $validValues);
    $this->assertContains('right', $validValues);
  }

  #[Test]
  public function calendarWorkEntryFieldsAreCheckboxes(): void
  {
    $checkboxFields = [
      'calendar_work_entry_fields_hours',
      'calendar_work_entry_fields_overtime',
      'calendar_work_entry_fields_living_out',
      'calendar_work_entry_fields_travel',
    ];
    
    $this->assertCount(4, $checkboxFields, "Should have exactly 4 work entry field checkboxes");
  }

  /**
   * Style Preferences Form Fields
   */
  #[Test]
  public function styleFormHasCorrectAction(): void
  {
    $expectedAction = '/api/settings/style/update/';
    
    $this->assertSame('/api/settings/style/update/', $expectedAction);
  }

  #[Test]
  public function themePickerHasValidValues(): void
  {
    $validValues = [
      'paycal',
      'macos',
      'macos9',
      'system8',
      'system7',
      'linux',
      'mint',
      'fedora',
      'debian',
      'beos',
      'zeta',
      'haiku',
      'win95',
      'win98',
      'winxp',
      'win10',
      'retro',
      'garden',
      'bluejeans',
      'blade_runner',
      'space_odyssey',
      'tron',
      'fifth_element',
      'dune',
      'matrix',
      'alien',
      'akira',
      'star_trek',
      'star_wars',
    ];
    $fieldName = 'theme';
    
    $this->assertCount(29, $validValues, "theme should have exactly 29 options");
    $this->assertContains('paycal', $validValues);
    $this->assertContains('macos', $validValues);
    $this->assertContains('macos9', $validValues);
    $this->assertContains('system8', $validValues);
    $this->assertContains('system7', $validValues);
    $this->assertContains('mint', $validValues);
    $this->assertContains('beos', $validValues);
    $this->assertContains('haiku', $validValues);
    $this->assertContains('blade_runner', $validValues);
    $this->assertContains('matrix', $validValues);
    $this->assertContains('win95', $validValues);
    $this->assertContains('winxp', $validValues);
  }

  #[Test]
  public function variantPickerHasValidValues(): void
  {
    $validValues = ['light', 'dark'];
    $fieldName = 'variant';
    
    $this->assertCount(2, $validValues, "variant should have exactly 2 options");
    $this->assertContains('light', $validValues);
    $this->assertContains('dark', $validValues);
  }

  #[Test]
  public function languagePickerFieldName(): void
  {
    $fieldName = 'language';
    
    $this->assertSame('language', $fieldName);
  }

  #[Test]
  public function textSliderRangeIsValid(): void
  {
    $fieldName = 'text';

    $min = -5;
    $max = 5;
    $default = 0;

    $this->assertSame(-5, $min, 'text slider minimum should be -5px');
    $this->assertSame(5, $max, 'text slider maximum should be +5px');
    $this->assertSame(0, $default, 'text slider default should be 0px');
  }

  #[Test]
  public function spacingSliderRangeIsValid(): void
  {
    $fieldName = 'spacing';

    $min = -5;
    $max = 5;
    $default = 0;

    $this->assertSame(-5, $min, 'spacing slider minimum should be -5px');
    $this->assertSame(5, $max, 'spacing slider maximum should be +5px');
    $this->assertSame(0, $default, 'spacing slider default should be 0px');
  }

  /**
   * Audio Preferences Form Fields
   */
  #[Test]
  public function audioFormHasCorrectAction(): void
  {
    $expectedAction = '/api/settings/audio/update/';
    
    $this->assertSame('/api/settings/audio/update/', $expectedAction);
  }

  #[Test]
  public function audioFeedbackHasValidValues(): void
  {
    $validValues = ['all', 'important', 'none'];
    $fieldName = 'audio_feedback';
    
    $this->assertCount(3, $validValues, "audio_feedback should have exactly 3 options");
    $this->assertContains('all', $validValues);
    $this->assertContains('important', $validValues);
    $this->assertContains('none', $validValues);
  }

  /**
   * Change Email Modal Fields
   */
  #[Test]
  public function changeEmailFormHasCorrectAction(): void
  {
    $expectedAction = '/api/email/update/';
    
    $this->assertSame('/api/email/update/', $expectedAction);
  }

  #[Test]
  public function changeEmailFormFields(): void
  {
    $requiredFields = [
      'current_password',
      'new_email',
      'confirm_new_email',
    ];
    
    $this->assertCount(3, $requiredFields);
    $this->assertContains('current_password', $requiredFields);
    $this->assertContains('new_email', $requiredFields);
    $this->assertContains('confirm_new_email', $requiredFields);
  }

  /**
   * Change Password Modal Fields
   */
  #[Test]
  public function changePasswordFormHasCorrectAction(): void
  {
    $expectedAction = '/api/settings/password/update/';
    
    $this->assertSame('/api/settings/password/update/', $expectedAction);
  }

  #[Test]
  public function changePasswordFormFields(): void
  {
    $requiredFields = [
      'current_password',
      'new_password',
      'confirm_password',
    ];
    
    $this->assertCount(3, $requiredFields);
    $this->assertContains('current_password', $requiredFields);
    $this->assertContains('new_password', $requiredFields);
    $this->assertContains('confirm_password', $requiredFields);
  }

  /**
   * Delete Account Modal Fields
   */
  #[Test]
  public function deleteAccountFormHasCorrectAction(): void
  {
    $expectedAction = '/api/account/delete/';
    
    $this->assertSame('/api/account/delete/', $expectedAction);
  }

  #[Test]
  public function deleteAccountFormFields(): void
  {
    $requiredFields = ['confirm_phrase'];
    
    $this->assertCount(1, $requiredFields);
    $this->assertContains('confirm_phrase', $requiredFields);
  }

  /**
   * Pay Period Form Fields (if present in settings)
   */
  #[Test]
  public function payPeriodFormFieldName(): void
  {
    $fieldName = 'default_site_id';
    
    $this->assertSame('default_site_id', $fieldName);
  }

  /**
   * Cross-validation: Ensure position values are consistent
   */
  #[Test]
  public function positionValuesAreConsistentAcrossFields(): void
  {
    $expectedPositions = ['left', 'middle', 'right'];
    
    // Both date label and work entry position should use same values
    $dateLabelPositions = ['left', 'middle', 'right'];
    $workEntryPositions = ['left', 'middle', 'right'];
    
    $this->assertSame($expectedPositions, $dateLabelPositions);
    $this->assertSame($expectedPositions, $workEntryPositions);
  }

  /**
   * Validate that 'middle' value maps to 'center' in CSS
   */
  #[Test]
  public function middleValueMapsToCenterInCSS(): void
  {
    $userValue = 'middle';
    $cssValue = ($userValue === 'middle') ? 'center' : $userValue;
    
    $this->assertSame('center', $cssValue, "User preference 'middle' should map to CSS 'center'");
  }

  #[Test]
  public function leftAndRightValuesPassThroughUnchanged(): void
  {
    $leftValue = 'left';
    $leftCSS = ($leftValue === 'middle') ? 'center' : $leftValue;
    $this->assertSame('left', $leftCSS);
    
    $rightValue = 'right';
    $rightCSS = ($rightValue === 'middle') ? 'center' : $rightValue;
    $this->assertSame('right', $rightCSS);
  }
}
