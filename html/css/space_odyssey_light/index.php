<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* 2001 SPACE ODYSSEY LIGHT */
:root {
  --dialog-shadow:                       rgba(19, 25, 36, 0.18);
  --btn-back-linear-gradient:            linear-gradient(145deg, #f4f6fa 0%, #e7edf4 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #edf2f8 0%, #e0e7f0 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #c8d0de;
  --work-back:                           linear-gradient(150deg, #eef2f7 0%, #e4ebf3 100%);
  --work-fore:                           #1f2430;
  --cal-day-fore:                        #1f2430;
  --cal-day-hover-fore:                  #fff5f5;
  --cal-day-hover-glow:                  0 0 0 3px rgba(217, 37, 37, 0.22);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(19, 25, 36, 0.12);
  --work-details-border:                 #c8d0de;
  --work-entry-back:                     #f4f6fa;
  --work-entry-fore:                     #1f2430;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #e9edf3;
  --color-bg-soft:                       color-mix(in srgb, #e9edf3 86%, #f1f4f8);
  --color-bg-elevated:                   color-mix(in srgb, #f1f4f8 90%, #e6ebf2);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #f1f4f8;
  --color-surface-muted:                 color-mix(in srgb, #f1f4f8 90%, #e6ebf2);
  --color-surface-strong:                #e7edf4;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #c8d0de 72%, #f1f4f8);
  --color-border-strong:                 color-mix(in srgb, #c8d0de 84%, black);

  --color-text:                          #1f2430;
  --color-text-muted:                    #3a4254;
  --color-text-inverse:                  #fff5f5;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #f06a6a;
  --color-primary-active:                color-mix(in srgb, #d92525 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #d92525 18%, #f1f4f8);
  --color-on-primary:                    #fff5f5;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, #d92525 24%, #f3f5f8);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #f1f4f8 92%, #e6ebf2);
  --elevation-2-bg:                      color-mix(in srgb, #f1f4f8 84%, #e6ebf2);
  --elevation-3-bg:                      color-mix(in srgb, #f1f4f8 76%, #e6ebf2);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #edf1f6;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 82%, black);
  --button-text:                         #1f2430;
  --button-border:                       #c8d0de;
  --button-border-active:                #d92525;
  --button-primary-bg:                   #d92525;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #e3e9f1;
  --button-secondary-text:               #1f2430;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #f1f4f8;
  --panel-text:                          #1f2430;
  --panel-border:                        #c8d0de;
  --panel-head-bg:                       #e7edf4;
  --panel-head-text:                     #1f2430;

  --dialog-bg:                           #f4f6fa;
  --dialog-text:                         #1f2430;
  --dialog-border:                       #c8d0de;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #e9edf3;
  --calendar-border:                     #c8d0de;
  --calendar-day-bg:                     #f4f6fa;
  --calendar-day-hover:                  #e3e9f2;
  --calendar-day-today:                  color-mix(in srgb, #d92525 12%, #ffffff);
  --calendar-day-selected:               color-mix(in srgb, #d92525 22%, #ffffff);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #1f2430);
  --calendar-range-bg:                   color-mix(in srgb, #d92525 18%, #e9edf3);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


