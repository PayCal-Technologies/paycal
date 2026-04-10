<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>


:root {
  --cal-day-fore: #F5F5F7;
  --cal-day-hover-fore: #F5F5F7;
  --cal-day-radius: 8px;
  --cal-day-shadow: 0 0.05rem 0.05rem rgba(0,0,0,0.30);
    --cal-day-hover-glow: 0 0 0 3px rgba(10,132,255,0.35);
    --cal-day-date-label: #0A84FF;
    --work-details-back: #1C1C1E;
  --border-size:                         3px;
  --border-radius:                       12px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --work-back: #1C1C1E;
  --work-fore: #F5F5F7;
  --work-details-border: rgba(84, 84, 88, 0.65);
  --work-entry-back: #2C2C2E;
  --work-entry-fore: #F5F5F7;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            rgba( 28, 28, 30, 1.00);
  --color-bg-soft:                       color-mix(in srgb, rgba( 28, 28, 30, 1.00) 86%, rgba( 28, 28, 30, 1.00));
  --color-bg-elevated:                   color-mix(in srgb, rgba( 44, 44, 46, 0.88) 90%, rgba( 28, 28, 30, 1.00));
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       rgba( 44, 44, 46, 0.88);
  --color-surface-muted:                 color-mix(in srgb, rgba( 44, 44, 46, 0.88) 90%, rgba( 28, 28, 30, 1.00));
  --color-surface-strong:                rgba( 28, 28, 30, 1.00);
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, rgba( 84, 84, 88, 0.65) 72%, rgba( 44, 44, 46, 0.88));
  --color-border-strong:                 color-mix(in srgb, rgba( 84, 84, 88, 0.65) 84%, black);

  --color-text:                          rgba(245, 245, 247, 1.00);
  --color-text-muted:                    rgba(245, 245, 247, 1.00);
  --color-text-inverse:                  #1F1F1F;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #ffffff;
  --color-primary-hover:                 rgba( 10, 132, 255, 1.00);
  --color-primary-active:                color-mix(in srgb, rgba( 10, 132, 255, 1.00) 82%, black);
  --color-primary-soft:                  color-mix(in srgb, rgba( 10, 132, 255, 1.00) 18%, rgba( 44, 44, 46, 0.88));
  --color-on-primary:                    #1F1F1F;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        rgba(192, 0, 0, 1.00);
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, rgba( 10, 132, 255, 1.00) 24%, rgba( 28, 28, 30, 1.00));
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, rgba( 44, 44, 46, 0.88) 92%, rgba( 28, 28, 30, 1.00));
  --elevation-2-bg:                      color-mix(in srgb, rgba( 44, 44, 46, 0.88) 84%, rgba( 28, 28, 30, 1.00));
  --elevation-3-bg:                      color-mix(in srgb, rgba( 44, 44, 46, 0.88) 76%, rgba( 28, 28, 30, 1.00));
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           rgba( 58, 58, 60, 0.90);
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #5F5F62 #5F5F62 #5F5F62 #5F5F62;
  --button-border-active:                #222222 #222222 #000000 #222222;
  --button-primary-bg:                   rgba( 10, 132, 255, 1.00);
  --button-primary-text:                 #000000;
  --button-secondary-bg:                 rgba( 58, 58, 60, 0.90);
  --button-secondary-text:               rgba(245, 245, 247, 1.00);
  --button-danger-text:                  rgba(192, 0, 0, 1.00);

  --panel-bg:                            rgba( 44, 44, 46, 0.88);
  --panel-text:                          rgba(245, 245, 247, 1.00);
  --panel-border:                        rgba( 84, 84, 88, 0.65);
  --panel-head-bg:                       rgba( 58, 58, 60, 0.90);
  --panel-head-text:                     rgba(245, 245, 247, 1.00);

  --dialog-bg:                           rgba( 44, 44, 46, 0.94);
  --dialog-text:                         rgba(245, 245, 247, 1.00);
  --dialog-border:                       rgba( 84, 84, 88, 0.65);
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         rgba( 28, 28, 30, 1.00);
  --calendar-border:                     rgba( 84, 84, 88, 0.65);
  --calendar-day-bg:                     #2C2C2E;
  --calendar-day-hover:                  #3A3A3C;
  --calendar-day-today:                  color-mix(in srgb, rgba( 10, 132, 255, 1.00) 12%, #2C2C2E);
  --calendar-day-selected:               color-mix(in srgb, rgba( 10, 132, 255, 1.00) 22%, #2C2C2E);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, rgba(245, 245, 247, 1.00));
  --calendar-range-bg:                   color-mix(in srgb, rgba( 10, 132, 255, 1.00) 18%, rgba( 28, 28, 30, 1.00));
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-active: #000000;
}


