<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Bluejeans Dark: raw indigo denim, washed seams, and cool chalk stitching */




  --nav-menu-back:                       #1D2432;
  --nav-menu-fore:                       #EDF3F8;
  --system-tray-back:                    #151C28;

  --heading-accent-color:                #87A9D8;

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          #EDF3F8;
  --text-muted:                          #AFC0CF;

  --dialog-shadow:                       rgba(0, 0, 0, 0.46);



  --panel-footer-back:                   #202B3B;




  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #40536E;

  --work-back:                           #17202C;
  --work-fore:                           #EDF3F8;
  --work-details-border:                 #40536E;
  --work-entry-back:                     #223041;
  --work-entry-fore:                     #EDF3F8;

  --cal-day-fore:                        #EDF3F8;
  --cal-day-hover-fore:                  #F7FBFF;
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.34);
  --cal-day-hover-glow:                  0 0 0 2px rgba(78, 121, 183, 0.34);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #141A26;
  --color-bg-soft:                       color-mix(in srgb, #141A26 86%, #1D2432);
  --color-bg-elevated:                   color-mix(in srgb, #1A2230 90%, #1D2432);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #1A2230;
  --color-surface-muted:                 color-mix(in srgb, #1A2230 90%, #1D2432);
  --color-surface-strong:                #1D2432;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #40536E 72%, #1A2230);
  --color-border-strong:                 color-mix(in srgb, #40536E 84%, black);

  --color-text:                          #EDF3F8;
  --color-text-muted:                    #C8D5E1;
  --color-text-inverse:                  #000000;
  --color-text-disabled:                 color-mix(in srgb, #EDF3F8 52%, #1A2230);

  --color-primary:                       #ffffff;
  --color-primary-hover:                 #243A53;
  --color-primary-active:                color-mix(in srgb, #4E79B7 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #4E79B7 18%, #1A2230);
  --color-on-primary:                    #000000;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #D9998D;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #4E79B7 24%, #141A26);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #1A2230 92%, #1D2432);
  --elevation-2-bg:                      color-mix(in srgb, #1A2230 84%, #1D2432);
  --elevation-3-bg:                      color-mix(in srgb, #1A2230 76%, #1D2432);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #2A3547;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #5A7392;
  --button-border-active:                #9BB6DB;
  --button-primary-bg:                   #527dbb;
  --button-primary-text:                 #000000;
  --button-secondary-bg:                 #33485C;
  --button-secondary-text:               #EDF3F8;
  --button-danger-text:                  #D9998D;

  --panel-bg:                            #1A2230;
  --panel-text:                          #EDF3F8;
  --panel-border:                        #40536E;
  --panel-head-bg:                       linear-gradient(180deg, #31445E 0%, #233248 100%);
  --panel-head-text:                     #F4F8FC;

  --dialog-bg:                           #212A39;
  --dialog-text:                         #EDF3F8;
  --dialog-border:                       #40536E;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #141A26;
  --calendar-border:                     #40536E;
  --calendar-day-bg:                     #1D2938;
  --calendar-day-hover:                  #2A3E56;
  --calendar-day-today:                  color-mix(in srgb, #4E79B7 12%, #1D2938);
  --calendar-day-selected:               color-mix(in srgb, #4E79B7 22%, #1D2938);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #EDF3F8);
  --calendar-range-bg:                   color-mix(in srgb, #4E79B7 18%, #141A26);
}


