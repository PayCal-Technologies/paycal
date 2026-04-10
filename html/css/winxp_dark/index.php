<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Windows XP inspired dark: cobalt accents with subdued graphite backgrounds */





  --text-color:                          #EAF0FF;
  --text-muted:                          #AFC1EE;

  --dialog-shadow:                       rgba(0, 0, 0, 0.55);



  --panel-footer-back:                   #15203A;




  --border-size:                         1px;
  --border-radius:                       6px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #4B67B7;

  --work-back:                           #131D33;
  --work-fore:                           #EEF3FF;
  --work-details-border:                 #4B67B7;
  --work-entry-back:                     #1B2646;
  --work-entry-fore:                     #EEF3FF;

  --cal-day-fore:                        #EEF3FF;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      6px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.45);
  --cal-day-hover-glow:                  0 0 0 2px rgba(95, 160, 255, 0.28);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #121A2F;
  --color-bg-soft:                       color-mix(in srgb, #121A2F 86%, #1B2F73);
  --color-bg-elevated:                   color-mix(in srgb, #18213A 90%, #1B2F73);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #18213A;
  --color-surface-muted:                 color-mix(in srgb, #18213A 90%, #1B2F73);
  --color-surface-strong:                #1B2F73;
  --input-bg:                            var(--color-surface);

  --color-border:                        #ffffff;
  --color-border-soft:                   color-mix(in srgb, #4B67B7 72%, #18213A);
  --color-border-strong:                 color-mix(in srgb, #4B67B7 84%, black);

  --color-text:                          #EEF3FF;
  --color-text-muted:                    #D7E2FF;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 color-mix(in srgb, #EEF3FF 52%, #18213A);

  --color-primary:                       #ffffff;
  --color-primary-hover:                 #2D4179;
  --color-primary-active:                color-mix(in srgb, #3A6BD9 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #3A6BD9 18%, #18213A);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #FF6B63;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #3A6BD9 24%, #121A2F);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #18213A 92%, #1B2F73);
  --elevation-2-bg:                      color-mix(in srgb, #18213A 84%, #1B2F73);
  --elevation-3-bg:                      color-mix(in srgb, #18213A 76%, #1B2F73);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #243157;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 82%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #6E89D6 #12182A #12182A #6E89D6;
  --button-border-active:                #12182A #6E89D6 #6E89D6 #12182A;
  --button-primary-bg:                   #3A6BD9;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #1D2847;
  --button-secondary-text:               #EAF0FF;
  --button-danger-text:                  #FF6B63;

  --panel-bg:                            #18213A;
  --panel-text:                          #EEF3FF;
  --panel-border:                        #4B67B7;
  --panel-head-bg:                       linear-gradient(180deg, #3A6BD9 0%, #2549A6 100%);
  --panel-head-text:                     #FFFFFF;

  --dialog-bg:                           #1A233D;
  --dialog-text:                         #EEF3FF;
  --dialog-border:                       #4B67B7;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #121A2F;
  --calendar-border:                     #4B67B7;
  --calendar-day-bg:                     #17213D;
  --calendar-day-hover:                  #21315C;
  --calendar-day-today:                  color-mix(in srgb, #3A6BD9 12%, #17213D);
  --calendar-day-selected:               color-mix(in srgb, #3A6BD9 22%, #17213D);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #EEF3FF);
  --calendar-range-bg:                   color-mix(in srgb, #3A6BD9 18%, #121A2F);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


