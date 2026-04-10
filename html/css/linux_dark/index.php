<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* LINUX DARK */
:root {





  --dialog-shadow:                       rgba(0, 0, 0, 0.72);





  --btn-back-linear-gradient:            linear-gradient(145deg, #2f2735 0%, #241f28 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #3a2f42 0%, #2b2431 100%);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #4a3f54;

  --work-back:                           linear-gradient(150deg, #241f28 0%, #1d1921 100%);
  --work-fore:                           #f5f1ec;

  --cal-day-fore:                        #f5f1ec;
  --cal-day-hover-fore:                  #1f1816;
  --cal-day-hover-glow:                  0 0 0 3px rgba(233, 84, 32, 0.35);

  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);

  --work-details-border:                 #594b63;
  --work-entry-back:                     #2f2735;
  --work-entry-fore:                     #f5f1ec;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #17131a;
  --color-bg-soft:                       color-mix(in srgb, #17131a 86%, #1f1a22);
  --color-bg-elevated:                   color-mix(in srgb, #241f28 90%, #1f1a22);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #241f28;
  --color-surface-muted:                 color-mix(in srgb, #241f28 90%, #1f1a22);
  --color-surface-strong:                #1f1a22;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #594b63 72%, #241f28);
  --color-border-strong:                 color-mix(in srgb, #594b63 84%, black);

  --color-text:                          #f5f1ec;
  --color-text-muted:                    #d5ccc1;
  --color-text-inverse:                  #1f1816;
  --color-text-disabled:                 color-mix(in srgb, #f5f1ec 52%, #241f28);

  --color-primary:                       #eb5723;
  --color-primary-hover:                 #ff7b49;
  --color-primary-active:                color-mix(in srgb, #eb5723 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #eb5723 18%, #241f28);
  --color-on-primary:                    #1f1816;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff4f64;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #e95420 24%, #17131a);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #241f28 92%, #1f1a22);
  --elevation-2-bg:                      color-mix(in srgb, #241f28 84%, #1f1a22);
  --elevation-3-bg:                      color-mix(in srgb, #241f28 76%, #1f1a22);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #2f2735;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #6e5a79;
  --button-border-active:                #ff7b49;
  --button-primary-bg:                   #eb5723;
  --button-primary-text:                 #1f1816;
  --button-secondary-bg:                 #3a3042;
  --button-secondary-text:               #f5f1ec;
  --button-danger-text:                  #ff4f64;

  --panel-bg:                            #241f28;
  --panel-text:                          #f5f1ec;
  --panel-border:                        #594b63;
  --panel-head-bg:                       #31283a;
  --panel-head-text:                     #ff7b49;

  --dialog-bg:                           #2f2735;
  --dialog-text:                         #f5f1ec;
  --dialog-border:                       #594b63;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #17131a;
  --calendar-border:                     #594b63;
  --calendar-day-bg:                     #1d1921;
  --calendar-day-hover:                  #e95420;
  --calendar-day-today:                  color-mix(in srgb, #e95420 12%, #1d1921);
  --calendar-day-selected:               color-mix(in srgb, #e95420 22%, #1d1921);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #f5f1ec);
  --calendar-range-bg:                   color-mix(in srgb, #e95420 18%, #17131a);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


