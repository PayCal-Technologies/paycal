  <dialog id="modal_cal_payperiod_picker" data-dialog-invoker-bridge data-dialog-close-tts="__MODAL_TITLE__" data-dialog-close-on-backdrop="true" aria-modal="true" aria-labelledby="cal_payperiod_picker_title" aria-describedby="cal_payperiod_picker_aria cal_payperiod_picker_meta">
    <div class="modal_aria visually_hidden">
      <span id="cal_payperiod_picker_aria">__MODAL_ARIA__</span>
    </div>
    <div class="modal_meta visually_hidden">
      <span id="cal_payperiod_picker_meta">__MODAL_META__</span>
    </div>
    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_cal_payperiod_picker" commandfor="modal_cal_payperiod_picker" command="close" aria-label="__CLOSE__">&times;</button>
      <h2 id="cal_payperiod_picker_title" class="modal_title centered">__MODAL_TITLE__</h2>
    </section>
    <section class="modal_content calendar_anchor_picker_content">
      <div id="cal_payperiod_picker_list" class="calendar_payperiod_picker_list" role="listbox" aria-label="__MODAL_TITLE__">__PAYPERIOD_OPTIONS__</div>
    </section>
    <section class="modal_footer">
      <div class="date_picker_actions" role="group" aria-label="__DATE_PICKER_ACTIONS_ARIA__">
        <button class="btn btn_cancel" type="button" data-dialog-close="modal_cal_payperiod_picker" commandfor="modal_cal_payperiod_picker" command="close">__CLOSE__</button>
      </div>
    </section>
  </dialog>
