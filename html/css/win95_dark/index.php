<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>
:root {
  /* Foundation palette */
  --foundation-night-100:                rgba(0, 0, 0, 0.97);
  --foundation-night-200:                rgba(0, 16, 16, 0.97);
  --foundation-night-300:                rgba(32, 32, 32, 0.97);
  --foundation-text-100:                 rgba(255, 255, 255, 0.97);
  --foundation-text-200:                 rgba(210, 210, 210, 0.97);
  --foundation-blue-100:                 rgba(0, 0, 144, 0.97);
  --foundation-blue-200:                 rgba(0, 32, 176, 0.97);
  --foundation-border-100:               rgba(130, 130, 96, 0.97);
  --foundation-danger-100:               rgba(192, 0, 0, 0.97);

  /* Semantic tokens */
  --color-bg:                            var(--foundation-night-200);
  --color-bg-soft:                       rgba(0, 10, 10, 0.97);
  --color-bg-elevated:                   rgba(12, 24, 24, 0.97);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.66);

  --color-surface:                       var(--foundation-night-200);
  --color-surface-muted:                 rgba(8, 20, 20, 0.97);
  --color-surface-strong:                var(--foundation-night-100);
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--foundation-border-100);
  --color-border-soft:                   rgba(98, 98, 70, 0.97);
  --color-border-strong:                 rgba(170, 170, 130, 0.97);

  --color-text:                          var(--foundation-text-100);
  --color-text-muted:                    var(--foundation-text-200);
  --color-text-inverse:                  #0F0F0F;
  --color-text-disabled:                 rgba(158, 158, 158, 0.97);

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 var(--foundation-blue-200);
  --color-primary-active:                rgba(0, 0, 108, 0.97);
  --color-primary-soft:                  rgba(0, 0, 144, 0.26);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2C8A3F;
  --color-warning:                       #D88722;
  --color-danger:                        var(--foundation-danger-100);
  --color-info:                          #2B88D8;

  --color-hover:                         rgba(255, 255, 255, 0.10);
  --color-active:                        rgba(255, 255, 255, 0.16);
  --color-focus-ring:                    rgba(96, 160, 255, 0.97);
  --color-selection:                     rgba(0, 0, 144, 0.30);
  --color-highlight:                     rgba(255, 234, 150, 0.22);
  --color-disabled-bg:                   rgba(255, 255, 255, 0.06);

  --elevation-1-bg:                      rgba(12, 20, 20, 0.97);
  --elevation-2-bg:                      rgba(16, 28, 28, 0.97);
  --elevation-3-bg:                      rgba(22, 34, 34, 0.97);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.62);

  --shadow-sm:                           0 1px 2px rgba(0, 0, 0, 0.30);
  --shadow-md:                           0 8px 18px rgba(0, 0, 0, 0.36);
  --shadow-lg:                           0 16px 36px rgba(0, 0, 0, 0.42);

  /* Component tokens */
  --button-bg:                           var(--foundation-night-100);
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 75%, white);
  --button-bg-active:                    var(--foundation-night-300);
  --button-text:                         var(--color-text);
  --button-border:                       var(--color-border-soft);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   var(--foundation-blue-100);
  --button-primary-text:                 var(--color-on-primary);
  --button-secondary-bg:                 var(--foundation-night-300);
  --button-secondary-text:               var(--color-text);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       var(--foundation-blue-100);
  --panel-head-text:                     var(--color-on-primary);

  --dialog-bg:                           var(--color-surface);
  --dialog-text:                         var(--color-text);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     var(--color-surface);
  --calendar-day-hover:                  color-mix(in srgb, var(--color-primary) 20%, var(--calendar-day-bg));
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 14%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 26%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   rgba(0, 0, 144, 0.24);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --cal-day-fore:                        var(--color-text);
  --cal-day-hover-glow:                  0 0 10px 10px rgba(128, 128, 128, 1);
  --border-size:                         1px;
  --border-radius:                       0;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
}




