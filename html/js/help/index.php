<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';

header('Content-type: application/javascript');

?>

document.addEventListener('DOMContentLoaded', function() {
  const sidebarLinks = document.querySelectorAll('.help-sidebar a');
  const openers = document.querySelectorAll('[data-help-popover-open]');
  const closers = document.querySelectorAll('[data-help-popover-close]');
  const popovers = document.querySelectorAll('.help-image-popover');
  const supportsPopoverApi = typeof HTMLElement !== 'undefined' && 'showPopover' in HTMLElement.prototype;

  function setActiveLink() {
    const currentHash = window.location.hash || '#panel-getting-started';
    sidebarLinks.forEach((link) => {
      const linkHash = '#' + (link.getAttribute('href').split('#')[1] || '');
      link.classList.toggle('active', linkHash === currentHash);
    });
  }

  function getPopover(id) {
    if (!id) {
      return null;
    }

    return document.getElementById(id);
  }

  function hidePopover(popover) {
    if (!popover) {
      return;
    }

    popover.hidden = true;
    popover.style.display = 'none';

    if (supportsPopoverApi) {
      if (popover.matches(':popover-open')) {
        popover.hidePopover();
      }
      return;
    }

    popover.classList.remove('is-open');
    popover.hidden = true;
  }

  function showPopover(popover) {
    if (!popover) {
      return;
    }

    popover.hidden = false;
    popover.style.display = 'grid';

    if (supportsPopoverApi) {
      if (!popover.matches(':popover-open')) {
        popover.showPopover();
      }
      return;
    }

    popover.classList.add('is-open');
  }

  function isPopoverOpen(popover) {
    if (!popover) {
      return false;
    }

    if (supportsPopoverApi) {
      return popover.matches(':popover-open');
    }

    return popover.classList.contains('is-open');
  }

  popovers.forEach((popover) => {
    // Ensure popovers never render expanded on first paint.
    popover.hidden = true;
    popover.classList.remove('is-open');
    popover.style.display = 'none';
  });

  openers.forEach((button) => {
    button.addEventListener('click', () => {
      const popover = getPopover(button.getAttribute('data-help-popover-open'));
      if (!popover) {
        return;
      }

      if (isPopoverOpen(popover)) {
        hidePopover(popover);
        return;
      }

      showPopover(popover);
    });
  });

  document.querySelectorAll('.help-image-popover').forEach((popover) => {
    popover.addEventListener('click', (event) => {
      if (event.target === popover) {
        hidePopover(popover);
      }
    });
  });

  document.querySelectorAll('.help-image-popover-card').forEach((card) => {
    card.addEventListener('click', (event) => {
      if (event.target !== card) {
        return;
      }

      const popover = card.closest('.help-image-popover');
      hidePopover(popover);
    });
  });

  closers.forEach((button) => {
    button.addEventListener('click', () => {
      hidePopover(getPopover(button.getAttribute('data-help-popover-close')));
    });
  });

  document.addEventListener('click', (event) => {
    popovers.forEach((popover) => {
      if (!isPopoverOpen(popover)) {
        return;
      }

      if (popover.contains(event.target)) {
        return;
      }

      const opener = document.querySelector(
        '[data-help-popover-open="' + popover.id + '"]'
      );

      if (opener && opener.contains(event.target)) {
        return;
      }

      hidePopover(popover);
    });
  });

  window.addEventListener('hashchange', setActiveLink);
  setActiveLink();
});
