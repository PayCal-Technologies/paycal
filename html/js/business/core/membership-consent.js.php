<?php namespace PayCal\Domain; ?>

  const closeMembershipConsentDialog = () => {
    if (elements.membershipConsentDialog instanceof HTMLDialogElement && elements.membershipConsentDialog.open) {
      elements.membershipConsentDialog.close();
    }
  };

  const promptMembershipConsent = async (actionLabel) => {
    if (!(elements.membershipConsentDialog instanceof HTMLDialogElement)
      || !(elements.membershipConsentForm instanceof HTMLFormElement)
      || !(elements.membershipConsentAcknowledge instanceof HTMLInputElement)
      || !(elements.membershipConsentDisclaimer instanceof HTMLTextAreaElement)) {
      return {
        consent_acknowledged: '1',
        consent_version: 'v1',
        disclaimer_text: T.membershipConsentDefaultDisclaimer,
      };
    }

    elements.membershipConsentAcknowledge.checked = false;
    elements.membershipConsentDisclaimer.value = '';

    const refreshMembershipConsentMatrix = () => {
      const consented = elements.membershipConsentAcknowledge.checked;
      if (elements.membershipConsentCurrentAck instanceof HTMLElement) {
        elements.membershipConsentCurrentAck.textContent = consented
          ? 'Granted when you continue.'
          : 'Not granted.';
      }
      if (elements.membershipConsentCurrentVersion instanceof HTMLElement) {
        elements.membershipConsentCurrentVersion.textContent = 'v1';
      }
      if (elements.membershipConsentCurrentSharing instanceof HTMLElement) {
        elements.membershipConsentCurrentSharing.textContent = consented
          ? 'Enabled only while membership, consent, credential, and envelope checks pass.'
          : 'Disabled until you consent.';
      }
    };

    refreshMembershipConsentMatrix();
    if (elements.membershipConsentAction instanceof HTMLElement) {
      elements.membershipConsentAction.textContent = String(actionLabel || T.membershipConsentIntro);
    }
    if (elements.membershipConsentError instanceof HTMLElement) {
      elements.membershipConsentError.textContent = '';
      elements.membershipConsentError.classList.add('hidden');
    }

    return await new Promise((resolve) => {
      let settled = false;

      const settle = (value) => {
        if (settled) {
          return;
        }
        settled = true;
        cleanup();
        resolve(value);
      };

      const onSubmit = (event) => {
        event.preventDefault();
        if (!elements.membershipConsentAcknowledge.checked) {
          if (elements.membershipConsentError instanceof HTMLElement) {
            elements.membershipConsentError.textContent = T.membershipConsentAckRequired;
            elements.membershipConsentError.classList.remove('hidden');
          }
          return;
        }

        const disclaimerInput = String(elements.membershipConsentDisclaimer.value || '').trim();
        settle({
          consent_acknowledged: '1',
          consent_version: 'v1',
          disclaimer_text: disclaimerInput === '' ? T.membershipConsentDefaultDisclaimer : disclaimerInput,
        });

        closeMembershipConsentDialog();
      };

      const onDialogClose = () => {
        if (!settled) {
          settle(null);
        }
      };

      const cleanup = () => {
        elements.membershipConsentForm?.removeEventListener('submit', onSubmit);
        elements.membershipConsentAcknowledge?.removeEventListener('change', refreshMembershipConsentMatrix);
        elements.membershipConsentDialog?.removeEventListener('close', onDialogClose);
      };

      elements.membershipConsentForm.addEventListener('submit', onSubmit);
      elements.membershipConsentAcknowledge.addEventListener('change', refreshMembershipConsentMatrix);
      elements.membershipConsentDialog.addEventListener('close', onDialogClose);

      elements.membershipConsentDialog.showModal();
      elements.membershipConsentAcknowledge.focus();
    });
  };
