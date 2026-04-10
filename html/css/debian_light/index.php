<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* DEBIAN LIGHT */
:root {





  --dialog-shadow:                       rgba(66, 18, 42, 0.20);





  --btn-back-linear-gradient:            linear-gradient(145deg, #fff8fb 0%, #fbeef4 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #fbeef4 0%, #f4dde8 100%);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #d7a4bc;

  --work-back:                           linear-gradient(150deg, #fdf1f6 0%, #f6e2ec 100%);
  --work-fore:                           #4a1830;

  --cal-day-fore:                        #4a1830;
  --cal-day-hover-fore:                  #fff4f8;
  --cal-day-hover-glow:                  0 0 0 3px rgba(168, 0, 48, 0.24);

  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(66, 18, 42, 0.16);

  --work-details-border:                 #d7a4bc;
  --work-entry-back:                     #fff8fb;
  --work-entry-fore:                     #4a1830;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #f8e2ec;
  --color-bg-soft:                       color-mix(in srgb, #f8e2ec 86%, #fbecf2);
  --color-bg-elevated:                   color-mix(in srgb, #fdf1f6 90%, #fbecf2);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #fdf1f6;
  --color-surface-muted:                 color-mix(in srgb, #fdf1f6 90%, #fbecf2);
  --color-surface-strong:                #fbecf2;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #d7a4bc 72%, #fdf1f6);
  --color-border-strong:                 color-mix(in srgb, #d7a4bc 84%, black);

  --color-text:                          #4a1830;
  --color-text-muted:                    #6c2b49;
  --color-text-inverse:                  #fff4f8;
  --color-text-disabled:                 #000000;

  --color-primary:                       #a80030;
  --color-primary-hover:                 #c65079;
  --color-primary-active:                color-mix(in srgb, #a80030 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #a80030 18%, #fdf1f6);
  --color-on-primary:                    #fff4f8;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #a80030;
  --color-selection:                     color-mix(in srgb, #a80030 24%, #f8e2ec);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #fdf1f6 92%, #fbecf2);
  --elevation-2-bg:                      color-mix(in srgb, #fdf1f6 84%, #fbecf2);
  --elevation-3-bg:                      color-mix(in srgb, #fdf1f6 76%, #fbecf2);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #fff8fb;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #4a1830;
  --button-border:                       #d7a4bc;
  --button-border-active:                #a80030;
  --button-primary-bg:                   #a80030;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #f0d5e1;
  --button-secondary-text:               #4a1830;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #fdf1f6;
  --panel-text:                          #4a1830;
  --panel-border:                        #d7a4bc;
  --panel-head-bg:                       #f6dbe7;
  --panel-head-text:                     #8b1d45;

  --dialog-bg:                           #fff8fb;
  --dialog-text:                         #4a1830;
  --dialog-border:                       #d7a4bc;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #f8e2ec;
  --calendar-border:                     #d7a4bc;
  --calendar-day-bg:                     #fff9fc;
  --calendar-day-hover:                  #a80030;
  --calendar-day-today:                  color-mix(in srgb, #a80030 12%, #fff9fc);
  --calendar-day-selected:               color-mix(in srgb, #a80030 22%, #fff9fc);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #4a1830);
  --calendar-range-bg:                   color-mix(in srgb, #a80030 18%, #f8e2ec);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #ffffff;

  --button-text-active: #000000;
}


