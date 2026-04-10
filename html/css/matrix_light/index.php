<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* MATRIX LIGHT */
:root {
  --dialog-shadow:                       rgba(12, 38, 16, 0.20);
  --btn-back-linear-gradient:            linear-gradient(145deg, #edf7ee 0%, #dff0e0 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #dff0e0 0%, #cee8d0 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #87b58c;
  --work-back:                           linear-gradient(150deg, #e6f3e7 0%, #d6ead8 100%);
  --work-fore:                           #17321a;
  --cal-day-fore:                        #17321a;
  --cal-day-hover-fore:                  #07220f;
  --cal-day-hover-glow:                  0 0 0 3px rgba(0, 177, 64, 0.24);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(12, 38, 16, 0.14);
  --work-details-border:                 #87b58c;
  --work-entry-back:                     #edf7ee;
  --work-entry-fore:                     #17321a;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #d9edd9;
  --color-bg-soft:                       color-mix(in srgb, #d9edd9 86%, #e3f2e3);
  --color-bg-elevated:                   color-mix(in srgb, #e6f3e7 90%, #e3f2e3);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #e6f3e7;
  --color-surface-muted:                 color-mix(in srgb, #e6f3e7 90%, #e3f2e3);
  --color-surface-strong:                #e3f2e3;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #87b58c 72%, #e6f3e7);
  --color-border-strong:                 color-mix(in srgb, #87b58c 84%, black);

  --color-text:                          #17321a;
  --color-text-muted:                    #2d5632;
  --color-text-inverse:                  #07220f;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #53c877;
  --color-primary-active:                color-mix(in srgb, #00b140 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #00b140 18%, #e6f3e7);
  --color-on-primary:                    #07220f;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #00b140 24%, #d9edd9);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #e6f3e7 92%, #e3f2e3);
  --elevation-2-bg:                      color-mix(in srgb, #e6f3e7 84%, #e3f2e3);
  --elevation-3-bg:                      color-mix(in srgb, #e6f3e7 76%, #e3f2e3);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #edf7ee;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #17321a;
  --button-border:                       #87b58c;
  --button-border-active:                #00b140;
  --button-primary-bg:                   #00b140;
  --button-primary-text:                 #07220f;
  --button-secondary-bg:                 #d8ead9;
  --button-secondary-text:               #17321a;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #e6f3e7;
  --panel-text:                          #17321a;
  --panel-border:                        #87b58c;
  --panel-head-bg:                       #d6ebd8;
  --panel-head-text:                     #0d6f29;

  --dialog-bg:                           #edf7ee;
  --dialog-text:                         #17321a;
  --dialog-border:                       #87b58c;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #d9edd9;
  --calendar-border:                     #87b58c;
  --calendar-day-bg:                     #f1f9f2;
  --calendar-day-hover:                  #00b140;
  --calendar-day-today:                  color-mix(in srgb, #00b140 12%, #f1f9f2);
  --calendar-day-selected:               color-mix(in srgb, #00b140 22%, #f1f9f2);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #17321a);
  --calendar-range-bg:                   color-mix(in srgb, #00b140 18%, #d9edd9);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


