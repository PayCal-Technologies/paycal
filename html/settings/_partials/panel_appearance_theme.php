<?php declare(strict_types=1);

namespace PayCal\Domain;

$accentPreset = strtolower(trim((string) ($user->accent_preset ?? UserPreferenceDefaults::DEFAULT_ACCENT_PRESET)));
$accentPresets = UserPreferenceDefaults::accentPresets();
if (!array_key_exists($accentPreset, $accentPresets)) {
  $accentPreset = UserPreferenceDefaults::DEFAULT_ACCENT_PRESET;
}
$accentPresetLabel = (string) ($accentPresets[$accentPreset]['label'] ?? $accentPresets[UserPreferenceDefaults::DEFAULT_ACCENT_PRESET]['label']);
$densityPreset = 'comfortable';
if ($textSliderValue <= -2 && $spacingSliderValue <= -3) {
  $densityPreset = 'compact';
} elseif ($textSliderValue >= 2 && $spacingSliderValue >= 3) {
  $densityPreset = 'spacious';
}
$currentTheme = (string) ($user->theme ?? 'paycal_blue');
if ($currentTheme === 'paycal') {
  $currentTheme = 'paycal_black';
}
$currentVariant = (string) ($user->variant ?? 'dark');
$themeCatalog = [
  'Core' => [
    ['value' => 'paycal_blue', 'label' => 'PayCal Blue', 'icon' => 'shield'],
    ['value' => 'paycal_black', 'label' => 'PayCal Black', 'icon' => 'shield'],
    ['value' => 'paycal_red', 'label' => 'PayCal Red', 'icon' => 'shield'],
    ['value' => 'paycal_green', 'label' => 'PayCal Green', 'icon' => 'shield'],
    ['value' => 'paycal_white', 'label' => 'PayCal White', 'icon' => 'shield'],
  ],
  'BeOS Lineage' => [
    ['value' => 'beos', 'label' => 'BeOS', 'icon' => 'window'],
    ['value' => 'haiku', 'label' => 'Haiku', 'icon' => 'spark'],
    ['value' => 'zeta', 'label' => 'Zeta', 'icon' => 'spark'],
  ],
  'Linux Family' => [
    ['value' => 'debian', 'label' => 'Debian', 'icon' => 'swirl'],
    ['value' => 'fedora', 'label' => 'Fedora', 'icon' => 'circle'],
    ['value' => 'mint', 'label' => 'Mint', 'icon' => 'leaf'],
    ['value' => 'linux', 'label' => 'Ubuntu', 'icon' => 'circle'],
  ],
  'Mac OS Family' => [
    ['value' => 'system7', 'label' => 'Mac OS 7', 'icon' => 'monitor'],
    ['value' => 'system8', 'label' => 'Mac OS 8', 'icon' => 'monitor'],
    ['value' => 'macos9', 'label' => 'Mac OS 9', 'icon' => 'monitor'],
    ['value' => 'macos', 'label' => 'Mac OS X', 'icon' => 'monitor'],
  ],
  'Other' => [
    ['value' => 'bluejeans', 'label' => 'Bluejeans', 'icon' => 'waves'],
    ['value' => 'garden', 'label' => 'Garden', 'icon' => 'leaf'],
    ['value' => 'retro', 'label' => 'Retro', 'icon' => 'bolt'],
    ['value' => 'arcade', 'label' => 'Arcade', 'icon' => 'game'],
    ['value' => 'amiga', 'label' => 'Amiga', 'icon' => 'spark'],
    ['value' => 'workbench', 'label' => 'Workbench', 'icon' => 'window'],
    ['value' => 'nextstep', 'label' => 'NeXTSTEP', 'icon' => 'cube'],
    ['value' => 'openstep', 'label' => 'OpenStep', 'icon' => 'cube'],
    ['value' => 'solaris', 'label' => 'Solaris', 'icon' => 'sun'],
    ['value' => 'terminal', 'label' => 'Terminal', 'icon' => 'terminal'],
    ['value' => 'c64', 'label' => 'C64', 'icon' => 'terminal'],
    ['value' => 'irix', 'label' => 'IRIX', 'icon' => 'cube'],
    ['value' => 'os2_warp', 'label' => 'OS/2 Warp', 'icon' => 'orbit'],
    ['value' => 'palm_os', 'label' => 'Palm OS', 'icon' => 'grid'],
    ['value' => 'cyberdeck', 'label' => 'Cyberdeck', 'icon' => 'terminal'],
    ['value' => 'solarpunk', 'label' => 'Solarpunk', 'icon' => 'leaf'],
  ],
  'Sci-Fi' => [
    ['value' => 'space_odyssey', 'label' => '2001', 'icon' => 'orbit'],
    ['value' => 'akira', 'label' => 'Akira', 'icon' => 'bolt'],
    ['value' => 'alien', 'label' => 'Alien', 'icon' => 'orbit'],
    ['value' => 'blade_runner', 'label' => 'Blade Runner', 'icon' => 'city'],
    ['value' => 'dune', 'label' => 'Dune', 'icon' => 'waves'],
    ['value' => 'star_trek', 'label' => 'Star Trek', 'icon' => 'star'],
    ['value' => 'star_wars', 'label' => 'Star Wars', 'icon' => 'star'],
    ['value' => 'fifth_element', 'label' => 'Fifth Element', 'icon' => 'spark'],
    ['value' => 'matrix', 'label' => 'The Matrix', 'icon' => 'terminal'],
    ['value' => 'tron', 'label' => 'TRON', 'icon' => 'grid'],
    ['value' => 'vaporwave', 'label' => 'Vaporwave', 'icon' => 'sun'],
  ],
  'Windows' => [
    ['value' => 'win10', 'label' => 'Windows 10', 'icon' => 'window'],
    ['value' => 'win11', 'label' => 'Windows 11', 'icon' => 'window'],
    ['value' => 'win95', 'label' => 'Windows 95', 'icon' => 'window'],
    ['value' => 'win98', 'label' => 'Windows 98', 'icon' => 'window'],
    ['value' => 'winxp', 'label' => 'Windows XP', 'icon' => 'window'],
  ],
];
$currentThemeLabel = 'PayCal Blue';
foreach ($themeCatalog as $themes) {
  foreach ($themes as $theme) {
    if ($currentTheme === $theme['value']) {
      $currentThemeLabel = (string) $theme['label'];
      break 2;
    }
  }
}
$themeIcon = static function (string $icon): string {
  $attrs = 'viewBox="0 0 24 24" aria-hidden="true" focusable="false"';
  $common = 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';
  return match ($icon) {
    'bolt' => '<svg ' . $attrs . ' ' . $common . '><path d="M13 2 4 14h7l-1 8 10-13h-7l1-7Z"/></svg>',
    'circle' => '<svg ' . $attrs . ' ' . $common . '><circle cx="12" cy="12" r="8"/><path d="M8 12h8M12 8v8"/></svg>',
    'city' => '<svg ' . $attrs . ' ' . $common . '><path d="M4 20V9l5-3v14M9 20V4h6v16M15 20V8l5 3v9"/><path d="M7 11h.01M12 8h.01M17 13h.01"/></svg>',
    'cube' => '<svg ' . $attrs . ' ' . $common . '><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/></svg>',
    'game' => '<svg ' . $attrs . ' ' . $common . '><rect x="4" y="8" width="16" height="10" rx="3"/><path d="M8 13h4M10 11v4M16 12h.01M18 15h.01"/></svg>',
    'grid' => '<svg ' . $attrs . ' ' . $common . '><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 12h16M12 4v16"/></svg>',
    'leaf' => '<svg ' . $attrs . ' ' . $common . '><path d="M5 19c9 0 14-5 14-14C10 5 5 10 5 19Z"/><path d="M5 19 15 9"/></svg>',
    'monitor' => '<svg ' . $attrs . ' ' . $common . '><rect x="4" y="5" width="16" height="12" rx="2"/><path d="M9 21h6M12 17v4"/></svg>',
    'orbit' => '<svg ' . $attrs . ' ' . $common . '><circle cx="12" cy="12" r="3"/><path d="M3 12c3-6 15-6 18 0M3 12c3 6 15 6 18 0"/></svg>',
    'shield' => '<svg ' . $attrs . ' ' . $common . '><path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/><path d="M9 12l2 2 4-5"/></svg>',
    'spark' => '<svg ' . $attrs . ' ' . $common . '><path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z"/></svg>',
    'sun' => '<svg ' . $attrs . ' ' . $common . '><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
    'swirl' => '<svg ' . $attrs . ' ' . $common . '><path d="M18 7c-2-3-7-4-10-1-4 4-1 11 5 11 4 0 6-3 5-6-.8-3-5-4-7-2-2 2-.8 5 2 5"/></svg>',
    'terminal' => '<svg ' . $attrs . ' ' . $common . '><rect x="4" y="5" width="16" height="14" rx="2"/><path d="m8 10 3 2-3 2M13 15h4"/></svg>',
    'waves' => '<svg ' . $attrs . ' ' . $common . '><path d="M3 8c2 0 2 2 4 2s2-2 4-2 2 2 4 2 2-2 4-2 2 2 4 2"/><path d="M3 14c2 0 2 2 4 2s2-2 4-2 2 2 4 2 2-2 4-2 2 2 4 2"/></svg>',
    default => '<svg ' . $attrs . ' ' . $common . '><rect x="5" y="5" width="14" height="14" rx="3"/></svg>',
  };
};

