<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * AccessibilityHelper.php
 *
 * Purpose: Accessibility utility helper for ARIA attributes, focus behavior,
 * keyboard affordances, and environment-aware UI compliance helpers.
 *
 * Developer notes:
 * - Keep generated attributes and accessibility defaults aligned with the
 *   frontend semantics consumed by server-rendered and dynamic interfaces.
 * - This helper should shape accessible output, not become a general UI state
 *   container.
 *
 * Architectural role:
 * - Reusable domain helper consumed by templates, controllers, and UI-support
 *   code that need stable accessibility conventions.
 * - Encapsulates presentational accessibility rules outside the HTTP layer.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @subpackage Core
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * AccessibilityHelper
 *
 * Utilities for keyboard navigation, focus management, ARIA attributes,
 * and accessibility compliance across dynamic interfaces.
 */
final class AccessibilityHelper
{
  /**
   * Generate ARIA attributes for a dismissible alert/notification
   * @return array<string, string>
   */
  public static function ariaAlert(string $role = 'alert', bool $live = true): array
  {
    return [
      'role' => $role,
      'aria-live' => $live ? 'polite' : 'off',
      'aria-atomic' => 'true',
    ];
  }

  /**
   * Generate ARIA attributes for a modal dialog
   * @return array<string, string>
   */
  public static function ariaModal(string $labelledBy = '', bool $hidden = false): array
  {
    $attrs = [
      'role' => 'dialog',
      'aria-modal' => 'true',
    ];

    if ($labelledBy !== '') {
      $attrs['aria-labelledby'] = $labelledBy;
    }

    if ($hidden) {
      $attrs['aria-hidden'] = 'true';
    }

    return $attrs;
  }

  /**
   * Generate ARIA attributes for a button that controls visibility of another element
   * @return array<string, string>
   */
  public static function ariaExpanded(string $controlledElementId, bool $expanded = false): array
  {
    return [
      'aria-expanded' => $expanded ? 'true' : 'false',
      'aria-controls' => $controlledElementId,
    ];
  }

  /**
   * Generate ARIA attributes for a toggle button (e.g., menu, sidebar)
   * @return array<string, string>
   */
  public static function ariaToggle(string $controlledElementId, bool $pressed = false): array
  {
    return [
      'aria-pressed' => $pressed ? 'true' : 'false',
      'aria-controls' => $controlledElementId,
    ];
  }

  /**
   * Generate ARIA attributes for a tab in a tablist
   * @return array<string, string>
   */
  public static function ariaTab(string $panelId, bool $selected = false): array
  {
    return [
      'role' => 'tab',
      'aria-selected' => $selected ? 'true' : 'false',
      'aria-controls' => $panelId,
      'tabindex' => $selected ? '0' : '-1',
    ];
  }

  /**
   * Generate ARIA attributes for a tabpanel
   * @return array<string, string>
   */
  public static function ariaTabpanel(string $tabId): array
  {
    return [
      'role' => 'tabpanel',
      'aria-labelledby' => $tabId,
    ];
  }

  /**
   * Generate ARIA attributes for a combobox (autocomplete/dropdown)
   * @return array<string, string>
   */
  public static function ariaCombobox(string $listboxId, bool $expanded = false): array
  {
    return [
      'role' => 'combobox',
      'aria-expanded' => $expanded ? 'true' : 'false',
      'aria-owns' => $listboxId,
      'aria-haspopup' => 'listbox',
    ];
  }

  /**
   * Render HTML output for skip-to-main-content link
   */
  public static function renderSkipLink(string $mainContentId = 'main-content'): string
  {
    return <<<HTML
<a href="#{$mainContentId}" class="sr-only sr-only-focusable">
  Skip to main content
</a>
HTML;
  }

  /**
   * Render HTML attributes for screen reader only (sr-only) content
   * @return array<string, string>
   */
  public static function srOnly(bool $focusable = false): array
  {
    $class = $focusable ? 'sr-only sr-only-focusable' : 'sr-only';
    return ['class' => $class];
  }

  /**
   * Generate keyboard event handler attributes for common shortcuts
   * (Note: Actual event binding happens in JavaScript for CSP compliance)
   * @return array<string, string>
   */
  public static function keyboardShortcut(string $keys, string $actionId = ''): array
  {
    return [
      'data-keyboard-shortcut' => $keys,
      'data-keyboard-action' => $actionId,
    ];
  }

  /**
   * Check if focus management is needed based on page context
   */
  public static function shouldManageFocus(): bool
  {
    return Authentication::validateAndTouchSession() && !Environment::devSecurityDisabled();
  }

  /**
   * Get focus trap boundaries for modal dialogs
   * @return array{first: string, last: string}
   */
  public static function focusTrapBoundaries(): array
  {
    return [
      'first' => 'data-focus-trap-first',
      'last' => 'data-focus-trap-last',
    ];
  }

  /**
   * Generate announcement text for dynamic content updates
   */
  public static function announceUpdate(string $message, string $type = 'info'): string
  {
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $role = match ($type) {
      'error' => 'alert',
      'warning' => 'alert',
      default => 'status',
    };

    return "<div role=\"{$role}\" aria-live=\"polite\" aria-atomic=\"true\" class=\"sr-only\">{$message}</div>";
  }
}
