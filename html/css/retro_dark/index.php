<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Retro Dark: charcoal backdrop with warm red 80s accents */




  --nav-menu-back:                       #2B2423;
  --nav-menu-fore:                       #F3ECE8;
  --system-tray-back:                    #231D1C;


  --text-color:                          #F3ECE8;
  --text-muted:                          #C4AFAB;

  --dialog-shadow:                       rgba(0, 0, 0, 0.44);



  --panel-footer-back:                   #2D2524;




  --border-size:                         1px;
  --border-radius:                       6px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #5F4742;

  --work-back:                           #231D1C;
  --work-fore:                           #F3ECE8;
  --work-details-border:                 #5F4742;
  --work-entry-back:                     #2E2726;
  --work-entry-fore:                     #F3ECE8;

  --cal-day-fore:                        #F3ECE8;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      6px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.30);
  --cal-day-hover-glow:                  0 0 0 2px rgba(201, 90, 84, 0.34);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #1F1A19;
  --color-bg-soft:                       color-mix(in srgb, #1F1A19 86%, #2B2423);
  --color-bg-elevated:                   color-mix(in srgb, #26201F 90%, #2B2423);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #26201F;
  --color-surface-muted:                 color-mix(in srgb, #26201F 90%, #2B2423);
  --color-surface-strong:                #2B2423;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #5F4742 72%, #26201F);
  --color-border-strong:                 color-mix(in srgb, #5F4742 84%, black);

  --color-text:                          #F3ECE8;
  --color-text-muted:                    #D9C9C3;
  --color-text-inverse:                  #121212;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #ffffff;
  --color-primary-hover:                 #5B3230;
  --color-primary-active:                color-mix(in srgb, #C95A54 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #C95A54 18%, #26201F);
  --color-on-primary:                    #121212;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #E5857D;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #C95A54 24%, #1F1A19);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #26201F 92%, #2B2423);
  --elevation-2-bg:                      color-mix(in srgb, #26201F 84%, #2B2423);
  --elevation-3-bg:                      color-mix(in srgb, #26201F 76%, #2B2423);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #3A2F2D;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 88%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #735652;
  --button-border-active:                #E07B74;
  --button-primary-bg:                   #C95A54;
  --button-primary-text:                 #000000;
  --button-secondary-bg:                 #4A3B38;
  --button-secondary-text:               #F3ECE8;
  --button-danger-text:                  #E5857D;

  --panel-bg:                            #26201F;
  --panel-text:                          #F3ECE8;
  --panel-border:                        #5F4742;
  --panel-head-bg:                       linear-gradient(180deg, #3C2E2B 0%, #2F2422 100%);
  --panel-head-text:                     #F3ECE8;

  --dialog-bg:                           #2D2625;
  --dialog-text:                         #F3ECE8;
  --dialog-border:                       #5F4742;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #1F1A19;
  --calendar-border:                     #5F4742;
  --calendar-day-bg:                     #2A2221;
  --calendar-day-hover:                  #5C3230;
  --calendar-day-today:                  color-mix(in srgb, #C95A54 12%, #2A2221);
  --calendar-day-selected:               color-mix(in srgb, #C95A54 22%, #2A2221);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #F3ECE8);
  --calendar-range-bg:                   color-mix(in srgb, #C95A54 18%, #1F1A19);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


