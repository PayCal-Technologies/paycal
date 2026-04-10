<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Mac System 7 inspired dark: same geometry with low-color grayscale contrast */





  --text-color:                          #F2F2F2;
  --text-muted:                          #C5C5C5;

  --dialog-shadow:                       rgba(0, 0, 0, 0.55);



  --panel-footer-back:                   #242424;




  --border-size:                         1px;
  --border-radius:                       0;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #7B7B7B;

  --work-back:                           #252525;
  --work-fore:                           #F2F2F2;
  --work-details-border:                 #7B7B7B;
  --work-entry-back:                     #2F2F2F;
  --work-entry-fore:                     #F2F2F2;

  --cal-day-fore:                        #F2F2F2;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      0;
  --cal-day-shadow:                      none;
  --cal-day-hover-glow:                  none;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #1E1E1E;
  --color-bg-soft:                       color-mix(in srgb, #1E1E1E 86%, #2B2B2B);
  --color-bg-elevated:                   color-mix(in srgb, #292929 90%, #2B2B2B);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #292929;
  --color-surface-muted:                 color-mix(in srgb, #292929 90%, #2B2B2B);
  --color-surface-strong:                #2B2B2B;
  --input-bg:                            var(--color-surface);

  --color-border:                        #ffffff;
  --color-border-soft:                   color-mix(in srgb, #7B7B7B 72%, #292929);
  --color-border-strong:                 color-mix(in srgb, #7B7B7B 84%, black);

  --color-text:                          #F2F2F2;
  --color-text-muted:                    #E6E6E6;
  --color-text-inverse:                  #1A1A1A;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #D7D7D7;
  --color-primary-hover:                 #3E3E3E;
  --color-primary-active:                color-mix(in srgb, #D7D7D7 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #D7D7D7 18%, #292929);
  --color-on-primary:                    #1A1A1A;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #FF6860;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #D7D7D7;
  --color-selection:                     color-mix(in srgb, #D7D7D7 24%, #1E1E1E);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #292929 92%, #2B2B2B);
  --elevation-2-bg:                      color-mix(in srgb, #292929 84%, #2B2B2B);
  --elevation-3-bg:                      color-mix(in srgb, #292929 76%, #2B2B2B);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #343434;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #8A8A8A #151515 #151515 #8A8A8A;
  --button-border-active:                #151515 #8A8A8A #8A8A8A #151515;
  --button-primary-bg:                   #D7D7D7;
  --button-primary-text:                 #1A1A1A;
  --button-secondary-bg:                 #2E2E2E;
  --button-secondary-text:               #F2F2F2;
  --button-danger-text:                  #FF6860;

  --panel-bg:                            #292929;
  --panel-text:                          #F2F2F2;
  --panel-border:                        #7B7B7B;
  --panel-head-bg:                       linear-gradient(180deg, #4A4A4A 0%, #2F2F2F 100%);
  --panel-head-text:                     #FFFFFF;

  --dialog-bg:                           #2E2E2E;
  --dialog-text:                         #F2F2F2;
  --dialog-border:                       #7B7B7B;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #1E1E1E;
  --calendar-border:                     #7B7B7B;
  --calendar-day-bg:                     #2A2A2A;
  --calendar-day-hover:                  #383838;
  --calendar-day-today:                  color-mix(in srgb, #D7D7D7 12%, #2A2A2A);
  --calendar-day-selected:               color-mix(in srgb, #D7D7D7 22%, #2A2A2A);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #F2F2F2);
  --calendar-range-bg:                   color-mix(in srgb, #D7D7D7 18%, #1E1E1E);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


