<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* AKIRA LIGHT */
:root {
  --dialog-shadow:                       rgba(56, 19, 19, 0.20);
  --btn-back-linear-gradient:            linear-gradient(145deg, #fdf2f2 0%, #f4dfdf 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #f4dfdf 0%, #ebcaca 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #c59090;
  --work-back:                           linear-gradient(150deg, #f5e5e5 0%, #edd6d6 100%);
  --work-fore:                           #3b1a1a;
  --cal-day-fore:                        #3b1a1a;
  --cal-day-hover-fore:                  #fff2f2;
  --cal-day-hover-glow:                  0 0 0 3px rgba(225, 38, 45, 0.25);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(56, 19, 19, 0.14);
  --work-details-border:                 #c59090;
  --work-entry-back:                     #fdf2f2;
  --work-entry-fore:                     #3b1a1a;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #f1dddd;
  --color-bg-soft:                       color-mix(in srgb, #f1dddd 86%, #f7e7e7);
  --color-bg-elevated:                   color-mix(in srgb, #f5e5e5 90%, #f7e7e7);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #f5e5e5;
  --color-surface-muted:                 color-mix(in srgb, #f5e5e5 90%, #f7e7e7);
  --color-surface-strong:                #f7e7e7;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #c59090 72%, #f5e5e5);
  --color-border-strong:                 color-mix(in srgb, #c59090 84%, black);

  --color-text:                          #3b1a1a;
  --color-text-muted:                    #5f2f2f;
  --color-text-inverse:                  #fff2f2;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #ee5a60;
  --color-primary-active:                color-mix(in srgb, #e1262d 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #e1262d 18%, #f5e5e5);
  --color-on-primary:                    #fff2f2;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, #e1262d 24%, #f1dddd);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #f5e5e5 92%, #f7e7e7);
  --elevation-2-bg:                      color-mix(in srgb, #f5e5e5 84%, #f7e7e7);
  --elevation-3-bg:                      color-mix(in srgb, #f5e5e5 76%, #f7e7e7);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #fdf2f2;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #3b1a1a;
  --button-border:                       #c59090;
  --button-border-active:                #e1262d;
  --button-primary-bg:                   #c81d24;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #eed3d3;
  --button-secondary-text:               #3b1a1a;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #f5e5e5;
  --panel-text:                          #3b1a1a;
  --panel-border:                        #c59090;
  --panel-head-bg:                       #f0d6d6;
  --panel-head-text:                     #a11a20;

  --dialog-bg:                           #fdf2f2;
  --dialog-text:                         #3b1a1a;
  --dialog-border:                       #c59090;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #f1dddd;
  --calendar-border:                     #c59090;
  --calendar-day-bg:                     #fef6f6;
  --calendar-day-hover:                  #e1262d;
  --calendar-day-today:                  color-mix(in srgb, #e1262d 12%, #fef6f6);
  --calendar-day-selected:               color-mix(in srgb, #e1262d 22%, #fef6f6);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #3b1a1a);
  --calendar-range-bg:                   color-mix(in srgb, #e1262d 18%, #f1dddd);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #ffffff;

  --button-text-active: #000000;
}


