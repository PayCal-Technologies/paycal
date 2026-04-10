<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>


:root {

  --cal-day-fore: #1D1D1F;
  --cal-day-hover-fore: #1D1D1F;
  --cal-day-radius: 8px;
  --cal-day-shadow: 0 0.05rem 0.05rem rgba(60,60,67,0.10);
  --cal-day-hover-glow: 0 0 0 3px rgba(0,122,255,0.25);
  --border-size:                         3px;
  --border-radius:                       12px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --work-back: #EAEAF0;
  --work-fore: #1D1D1F;
  --work-details-border: rgba(60, 60, 67, 0.24);
  --work-entry-back: rgba(246, 246, 248, 1.00);
  --work-entry-fore: #1D1D1F;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            rgba(234, 234, 239, 1.00);
  --color-bg-soft:                       color-mix(in srgb, rgba(234, 234, 239, 1.00) 86%, rgba(240, 240, 244, 1.00));
  --color-bg-elevated:                   color-mix(in srgb, rgba(246, 246, 248, 0.96) 90%, rgba(236, 236, 240, 1.00));
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       rgba(246, 246, 248, 0.96);
  --color-surface-muted:                 color-mix(in srgb, rgba(246, 246, 248, 0.96) 90%, rgba(236, 236, 240, 1.00));
  --color-surface-strong:                rgba(238, 238, 242, 1.00);
  --input-bg:                            var(--color-surface);

  --color-border:                        rgba( 60, 60, 67, 0.24);
  --color-border-soft:                   color-mix(in srgb, rgba( 60, 60, 67, 0.24) 72%, rgba(246, 246, 248, 0.96));
  --color-border-strong:                 color-mix(in srgb, rgba( 60, 60, 67, 0.24) 84%, black);

  --color-text:                          rgba( 29, 29, 31, 1.00);
  --color-text-muted:                    rgba( 29, 29, 31, 1.00);
  --color-text-inverse:                  #141414;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 rgba( 0, 122, 255, 1.00);
  --color-primary-active:                color-mix(in srgb, rgba( 0, 122, 255, 1.00) 82%, black);
  --color-primary-soft:                  color-mix(in srgb, rgba( 0, 122, 255, 1.00) 18%, rgba(246, 246, 248, 0.96));
  --color-on-primary:                    #141414;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        rgba(192, 0, 0, 1.00);
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, rgba( 0, 122, 255, 1.00) 24%, rgba(242, 242, 247, 1.00));
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, rgba(246, 246, 248, 0.96) 92%, rgba(236, 236, 240, 1.00));
  --elevation-2-bg:                      color-mix(in srgb, rgba(246, 246, 248, 0.96) 84%, rgba(236, 236, 240, 1.00));
  --elevation-3-bg:                      color-mix(in srgb, rgba(246, 246, 248, 0.96) 76%, rgba(236, 236, 240, 1.00));
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           rgba(243, 243, 246, 0.98);
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 82%, black);
  --button-text:                         rgba( 29, 29, 31, 1.00);
  --button-text-hover:                   #000000;
  --button-border:                       #C6C6C8 #C6C6C8 #C6C6C8 #C6C6C8;
  --button-border-active:                rgba( 60, 60, 67, 0.50);
  --button-primary-bg:                   rgba( 0, 122, 255, 1.00);
  --button-primary-text:                 #000000;
  --button-secondary-bg:                 rgba(238, 238, 242, 1.00);
  --button-secondary-text:               rgba( 29, 29, 31, 1.00);
  --button-danger-text:                  rgba(192, 0, 0, 1.00);

  --panel-bg:                            rgba(246, 246, 248, 0.96);
  --panel-text:                          rgba( 29, 29, 31, 1.00);
  --panel-border:                        rgba( 60, 60, 67, 0.24);
  --panel-head-bg:                       rgba(239, 239, 243, 0.94);
  --panel-head-text:                     rgba( 29, 29, 31, 1.00);

  --dialog-bg:                           rgba(247, 247, 249, 0.98);
  --dialog-text:                         rgba( 29, 29, 31, 1.00);
  --dialog-border:                       rgba( 60, 60, 67, 0.24);
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         rgba(234, 234, 239, 1.00);
  --calendar-border:                     rgba( 60, 60, 67, 0.24);
  --calendar-day-bg:                     rgba(248, 248, 250, 1.00);
  --calendar-day-hover:                  #E5F1FF;
  --calendar-day-today:                  color-mix(in srgb, rgba( 0, 122, 255, 1.00) 12%, #FFFFFF);
  --calendar-day-selected:               color-mix(in srgb, rgba( 0, 122, 255, 1.00) 22%, #FFFFFF);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, rgba( 29, 29, 31, 1.00));
  --calendar-range-bg:                   color-mix(in srgb, rgba( 0, 122, 255, 1.00) 18%, rgba(234, 234, 239, 1.00));
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


