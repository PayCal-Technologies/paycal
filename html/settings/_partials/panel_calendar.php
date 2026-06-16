<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
<section class="panel" id="panel-calendar">
  <form id="account_calendar_form" method="POST" action="<?php echo Environment::appURL('api/v1/settings/calendar/update/'); ?>" aria-label="<?php echo settings_index_i18n('SETTINGS_ARIA_CALENDAR_PREFERENCES_FORM'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfNonce; ?>">
    <h2 class="heading-accent"><?php echo settings_index_i18n('CALENDAR'); ?></h2>
    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('FOCUS'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Choose which date the calendar focuses first when opening.">
          <input type="radio" class="radio" id="calendar_autofocus_first" name="calendar_autofocus" value="first" <?php if ('first' === $user->calendar_autofocus) {
            echo 'checked';
          } ?>>
          <label for="calendar_autofocus_first"><?php echo settings_index_i18n('FIRST'); ?></label>
          <input type="radio" class="radio" id="calendar_autofocus_today" name="calendar_autofocus" value="today" <?php if ('today' === $user->calendar_autofocus || 'current' === $user->calendar_autofocus) {
            echo 'checked';
          } ?>>
          <label for="calendar_autofocus_today"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_TODAY'); ?></label>
          <input type="radio" class="radio" id="calendar_autofocus_last" name="calendar_autofocus" value="last" <?php if ('last' === $user->calendar_autofocus) {
            echo 'checked';
          } ?>>
          <label for="calendar_autofocus_last"><?php echo settings_index_i18n('LAST'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_DATE'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Set weekday label length: narrow, short, or long names.">
          <input type="radio" class="radio" id="calendar_day_name_format_narrow" name="calendar_day_name_format" value="narrow" <?php if ('narrow' === $user->calendar_day_name_format) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_format_narrow"><?php echo settings_index_i18n('NARROW'); ?></label>
          <input type="radio" class="radio" id="calendar_day_name_format_short" name="calendar_day_name_format" value="short" <?php if ('short' === $user->calendar_day_name_format) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_format_short"><?php echo settings_index_i18n('SHORT'); ?></label>
          <input type="radio" class="radio" id="calendar_day_name_format_long" name="calendar_day_name_format" value="long" <?php if ('long' === $user->calendar_day_name_format) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_format_long"><?php echo settings_index_i18n('LONG'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_POSITION'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Align weekday headings above the calendar grid.">
          <input type="radio" class="radio" id="calendar_day_name_position_left" name="calendar_day_name_position" value="left" <?php if ('left' === $user->calendar_day_name_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_position_left"><?php echo settings_index_i18n('SETTINGS_POSITION_LEFT'); ?></label>
          <input type="radio" class="radio" id="calendar_day_name_position_middle" name="calendar_day_name_position" value="middle" <?php if ('middle' === $user->calendar_day_name_position || 'center' === $user->calendar_day_name_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_position_middle"><?php echo settings_index_i18n('SETTINGS_POSITION_MIDDLE'); ?></label>
          <input type="radio" class="radio" id="calendar_day_name_position_right" name="calendar_day_name_position" value="right" <?php if ('right' === $user->calendar_day_name_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_day_name_position_right"><?php echo settings_index_i18n('SETTINGS_POSITION_RIGHT'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_LABEL'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Pick where day labels appear inside each calendar cell.">
          <input type="radio" class="radio" id="calendar_date_label_left" name="calendar_date_label_position" value="left" <?php if ('left' === $user->calendar_date_label_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_date_label_left"><?php echo settings_index_i18n('SETTINGS_POSITION_LEFT'); ?></label>
          <input type="radio" class="radio" id="calendar_date_label_middle" name="calendar_date_label_position" value="middle" <?php if ('middle' === $user->calendar_date_label_position || 'center' === $user->calendar_date_label_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_date_label_middle"><?php echo settings_index_i18n('SETTINGS_POSITION_MIDDLE'); ?></label>
          <input type="radio" class="radio" id="calendar_date_label_right" name="calendar_date_label_position" value="right" <?php if ('right' === $user->calendar_date_label_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_date_label_right"><?php echo settings_index_i18n('SETTINGS_POSITION_RIGHT'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_AUDIO'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group calendar_long_pills" data-hover-help="Choose how focused calendar dates are announced: day only, month plus day, or full date with year.">
          <input type="radio" class="radio" id="calendar_audiolabels_number" name="calendar_audio_labels" value="number" <?php if ('number' === $user->calendar_audio_labels) {
            echo 'checked';
          } ?>>
          <label for="calendar_audiolabels_number">Day only (25)</label>
          <input type="radio" class="radio" id="calendar_audiolabels_shortdate" name="calendar_audio_labels" value="short" <?php if ('short' === $user->calendar_audio_labels) {
            echo 'checked';
          } ?>>
          <label for="calendar_audiolabels_shortdate">Month + day (March 25)</label>
          <input type="radio" class="radio" id="calendar_audiolabels_fulldate" name="calendar_audio_labels" value="long" <?php if ('long' === $user->calendar_audio_labels) {
            echo 'checked';
          } ?>>
          <label for="calendar_audiolabels_fulldate">Full date (March 25, 2026)</label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_ENTRIES'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="Choose where work entry values appear in day cells.">
          <input type="radio" class="radio" id="calendar_work_entry_left" name="calendar_work_entry_position" value="left" <?php if ('left' === $user->calendar_work_entry_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_work_entry_left"><?php echo settings_index_i18n('SETTINGS_POSITION_LEFT'); ?></label>
          <input type="radio" class="radio" id="calendar_work_entry_middle" name="calendar_work_entry_position" value="middle" <?php if ('middle' === $user->calendar_work_entry_position || 'center' === $user->calendar_work_entry_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_work_entry_middle"><?php echo settings_index_i18n('SETTINGS_POSITION_MIDDLE'); ?></label>
          <input type="radio" class="radio" id="calendar_work_entry_right" name="calendar_work_entry_position" value="right" <?php if ('right' === $user->calendar_work_entry_position) {
            echo 'checked';
          } ?>>
          <label for="calendar_work_entry_right"><?php echo settings_index_i18n('SETTINGS_POSITION_RIGHT'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_BADGES'); ?></label>
      <div class="w75">
        <div class="calendar_badge_pills" data-hover-help="<?php echo settings_index_i18n('SETTINGS_CALENDAR_BADGES_HOVER'); ?>">
        <input type="checkbox" name="calendar_work_entry_fields_hours" value="1" class="work_entry_field" id="work_entry_hours" <?php if ($user->calendar_work_entry_fields_hours) {
          echo 'checked';
        } ?>>
        <label for="work_entry_hours"><?php echo settings_index_i18n('SETTINGS_CALENDAR_SHOW_HOURS_BADGE'); ?></label>

        <input type="checkbox" name="calendar_work_entry_fields_regular" value="1" class="work_entry_field" id="work_entry_regular" <?php if ($user->calendar_work_entry_fields_regular) {
          echo 'checked';
        } ?>>
        <label for="work_entry_regular"><?php echo settings_index_i18n('SETTINGS_CALENDAR_SHOW_REGULAR_BADGE'); ?></label>

        <input type="checkbox" name="calendar_work_entry_fields_overtime" value="1" class="work_entry_field" id="work_entry_overtime" <?php if ($user->calendar_work_entry_fields_overtime) {
          echo 'checked';
        } ?>>
        <label for="work_entry_overtime">Overtime</label>

        <input type="checkbox" name="calendar_work_entry_fields_living_out" value="1" class="work_entry_field" id="work_entry_living_out" <?php if ($user->calendar_work_entry_fields_living_out) {
          echo 'checked';
        } ?>>
        <label for="work_entry_living_out">Living Out Allowance</label>

        <input type="checkbox" name="calendar_work_entry_fields_travel" value="1" class="work_entry_field" id="work_entry_travel" <?php if ($user->calendar_work_entry_fields_travel) {
          echo 'checked';
        } ?>>
        <label for="work_entry_travel">Travel</label>

        <input type="checkbox" name="calendar_show_gross_badge" value="1" class="work_entry_field" id="calendar_show_gross_badge" <?php if ($user->calendar_show_gross_badge) {
          echo 'checked';
        } ?>>
        <label for="calendar_show_gross_badge"><?php echo settings_index_i18n('SETTINGS_CALENDAR_SHOW_GROSS_BADGE'); ?></label>

        <input type="checkbox" name="calendar_show_net_badge" value="1" class="work_entry_field" id="calendar_show_net_badge" <?php if ($user->calendar_show_net_badge) {
          echo 'checked';
        } ?>>
        <label for="calendar_show_net_badge"><?php echo settings_index_i18n('SETTINGS_CALENDAR_SHOW_NET_BADGE'); ?></label>

        <input type="checkbox" name="calendar_show_deductions_badge" value="1" class="work_entry_field" id="calendar_show_deductions_badge" <?php if ($user->calendar_show_deductions_badge) {
          echo 'checked';
        } ?>>
        <label for="calendar_show_deductions_badge"><?php echo settings_index_i18n('SETTINGS_CALENDAR_SHOW_DEDUCTIONS_BADGE'); ?></label>

        <input type="checkbox" name="calendar_highlight_pay_period" value="1" class="work_entry_field" id="calendar_highlight_pay_period" <?php if ($user->calendar_highlight_pay_period) {
          echo 'checked';
        } ?>>
        <label for="calendar_highlight_pay_period"><?php echo settings_index_i18n('SETTINGS_CALENDAR_PAY_PERIOD_BADGE'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_WEEK_START'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_CALENDAR_WEEK_START_HOVER'); ?>">
          <input type="radio" class="radio" id="calendar_week_start_sunday" name="calendar_week_start" value="0" <?php if ('0' === $calendarWeekStart) {
            echo 'checked';
          } ?>>
          <label for="calendar_week_start_sunday"><?php echo settings_index_i18n('SETTINGS_CALENDAR_WEEK_START_SUNDAY'); ?></label>
          <input type="radio" class="radio" id="calendar_week_start_monday" name="calendar_week_start" value="1" <?php if ('1' === $calendarWeekStart) {
            echo 'checked';
          } ?>>
          <label for="calendar_week_start_monday"><?php echo settings_index_i18n('SETTINGS_CALENDAR_WEEK_START_MONDAY'); ?></label>
        </div>
      </div>
    </div>

    <div class="flex f_baseline w100">
      <label class="w25"><?php echo settings_index_i18n('SETTINGS_CALENDAR_LABEL_DEFAULT_VIEW'); ?></label>
      <div class="w75">
        <div class="radio_group pill_group" data-hover-help="<?php echo settings_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_HOVER'); ?>">
          <input type="radio" class="radio" id="calendar_default_view_month" name="calendar_default_view" value="month" <?php if ('month' === $calendarDefaultView) {
            echo 'checked';
          } ?>>
          <label for="calendar_default_view_month"><?php echo settings_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_MONTH'); ?></label>
          <input type="radio" class="radio" id="calendar_default_view_week" name="calendar_default_view" value="week" <?php if ('week' === $calendarDefaultView) {
            echo 'checked';
          } ?>>
          <label for="calendar_default_view_week"><?php echo settings_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_WEEK'); ?></label>
          <input type="radio" class="radio" id="calendar_default_view_pay_period" name="calendar_default_view" value="pay_period" <?php if ('pay_period' === $calendarDefaultView) {
            echo 'checked';
          } ?>>
          <label for="calendar_default_view_pay_period"><?php echo settings_index_i18n('SETTINGS_CALENDAR_DEFAULT_VIEW_PAY_PERIOD'); ?></label>
        </div>
      </div>
    </div>
  </form>
</section>
