<?php namespace PayCal\Domain; ?>

  const CONTACT_AVATAR_PLACEHOLDER_SRC = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22128%22 height=%22128%22 viewBox=%220 0 128 128%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23343a46%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23262c36%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22128%22 height=%22128%22 rx=%2264%22 fill=%22url(%23g)%22/%3E%3Ccircle cx=%2264%22 cy=%2248%22 r=%2222%22 fill=%22%238a95a8%22/%3E%3Cpath d=%22M24 110c4-21 20-33 40-33s36 12 40 33%22 fill=%22%238a95a8%22/%3E%3C/svg%3E';

  const normalizeContactImageDataUrl = (rawValue) => {
    const value = String(rawValue || '').trim();
    const match = value.match(/^data:image\/(png|jpe?g|webp|gif);base64,([a-z0-9+/=\s]+)$/i);
    if (!match) {
      return '';
    }

    const mime = String(match[1] || '').toLowerCase();
    const payload = String(match[2] || '').replace(/\s+/g, '');
    if (payload.length < 256 || payload.length > 19000) {
      return '';
    }

    // Quick decode sanity check to reject malformed values that still match regex.
    try {
      atob(payload);
    } catch (_error) {
      return '';
    }

    return `data:image/${mime};base64,${payload}`;
  };

  const getContactAvatarPreviewSrc = (rawValue) => {
    const normalized = normalizeContactImageDataUrl(rawValue);
    return normalized === '' ? CONTACT_AVATAR_PLACEHOLDER_SRC : normalized;
  };

  const uid = () => `cc_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;

  const normalizeCustomContactCards = (rawValue) => {
    let parsed = [];
    try {
      const maybe = JSON.parse(String(rawValue || '[]'));
      if (Array.isArray(maybe)) {
        parsed = maybe;
      }
    } catch {
      parsed = [];
    }

    return parsed
      .filter((item) => item && typeof item === 'object')
      .map((item) => ({
        id: String(item.id || uid()),
        name: String(item.name || ''),
        email: String(item.email || ''),
        phone: typeof PC.formatPhoneNumberValue === 'function'
          ? PC.formatPhoneNumberValue(String(item.phone || ''))
          : String(item.phone || ''),
        role: String(item.role || ''),
        image_url: String(item.image_url || ''),
      }));
  };

  const syncCustomCardsHiddenInput = () => {
    if (!(elements.customCardsJson instanceof HTMLInputElement)) {
      return;
    }
    elements.customCardsJson.value = JSON.stringify(state.customContactCards);
  };

  const renderCustomContactCards = () => {
    if (!(elements.customCardsContainer instanceof HTMLElement)) {
      return;
    }

    if (state.customContactCards.length === 0) {
      elements.customCardsContainer.textContent = '';
      return;
    }

    const markup = state.customContactCards.map((card) => {
      const previewId = `businesses_editor_contact_custom_${card.id}_avatar_preview`;
      const imageFieldId = `businesses_editor_contact_custom_${card.id}_image_url`;
      const previewSrc = getContactAvatarPreviewSrc(card.image_url);
      return `
        <div class="businesses_contact_card businesses_contact_card_custom" data-custom-card-id="${escapeHtml(card.id)}">
          <button type="button" class="businesses_contact_card_avatar_button" aria-haspopup="dialog" aria-controls="businesses_contact_image_popover" aria-expanded="false" aria-label="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_POPOVER_ARIA')); ?>">
            <img id="${escapeHtml(previewId)}" class="businesses_contact_card_avatar" src="${escapeHtml(previewSrc)}" alt="" role="presentation" loading="lazy">
          </button>
          <input id="${escapeHtml(imageFieldId)}" class="businesses_contact_image_input" data-custom-field="image_url" data-custom-card-id="${escapeHtml(card.id)}" data-preview-id="${escapeHtml(previewId)}" type="hidden" maxlength="20000" value="${escapeHtml(card.image_url)}">
          <input class="businesses_contact_custom_input businesses_contact_role_input" data-custom-field="role" data-custom-card-id="${escapeHtml(card.id)}" type="text" maxlength="80" placeholder="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ROLE_PH')); ?>" value="${escapeHtml(card.role)}">
          <input class="businesses_contact_custom_input businesses_contact_body_input" name="name" autocomplete="name" data-custom-field="name" data-custom-card-id="${escapeHtml(card.id)}" type="text" maxlength="100" placeholder="<?php echo addslashes(org_js_index_i18n('NAME')); ?>" value="${escapeHtml(card.name)}">
          <input class="businesses_contact_custom_input businesses_contact_body_input" name="email" autocomplete="email" data-custom-field="email" data-custom-card-id="${escapeHtml(card.id)}" type="email" maxlength="160" placeholder="<?php echo addslashes(org_js_index_i18n('EMAIL')); ?>" value="${escapeHtml(card.email)}">
          <input class="businesses_contact_custom_input businesses_contact_body_input" name="phone" autocomplete="tel" data-custom-field="phone" data-custom-card-id="${escapeHtml(card.id)}" type="tel" inputmode="numeric" maxlength="14" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_PHONE_PLACEHOLDER')); ?>" value="${escapeHtml(typeof PC.formatPhoneNumberValue === 'function' ? PC.formatPhoneNumberValue(card.phone) : card.phone)}">
          <div class="businesses_contact_card_menu">
            <button type="button" class="btn btn_secondary businesses_contact_card_menu_toggle" aria-haspopup="menu" aria-expanded="false" aria-label="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ACTIONS_ARIA')); ?>">...</button>
            <button type="button" class="btn btn_secondary businesses_contact_card_menu_delete" data-card-type="custom" data-custom-card-id="${escapeHtml(card.id)}" data-confirming="false" hidden><?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEAR')); ?></button>
          </div>
        </div>
      `;
    }).join('');

    Guardian.setHTML(elements.customCardsContainer, markup);
    applyContactInputAriaLabels(elements.customCardsContainer);
  };

  const applyContactInputAriaLabels = (root = document) => {
    if (!(root instanceof Document || root instanceof HTMLElement)) {
      return;
    }

    const resolveLabel = (input) => {
      if (!(input instanceof HTMLInputElement)) {
        return '';
      }
      if (input.classList.contains('businesses_contact_role_input')) {
        return T.contactRoleLabel;
      }
      if (input.name === 'name') {
        return T.contactNameLabel;
      }
      if (input.name === 'email') {
        return T.contactEmailLabel;
      }
      if (input.name === 'phone') {
        return T.contactPhoneLabel;
      }
      return '';
    };

    root.querySelectorAll('.businesses_contact_body_input, .businesses_contact_role_input').forEach((field) => {
      if (!(field instanceof HTMLInputElement)) {
        return;
      }
      const label = resolveLabel(field);
      if (label !== '') {
        field.setAttribute('aria-label', label);
      }
    });
  };

  const upsertCustomCardField = (cardId, fieldName, fieldValue) => {
    const idx = state.customContactCards.findIndex((card) => card.id === cardId);
    if (idx === -1) {
      return;
    }
    state.customContactCards[idx][fieldName] = String(fieldValue || '');
    syncCustomCardsHiddenInput();
  };

  const contactDeleteTimers = new WeakMap();
  let businessDetailsContactPanelEventsBound = false;

  const resetContactCardDeleteButton = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    const timerId = contactDeleteTimers.get(button);
    if (typeof timerId === 'number') {
      window.clearTimeout(timerId);
      contactDeleteTimers.delete(button);
    }

    button.dataset.confirming = 'false';
    button.classList.remove('is_confirming');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEAR')); ?>';
  };

  const setContactCardMenuOpen = (menu, isOpen) => {
    if (!(menu instanceof HTMLElement)) {
      return;
    }

    const toggle = menu.querySelector('.businesses_contact_card_menu_toggle');
    const deleteButton = menu.querySelector('.businesses_contact_card_menu_delete');
    if (toggle instanceof HTMLButtonElement) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (deleteButton instanceof HTMLButtonElement) {
      deleteButton.hidden = !isOpen;
      if (!isOpen) {
        resetContactCardDeleteButton(deleteButton);
      }
    }
    menu.classList.toggle('is_open', isOpen);
  };

  const closeAllContactCardMenus = (exceptMenu = null) => {
    document.querySelectorAll('.businesses_contact_card_menu.is_open').forEach((menu) => {
      if (!(menu instanceof HTMLElement)) {
        return;
      }
      if (exceptMenu instanceof HTMLElement && menu === exceptMenu) {
        return;
      }
      setContactCardMenuOpen(menu, false);
    });
  };

  const armContactCardDeleteButton = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    button.dataset.confirming = 'true';
    button.classList.add('is_confirming');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CONFIRM_CLEAR')); ?>';

    const timerId = window.setTimeout(() => {
      resetContactCardDeleteButton(button);
    }, 3800);
    contactDeleteTimers.set(button, timerId);
  };

  const clearFixedContactCard = (card) => {
    if (!(card instanceof HTMLElement)) {
      return false;
    }

    let changed = false;
    card.querySelectorAll('input').forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      if (input.type === 'hidden') {
        if (input.classList.contains('businesses_contact_image_input') && String(input.value || '') !== '') {
          input.value = '';
          syncContactAvatarPreview(input);
          changed = true;
        }
        return;
      }

      if (String(input.value || '') !== '') {
        input.value = '';
        changed = true;
      }
    });

    return changed;
  };

  const clearCustomContactCard = (cardId) => {
    const normalizedId = String(cardId || '').trim();
    if (normalizedId === '') {
      return false;
    }

    const idx = state.customContactCards.findIndex((card) => card.id === normalizedId);
    if (idx === -1) {
      return false;
    }

    const card = state.customContactCards[idx];
    let changed = false;
    ['name', 'email', 'phone', 'role', 'image_url'].forEach((fieldName) => {
      if (String(card[fieldName] || '') !== '') {
        card[fieldName] = '';
        changed = true;
      }
    });

    if (changed) {
      syncCustomCardsHiddenInput();
      renderCustomContactCards();
    }

    return changed;
  };

  const handleContactCardDeleteAction = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    if (button.dataset.confirming !== 'true') {
      armContactCardDeleteButton(button);
      return;
    }

    resetContactCardDeleteButton(button);

    const cardType = String(button.dataset.cardType || 'fixed').trim();
    if (cardType === 'custom') {
      const cardId = String(button.dataset.customCardId || '').trim();
      if (cardId === '') {
        return;
      }

      const changed = clearCustomContactCard(cardId);
      scheduleEditorAutoSave(220, 'custom-contact-clear');
      showBusinessesToast(
        changed
          ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEARED')); ?>'
          : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ALREADY_EMPTY')); ?>',
        'save',
        2200,
        true,
      );
      closeAllContactCardMenus();
      return;
    }

    const card = button.closest('.businesses_contact_card');
    if (!(card instanceof HTMLElement)) {
      return;
    }

    const changed = clearFixedContactCard(card);
    scheduleEditorAutoSave(220, 'fixed-contact-clear');
    showBusinessesToast(
      changed
        ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEARED')); ?>'
        : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ALREADY_EMPTY')); ?>',
      'save',
      2200,
      true,
    );
    const menu = button.closest('.businesses_contact_card_menu');
    if (menu instanceof HTMLElement) {
      setContactCardMenuOpen(menu, false);
    }
  };

  const closeContactImagePopover = ({ restoreFocus = false } = {}) => {
    if (elements.contactImagePopover instanceof HTMLElement) {
      elements.contactImagePopover.classList.add('hidden');
      elements.contactImagePopover.hidden = true;
      elements.contactImagePopover.style.removeProperty('--contact-popover-top');
      elements.contactImagePopover.style.removeProperty('--contact-popover-left');
    }

    const trigger = state.contactImagePopoverTrigger;
    if (trigger instanceof HTMLElement) {
      trigger.setAttribute('aria-expanded', 'false');
    }

    state.contactImagePopoverTargetFieldId = '';
    state.contactImagePopoverTrigger = null;

    if (restoreFocus && trigger instanceof HTMLElement && typeof trigger.focus === 'function') {
      trigger.focus();
    }
  };

  const isContactImagePopoverOpen = () => {
    return elements.contactImagePopover instanceof HTMLElement
      && !elements.contactImagePopover.classList.contains('hidden');
  };

  const trapContactImagePopoverFocus = (event) => {
    return trapFocusWithin(elements.contactImagePopover, event);
  };

  const syncBusinessDetailsContactElementRefs = () => {
    elements.customCardsContainer = document.getElementById('businesses_contact_directory_custom_cards');
    elements.customCardsJson = document.getElementById('businesses_editor_contact_custom_json');
    elements.contactImagePopover = document.getElementById('businesses_contact_image_popover');
    elements.contactImageDropzone = document.getElementById('businesses_contact_image_dropzone');
    elements.contactImageFile = document.getElementById('businesses_contact_image_file');
    elements.contactImageClear = document.getElementById('businesses_contact_image_clear');
    elements.contactImageCancel = document.getElementById('businesses_contact_image_cancel');
  };

  const mountContactImagePopover = () => {
    syncBusinessDetailsContactElementRefs();
    if (!(elements.contactImagePopover instanceof HTMLElement)) {
      return;
    }

    if (elements.contactImagePopover.parentElement !== document.body) {
      document.body.appendChild(elements.contactImagePopover);
    }

    if (elements.contactImagePopover.classList.contains('hidden')) {
      elements.contactImagePopover.hidden = true;
    }
  };

  const openContactImagePopover = (targetField, anchorElement) => {
    mountContactImagePopover();
    if (!(elements.contactImagePopover instanceof HTMLElement)) {
      return;
    }

    if (isContactImagePopoverOpen()) {
      closeContactImagePopover({ restoreFocus: false });
    }

    state.contactImagePopoverTargetFieldId = targetField.id;
    state.contactImagePopoverTrigger = anchorElement instanceof HTMLElement ? anchorElement : null;

    const rect = anchorElement.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 220, rect.bottom + 8);
    const left = Math.min(window.innerWidth - 380, Math.max(8, rect.left - 12));
    elements.contactImagePopover.style.setProperty('--contact-popover-top', `${Math.max(8, top)}px`);
    elements.contactImagePopover.style.setProperty('--contact-popover-left', `${Math.max(8, left)}px`);
    elements.contactImagePopover.classList.remove('hidden');
    elements.contactImagePopover.hidden = false;

    if (state.contactImagePopoverTrigger instanceof HTMLElement) {
      state.contactImagePopoverTrigger.setAttribute('aria-expanded', 'true');
    }

    const focusTarget = elements.contactImageDropzone instanceof HTMLElement
      ? elements.contactImageDropzone
      : elements.contactImageCancel;
    if (focusTarget instanceof HTMLElement) {
      focusTarget.focus();
    }
  };

  const applyContactImageValue = (rawValue) => {
    const targetField = document.getElementById(state.contactImagePopoverTargetFieldId);
    if (!(targetField instanceof HTMLInputElement)) {
      return;
    }

    const nextValue = String(rawValue || '').trim();
    if (targetField.maxLength > 0 && nextValue.length > targetField.maxLength) {
      showBusinessesToast(formatPhpTemplate(T.contactImageTooLong, [targetField.maxLength]), 'error', 5000, true);
      return;
    }
    targetField.value = nextValue;
    syncContactAvatarPreview(targetField);

    const customCardId = String(targetField.dataset.customCardId || '');
    const customField = String(targetField.dataset.customField || '');
    if (customCardId !== '' && customField === 'image_url') {
      upsertCustomCardField(customCardId, 'image_url', nextValue);
      syncCustomCardsHiddenInput();
    }

    showBusinessesToast(T.contactImageSaving, 'save', 1400, true);
    saveBusinessEditorSettings('contact-image', false)
      .then((saved) => {
        if (!saved) {
          showBusinessesToast(T.contactImageUnchanged, 'save', 2200, true);
        }
      })
      .catch((error) => PW.error(error));
    closeContactImagePopover({ restoreFocus: true });
  };

  const fileToCompactDataUrl = async (file) => {
    if (!(file instanceof File)) {
      return '';
    }

    const sourceDataUrl = await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('Image read failed'));
      reader.readAsDataURL(file);
    });

    if (sourceDataUrl === '') {
      return '';
    }

    const image = await new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error('Image load failed'));
      img.src = sourceDataUrl;
    });

    const canvas = document.createElement('canvas');
    const maxAllowedLength = 16000;
    const sizeCandidates = [96, 88, 80, 72, 64, 56];
    const qualityCandidates = [0.62, 0.55, 0.48, 0.42, 0.35];
    const ctx = canvas.getContext('2d');
    if (!ctx) {
      return '';
    }

    const srcW = image.width;
    const srcH = image.height;
    const crop = Math.min(srcW, srcH);
    const sx = Math.floor((srcW - crop) / 2);
    const sy = Math.floor((srcH - crop) / 2);

    let candidate = '';
    for (const size of sizeCandidates) {
      canvas.width = size;
      canvas.height = size;
      ctx.clearRect(0, 0, size, size);
      ctx.drawImage(image, sx, sy, crop, crop, 0, 0, size, size);

      for (const quality of qualityCandidates) {
        candidate = canvas.toDataURL('image/webp', quality);
        if (candidate.length <= maxAllowedLength) {
          return candidate;
        }
      }
    }

    return candidate;
  };

  const getImageFieldForAvatar = (avatar) => {
    if (!(avatar instanceof HTMLImageElement)) {
      return null;
    }

    const avatarId = String(avatar.id || '').trim();
    if (avatarId === '') {
      return null;
    }

    const field = document.querySelector(`.businesses_contact_image_input[data-preview-id="${CSS.escape(avatarId)}"]`);
    return field instanceof HTMLInputElement ? field : null;
  };

  const resolveContactCardAvatar = (target) => {
    if (!(target instanceof Element)) {
      return null;
    }

    const avatar = target.closest('.businesses_contact_card_avatar');
    if (avatar instanceof HTMLImageElement) {
      return avatar;
    }

    const avatarButton = target.closest('.businesses_contact_card_avatar_button');
    if (avatarButton instanceof HTMLButtonElement) {
      const nestedAvatar = avatarButton.querySelector('.businesses_contact_card_avatar');
      return nestedAvatar instanceof HTMLImageElement ? nestedAvatar : null;
    }

    return null;
  };

  const resolveContactCardAvatarAnchor = (avatar) => {
    if (!(avatar instanceof HTMLImageElement)) {
      return null;
    }

    const avatarButton = avatar.closest('.businesses_contact_card_avatar_button');
    return avatarButton instanceof HTMLButtonElement ? avatarButton : avatar;
  };

  const handleContactImageFiles = async (files) => {
    const first = files && files.length > 0 ? files[0] : null;
    if (!(first instanceof File)) {
      return;
    }

    try {
      const dataUrl = await fileToCompactDataUrl(first);
      applyContactImageValue(dataUrl);
    } catch (error) {
      PW.error(error);
      showBusinessesToast(T.contactImageProcessFailed, 'error', 5000, true);
    }
  };

  const syncContactAvatarPreview = (field) => {
    if (!(field instanceof HTMLInputElement) || !field.classList.contains('businesses_contact_image_input')) {
      return;
    }

    const previewId = String(field.dataset.previewId || '').trim();
    if (previewId === '') {
      return;
    }

    const preview = document.getElementById(previewId);
    if (!(preview instanceof HTMLImageElement)) {
      return;
    }

    const nextSrc = getContactAvatarPreviewSrc(field.value);
    preview.src = nextSrc;
    preview.alt = '';
    preview.setAttribute('role', 'presentation');
  };

  const applyPhoneInputFormatting = (input) => {
    if (!(input instanceof HTMLInputElement)) {
      return;
    }

    input.type = 'tel';
    input.autocomplete = input.autocomplete || 'tel';
    input.inputMode = 'numeric';
    input.maxLength = 14;
    input.pattern = '\\([0-9]{3}\\) [0-9]{3}-[0-9]{4}';
    if (input.placeholder === '' || input.placeholder === 'Phone' || input.placeholder === '123-456-7890') {
      input.placeholder = '(123) 456-7890';
    }
    PC.formatPhoneNumber(input);
  };

  const formatPhoneInputsWithin = (root = document) => {
    if (!(root instanceof Document || root instanceof HTMLElement || root instanceof HTMLDialogElement)) {
      return;
    }

    root.querySelectorAll('input[id$="_phone"], .businesses_contact_custom_input[data-custom-field="phone"]').forEach((field) => {
      if (field instanceof HTMLInputElement) {
        applyPhoneInputFormatting(field);
      }
    });
  };

  const bindBusinessDetailsContactPanelEvents = () => {
    mountContactImagePopover();

    const contactRoot = document.getElementById('business-workspace') ?? elements.dialog ?? document;
    formatPhoneInputsWithin(contactRoot);
    applyContactInputAriaLabels(contactRoot);

    document.querySelectorAll('.businesses_contact_image_input').forEach((field) => {
      if (field instanceof HTMLInputElement) {
        syncContactAvatarPreview(field);
      }
    });

    if (businessDetailsContactPanelEventsBound) {
      return;
    }
    businessDetailsContactPanelEventsBound = true;

    const handleContactCardPanelClick = (event) => {
      const menuToggle = event.target instanceof Element
        ? event.target.closest('.businesses_contact_card_menu_toggle')
        : null;
      if (menuToggle instanceof HTMLButtonElement) {
        const menu = menuToggle.closest('.businesses_contact_card_menu');
        if (menu instanceof HTMLElement) {
          const shouldOpen = !menu.classList.contains('is_open');
          closeAllContactCardMenus(shouldOpen ? menu : null);
          setContactCardMenuOpen(menu, shouldOpen);
        }
        return;
      }

      const deleteButton = event.target instanceof Element
        ? event.target.closest('.businesses_contact_card_menu_delete')
        : null;
      if (deleteButton instanceof HTMLButtonElement) {
        handleContactCardDeleteAction(deleteButton);
        return;
      }

      const avatar = resolveContactCardAvatar(event.target);
      if (!(avatar instanceof HTMLImageElement)) {
        return;
      }

      const targetField = getImageFieldForAvatar(avatar);
      if (!(targetField instanceof HTMLInputElement)) {
        return;
      }

      const anchor = resolveContactCardAvatarAnchor(avatar);
      if (!(anchor instanceof HTMLElement)) {
        return;
      }

      openContactImagePopover(targetField, anchor);
    };

    const businessWorkspace = document.getElementById('business-workspace');
    elements.dialog?.addEventListener('click', handleContactCardPanelClick);
    businessWorkspace?.addEventListener('click', handleContactCardPanelClick);

    const handleContactCardAvatarError = (event) => {
      const target = event.target;
      if (!(target instanceof HTMLImageElement) || !target.classList.contains('businesses_contact_card_avatar')) {
        return;
      }
      if (target.src !== CONTACT_AVATAR_PLACEHOLDER_SRC) {
        target.src = CONTACT_AVATAR_PLACEHOLDER_SRC;
      }
      target.alt = '';
      target.setAttribute('role', 'presentation');
    };

    elements.dialog?.addEventListener('error', handleContactCardAvatarError, true);
    businessWorkspace?.addEventListener('error', handleContactCardAvatarError, true);

    elements.contactImageDropzone?.addEventListener('click', () => {
      elements.contactImageFile?.click();
    });

    elements.contactImageDropzone?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        elements.contactImageFile?.click();
      }
    });

    elements.contactImageDropzone?.addEventListener('dragover', (event) => {
      event.preventDefault();
      elements.contactImageDropzone?.classList.add('is_dragover');
    });

    elements.contactImageDropzone?.addEventListener('dragleave', () => {
      elements.contactImageDropzone?.classList.remove('is_dragover');
    });

    elements.contactImageDropzone?.addEventListener('drop', (event) => {
      event.preventDefault();
      elements.contactImageDropzone?.classList.remove('is_dragover');
      const files = event.dataTransfer?.files;
      handleContactImageFiles(files).catch((error) => PW.error(error));
    });

    elements.contactImageFile?.addEventListener('change', () => {
      const files = elements.contactImageFile?.files;
      handleContactImageFiles(files).catch((error) => PW.error(error));
      if (elements.contactImageFile instanceof HTMLInputElement) {
        elements.contactImageFile.value = '';
      }
    });

    elements.contactImageClear?.addEventListener('click', () => {
      applyContactImageValue('');
    });

    elements.contactImageCancel?.addEventListener('click', () => {
      closeContactImagePopover({ restoreFocus: true });
    });

    elements.contactImagePopover?.addEventListener('keydown', (event) => {
      if (!isContactImagePopoverOpen()) {
        return;
      }

      trapContactImagePopoverFocus(event);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || !isContactImagePopoverOpen()) {
        return;
      }

      event.preventDefault();
      closeContactImagePopover({ restoreFocus: true });
    });

    document.addEventListener('mousedown', (event) => {
      const target = event.target;
      if (target instanceof Element && !target.closest('.businesses_contact_card_menu')) {
        closeAllContactCardMenus();
      }

      mountContactImagePopover();
      if (!(elements.contactImagePopover instanceof HTMLElement) || elements.contactImagePopover.classList.contains('hidden')) {
        return;
      }

      if (!(target instanceof Element)) {
        return;
      }

      const insidePopover = elements.contactImagePopover.contains(target);
      const onAvatarControl = target.closest('.businesses_contact_card_avatar')
        || target.closest('.businesses_contact_card_avatar_button');
      if (!insidePopover && !onAvatarControl) {
        closeContactImagePopover();
      }
    });
  };
