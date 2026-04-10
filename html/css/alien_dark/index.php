<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* ALIEN DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.76);
  --btn-back-linear-gradient:            linear-gradient(145deg, #1b271d 0%, #121a14 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #253828 0%, #17231a 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #3b4d3c;
  --work-back:                           linear-gradient(150deg, #121a14 0%, #0e140f 100%);
  --work-fore:                           #d8e5d3;
  --cal-day-fore:                        #d8e5d3;
  --cal-day-hover-fore:                  #13200d;
  --cal-day-hover-glow:                  0 0 0 3px rgba(143, 191, 63, 0.36);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #3b4d3c;
  --work-entry-back:                     #172019;
  --work-entry-fore:                     #d8e5d3;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #0a0f0c;
  --color-bg-soft:                       color-mix(in srgb, #0a0f0c 86%, #0d130f);
  --color-bg-elevated:                   color-mix(in srgb, #121a14 90%, #0d130f);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #121a14;
  --color-surface-muted:                 color-mix(in srgb, #121a14 90%, #0d130f);
  --color-surface-strong:                #0d130f;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #3b4d3c 72%, #121a14);
  --color-border-strong:                 color-mix(in srgb, #3b4d3c 84%, black);

  --color-text:                          #d8e5d3;
  --color-text-muted:                    #a6bc9f;
  --color-text-inverse:                  #13200d;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #8fbf3f;
  --color-primary-hover:                 #a8d26a;
  --color-primary-active:                color-mix(in srgb, #8fbf3f 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #8fbf3f 18%, #121a14);
  --color-on-primary:                    #13200d;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #8fbf3f;
  --color-selection:                     color-mix(in srgb, #8fbf3f 24%, #0a0f0c);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #121a14 92%, #0d130f);
  --elevation-2-bg:                      color-mix(in srgb, #121a14 84%, #0d130f);
  --elevation-3-bg:                      color-mix(in srgb, #121a14 76%, #0d130f);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #172019;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #506551;
  --button-border-active:                #a8d26a;
  --button-primary-bg:                   #8fbf3f;
  --button-primary-text:                 #13200d;
  --button-secondary-bg:                 #263728;
  --button-secondary-text:               #d8e5d3;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #121a14;
  --panel-text:                          #d8e5d3;
  --panel-border:                        #3b4d3c;
  --panel-head-bg:                       #1c2a1e;
  --panel-head-text:                     #a8d26a;

  --dialog-bg:                           #172019;
  --dialog-text:                         #d8e5d3;
  --dialog-border:                       #3b4d3c;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #0a0f0c;
  --calendar-border:                     #3b4d3c;
  --calendar-day-bg:                     #0e140f;
  --calendar-day-hover:                  #8fbf3f;
  --calendar-day-today:                  color-mix(in srgb, #8fbf3f 12%, #0e140f);
  --calendar-day-selected:               color-mix(in srgb, #8fbf3f 22%, #0e140f);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #d8e5d3);
  --calendar-range-bg:                   color-mix(in srgb, #8fbf3f 18%, #0a0f0c);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


