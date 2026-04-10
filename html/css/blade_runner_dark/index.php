<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* BLADE RUNNER DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.74);
  --btn-back-linear-gradient:            linear-gradient(145deg, #2a1f23 0%, #21181b 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #34272c 0%, #261c20 100%);
  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #614947;
  --work-back:                           linear-gradient(150deg, #21181b 0%, #1a1315 100%);
  --work-fore:                           #f0d8c5;
  --cal-day-fore:                        #f0d8c5;
  --cal-day-hover-fore:                  #221711;
  --cal-day-hover-glow:                  0 0 0 3px rgba(255, 138, 61, 0.33);
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #614947;
  --work-entry-back:                     #2a1f23;
  --work-entry-fore:                     #f0d8c5;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #140f11;
  --color-bg-soft:                       color-mix(in srgb, #140f11 86%, #1b1416);
  --color-bg-elevated:                   color-mix(in srgb, #21181b 90%, #1b1416);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #21181b;
  --color-surface-muted:                 color-mix(in srgb, #21181b 90%, #1b1416);
  --color-surface-strong:                #1b1416;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #614947 72%, #21181b);
  --color-border-strong:                 color-mix(in srgb, #614947 84%, black);

  --color-text:                          #f0d8c5;
  --color-text-muted:                    #d1b39b;
  --color-text-inverse:                  #221711;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #ff8a3d;
  --color-primary-hover:                 #ffb48a;
  --color-primary-active:                color-mix(in srgb, #ff8a3d 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #ff8a3d 18%, #21181b);
  --color-on-primary:                    #221711;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ff8a3d;
  --color-selection:                     color-mix(in srgb, #ff8a3d 24%, #140f11);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #21181b 92%, #1b1416);
  --elevation-2-bg:                      color-mix(in srgb, #21181b 84%, #1b1416);
  --elevation-3-bg:                      color-mix(in srgb, #21181b 76%, #1b1416);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #2a1f23;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #7b5a5f;
  --button-border-active:                #ffb48a;
  --button-primary-bg:                   #ff8a3d;
  --button-primary-text:                 #221711;
  --button-secondary-bg:                 #3a2b30;
  --button-secondary-text:               #f0d8c5;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #21181b;
  --panel-text:                          #f0d8c5;
  --panel-border:                        #614947;
  --panel-head-bg:                       #332529;
  --panel-head-text:                     #ffb48a;

  --dialog-bg:                           #2a1f23;
  --dialog-text:                         #f0d8c5;
  --dialog-border:                       #614947;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #140f11;
  --calendar-border:                     #614947;
  --calendar-day-bg:                     #1a1315;
  --calendar-day-hover:                  #ff8a3d;
  --calendar-day-today:                  color-mix(in srgb, #ff8a3d 12%, #1a1315);
  --calendar-day-selected:               color-mix(in srgb, #ff8a3d 22%, #1a1315);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #f0d8c5);
  --calendar-range-bg:                   color-mix(in srgb, #ff8a3d 18%, #140f11);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


