<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* ZETA LIGHT */
:root {





  --dialog-shadow:                       rgba(20, 27, 49, 0.22);





  --btn-back-linear-gradient:            linear-gradient(145deg, #f7f9fe 0%, #dce3f2 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #edf2fb 0%, #d0d9ed 100%);





  --border-size:                         1px;
  --border-radius:                       5px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #99a7c9;

  --work-back:                           #e3e8f3;
  --work-fore:                           #1b2340;

  --cal-day-fore:                        #1b2340;
  --cal-day-hover-fore:                  #eef2ff;
  --cal-day-hover-glow:                  0 0 0 2px rgba(64, 93, 181, 0.26);

  --cal-day-radius:                      5px;
  --cal-day-shadow:                      0 1px 2px rgba(20, 27, 49, 0.16);

  --work-details-border:                 #99a7c9;
  --work-entry-back:                     #f3f6fc;
  --work-entry-fore:                     #1b2340;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #dfe4f0;
  --color-bg-soft:                       color-mix(in srgb, #dfe4f0 86%, #ebeef6);
  --color-bg-elevated:                   color-mix(in srgb, #e8ecf6 90%, #ebeef6);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #e8ecf6;
  --color-surface-muted:                 color-mix(in srgb, #e8ecf6 90%, #ebeef6);
  --color-surface-strong:                #ebeef6;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #99a7c9 72%, #e8ecf6);
  --color-border-strong:                 color-mix(in srgb, #99a7c9 84%, black);

  --color-text:                          #1b2340;
  --color-text-muted:                    #323d5f;
  --color-text-inverse:                  #eef2ff;
  --color-text-disabled:                 #000000;

  --color-primary:                       #3d59ab;
  --color-primary-hover:                 #c0cae2;
  --color-primary-active:                color-mix(in srgb, #3d59ab 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #3d59ab 18%, #e8ecf6);
  --color-on-primary:                    #eef2ff;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-primary);
  --color-selection:                     color-mix(in srgb, #405db5 24%, #dfe4f0);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #e8ecf6 92%, #ebeef6);
  --elevation-2-bg:                      color-mix(in srgb, #e8ecf6 84%, #ebeef6);
  --elevation-3-bg:                      color-mix(in srgb, #e8ecf6 76%, #ebeef6);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f3f6fc;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #1b2340;
  --button-border:                       #ffffff #7d8db2 #7d8db2 #ffffff;
  --button-border-active:                #7d8db2 #ffffff #ffffff #7d8db2;
  --button-primary-bg:                   #3d59ab;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #dce3f2;
  --button-secondary-text:               #1b2340;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #e8ecf6;
  --panel-text:                          #1b2340;
  --panel-border:                        #99a7c9;
  --panel-head-bg:                       linear-gradient(180deg, #f7f9fe 0%, #ccd5ea 100%);
  --panel-head-text:                     #27366b;

  --dialog-bg:                           #f3f6fc;
  --dialog-text:                         #1b2340;
  --dialog-border:                       #99a7c9;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #dfe4f0;
  --calendar-border:                     #99a7c9;
  --calendar-day-bg:                     #f3f6fc;
  --calendar-day-hover:                  #405db5;
  --calendar-day-today:                  color-mix(in srgb, #405db5 12%, #f3f6fc);
  --calendar-day-selected:               color-mix(in srgb, #405db5 22%, #f3f6fc);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #1b2340);
  --calendar-range-bg:                   color-mix(in srgb, #405db5 18%, #dfe4f0);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #ffffff;
}


