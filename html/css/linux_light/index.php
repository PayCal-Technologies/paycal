<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* LINUX LIGHT */
:root {





  --dialog-shadow:                       rgba(59, 37, 28, 0.25);





  --btn-back-linear-gradient:            linear-gradient(145deg, #f8f1e9 0%, #eedfd1 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #f2e5d7 0%, #e7d1bb 100%);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #d6b79f;

  --work-back:                           linear-gradient(150deg, #f7efe6 0%, #eddccf 100%);
  --work-fore:                           #2d2330;

  --cal-day-fore:                        #2d2330;
  --cal-day-hover-fore:                  #201815;
  --cal-day-hover-glow:                  0 0 0 3px rgba(233, 84, 32, 0.25);

  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(59, 37, 28, 0.18);

  --work-details-border:                 #d6b79f;
  --work-entry-back:                     #f9f3ec;
  --work-entry-fore:                     #2d2330;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #f4e6d9;
  --color-bg-soft:                       color-mix(in srgb, #f4e6d9 86%, #f8eee5);
  --color-bg-elevated:                   color-mix(in srgb, #f7efe7 90%, #f4e9df);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #f7efe7;
  --color-surface-muted:                 color-mix(in srgb, #f7efe7 90%, #f3e8de);
  --color-surface-strong:                #eee2d6;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #d6b79f 72%, #f7efe7);
  --color-border-strong:                 color-mix(in srgb, #d6b79f 84%, black);

  --color-text:                          #2d2330;
  --color-text-muted:                    #4a3a4f;
  --color-text-inverse:                  #201815;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #ff7b49;
  --color-primary-active:                color-mix(in srgb, #c24a1c 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #c24a1c 18%, #f7efe7);
  --color-on-primary:                    #201815;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #c62828;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, #c24a1c 24%, #f4e6d9);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #f7efe7 92%, #f4e9df);
  --elevation-2-bg:                      color-mix(in srgb, #f7efe7 84%, #f4e9df);
  --elevation-3-bg:                      color-mix(in srgb, #f7efe7 76%, #f4e9df);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f2e7dc;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #2d2330;
  --button-border:                       #d6b79f;
  --button-border-active:                #e95420;
  --button-primary-bg:                   #eb5723;
  --button-primary-text:                 #201815;
  --button-secondary-bg:                 #eadbcd;
  --button-secondary-text:               #2d2330;
  --button-danger-text:                  #c62828;

  --panel-bg:                            #f7efe7;
  --panel-text:                          #2d2330;
  --panel-border:                        #d6b79f;
  --panel-head-bg:                       #f1e0d3;
  --panel-head-text:                     #a33f1a;

  --dialog-bg:                           #f9f3ec;
  --dialog-text:                         #2d2330;
  --dialog-border:                       #d6b79f;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #f4e6d9;
  --calendar-border:                     #d6b79f;
  --calendar-day-bg:                     #f9f3ec;
  --calendar-day-hover:                  #e95420;
  --calendar-day-today:                  color-mix(in srgb, #e95420 12%, #fffdf9);
  --calendar-day-selected:               color-mix(in srgb, #e95420 22%, #fffdf9);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #2d2330);
  --calendar-range-bg:                   color-mix(in srgb, #e95420 18%, #fff3e6);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;
}


