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

?>
<section class="panel settings_card_group" id="panel-style">
  <form id="account_style_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/style/update/'); ?>" aria-label="<?php echo settings_index_i18n('STYLE_PREFS'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('SETTINGS_APPEARANCE_THEME_TITLE'); ?></h2>

    <div class="flex f_baseline w100 settings_theme_mode_row">
      <div class="settings_theme_mode_field settings_theme_mode_field--theme">
        <label for="theme_picker" class="settings_theme_mode_label"><?php echo settings_index_i18n('THEME'); ?></label>
        <select id="theme_picker" name="theme" aria-label="<?php echo settings_index_i18n('THEME_PICKER'); ?>" data-hover-help="Theme controls color palette and overall visual mood.">
        <option value="choose" disabled selected><?php echo settings_index_i18n('CHOOSE_A_THEME'); ?></option>
        <option value="" disabled>------ Core ------</option>
        <option value="paycal_blue"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_blue'], true)) echo ' selected'; ?>>PayCal Blue</option>
        <option value="paycal_black"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_black', 'paycal'], true)) echo ' selected'; ?>>PayCal Black</option>
        <option value="paycal_red"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_red'], true)) echo ' selected'; ?>>PayCal Red</option>
        <option value="paycal_green"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_green'], true)) echo ' selected'; ?>>PayCal Green</option>
        <option value="paycal_white"<?php if (in_array(($user->theme ?? 'paycal_blue'), ['paycal_white'], true)) echo ' selected'; ?>>PayCal White</option>

        <option value="" disabled>-- BeOS Lineage --</option>
        <option value="beos"<?php if (($user->theme ?? 'paycal') === 'beos') echo ' selected'; ?>>BeOS</option>
        <option value="haiku"<?php if (($user->theme ?? 'paycal') === 'haiku') echo ' selected'; ?>>Haiku</option>
        <option value="zeta"<?php if (($user->theme ?? 'paycal') === 'zeta') echo ' selected'; ?>>Zeta</option>

        <option value="" disabled>--- Linux Family ---</option>
        <option value="debian"<?php if (($user->theme ?? 'paycal') === 'debian') echo ' selected'; ?>>Debian</option>
        <option value="fedora"<?php if (($user->theme ?? 'paycal') === 'fedora') echo ' selected'; ?>>Fedora</option>
        <option value="mint"<?php if (($user->theme ?? 'paycal') === 'mint') echo ' selected'; ?>>Mint</option>
        <option value="linux"<?php if (($user->theme ?? 'paycal') === 'linux') echo ' selected'; ?>>Ubuntu</option>

        <option value="" disabled>---- Mac OS Family ----</option>
        <option value="system7"<?php if (($user->theme ?? 'paycal') === 'system7') echo ' selected'; ?>>Mac OS 7</option>
        <option value="system8"<?php if (($user->theme ?? 'paycal') === 'system8') echo ' selected'; ?>>Mac OS 8</option>
        <option value="macos9"<?php if (($user->theme ?? 'paycal') === 'macos9') echo ' selected'; ?>>Mac OS 9</option>
        <option value="macos"<?php if (($user->theme ?? 'paycal') === 'macos') echo ' selected'; ?>>Mac OS X</option>

        <option value="" disabled>------ Other ------</option>
        <option value="bluejeans"<?php if (in_array(($user->theme ?? 'paycal'), ['bluejeans', 'denim_dream'], true)) echo ' selected'; ?>>Bluejeans</option>
        <option value="garden"<?php if (in_array(($user->theme ?? 'paycal'), ['garden', 'sweater_weather'], true)) echo ' selected'; ?>>Garden</option>
        <option value="retro"<?php if (($user->theme ?? 'paycal') === 'retro') echo ' selected'; ?>>Retro</option>
        <option value="arcade"<?php if (($user->theme ?? 'paycal') === 'arcade') echo ' selected'; ?>>Arcade</option>

        <option value="" disabled>------ Sci-Fi -----</option>
        <option value="space_odyssey"<?php if (($user->theme ?? 'paycal') === 'space_odyssey') echo ' selected'; ?>>2001: A Space Odyssey</option>
        <option value="akira"<?php if (($user->theme ?? 'paycal') === 'akira') echo ' selected'; ?>>Akira</option>
        <option value="alien"<?php if (($user->theme ?? 'paycal') === 'alien') echo ' selected'; ?>>Alien</option>
        <option value="blade_runner"<?php if (($user->theme ?? 'paycal') === 'blade_runner') echo ' selected'; ?>>Blade Runner</option>
        <option value="dune"<?php if (($user->theme ?? 'paycal') === 'dune') echo ' selected'; ?>>Dune</option>
        <option value="star_trek"<?php if (($user->theme ?? 'paycal') === 'star_trek') echo ' selected'; ?>>Star Trek</option>
        <option value="star_wars"<?php if (($user->theme ?? 'paycal') === 'star_wars') echo ' selected'; ?>>Star Wars</option>
        <option value="fifth_element"<?php if (($user->theme ?? 'paycal') === 'fifth_element') echo ' selected'; ?>>The Fifth Element</option>
        <option value="matrix"<?php if (($user->theme ?? 'paycal') === 'matrix') echo ' selected'; ?>>The Matrix</option>
        <option value="tron"<?php if (($user->theme ?? 'paycal') === 'tron') echo ' selected'; ?>>TRON</option>

        <option value="" disabled>----- Windows -----</option>
        <option value="win10"<?php if (($user->theme ?? 'paycal') === 'win10') echo ' selected'; ?>>Windows 10</option>
        <option value="win95"<?php if (($user->theme ?? 'paycal') === 'win95') echo ' selected'; ?>>Windows 95</option>
        <option value="win98"<?php if (($user->theme ?? 'paycal') === 'win98') echo ' selected'; ?>>Windows 98</option>
        <option value="winxp"<?php if (($user->theme ?? 'paycal') === 'winxp') echo ' selected'; ?>>Windows XP</option>
        </select>
      </div>
      <div class="settings_theme_mode_field settings_theme_mode_field--mode">
        <label for="variant_picker" class="settings_theme_mode_label"><?php echo settings_index_i18n('VARIANT'); ?></label>
        <select id="variant_picker" name="variant" aria-label="<?php echo settings_index_i18n('VARIANT_PICKER'); ?>" data-hover-help="Mode switches between light and dark treatment.">
          <option value="light"<?php if (($user->variant ?? 'dark') === 'light') echo ' selected'; ?>><?php echo settings_index_i18n('LIGHT'); ?></option>
          <option value="dark"<?php if (($user->variant ?? 'dark') === 'dark') echo ' selected'; ?>><?php echo settings_index_i18n('DARK'); ?></option>
        </select>
      </div>
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
              <div class="settings_accent_preview_report" aria-hidden="true">
                <div class="settings_accent_preview_report_title">Earnings</div>
                <div class="settings_accent_preview_report_value">$1,842.50</div>
                <div class="settings_accent_preview_bar"><span></span></div>
              </div>
              <div class="settings_accent_preview_controls" aria-hidden="true">
                <button type="button" class="settings_accent_preview_button">Save</button>
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
