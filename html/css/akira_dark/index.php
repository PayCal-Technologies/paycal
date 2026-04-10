<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* AKIRA DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.76);
  --btn-back-linear-gradient:            linear-gradient(145deg, #2c1414 0%, #1f0d0d 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #3a1a1a 0%, #271010 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #6e3333;
  --work-back:                           linear-gradient(150deg, #1f0d0d 0%, #170909 100%);
  --work-fore:                           #f3d1d1;
  --cal-day-fore:                        #f3d1d1;
  --cal-day-hover-fore:                  #fff2f2;
  --cal-day-hover-glow:                  0 0 0 3px rgba(225, 38, 45, 0.36);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #6e3333;
  --work-entry-back:                     #261111;
  --work-entry-fore:                     #f3d1d1;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #120707;
  --color-bg-soft:                       color-mix(in srgb, #120707 86%, #180a0a);
  --color-bg-elevated:                   color-mix(in srgb, #1f0d0d 90%, #180a0a);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #1f0d0d;
  --color-surface-muted:                 color-mix(in srgb, #1f0d0d 90%, #180a0a);
  --color-surface-strong:                #180a0a;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #6e3333 72%, #1f0d0d);
  --color-border-strong:                 color-mix(in srgb, #6e3333 84%, black);

  --color-text:                          #f3d1d1;
  --color-text-muted:                    #d29a9a;
  --color-text-inverse:                  #fff2f2;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #ffffff;
  --color-primary-hover:                 #ee5a60;
  --color-primary-active:                color-mix(in srgb, #e1262d 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #e1262d 18%, #1f0d0d);
  --color-on-primary:                    #fff2f2;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #e1262d 24%, #120707);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #1f0d0d 92%, #180a0a);
  --elevation-2-bg:                      color-mix(in srgb, #1f0d0d 84%, #180a0a);
  --elevation-3-bg:                      color-mix(in srgb, #1f0d0d 76%, #180a0a);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #261111;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 80%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #854242;
  --button-border-active:                #ee5a60;
  --button-primary-bg:                   #c81d24;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #3d1c1c;
  --button-secondary-text:               #f3d1d1;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #1f0d0d;
  --panel-text:                          #f3d1d1;
  --panel-border:                        #6e3333;
  --panel-head-bg:                       #321515;
  --panel-head-text:                     #ee5a60;

  --dialog-bg:                           #261111;
  --dialog-text:                         #f3d1d1;
  --dialog-border:                       #6e3333;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #120707;
  --calendar-border:                     #6e3333;
  --calendar-day-bg:                     #170909;
  --calendar-day-hover:                  #e1262d;
  --calendar-day-today:                  color-mix(in srgb, #e1262d 12%, #170909);
  --calendar-day-selected:               color-mix(in srgb, #e1262d 22%, #170909);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #f3d1d1);
  --calendar-range-bg:                   color-mix(in srgb, #e1262d 18%, #120707);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #ffffff;

  --button-text-active: #000000;
}


