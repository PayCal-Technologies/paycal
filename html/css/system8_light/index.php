<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Mac OS 8: brighter platinum with stronger blue accents than System 7 */





  --text-color:                          #132235;
  --text-muted:                          #2E4564;

  --dialog-shadow:                       rgba(19, 34, 53, 0.24);



  --panel-footer-back:                   #D8E3F2;




  --border-size:                         1px;
  --border-radius:                       4px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #7D97B8;

  --work-back:                           #E0E8F4;
  --work-fore:                           #132235;
  --work-details-border:                 #7D97B8;
  --work-entry-back:                     #F0F5FC;
  --work-entry-fore:                     #132235;

  --cal-day-fore:                        #132235;
  --cal-day-hover-fore:                  #102744;
  --cal-day-radius:                      4px;
  --cal-day-shadow:                      0 1px 2px rgba(19, 34, 53, 0.16);
  --cal-day-hover-glow:                  0 0 0 2px rgba(47, 115, 199, 0.24);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #C5D1E0;
  --color-bg-soft:                       color-mix(in srgb, #C5D1E0 86%, #DDE4EE);
  --color-bg-elevated:                   color-mix(in srgb, #E6EDF7 90%, #DDE4EE);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #E6EDF7;
  --color-surface-muted:                 color-mix(in srgb, #E6EDF7 90%, #DDE4EE);
  --color-surface-strong:                #DDE4EE;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #7D97B8 72%, #E6EDF7);
  --color-border-strong:                 color-mix(in srgb, #7D97B8 84%, black);

  --color-text:                          #132235;
  --color-text-muted:                    #132235;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #CDDCF0;
  --color-primary-active:                color-mix(in srgb, #2F73C7 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #2F73C7 18%, #E6EDF7);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #B62828;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, #2F73C7 24%, #C5D1E0);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #E6EDF7 92%, #DDE4EE);
  --elevation-2-bg:                      color-mix(in srgb, #E6EDF7 84%, #DDE4EE);
  --elevation-3-bg:                      color-mix(in srgb, #E6EDF7 76%, #DDE4EE);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #E9EFF8;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #132235;
  --button-border:                       #FFFFFF #6E87A7 #6E87A7 #FFFFFF;
  --button-border-active:                #5C7597 #F8FBFF #F8FBFF #5C7597;
  --button-primary-bg:                   #2D70C0;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #DDE7F4;
  --button-secondary-text:               #132235;
  --button-danger-text:                  #B62828;

  --panel-bg:                            #E6EDF7;
  --panel-text:                          #132235;
  --panel-border:                        #7D97B8;
  --panel-head-bg:                       linear-gradient(180deg, #F7FAFF 0%, #D6E1F1 100%);
  --panel-head-text:                     #132235;

  --dialog-bg:                           #E9EFF8;
  --dialog-text:                         #132235;
  --dialog-border:                       #7D97B8;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #C5D1E0;
  --calendar-border:                     #7D97B8;
  --calendar-day-bg:                     #EEF3FA;
  --calendar-day-hover:                  #DBE7F7;
  --calendar-day-today:                  color-mix(in srgb, #2F73C7 12%, #EEF3FA);
  --calendar-day-selected:               color-mix(in srgb, #2F73C7 22%, #EEF3FA);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #132235);
  --calendar-range-bg:                   color-mix(in srgb, #2F73C7 18%, #C5D1E0);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


