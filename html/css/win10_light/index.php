<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Windows 10 Light: neutral grays + Fluent-era accent blue */




  --nav-menu-back:                       #ECEEF1;
  --nav-menu-fore:                       #1F1F1F;
  --system-tray-back:                    #E0E4E8;


  --text-color:                          #1F1F1F;
  --text-muted:                          #5C5C5C;

  --dialog-shadow:                       rgba(0, 0, 0, 0.18);



  --panel-footer-back:                   #E8EDF2;




  --border-size:                         1px;
  --border-radius:                       4px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #D2D7DE;

  --work-back:                           #E6EBF1;
  --work-fore:                           #1F1F1F;
  --work-details-border:                 #D2D7DE;
  --work-entry-back:                     #F3F5F7;
  --work-entry-fore:                     #1F1F1F;

  --cal-day-fore:                        #1F1F1F;
  --cal-day-hover-fore:                  #1F1F1F;
  --cal-day-radius:                      4px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.08);
  --cal-day-hover-glow:                  0 0 0 2px rgba(0, 120, 212, 0.22);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #EAEEF2;
  --color-bg-soft:                       color-mix(in srgb, #EAEEF2 86%, #F0F2F4);
  --color-bg-elevated:                   color-mix(in srgb, #F1F3F6 90%, #ECEEF1);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #F1F3F6;
  --color-surface-muted:                 color-mix(in srgb, #F1F3F6 90%, #ECEEF1);
  --color-surface-strong:                #E6EAEE;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #D2D7DE 72%, #F1F3F6);
  --color-border-strong:                 color-mix(in srgb, #D2D7DE 84%, black);

  --color-text:                          #1F1F1F;
  --color-text-muted:                    #2E2E2E;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #DEECF9;
  --color-primary-active:                color-mix(in srgb, #0078D4 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #0078D4 18%, #F1F3F6);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #C42B1C;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, #0078D4 24%, #F5F6F8);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #F1F3F6 92%, #ECEEF1);
  --elevation-2-bg:                      color-mix(in srgb, #F1F3F6 84%, #ECEEF1);
  --elevation-3-bg:                      color-mix(in srgb, #F1F3F6 76%, #ECEEF1);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #E7EBEF;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #1F1F1F;
  --button-border:                       #BFC6CF;
  --button-border-active:                #005A9E;
  --button-primary-bg:                   #0b7fd9;
  --button-primary-text:                 #000000;
  --button-secondary-bg:                 #E2E7EC;
  --button-secondary-text:               #1F1F1F;
  --button-danger-text:                  #C42B1C;

  --panel-bg:                            #F1F3F6;
  --panel-text:                          #1F1F1F;
  --panel-border:                        #D2D7DE;
  --panel-head-bg:                       linear-gradient(180deg, #F5F7FA 0%, #E8EDF2 100%);
  --panel-head-text:                     #1F1F1F;

  --dialog-bg:                           #F3F5F7;
  --dialog-text:                         #1F1F1F;
  --dialog-border:                       #D2D7DE;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #EAEEF2;
  --calendar-border:                     #D2D7DE;
  --calendar-day-bg:                     #F5F7F9;
  --calendar-day-hover:                  #EAF3FC;
  --calendar-day-today:                  color-mix(in srgb, #0078D4 12%, #FFFFFF);
  --calendar-day-selected:               color-mix(in srgb, #0078D4 22%, #FFFFFF);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #1F1F1F);
  --calendar-range-bg:                   color-mix(in srgb, #0078D4 18%, #EAEEF2);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


