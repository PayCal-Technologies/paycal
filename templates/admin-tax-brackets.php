<div id='status' class='status centered' aria-live='polite' role='status'>
  &nbsp;
</div>

<!-- PAGE TOP -->
<section class='w100' role="main" aria-label="__ADMIN_TAX_BRACKETS_EDITOR_ARIA__">
  <h1>__PAGE_LABEL__</h1>
</section>

<section class='flex f_row panel w100 mar_sm pad_md' aria-label='__ADMIN_TAX_BRACKETS_SELECTIONS_ARIA__'>
  <section class='f_column w50 pad_md'>
    <label for='country_select'>__ADMIN_TAX_COUNTRY_LABEL__</label>
    __COUNTRY_SELECT_HTML__
  </section>
  <section class='f_column w50 pad_md'>
    <label for='province_select'>__ADMIN_TAX_PROVINCE_LABEL__</label>
    __PROVINCE_SELECT_HTML__
  </section>
</section>

<section class='flex f_row panel w100 mar_sm pad_md' aria-label='__ADMIN_TAX_BRACKETS_EDITOR_ARIA__'>

  <section class='f_column panel w50 mar_sm pad_md' aria-label='__ADMIN_TAX_FEDERAL_ARIA__'>
    <h2>__ADMIN_TAX_FEDERAL_TITLE__</h2>
    <form id='federal_form'>
      <div class='bracket-container'>
        <div class='bracket-header'>
          <span>__ADMIN_TAX_MIN_INCOME__</span>
          <span>__ADMIN_TAX_MAX_INCOME__</span>
          <span>__ADMIN_TAX_RATE_PERCENT__</span>
        </div>
        __FEDERAL_BRACKETS_HTML__
      </div>
      <button type='button' class='btn btn_primary' id='save_federal'>__ADMIN_TAX_SAVE_FEDERAL__</button>
    </form>
  </section>

  <section class='f_column panel w50 mar_sm pad_md' aria-label='__ADMIN_TAX_PROVINCIAL_ARIA__'>
    <h2>__ADMIN_TAX_PROVINCIAL_TITLE_PREFIX__ (__SELECTED_PROVINCE__)</h2>
    <form id='provincial_form'>
      <div class='bracket-container'>
        <div class='bracket-header'>
          <span>__ADMIN_TAX_MIN_INCOME__</span>
          <span>__ADMIN_TAX_MAX_INCOME__</span>
          <span>__ADMIN_TAX_RATE_PERCENT__</span>
        </div>
        __PROVINCIAL_BRACKETS_HTML__
      </div>
      <button type='button' class='btn btn_primary' id='save_provincial'>__ADMIN_TAX_SAVE_PROVINCIAL__</button>
    </form>
  </section>

</section>

<div id="admin-tax-brackets-config" data-api-base="__API_BASE_URL__"></div>
<script type="module" src="__ADMIN_TAX_BRACKETS_JS_URL__" nonce="__CSP_NONCE__"></script>