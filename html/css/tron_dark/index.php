<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* TRON DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.76);
  --btn-back-linear-gradient:            linear-gradient(145deg, #0f2234 0%, #091725 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #14314a 0%, #0c2234 100%);
  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #2a6078;
  --work-back:                           linear-gradient(150deg, #091725 0%, #06111c 100%);
  --work-fore:                           #d6f5ff;
  --cal-day-fore:                        #d6f5ff;
  --cal-day-hover-fore:                  #03202f;
  --cal-day-hover-glow:                  0 0 0 3px rgba(0, 200, 255, 0.38);
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #2a6078;
  --work-entry-back:                     #0c1c2c;
  --work-entry-fore:                     #d6f5ff;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #040b13;
  --color-bg-soft:                       color-mix(in srgb, #040b13 86%, #06101a);
  --color-bg-elevated:                   color-mix(in srgb, #091725 90%, #06101a);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #091725;
  --color-surface-muted:                 color-mix(in srgb, #091725 90%, #06101a);
  --color-surface-strong:                #06101a;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #2a6078 72%, #091725);
  --color-border-strong:                 color-mix(in srgb, #2a6078 84%, black);

  --color-text:                          #d6f5ff;
  --color-text-muted:                    #9bcad7;
  --color-text-inverse:                  #03202f;
  --color-text-disabled:                 color-mix(in srgb, #d6f5ff 52%, #091725);

  --color-primary:                       #00c8ff;
  --color-primary-hover:                 #3ad7ff;
  --color-primary-active:                color-mix(in srgb, #00c8ff 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #00c8ff 18%, #091725);
  --color-on-primary:                    #03202f;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #00c8ff;
  --color-selection:                     color-mix(in srgb, #00c8ff 24%, #040b13);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #091725 92%, #06101a);
  --elevation-2-bg:                      color-mix(in srgb, #091725 84%, #06101a);
  --elevation-3-bg:                      color-mix(in srgb, #091725 76%, #06101a);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #0c1c2c;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #3c7995;
  --button-border-active:                #76e7ff;
  --button-primary-bg:                   #00c8ff;
  --button-primary-text:                 #03202f;
  --button-secondary-bg:                 #15324b;
  --button-secondary-text:               #d6f5ff;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #091725;
  --panel-text:                          #d6f5ff;
  --panel-border:                        #2a6078;
  --panel-head-bg:                       #0f2436;
  --panel-head-text:                     #76e7ff;

  --dialog-bg:                           #0c1c2c;
  --dialog-text:                         #d6f5ff;
  --dialog-border:                       #2a6078;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #040b13;
  --calendar-border:                     #2a6078;
  --calendar-day-bg:                     #06111c;
  --calendar-day-hover:                  #00c8ff;
  --calendar-day-today:                  color-mix(in srgb, #00c8ff 12%, #06111c);
  --calendar-day-selected:               color-mix(in srgb, #00c8ff 22%, #06111c);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #d6f5ff);
  --calendar-range-bg:                   color-mix(in srgb, #00c8ff 18%, #040b13);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


