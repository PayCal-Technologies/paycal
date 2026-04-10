<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* DUNE DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.75);
  --btn-back-linear-gradient:            linear-gradient(145deg, #30261d 0%, #211b15 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #3a2d22 0%, #281f18 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #69543d;
  --work-back:                           linear-gradient(150deg, #211b15 0%, #18140f 100%);
  --work-fore:                           #e7d2b4;
  --cal-day-fore:                        #e7d2b4;
  --cal-day-hover-fore:                  #2c1f13;
  --cal-day-hover-glow:                  0 0 0 3px rgba(214, 161, 94, 0.34);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #69543d;
  --work-entry-back:                     #2a221b;
  --work-entry-fore:                     #e7d2b4;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #14110e;
  --color-bg-soft:                       color-mix(in srgb, #14110e 86%, #1b1713);
  --color-bg-elevated:                   color-mix(in srgb, #211b15 90%, #1b1713);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #211b15;
  --color-surface-muted:                 color-mix(in srgb, #211b15 90%, #1b1713);
  --color-surface-strong:                #1b1713;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #69543d 72%, #211b15);
  --color-border-strong:                 color-mix(in srgb, #69543d 84%, black);

  --color-text:                          #e7d2b4;
  --color-text-muted:                    #c5aa84;
  --color-text-inverse:                  #2c1f13;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #d6a15e;
  --color-primary-hover:                 #e2b780;
  --color-primary-active:                color-mix(in srgb, #d6a15e 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #d6a15e 18%, #211b15);
  --color-on-primary:                    #2c1f13;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #d6a15e;
  --color-selection:                     color-mix(in srgb, #d6a15e 24%, #14110e);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #211b15 92%, #1b1713);
  --elevation-2-bg:                      color-mix(in srgb, #211b15 84%, #1b1713);
  --elevation-3-bg:                      color-mix(in srgb, #211b15 76%, #1b1713);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #2a221b;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #7f6548;
  --button-border-active:                #e2b780;
  --button-primary-bg:                   #d6a15e;
  --button-primary-text:                 #2c1f13;
  --button-secondary-bg:                 #403225;
  --button-secondary-text:               #e7d2b4;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #211b15;
  --panel-text:                          #e7d2b4;
  --panel-border:                        #69543d;
  --panel-head-bg:                       #33281d;
  --panel-head-text:                     #e2b780;

  --dialog-bg:                           #2a221b;
  --dialog-text:                         #e7d2b4;
  --dialog-border:                       #69543d;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #14110e;
  --calendar-border:                     #69543d;
  --calendar-day-bg:                     #18140f;
  --calendar-day-hover:                  #d6a15e;
  --calendar-day-today:                  color-mix(in srgb, #d6a15e 12%, #18140f);
  --calendar-day-selected:               color-mix(in srgb, #d6a15e 22%, #18140f);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #e7d2b4);
  --calendar-range-bg:                   color-mix(in srgb, #d6a15e 18%, #14110e);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


