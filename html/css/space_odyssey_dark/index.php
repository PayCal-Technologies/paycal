<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* 2001 SPACE ODYSSEY DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.75);
  --btn-back-linear-gradient:            linear-gradient(145deg, #1a2331 0%, #131b25 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #213045 0%, #172333 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #3a475b;
  --work-back:                           linear-gradient(150deg, #131b25 0%, #0e141d 100%);
  --work-fore:                           #f2f5fb;
  --cal-day-fore:                        #f2f5fb;
  --cal-day-hover-fore:                  #fff5f5;
  --cal-day-hover-glow:                  0 0 0 3px rgba(217, 37, 37, 0.34);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #3a475b;
  --work-entry-back:                     #171f2b;
  --work-entry-fore:                     #f2f5fb;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #0a0d14;
  --color-bg-soft:                       color-mix(in srgb, #0a0d14 86%, #0d1118);
  --color-bg-elevated:                   color-mix(in srgb, #131b25 90%, #0d1118);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #131b25;
  --color-surface-muted:                 color-mix(in srgb, #131b25 90%, #0d1118);
  --color-surface-strong:                #0d1118;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #3a475b 72%, #131b25);
  --color-border-strong:                 color-mix(in srgb, #3a475b 84%, black);

  --color-text:                          #f2f5fb;
  --color-text-muted:                    #c4cedf;
  --color-text-inverse:                  #fff5f5;
  --color-text-disabled:                 color-mix(in srgb, #f2f5fb 52%, #131b25);

  --color-primary:                       #ffffff;
  --color-primary-hover:                 #f06a6a;
  --color-primary-active:                color-mix(in srgb, #d92525 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #d92525 18%, #131b25);
  --color-on-primary:                    #fff5f5;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #d92525 24%, #0a0d14);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #131b25 92%, #0d1118);
  --elevation-2-bg:                      color-mix(in srgb, #131b25 84%, #0d1118);
  --elevation-3-bg:                      color-mix(in srgb, #131b25 76%, #0d1118);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #171f2b;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 70%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #4e5f79;
  --button-border-active:                #f06a6a;
  --button-primary-bg:                   #d92525;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #223044;
  --button-secondary-text:               #f2f5fb;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #131b25;
  --panel-text:                          #f2f5fb;
  --panel-border:                        #3a475b;
  --panel-head-bg:                       #1a2534;
  --panel-head-text:                     #f2f5fb;

  --dialog-bg:                           #171f2b;
  --dialog-text:                         #f2f5fb;
  --dialog-border:                       #3a475b;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #0a0d14;
  --calendar-border:                     #3a475b;
  --calendar-day-bg:                     #0f1620;
  --calendar-day-hover:                  #d92525;
  --calendar-day-today:                  color-mix(in srgb, #d92525 12%, #0f1620);
  --calendar-day-selected:               color-mix(in srgb, #d92525 22%, #0f1620);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #f2f5fb);
  --calendar-range-bg:                   color-mix(in srgb, #d92525 18%, #0a0d14);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #ffffff;

  --button-text-active: #000000;
}


