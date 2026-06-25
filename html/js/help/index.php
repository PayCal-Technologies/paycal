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
  const supportsInvokerCommands = typeof HTMLButtonElement !== 'undefined'
    && Object.prototype.hasOwnProperty.call(HTMLButtonElement.prototype, 'commandForElement');

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

  function setPopoverExpanded(popover, expanded) {
    if (!popover || !popover.id) {
      return;
    }

    document
      .querySelectorAll('[data-help-popover-open="' + popover.id + '"]')
      .forEach((button) => {
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      });
  }

  function syncPopoverState(popover) {
    if (!popover) {
      return;
    }

    const open = isPopoverOpen(popover);
    setPopoverExpanded(popover, open);

    if (open) {
      popover.hidden = false;
      return;
    }

    popover.hidden = true;
    popover.classList.remove('is-open');
  }

  function hidePopover(popover) {
    if (!popover) {
      return;
    }

    setPopoverExpanded(popover, false);
    popover.hidden = true;

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

    if (supportsPopoverApi) {
      if (!popover.matches(':popover-open')) {
        popover.showPopover();
      }
      setPopoverExpanded(popover, true);
      return;
    }

    popover.classList.add('is-open');
    setPopoverExpanded(popover, true);
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
    setPopoverExpanded(popover, false);
    popover.addEventListener('toggle', () => {
      syncPopoverState(popover);
    });
  });

  if (!supportsInvokerCommands) {
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

    closers.forEach((button) => {
      button.addEventListener('click', () => {
        hidePopover(getPopover(button.getAttribute('data-help-popover-close')));
      });
    });
  }

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

  if (!supportsInvokerCommands) {
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
  }

  window.addEventListener('hashchange', setActiveLink);
  setActiveLink();
});
