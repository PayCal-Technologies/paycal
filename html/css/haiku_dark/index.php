<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* HAIKU DARK */
:root {





  --dialog-shadow:                       rgba(0, 0, 0, 0.70);





  --btn-back-linear-gradient:            linear-gradient(145deg, #203b57 0%, #1a3048 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #284a6b 0%, #1f3852 100%);





  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #3f6688;

  --work-back:                           linear-gradient(150deg, #1a3048 0%, #152739 100%);
  --work-fore:                           #e8f5ff;

  --cal-day-fore:                        #e8f5ff;
  --cal-day-hover-fore:                  #05263a;
  --cal-day-hover-glow:                  0 0 0 3px rgba(0, 160, 233, 0.34);

  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);

  --work-details-border:                 #3f6688;
  --work-entry-back:                     #203b57;
  --work-entry-fore:                     #e8f5ff;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #101d2c;
  --color-bg-soft:                       color-mix(in srgb, #101d2c 86%, #14263a);
  --color-bg-elevated:                   color-mix(in srgb, #1a3048 90%, #14263a);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #1a3048;
  --color-surface-muted:                 color-mix(in srgb, #1a3048 90%, #14263a);
  --color-surface-strong:                #14263a;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #3f6688 72%, #1a3048);
  --color-border-strong:                 color-mix(in srgb, #3f6688 84%, black);

  --color-text:                          #e8f5ff;
  --color-text-muted:                    #b9d5e8;
  --color-text-inverse:                  #05263a;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #00a0e9;
  --color-primary-hover:                 #4ea8d4;
  --color-primary-active:                color-mix(in srgb, #00a0e9 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #00a0e9 18%, #1a3048);
  --color-on-primary:                    #05263a;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #00a0e9 24%, #101d2c);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #1a3048 92%, #14263a);
  --elevation-2-bg:                      color-mix(in srgb, #1a3048 84%, #14263a);
  --elevation-3-bg:                      color-mix(in srgb, #1a3048 76%, #14263a);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #203b57;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #5483ab;
  --button-border-active:                #8ad8ff;
  --button-primary-bg:                   #00a0e9;
  --button-primary-text:                 #05263a;
  --button-secondary-bg:                 #2c4f71;
  --button-secondary-text:               #e8f5ff;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #1a3048;
  --panel-text:                          #e8f5ff;
  --panel-border:                        #3f6688;
  --panel-head-bg:                       #24486b;
  --panel-head-text:                     #8ad8ff;

  --dialog-bg:                           #203b57;
  --dialog-text:                         #e8f5ff;
  --dialog-border:                       #3f6688;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #101d2c;
  --calendar-border:                     #3f6688;
  --calendar-day-bg:                     #152739;
  --calendar-day-hover:                  #00a0e9;
  --calendar-day-today:                  color-mix(in srgb, #00a0e9 12%, #152739);
  --calendar-day-selected:               color-mix(in srgb, #00a0e9 22%, #152739);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #e8f5ff);
  --calendar-range-bg:                   color-mix(in srgb, #00a0e9 18%, #101d2c);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


