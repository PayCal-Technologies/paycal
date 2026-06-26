<?php namespace PayCal\Domain; ?>

  const setFieldErrorState = (input, errorId, message) => {
    const errorElement = document.getElementById(errorId);
    if (input instanceof HTMLElement) {
      input.classList.toggle('input_error', message !== '');
      if (message !== '') {
        input.setAttribute('aria-invalid', 'true');
        if (errorElement?.id) {
          input.setAttribute('aria-errormessage', errorElement.id);
        }
      } else {
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-errormessage');
      }
    }
    if (errorElement) {
      errorElement.textContent = message;
    }
  };

  const clearFieldErrorStates = (pairs) => {
    pairs.forEach(([inputId, errorId]) => {
      setFieldErrorState(document.getElementById(inputId), errorId, '');
    });
  };

  const clearFieldInvalidStates = (ids) => {
    ids.forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove('input_error');
        el.removeAttribute('aria-invalid');
      }
    });
  };

  const withSettingsCsrfToken = (payload = {}) => {
    const csrfToken = String((document.getElementById('settings_csrf_token')?.value || '')).trim();
    if (csrfToken === '') {
      return payload;
    }

    return {
      ...payload,
      csrf_token: csrfToken,
    };
  };

  const bindProfileEditDetails = () => {
    const editDetailsForm = document.getElementById('edit_details_form');
    const editDetailsPhone = document.getElementById('edit_details_phone');
    const editDetailsEmail = document.getElementById('edit_details_email');

    if (!(editDetailsForm instanceof HTMLFormElement)) {
      return;
    }

    if (editDetailsEmail instanceof HTMLInputElement && editDetailsEmail.readOnly) {
      const originalEmailValue = String(editDetailsEmail.value || '');

      editDetailsEmail.addEventListener('mouseenter', () => {
        editDetailsEmail.value = 'Change Email';
      });

      editDetailsEmail.addEventListener('mouseleave', () => {
        editDetailsEmail.value = originalEmailValue;
      });

      editDetailsEmail.addEventListener('click', () => {
        editDetailsEmail.value = originalEmailValue;
        resetChangeEmailModal();
        PC.openModal('modal_change_email', 'Change Email');
      });
    }

    const buildSettingsPayloadSignature = () => {
      const formData = new FormData(editDetailsForm);
      const pairs = [];
      for (const [key, value] of formData.entries()) {
        if (key === 'csrf_token') {
          continue;
        }
        pairs.push(`${key}:${String(value)}`);
      }
      pairs.sort();
      return pairs.join('|');
    };

    let lastSubmittedSignature = buildSettingsPayloadSignature();

    const validationPairs = [
      ['edit_details_full_name', 'edit_details_full_name_error'],
      ['edit_details_phone', 'edit_details_phone_error'],
      ['edit_details_province', 'edit_details_province_error'],
      ['edit_details_address_line1', 'edit_details_address_line1_error'],
      ['edit_details_address_city', 'edit_details_address_city_error'],
      ['edit_details_address_postal', 'edit_details_address_postal_error'],
    ];

    const clearValidationState = () => {
      clearFieldErrorStates(validationPairs);
    };

    const validateForm = () => {
      clearValidationState();

      const fullNameInput = document.getElementById('edit_details_full_name');
      const phoneInput = document.getElementById('edit_details_phone');
      const provinceInput = document.getElementById('edit_details_province');

      let firstInvalidField = null;
      const markInvalid = (input, errorId, message) => {
        setFieldErrorState(input, errorId, message);
        if (!firstInvalidField && input instanceof HTMLElement) {
          firstInvalidField = input;
        }
      };

      const fullName = String(fullNameInput?.value || '').trim();
      if (fullName.length < 2) {
        markInvalid(fullNameInput, 'edit_details_full_name_error', 'Enter your full name.');
      }

      const phone = String(phoneInput?.value || '').trim();
      if (phone.length > 0 && !/^\(\d{3}\) \d{3}-\d{4}$/.test(phone)) {
        markInvalid(phoneInput, 'edit_details_phone_error', 'Use phone format (123) 456-7890.');
      }

      const province = String(provinceInput?.value || '').trim();
      if (province.length !== 2) {
        markInvalid(provinceInput, 'edit_details_province_error', 'Select a province.');
      }

      if (firstInvalidField) {
        PC.showToast(T.correctHighlightedFields, 'error');
        firstInvalidField.focus();
        return false;
      }

      return true;
    };

    if (editDetailsPhone instanceof HTMLInputElement) {
      PC.formatPhoneNumber(editDetailsPhone);
      editDetailsPhone.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement) {
          PC.formatPhoneNumber(event.target);
        }
      });
      editDetailsPhone.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement) {
          PC.formatPhoneNumber(event.target);
        }
      });
    }

    editDetailsForm.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }

      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      // Allow finder inputs to keep their own Enter behavior for selection.
      if (target.id === 'businesses_personal_timezone_search' || target.id === 'businesses_personal_currency_search') {
        return;
      }

      if (target instanceof HTMLTextAreaElement) {
        return;
      }

      event.preventDefault();
      editDetailsForm.dispatchEvent(new Event('submit'));
    });

    editDetailsForm.addEventListener('submit', (event) => {
      event.preventDefault();

      const payloadSignature = buildSettingsPayloadSignature();
      if (payloadSignature === lastSubmittedSignature) {
        return;
      }

      if (!validateForm()) {
        return;
      }

      PC.showToast('<?php echo addslashes(org_js_index_i18n('UPDATING_INFO')); ?>...', 'working');

      const formData = new FormData(editDetailsForm);
      PC.updateResource('account/info', formData).then(() => {
        lastSubmittedSignature = payloadSignature;
        clearValidationState();
        PC.showToast('<?php echo addslashes(org_js_index_i18n('INFO_UPDATED')); ?>', 'save');
      }).catch((error) => {
        PC.showToast(T.saveAccountDetailsFailed, 'error');
        PW.error(error);
      });
    });

    // Auto-submit on field change with debounce
    let saveTimeout = null;
    const autoSaveFields = [
      'edit_details_full_name',
      'edit_details_phone',
      'edit_details_province',
      'edit_details_address_line1',
      'edit_details_address_city',
      'edit_details_address_postal'
    ];

    autoSaveFields.forEach((fieldId) => {
      const field = document.getElementById(fieldId);
      if (field) {
        const submitForm = () => {
          clearTimeout(saveTimeout);
          saveTimeout = setTimeout(() => {
            editDetailsForm.dispatchEvent(new Event('submit'));
          }, 800);
        };

        field.addEventListener('change', submitForm);
      }
    });
  };

  /* ── Change Email modal flow ── */

  const normalizeVerificationCode = (value) => String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);

  const updateChangeEmailVerifyState = () => {
    const verifyBtn = document.getElementById('change_email_verify_btn');
    const oldCodeInput = document.getElementById('change_email_old_code');
    const newCodeInput = document.getElementById('change_email_new_code');
    if (!verifyBtn || !oldCodeInput || !newCodeInput) {
      return;
    }

    const oldCode = normalizeVerificationCode(oldCodeInput.value);
    const newCode = normalizeVerificationCode(newCodeInput.value);
    const canVerify = oldCode.length >= 6 && newCode.length >= 6;

    verifyBtn.disabled = !canVerify;
    verifyBtn.setAttribute('aria-disabled', canVerify ? 'false' : 'true');
  };

  const toggleChangeEmailStep = (showStep2) => {
    const step1 = document.getElementById('change_email_step1_section');
    const step2 = document.getElementById('change_email_step2_section');
    const startBtn = document.getElementById('change_email_start_btn');
    const verifyBtn = document.getElementById('change_email_verify_btn');
    const resendBtn = document.getElementById('change_email_resend_btn');
    const prevBtn = document.getElementById('change_email_prev_btn');

    if (step1) step1.hidden = !!showStep2;
    if (step2) step2.hidden = !showStep2;
    if (startBtn) startBtn.hidden = !!showStep2;
    if (verifyBtn) verifyBtn.hidden = !showStep2;
    if (resendBtn) resendBtn.hidden = !showStep2;
    if (prevBtn) prevBtn.textContent = showStep2 ? T.previous : T.cancel;

    updateChangeEmailVerifyState();
  };

  const resetChangeEmailModal = () => {
    document.getElementById('change_email_form')?.reset();
    const status = document.getElementById('change_email_status');
    const verifyStatus = document.getElementById('change_email_verify_status');
    const txn = document.getElementById('change_email_txn_id');
    const expiry = document.getElementById('change_email_expiry_timer');
    const oldHint = document.getElementById('old_email_hint');
    const newHint = document.getElementById('new_email_hint');
    if (status) status.textContent = '';
    if (verifyStatus) verifyStatus.textContent = '';
    if (txn) txn.value = '';
    if (expiry) expiry.textContent = '';
    if (oldHint) oldHint.textContent = '';
    if (newHint) newHint.textContent = '';
    clearFieldInvalidStates([
      'change_email_new_email',
      'change_email_confirm_email',
      'change_email_old_code',
      'change_email_new_code',
    ]);
    clearFieldErrorStates([
      ['change_email_new_email', 'change_email_new_email_error'],
      ['change_email_confirm_email', 'change_email_confirm_email_error'],
      ['change_email_old_code', 'change_email_old_code_error'],
      ['change_email_new_code', 'change_email_new_code_error'],
    ]);
    toggleChangeEmailStep(false);
  };

  const attachChangeEmailCodeInputHandlers = () => {
    ['change_email_old_code', 'change_email_new_code'].forEach((id) => {
      const input = document.getElementById(id);
      if (!input) {
        return;
      }

      const syncInput = () => {
        const normalized = normalizeVerificationCode(input.value);
        if (input.value !== normalized) {
          input.value = normalized;
        }
        const errorId = id === 'change_email_old_code' ? 'change_email_old_code_error' : 'change_email_new_code_error';
        setFieldErrorState(input, errorId, '');
        updateChangeEmailVerifyState();
      };

      input.addEventListener('input', syncInput);
      input.addEventListener('blur', syncInput);
    });
  };

  const CHANGE_EMAIL_I18N = {
    enterBothEmails: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_EMAILS')); ?>',
    enterNewEmail: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_ENTER_NEW_EMAIL')); ?>',
    confirmNewEmail: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_CONFIRM_NEW_EMAIL')); ?>',
    emailsNoMatch: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_EMAILS_NO_MATCH')); ?>',
    emailsMustMatch: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_EMAILS_MUST_MATCH')); ?>',
    working: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_WORKING')); ?>',
    codesSent: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_CODES_SENT')); ?>',
    requestFailedPrefix: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_REQUEST_FAILED_PREFIX')); ?>',
    enterBothCodes: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_CODES')); ?>',
    enterValid6CharCode: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_ENTER_VALID_6_CHAR_CODE')); ?>',
    emailUpdated: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_EMAIL_UPDATED')); ?>',
    sessionExpired: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_SESSION_EXPIRED')); ?>',
    codesExpireMinutes: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_CODES_EXPIRE_MINUTES')); ?>',
    sendFailed: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_SEND_FAILED')); ?>',
    verifyFailed: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_VERIFY_FAILED')); ?>',
    resendFailed: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_RESEND_FAILED')); ?>',
  };

  const bindChangeEmail = () => {
    const hasChangeEmailUi = Boolean(
      document.getElementById('change_email_prev_btn')
      && document.getElementById('change_email_start_btn')
      && document.getElementById('change_email_verify_btn')
      && document.getElementById('change_email_resend_btn')
    );
    if (!hasChangeEmailUi) {
      return;
    }

    attachChangeEmailCodeInputHandlers();

    PC.addClickAndEnterListener('change_email_prev_btn', (e) => {
      e.preventDefault();
      const step2 = document.getElementById('change_email_step2_section');
      if (step2 && !step2.hidden) {
        toggleChangeEmailStep(false);
        return;
      }

      resetChangeEmailModal();
      PC.closeModal('modal_change_email', 'Change Email');
    });

    PC.addClickAndEnterListener('change_email_start_btn', async (e) => {
      e.preventDefault();
      const newEmailInput = document.getElementById('change_email_new_email');
      const confirmEmailInput = document.getElementById('change_email_confirm_email');
      const newEmail = String(newEmailInput?.value || '').trim();
      const confirmEmail = String(confirmEmailInput?.value || '').trim();
      const statusEl = document.getElementById('change_email_status');

      setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
      setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');

      if (!newEmail || !confirmEmail) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothEmails;
        if (!newEmail) {
          setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.enterNewEmail);
        }
        if (!confirmEmail) {
          setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.confirmNewEmail);
        }
        (newEmail ? confirmEmailInput : newEmailInput)?.focus();
        return;
      }
      if (newEmail !== confirmEmail) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailsNoMatch;
        setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
        setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
        confirmEmailInput?.focus();
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const { response, data, raw } = await postJsonRaw('/api/v1/account/change-email/start', withSettingsCsrfToken({
          new_email: newEmail,
        }));

        if (response.ok && data && data.status === 'success') {
          setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
          setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');
          const txn = document.getElementById('change_email_txn_id');
          const oldHint = document.getElementById('old_email_hint');
          const newHint = document.getElementById('new_email_hint');
          const expiry = document.getElementById('change_email_expiry_timer');

          if (txn) txn.value = data.txn_id || '';
          if (oldHint) oldHint.textContent = data.old_email_hint || '';
          if (newHint) newHint.textContent = data.new_email_hint || '';
          if (expiry) {
            expiry.textContent = formatPhpTemplate(CHANGE_EMAIL_I18N.codesExpireMinutes, [data.expires_in_minutes]);
          }
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;

          toggleChangeEmailStep(true);
          setTimeout(() => document.getElementById('change_email_old_code')?.focus(), 50);
        } else {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          if (statusEl) statusEl.textContent = apiMessage || formatPhpTemplate(CHANGE_EMAIL_I18N.sendFailed, [fallback]);
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || T.unknownError)}`;
        PW.error(error);
      }
    });

    PC.addClickAndEnterListener('change_email_verify_btn', async (e) => {
      e.preventDefault();
      const oldCodeInput = document.getElementById('change_email_old_code');
      const newCodeInput = document.getElementById('change_email_new_code');
      const txnId = String(document.getElementById('change_email_txn_id')?.value || '').trim();
      const oldCode = normalizeVerificationCode(oldCodeInput?.value || '');
      const newCode = normalizeVerificationCode(newCodeInput?.value || '');
      const statusEl = document.getElementById('change_email_verify_status');

      setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
      setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');

      if (!txnId || oldCode.length !== 6 || newCode.length !== 6) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothCodes;
        if (oldCode.length !== 6) {
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
        }
        if (newCode.length !== 6) {
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
        }
        (oldCode.length !== 6 ? oldCodeInput : newCodeInput)?.focus();
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const { response, data, raw } = await postJsonRaw('/api/v1/account/change-email/verify', withSettingsCsrfToken({
          txn_id: txnId,
          old_code: oldCode,
          new_code: newCode,
        }));

        if (response.ok && data && data.status === 'success') {
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailUpdated;
          setTimeout(() => {
            PC.closeModal('modal_change_email', 'Change Email');
            location.reload();
          }, 1000);
        } else if (statusEl) {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          const errorText = apiMessage || formatPhpTemplate(CHANGE_EMAIL_I18N.verifyFailed, [fallback]);
          statusEl.textContent = errorText;
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', errorText);
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', errorText);
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || T.unknownError)}`;
        PW.error(error);
      }
    });

    PC.addClickAndEnterListener('change_email_resend_btn', async (e) => {
      e.preventDefault();
      const txnId = String(document.getElementById('change_email_txn_id')?.value || '').trim();
      const statusEl = document.getElementById('change_email_verify_status');
      if (!txnId) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.sessionExpired;
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const { response, data, raw } = await postJsonRaw('/api/v1/account/change-email/resend', withSettingsCsrfToken({
          txn_id: txnId,
        }));
        if (response.ok && data && data.status === 'success') {
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;
        } else if (statusEl) {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          statusEl.textContent = apiMessage || formatPhpTemplate(CHANGE_EMAIL_I18N.resendFailed, [fallback]);
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || T.unknownError)}`;
        PW.error(error);
      }
    });
  };

  const bindDangerZone = () => {
    const deleteDataPill = document.getElementById('danger_delete_data_pill');
    const deleteDataForm = document.getElementById('danger_delete_data_form');
    const deleteDataPhraseInput = document.getElementById('danger_delete_data_phrase');
    const deleteDataConfirm = document.getElementById('danger_delete_data_confirm');

    const deleteAccountPill = document.getElementById('danger_delete_account_pill');
    const deleteAccountForm = document.getElementById('danger_delete_account_form');
    const deleteAccountPhraseInput = document.getElementById('danger_delete_account_phrase');
    const deleteAccountConfirm = document.getElementById('danger_delete_account_confirm');

    const dangerStatus = document.getElementById('danger_zone_status');

    if (!deleteDataPill && !deleteAccountPill) {
      return;
    }

    const updateDeleteDataConfirmState = () => {
      if (!(deleteDataPhraseInput instanceof HTMLInputElement) || !(deleteDataConfirm instanceof HTMLButtonElement)) {
        return;
      }

      const phrase = String(deleteDataPhraseInput.value || '').toUpperCase();
      deleteDataPhraseInput.value = phrase;
      deleteDataConfirm.disabled = phrase.trim() !== 'DELETE ALL DATA';
    };

    const updateDeleteAccountConfirmState = () => {
      if (!(deleteAccountPhraseInput instanceof HTMLInputElement) || !(deleteAccountConfirm instanceof HTMLButtonElement)) {
        return;
      }

      const phrase = String(deleteAccountPhraseInput.value || '').toUpperCase();
      deleteAccountPhraseInput.value = phrase;
      deleteAccountConfirm.disabled = phrase.trim() !== 'DELETE MY ACCOUNT';
    };

    deleteDataPhraseInput?.addEventListener('input', updateDeleteDataConfirmState);
    deleteAccountPhraseInput?.addEventListener('input', updateDeleteAccountConfirmState);

    updateDeleteDataConfirmState();
    updateDeleteAccountConfirmState();

    deleteAccountForm?.addEventListener('submit', (event) => {
      if (!(deleteAccountPhraseInput instanceof HTMLInputElement)) {
        return;
      }

      const phrase = String(deleteAccountPhraseInput.value || '').trim().toUpperCase();
      if (phrase !== 'DELETE MY ACCOUNT') {
        event.preventDefault();
        if (dangerStatus) {
          dangerStatus.textContent = T.deleteAccountTypePhrase;
        }
        deleteAccountPhraseInput.focus();
        deleteAccountPhraseInput.select();
      }
    });

    const submitDeleteDataForm = async (event) => {
      event?.preventDefault();
      if (!(deleteDataConfirm instanceof HTMLButtonElement) || !(deleteDataPhraseInput instanceof HTMLInputElement)) {
        return;
      }

      const phrase = String(deleteDataPhraseInput.value || '').trim().toUpperCase();
      if (phrase !== 'DELETE ALL DATA') {
        if (dangerStatus) {
          dangerStatus.textContent = T.deleteDataTypePhrase;
        }
        deleteDataPhraseInput.focus();
        deleteDataPhraseInput.select();
        return;
      }

      deleteDataConfirm.disabled = true;
      if (dangerStatus) {
        dangerStatus.textContent = T.deleteDataInProgress;
      }

      try {
        const formData = new FormData();
        formData.append('confirm_phrase', phrase);
        const settingsCsrfToken = String((document.getElementById('settings_csrf_token')?.value || '')).trim();
        if (settingsCsrfToken !== '') {
          formData.append('csrf_token', settingsCsrfToken);
        }

        const { response, data: payload } = await postFormDataRaw('/api/v1/account/data/delete/', formData);

        if (!response.ok || payload?.status !== 'success') {
          throw new Error(payload?.message || T.deleteDataFailed);
        }

        if (dangerStatus) {
          dangerStatus.textContent = T.deleteDataComplete;
        }
        if (deleteDataPill instanceof HTMLElement) {
          deleteDataPill.hidden = true;
        }
      } catch (error) {
        if (dangerStatus) {
          dangerStatus.textContent = error instanceof Error ? error.message : T.deleteDataFailed;
        }
      } finally {
        updateDeleteDataConfirmState();
      }
    };

    deleteDataForm?.addEventListener('submit', submitDeleteDataForm);

    updateDeleteDataConfirmState();
    updateDeleteAccountConfirmState();
  };
