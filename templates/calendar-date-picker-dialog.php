  <dialog id="modal_cal_picker" data-dialog-close-on-backdrop="true" aria-modal="true" aria-labelledby="date_picker_title" aria-describedby="date_picker_aria date_picker_meta">
    <div class="modal_aria visually_hidden">
      <span id="date_picker_aria">__MODAL_ARIA__</span>
    </div>
    <div class="modal_meta visually_hidden">
      <span id="date_picker_meta">__MODAL_META__</span>
    </div>
    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_cal_picker" aria-label="__CLOSE__">&times;</button>
      <h2 id="date_picker_title" class="modal_title centered">__MODAL_TITLE__</h2>
    </section>
    <section class="modal_content">
      <div id="cal_menu_left">__CAL_MENU_YEARS__</div>
      <div id="cal_menu_right">__CAL_MENU_MONTHS__</div>
    </section>
    <section class="modal_footer">
      <div class="date_picker_actions" role="group" aria-label="__DATE_PICKER_ACTIONS_ARIA__">
        <button id="date_picker_go_btn" class="btn btn_primary" type="button">
          __GO__
        </button>
        <button id="date_picker_close_btn" class="btn btn_cancel" type="button" data-dialog-close="modal_cal_picker">
          __CLOSE__
        </button>
      </div>
      <div class="date_picker_shortcuts" aria-hidden="true">
        <span><kbd>PgUp</kbd>/<kbd>PgDn</kbd> __YEAR_LOWER__</span>
        <span><kbd>__ARROWS__</kbd> __MONTHS_LOWER__</span>
        <span><kbd>__ENTER_KEY__</kbd> __VIEW_LOWER__</span>
      </div>
    </section>
  </dialog>

