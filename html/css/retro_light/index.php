<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Retro Light: soft paper neutrals with red-forward 80s accents */




  --nav-menu-back:                       #E9DED7;
  --nav-menu-fore:                       #2C2423;
  --system-tray-back:                    #DCCDC6;


  --text-color:                          #2C2423;
  --text-muted:                          #5E4F4C;

  --dialog-shadow:                       rgba(44, 36, 35, 0.18);



  --panel-footer-back:                   #E6D7D0;




  --border-size:                         1px;
  --border-radius:                       6px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #D5C0B8;

  --work-back:                           #ECE1DA;
  --work-fore:                           #2C2423;
  --work-details-border:                 #D5C0B8;
  --work-entry-back:                     #F7F1EB;
  --work-entry-fore:                     #2C2423;

  --cal-day-fore:                        #2C2423;
  --cal-day-hover-fore:                  #2C2423;
  --cal-day-radius:                      6px;
  --cal-day-shadow:                      0 1px 2px rgba(44, 36, 35, 0.10);
  --cal-day-hover-glow:                  0 0 0 2px rgba(169, 62, 58, 0.24);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #EFE7E1;
  --color-bg-soft:                       color-mix(in srgb, #EFE7E1 86%, #F2EAE4);
  --color-bg-elevated:                   color-mix(in srgb, #F4EEE8 90%, #E8DED6);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #F4EEE8;
  --color-surface-muted:                 color-mix(in srgb, #F4EEE8 90%, #F0E6DF);
  --color-surface-strong:                #E7DCD4;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #D5C0B8 72%, #F4EEE8);
  --color-border-strong:                 color-mix(in srgb, #D5C0B8 84%, black);

  --color-text:                          #2C2423;
  --color-text-muted:                    #3A2D2B;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       #A93E3A;
  --color-primary-hover:                 #F0D9D2;
  --color-primary-active:                color-mix(in srgb, #A93E3A 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #A93E3A 18%, #F4EEE8);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #A33631;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #A93E3A;
  --color-selection:                     color-mix(in srgb, #A93E3A 24%, #F8F3EF);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #F4EEE8 92%, #E8DED6);
  --elevation-2-bg:                      color-mix(in srgb, #F4EEE8 84%, #E8DED6);
  --elevation-3-bg:                      color-mix(in srgb, #F4EEE8 76%, #E8DED6);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #F0E3DE;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #2C2423;
  --button-border:                       #B7968C;
  --button-border-active:                #8E312E;
  --button-primary-bg:                   #A93E3A;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #E6D6CE;
  --button-secondary-text:               #2C2423;
  --button-danger-text:                  #A33631;

  --panel-bg:                            #F4EEE8;
  --panel-text:                          #2C2423;
  --panel-border:                        #D5C0B8;
  --panel-head-bg:                       linear-gradient(180deg, #F6EAE4 0%, #EDDCD5 100%);
  --panel-head-text:                     #2C2423;

  --dialog-bg:                           #F7F1EB;
  --dialog-text:                         #2C2423;
  --dialog-border:                       #D5C0B8;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #EFE7E1;
  --calendar-border:                     #D5C0B8;
  --calendar-day-bg:                     #F7F1EB;
  --calendar-day-hover:                  #F3DFD8;
  --calendar-day-today:                  color-mix(in srgb, #A93E3A 12%, #FEFAF7);
  --calendar-day-selected:               color-mix(in srgb, #A93E3A 22%, #FEFAF7);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #2C2423);
  --calendar-range-bg:                   color-mix(in srgb, #A93E3A 18%, #F8F3EF);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


