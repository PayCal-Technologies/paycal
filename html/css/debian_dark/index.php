<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* DEBIAN DARK */
:root {





  --dialog-shadow:                       rgba(0, 0, 0, 0.72);





  --btn-back-linear-gradient:            linear-gradient(145deg, #462035 0%, #381a2b 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #552a42 0%, #411f31 100%);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #71374f;

  --work-back:                           linear-gradient(150deg, #381a2b 0%, #2e1523 100%);
  --work-fore:                           #fbeef4;

  --cal-day-fore:                        #fbeef4;
  --cal-day-hover-fore:                  #fff4f8;
  --cal-day-hover-glow:                  0 0 0 3px rgba(168, 0, 48, 0.34);

  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);

  --work-details-border:                 #7f3e5c;
  --work-entry-back:                     #462035;
  --work-entry-fore:                     #fbeef4;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #24101b;
  --color-bg-soft:                       color-mix(in srgb, #24101b 86%, #301423);
  --color-bg-elevated:                   color-mix(in srgb, #381a2b 90%, #301423);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #381a2b;
  --color-surface-muted:                 color-mix(in srgb, #381a2b 90%, #301423);
  --color-surface-strong:                #301423;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #7f3e5c 72%, #381a2b);
  --color-border-strong:                 color-mix(in srgb, #7f3e5c 84%, black);

  --color-text:                          #fbeef4;
  --color-text-muted:                    #ddb9ca;
  --color-text-inverse:                  #fff4f8;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #c65079;
  --color-primary-active:                color-mix(in srgb, #a80030 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #a80030 18%, #381a2b);
  --color-on-primary:                    #fff4f8;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #a80030 24%, #24101b);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #381a2b 92%, #301423);
  --elevation-2-bg:                      color-mix(in srgb, #381a2b 84%, #301423);
  --elevation-3-bg:                      color-mix(in srgb, #381a2b 76%, #301423);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #462035;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 82%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #95506e;
  --button-border-active:                #e47299;
  --button-primary-bg:                   #a80030;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #5b2b43;
  --button-secondary-text:               #fbeef4;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #381a2b;
  --panel-text:                          #fbeef4;
  --panel-border:                        #7f3e5c;
  --panel-head-bg:                       #421d30;
  --panel-head-text:                     #e87aa0;

  --dialog-bg:                           #462035;
  --dialog-text:                         #fbeef4;
  --dialog-border:                       #7f3e5c;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #24101b;
  --calendar-border:                     #7f3e5c;
  --calendar-day-bg:                     #2e1523;
  --calendar-day-hover:                  #a80030;
  --calendar-day-today:                  color-mix(in srgb, #a80030 12%, #2e1523);
  --calendar-day-selected:               color-mix(in srgb, #a80030 22%, #2e1523);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #fbeef4);
  --calendar-range-bg:                   color-mix(in srgb, #a80030 18%, #24101b);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-active: #000000;
}


