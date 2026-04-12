<!-- TEST RUNNER -->
<section class='flex f_row panel w100 mar_sm pad_md test_runner_primary' aria-label='__ADMIN_TEST_RUNNER_ARIA__'>
  <section class='f_column w100 pad_md test_runner_shell'>
    <div id='status' class='status centered' aria-live='polite' role='status'>
      &nbsp;
    </div>

    <h2>__ADMIN_TEST_RUN_SUITE_TITLE__</h2>
    <p class='text-muted'>__ADMIN_TEST_RUN_SUITE_SUBTITLE__</p>
    
    <div class='test_runner_controls'>
      <button class='btn btn_primary' id='btn_run_tests'>
        <span class='btn_icon'>▶</span> __ADMIN_TEST_RUN_ALL_TESTS__
      </button>
      <button class='btn btn_warning hidden' id='btn_stop_tests' disabled>
        <span class='btn_icon'>■</span> Stop
      </button>
      <button class='btn btn_secondary hidden' id='btn_download_report'>
        <span class='btn_icon'>↓</span> __ADMIN_TEST_DOWNLOAD_REPORT__
      </button>
    </div>
    
    <div id='test_spinner' class='spinner hidden'>
      <div class='spinner_icon'>⟳</div>
      <span>__ADMIN_TEST_RUNNING_TESTS__</span>
    </div>
    
    <div class='test_results_section' id='results_container'>
      <textarea id='test_results_output' class='test_results_textarea' readonly placeholder='Test results will appear here after running tests'></textarea>
    </div>

    <section id='final_result_callout' class='final_result_callout hidden' aria-live='polite'>
      <h3 class='final_result_title'>Final Result</h3>
      <p id='final_result_message' class='final_result_message'>No completed run yet.</p>
    </section>
    
    <div id='last_run_results'>
      __LAST_RUN_HTML__
    </div>
  </section>
</section>

<!-- TEST METRICS SUMMARY -->
<section class='flex f_row panel w100 mar_sm pad_md' aria-label='__ADMIN_TEST_METRICS_SUMMARY_ARIA__'>
  <div class='metrics_grid'>
    <div class='metric_card'>
      <div class='metric_value'>__TOTAL_TESTS__</div>
      <div class='metric_label'>__ADMIN_TEST_TOTAL_TESTS__</div>
    </div>
    <div class='metric_card'>
      <div class='metric_value'>__TOTAL_ASSERTIONS__</div>
      <div class='metric_label'>__ADMIN_TEST_TOTAL_ASSERTIONS__</div>
    </div>
    <div class='metric_card'>
      <div class='metric_value' id='pass_rate'>--</div>
      <div class='metric_label'>__ADMIN_TEST_PASS_RATE__</div>
    </div>
    <div class='metric_card'>
      <div class='metric_value' id='coverage'>--</div>
      <div class='metric_label'>__ADMIN_TEST_COVERAGE__</div>
    </div>
  </div>
</section>

<!-- PHASE PROGRESS -->
<section class='flex f_row panel w100 mar_sm pad_md' aria-label='__ADMIN_TEST_PHASE_PROGRESS_ARIA__'>
  <section class='f_column w100 pad_md'>
    <h2>__ADMIN_TEST_IMPLEMENTATION_PROGRESS__</h2>
    __PHASE_PROGRESS_HTML__
  </section>
</section>

<!-- TEST CLASS LIST -->
<section class='flex f_row panel w100 mar_sm pad_md' aria-label='__ADMIN_TEST_CLASS_LIST_ARIA__'>
  <section class='f_column w100 pad_md'>
    <h2>__ADMIN_TEST_CLASSES_TITLE__</h2>
    __TEST_CLASS_LIST_HTML__
  </section>
</section>

<script type="module" src="__SITE__js/tests/" nonce="__CSP_NONCE__"></script>
