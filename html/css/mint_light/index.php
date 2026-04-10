<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* MINT LIGHT */
:root {





  --dialog-shadow:                       rgba(16, 49, 34, 0.20);





  --btn-back-linear-gradient:            linear-gradient(145deg, #f4fbf6 0%, #e8f6ed 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #e8f6ed 0%, #dbefdf 100%);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #9ec8b1;

  --work-back:                           linear-gradient(150deg, #eff9f2 0%, #e3f3e9 100%);
  --work-fore:                           #1d3b2d;

  --cal-day-fore:                        #1d3b2d;
  --cal-day-hover-fore:                  #0f241a;
  --cal-day-hover-glow:                  0 0 0 3px rgba(102, 187, 106, 0.24);

  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(16, 49, 34, 0.15);

  --work-details-border:                 #9ec8b1;
  --work-entry-back:                     #f4fbf6;
  --work-entry-fore:                     #1d3b2d;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #e6f5eb;
  --color-bg-soft:                       color-mix(in srgb, #e6f5eb 86%, #edf8f1);
  --color-bg-elevated:                   color-mix(in srgb, #eef9f1 90%, #edf8f1);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #eef9f1;
  --color-surface-muted:                 color-mix(in srgb, #eef9f1 90%, #edf8f1);
  --color-surface-strong:                #edf8f1;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #9ec8b1 72%, #eef9f1);
  --color-border-strong:                 color-mix(in srgb, #9ec8b1 84%, black);

  --color-text:                          #1d3b2d;
  --color-text-muted:                    #2d5a45;
  --color-text-inverse:                  #0f241a;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #88cf8b;
  --color-primary-active:                color-mix(in srgb, #66bb6a 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #66bb6a 18%, #eef9f1);
  --color-on-primary:                    #0f241a;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #66bb6a 24%, #e6f5eb);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #eef9f1 92%, #edf8f1);
  --elevation-2-bg:                      color-mix(in srgb, #eef9f1 84%, #edf8f1);
  --elevation-3-bg:                      color-mix(in srgb, #eef9f1 76%, #edf8f1);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f4fbf6;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #1d3b2d;
  --button-border:                       #9ec8b1;
  --button-border-active:                #66bb6a;
  --button-primary-bg:                   #66bb6a;
  --button-primary-text:                 #0f241a;
  --button-secondary-bg:                 #dcefe3;
  --button-secondary-text:               #1d3b2d;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #eef9f1;
  --panel-text:                          #1d3b2d;
  --panel-border:                        #9ec8b1;
  --panel-head-bg:                       #d9f0e2;
  --panel-head-text:                     #1c5e3f;

  --dialog-bg:                           #f4fbf6;
  --dialog-text:                         #1d3b2d;
  --dialog-border:                       #9ec8b1;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #e6f5eb;
  --calendar-border:                     #9ec8b1;
  --calendar-day-bg:                     #f7fcf8;
  --calendar-day-hover:                  #66bb6a;
  --calendar-day-today:                  color-mix(in srgb, #66bb6a 12%, #f7fcf8);
  --calendar-day-selected:               color-mix(in srgb, #66bb6a 22%, #f7fcf8);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #1d3b2d);
  --calendar-range-bg:                   color-mix(in srgb, #66bb6a 18%, #e6f5eb);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


