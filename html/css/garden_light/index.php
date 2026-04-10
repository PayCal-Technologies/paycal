<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Sweater Weather Light: heather knits with soft evergreen accents */




  --nav-menu-back:                       #ECE7E1;
  --nav-menu-fore:                       #3E3A3B;
  --system-tray-back:                    #DED7CF;


  --text-color:                          #3E3A3B;
  --text-muted:                          #5C5E57;

  --dialog-shadow:                       rgba(62, 58, 59, 0.14);



  --panel-footer-back:                   #EFE5DC;




  --border-size:                         1px;
  --border-radius:                       7px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #D4C9BF;

  --work-back:                           #F1EAE2;
  --work-fore:                           #3E3A3B;
  --work-details-border:                 #D4C9BF;
  --work-entry-back:                     #FCF9F4;
  --work-entry-fore:                     #3E3A3B;

  --cal-day-fore:                        #3E3A3B;
  --cal-day-hover-fore:                  #3E3A3B;
  --cal-day-radius:                      7px;
  --cal-day-shadow:                      0 1px 2px rgba(62, 58, 59, 0.08);
  --cal-day-hover-glow:                  0 0 0 2px rgba(53, 111, 84, 0.24);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #F7F3EE;
  --color-bg-soft:                       color-mix(in srgb, #F7F3EE 86%, #ECE7E1);
  --color-bg-elevated:                   color-mix(in srgb, #F8F2EB 90%, #ECE7E1);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #F8F2EB;
  --color-surface-muted:                 color-mix(in srgb, #F8F2EB 90%, #ECE7E1);
  --color-surface-strong:                #ECE7E1;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #D4C9BF 72%, #F8F2EB);
  --color-border-strong:                 color-mix(in srgb, #D4C9BF 84%, black);

  --color-text:                          #3E3A3B;
  --color-text-muted:                    #4D4647;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       #356F54;
  --color-primary-hover:                 #DCEEE4;
  --color-primary-active:                color-mix(in srgb, #356F54 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #356F54 18%, #F8F2EB);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #A85754;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #356F54;
  --color-selection:                     color-mix(in srgb, #356F54 24%, #F7F3EE);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #F8F2EB 92%, #ECE7E1);
  --elevation-2-bg:                      color-mix(in srgb, #F8F2EB 84%, #ECE7E1);
  --elevation-3-bg:                      color-mix(in srgb, #F8F2EB 76%, #ECE7E1);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #E9DED4;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #3E3A3B;
  --button-border:                       #C7B9AE;
  --button-border-active:                #2C5F48;
  --button-primary-bg:                   #356F54;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #E5DCCB;
  --button-secondary-text:               #3E3A3B;
  --button-danger-text:                  #A85754;

  --panel-bg:                            #F8F2EB;
  --panel-text:                          #3E3A3B;
  --panel-border:                        #D4C9BF;
  --panel-head-bg:                       linear-gradient(180deg, #F2EAE3 0%, #E9DED4 100%);
  --panel-head-text:                     #3E3A3B;

  --dialog-bg:                           #FCF9F4;
  --dialog-text:                         #3E3A3B;
  --dialog-border:                       #D4C9BF;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #F7F3EE;
  --calendar-border:                     #D4C9BF;
  --calendar-day-bg:                     #FCF9F4;
  --calendar-day-hover:                  #E4F0E9;
  --calendar-day-today:                  color-mix(in srgb, #356F54 12%, #FCF9F4);
  --calendar-day-selected:               color-mix(in srgb, #356F54 22%, #FCF9F4);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #3E3A3B);
  --calendar-range-bg:                   color-mix(in srgb, #356F54 18%, #F7F3EE);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