?>
<section class="panel settings_card_group" id="panel-style">
  <form id="account_style_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/style/update/'); ?>" aria-label="<?php echo settings_index_i18n('STYLE_PREFS'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <div class="settings_theme_header">
      <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_APPEARANCE_THEME_TITLE'); ?></h2>
      <div class="settings_mode_toggle" role="group" aria-label="<?php echo settings_index_i18n('VARIANT_PICKER'); ?>">
        <button type="button" class="settings_mode_option<?php if ($currentVariant === 'light') echo ' is-selected'; ?>" data-variant-value="light" aria-pressed="<?php echo $currentVariant === 'light' ? 'true' : 'false'; ?>">
          <?php echo $themeIcon('sun'); ?>
          <span><?php echo settings_index_i18n('LIGHT'); ?></span>
        </button>
        <button type="button" class="settings_mode_option<?php if ($currentVariant === 'dark') echo ' is-selected'; ?>" data-variant-value="dark" aria-pressed="<?php echo $currentVariant === 'dark' ? 'true' : 'false'; ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A7.8 7.8 0 0 1 9.5 4 8.2 8.2 0 1 0 20 14.5Z"/></svg>
          <span><?php echo settings_index_i18n('DARK'); ?></span>
        </button>
      </div>
    </div>
    <input id="theme_picker" type="hidden" name="theme" value="<?php echo htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>">
    <input id="variant_picker" type="hidden" name="variant" value="<?php echo htmlspecialchars($currentVariant, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="settings_theme_grid" role="group" aria-label="<?php echo settings_index_i18n('THEME_PICKER'); ?>">
      <?php foreach ($themeCatalog as $themes) { foreach ($themes as $theme) { ?>
        <button
          type="button"
          class="settings_theme_card<?php if ($currentTheme === $theme['value']) echo ' is-selected'; ?>"
          data-theme-value="<?php echo htmlspecialchars($theme['value'], ENT_QUOTES, 'UTF-8'); ?>"
          data-label="<?php echo htmlspecialchars($theme['label'], ENT_QUOTES, 'UTF-8'); ?>"
          aria-label="<?php echo htmlspecialchars($theme['label'], ENT_QUOTES, 'UTF-8'); ?>"
          aria-pressed="<?php echo $currentTheme === $theme['value'] ? 'true' : 'false'; ?>"
          title="<?php echo htmlspecialchars($theme['label'], ENT_QUOTES, 'UTF-8'); ?>"
        >
          <span class="settings_theme_card_icon"><?php echo $themeIcon((string) $theme['icon']); ?></span>
          <span class="settings_theme_card_name"><?php echo htmlspecialchars($theme['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </button>
      <?php } } ?>
    </div>
    <div class="settings_theme_selected_label" id="selected_theme_label" aria-live="polite">
      Selected: <span><?php echo htmlspecialchars($currentThemeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <div class="flex f_baseline w100">
      <label for="accent_preset" class="w25"><?php echo settings_index_i18n('SETTINGS_ACCENT_PRESET_LABEL'); ?></label>
      <div class="settings_accent_picker w75">
        <input type="hidden" id="accent_preset" name="accent_preset" value="<?php echo htmlspecialchars($accentPreset, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="settings_accent_swatches" id="accent_preset_swatches" role="group" aria-label="<?php echo settings_index_i18n('SETTINGS_ACCENT_PRESET_LABEL'); ?>">
        <?php $accentIndex = 0; foreach ($accentPresets as $value => $accent) { ?>
          <button
            type="button"
            class="settings_accent_swatch<?php if ($accentPreset === $value) { echo ' is-selected'; } ?>"
            data-accent-idx="<?php echo $accentIndex; ?>"
            data-preset="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
            data-label="<?php echo htmlspecialchars((string) $accent['label'], ENT_QUOTES, 'UTF-8'); ?>"
            aria-label="<?php echo htmlspecialchars((string) $accent['label'], ENT_QUOTES, 'UTF-8'); ?>"
            aria-pressed="<?php echo $accentPreset === $value ? 'true' : 'false'; ?>"
            title="<?php echo htmlspecialchars((string) $accent['label'], ENT_QUOTES, 'UTF-8'); ?>"
          ></button>
        <?php $accentIndex++; } ?>
        </div>
        <div class="settings_accent_preview" id="accent_preset_preview" aria-label="Accent preview">
          <div class="settings_accent_preview_window">
            <div class="settings_accent_preview_titlebar">
              <span id="accent_preset_preview_label"><?php echo htmlspecialchars($accentPresetLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="settings_accent_preview_body">
              <div class="settings_accent_preview_calendar" aria-hidden="true">
                <div class="settings_accent_preview_day">19</div>
                <div class="settings_accent_preview_shift">8h</div>
              </div>
              <div class="settings_accent_preview_example" aria-hidden="true">
                <span>abc</span>
                <span>123</span>
                <span>xyz</span>
              </div>
              <div class="settings_accent_preview_report" aria-hidden="true">
                <div class="settings_accent_preview_report_title">Earnings</div>
                <div class="settings_accent_preview_report_value">$1,842.50</div>
                <div class="settings_accent_preview_bar"><span></span></div>
              </div>
              <div class="settings_accent_preview_controls" aria-hidden="true">
                <button type="button" class="settings_accent_preview_button">Example</button>
                <span class="settings_accent_preview_pill">Selected</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_DENSITY_PRESET_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_DENSITY_PRESET_LABEL'); ?>">
          <?php foreach (['compact', 'comfortable', 'spacious'] as $preset) { ?>
            <input type="radio" class="radio" id="density_preset_<?php echo $preset; ?>" name="density_preset_ui" value="<?php echo $preset; ?>"<?php if ($densityPreset === $preset) { echo ' checked'; } ?>>
            <label for="density_preset_<?php echo $preset; ?>"><?php echo settings_index_i18n('SETTINGS_DENSITY_PRESET_' . strtoupper($preset)); ?></label>
          <?php } ?>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_DEPTH_PRESET_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group settings_depth_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_DEPTH_PRESET_LABEL'); ?>">
          <?php foreach (['flat', 'low', 'standard', 'high'] as $preset) { ?>
            <input type="radio" class="radio" id="depth_preset_<?php echo $preset; ?>" name="depth" value="<?php echo $preset; ?>"<?php if ($depthPreset === $preset) { echo ' checked'; } ?>>
            <label for="depth_preset_<?php echo $preset; ?>"><?php echo settings_index_i18n('SETTINGS_DEPTH_PRESET_' . strtoupper($preset)); ?></label>
          <?php } ?>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label for="text_slider" class="w25"><?php echo settings_index_i18n('TEXT'); ?></label>
      <div class="w75">
        <div class="proximity_slider_wrap" data-hover-help="Text size adjustment. 0 is the default.">
          <input type="range" id="text_slider" name="text" min="-5" max="5" step="1" value="<?php echo $textSliderValue; ?>" aria-valuemin="-5" aria-valuemax="5" aria-valuenow="<?php echo $textSliderValue; ?>" aria-label="Text size adjustment">
          <output for="text_slider" id="text_slider_value"><?php echo htmlspecialchars($textSliderLabel, ENT_QUOTES, 'UTF-8'); ?></output>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label for="spacing_slider" class="w25"><?php echo settings_index_i18n('SPACING'); ?></label>
      <div class="w75">
        <div class="proximity_slider_wrap" data-hover-help="Spacing adjustment. 0 is the default.">
          <input type="range" id="spacing_slider" name="spacing" min="-5" max="5" step="1" value="<?php echo $spacingSliderValue; ?>" aria-valuemin="-5" aria-valuemax="5" aria-valuenow="<?php echo $spacingSliderValue; ?>" aria-label="Spacing adjustment">
          <output for="spacing_slider" id="spacing_slider_value"><?php echo htmlspecialchars($spacingSliderLabel, ENT_QUOTES, 'UTF-8'); ?></output>
        </div>
      </div>
    </div>

  </form>
</section>
