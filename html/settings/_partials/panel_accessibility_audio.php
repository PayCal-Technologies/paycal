<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel settings_card_group" id="panel-audio">
  <form id="account_audio_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/audio/update/'); ?>" aria-label="<?php echo settings_index_i18n('AUDIO_PREFS'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent settings_card_title"><?php echo settings_index_i18n('AUDIO'); ?></h2>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('CUES'); ?></label>
      <div class="w75">
        <div id="audio_feedback_group" class="radio_group" role="radiogroup" aria-label="<?php echo settings_index_i18n('SETTINGS_AUDIO_FEEDBACK_MODE_ARIA'); ?>" data-hover-help="Cues off disables spoken feedback and locks voice selection.">
          <input class="radio" type="radio" id="audio_feedback_none" name="audio_feedback" value="none" data-tts="Off" <?php echo ('none' === $user->audio_feedback) ? 'checked' : ''; ?>>
          <label for="audio_feedback_none">Off</label>
          <input class="radio" type="radio" id="audio_feedback_all" name="audio_feedback" value="all" data-tts="On" <?php echo (('all' === $user->audio_feedback) || ('base' === $user->audio_feedback) || ('' === (string) $user->audio_feedback)) ? 'checked' : ''; ?>>
          <label for="audio_feedback_all">On</label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label for="voice_volume" class="w25"><?php echo settings_index_i18n('SETTINGS_VOICE_VOLUME_LABEL'); ?></label>
      <div class="w75">
        <div class="proximity_slider_wrap" data-hover-help="<?php echo settings_index_i18n('SETTINGS_VOICE_VOLUME_HOVER'); ?>">
          <input
            type="range"
            id="voice_volume"
            name="voice_volume"
            min="0"
            max="100"
            step="5"
            value="<?php echo $voiceVolumePercent; ?>"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="<?php echo $voiceVolumePercent; ?>"
            aria-label="<?php echo settings_index_i18n('SETTINGS_VOICE_VOLUME_LABEL'); ?>"
          >
          <output for="voice_volume" id="voice_volume_output"><?php echo $voiceVolumePercent; ?>%</output>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('VOICE'); ?></label>
      <div id="voice_picker" class="w75" aria-describedby="voice_picker_disabled_hint" data-hover-help="Voice changes speech output only, never your saved data.">
        <div class="radio_group">
          <input class="radio" type="radio" id="voice_google_en_us_1" name="voice" value="google_en_us_1" data-tts="Google US Voice 1" <?php echo (($user->voice ?? 'system_default') === 'google_en_us_1') ? 'checked' : ''; ?>>
          <label for="voice_google_en_us_1">US Voice 1</label>
          <input class="radio" type="radio" id="voice_google_en_us_2" name="voice" value="google_en_us_2" data-tts="Google US Voice 2" <?php echo (($user->voice ?? 'system_default') === 'google_en_us_2') ? 'checked' : ''; ?>>
          <label for="voice_google_en_us_2">US Voice 2</label>
          <input class="radio" type="radio" id="voice_google_en_ca_1" name="voice" value="google_en_ca_1" data-tts="Google Canada Voice 1" <?php echo (($user->voice ?? 'system_default') === 'google_en_ca_1') ? 'checked' : ''; ?>>
          <label for="voice_google_en_ca_1">CA Voice 1</label>
          <input class="radio" type="radio" id="voice_system_default" name="voice" value="system_default" data-tts="System Default" <?php echo (($user->voice ?? 'system_default') === 'system_default') ? 'checked' : ''; ?>>
          <label for="voice_system_default">Default</label>
          <input class="radio" type="radio" id="voice_system_female" name="voice" value="system_female" data-tts="System Female" <?php echo (($user->voice ?? 'system_default') === 'system_female') ? 'checked' : ''; ?>>
          <label for="voice_system_female">Female</label>
          <input class="radio" type="radio" id="voice_system_male" name="voice" value="system_male" data-tts="System Male" <?php echo (($user->voice ?? 'system_default') === 'system_male') ? 'checked' : ''; ?>>
          <label for="voice_system_male">Male</label>
        </div>
        <p id="voice_picker_disabled_hint" class="voice_picker_disabled_hint"><?php echo settings_index_i18n('SETTINGS_VOICE_PICKER_ENABLE_CUES'); ?></p>
        <button type="button" id="settings_voice_preview_btn" class="btn btn_secondary settings_voice_preview_btn"><?php echo settings_index_i18n('SETTINGS_VOICE_PREVIEW_BTN'); ?></button>
      </div>
    </div>
  </form>
</section>
