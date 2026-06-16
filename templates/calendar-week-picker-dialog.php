  <dialog id="modal_cal_week_picker" data-dialog-close-on-backdrop="true" aria-modal="true" aria-labelledby="cal_week_picker_title" aria-describedby="cal_week_picker_aria cal_week_picker_meta">
    <div class="modal_aria visually_hidden">
      <span id="cal_week_picker_aria">__MODAL_ARIA__</span>
    </div>
    <div class="modal_meta visually_hidden">
      <span id="cal_week_picker_meta">__MODAL_META__</span>
    </div>
    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_cal_week_picker" aria-label="__CLOSE__">&times;</button>
      <h2 id="cal_week_picker_title" class="modal_title centered">__MODAL_TITLE__</h2>
    </section>
    <section class="modal_content calendar_anchor_picker_content">
      <label for="cal_week_date_input" class="calendar_anchor_picker_label">__DATE_LABEL__</label>
      <input type="date" id="cal_week_date_input" class="calendar_anchor_picker_input" value="__CURRENT_DATE__" min="__MIN_DATE__" max="__MAX_DATE__">
    </section>
    <section class="modal_footer">
      <div class="date_picker_actions" role="group" aria-label="__DATE_PICKER_ACTIONS_ARIA__">
        <button id="cal_week_picker_go_btn" class="btn btn_primary" type="button">__GO__</button>
        <button class="btn btn_cancel" type="button" data-dialog-close="modal_cal_week_picker">__CLOSE__</button>
      </div>
    </section>
  </dialog>
