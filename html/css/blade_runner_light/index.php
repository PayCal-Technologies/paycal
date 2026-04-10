<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* BLADE RUNNER LIGHT */
:root {
  --dialog-shadow:                       rgba(37, 24, 17, 0.24);
  --btn-back-linear-gradient:            linear-gradient(145deg, #fbf3ea 0%, #f1e4d5 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #f2e4d5 0%, #e8d5c2 100%);
  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #c9ae97;
  --work-back:                           linear-gradient(150deg, #f4eadf 0%, #eadbcb 100%);
  --work-fore:                           #2f241f;
  --cal-day-fore:                        #2f241f;
  --cal-day-hover-fore:                  #221711;
  --cal-day-hover-glow:                  0 0 0 3px rgba(255, 138, 61, 0.25);
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(37, 24, 17, 0.18);
  --work-details-border:                 #c9ae97;
  --work-entry-back:                     #fbf3ea;
  --work-entry-fore:                     #2f241f;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #efe4d6;
  --color-bg-soft:                       color-mix(in srgb, #efe4d6 86%, #f4ece2);
  --color-bg-elevated:                   color-mix(in srgb, #f4eadf 90%, #f4ece2);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #f4eadf;
  --color-surface-muted:                 color-mix(in srgb, #f4eadf 90%, #f4ece2);
  --color-surface-strong:                #f4ece2;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #c9ae97 72%, #f4eadf);
  --color-border-strong:                 color-mix(in srgb, #c9ae97 84%, black);

  --color-text:                          #2f241f;
  --color-text-muted:                    #4c3a31;
  --color-text-inverse:                  #221711;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #ffa86e;
  --color-primary-active:                color-mix(in srgb, #ff8a3d 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #ff8a3d 18%, #f4eadf);
  --color-on-primary:                    #221711;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #ff8a3d 24%, #efe4d6);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #f4eadf 92%, #f4ece2);
  --elevation-2-bg:                      color-mix(in srgb, #f4eadf 84%, #f4ece2);
  --elevation-3-bg:                      color-mix(in srgb, #f4eadf 76%, #f4ece2);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #fbf3ea;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #2f241f;
  --button-border:                       #c9ae97;
  --button-border-active:                #ff8a3d;
  --button-primary-bg:                   #ff8a3d;
  --button-primary-text:                 #221711;
  --button-secondary-bg:                 #e9d8c6;
  --button-secondary-text:               #2f241f;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #f4eadf;
  --panel-text:                          #2f241f;
  --panel-border:                        #c9ae97;
  --panel-head-bg:                       #f0ddcc;
  --panel-head-text:                     #8f4e2b;

  --dialog-bg:                           #fbf3ea;
  --dialog-text:                         #2f241f;
  --dialog-border:                       #c9ae97;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #efe4d6;
  --calendar-border:                     #c9ae97;
  --calendar-day-bg:                     #fbf3ea;
  --calendar-day-hover:                  #ff8a3d;
  --calendar-day-today:                  color-mix(in srgb, #ff8a3d 12%, #fbf3ea);
  --calendar-day-selected:               color-mix(in srgb, #ff8a3d 22%, #fbf3ea);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #2f241f);
  --calendar-range-bg:                   color-mix(in srgb, #ff8a3d 18%, #efe4d6);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


