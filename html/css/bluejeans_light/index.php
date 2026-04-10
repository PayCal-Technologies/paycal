<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Bluejeans Light: faded chambray, bleached seams, and indigo-dyed accents */




  --nav-menu-back:                       #DDE5EE;
  --nav-menu-fore:                       #243545;
  --system-tray-back:                    #CFD9E4;

  --heading-accent-color:                #395E97;

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          #243545;
  --text-muted:                          #576B7D;

  --dialog-shadow:                       rgba(36, 53, 69, 0.15);



  --panel-footer-back:                   #E6EEF5;




  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #B8C8D8;

  --work-back:                           #E6EEF4;
  --work-fore:                           #243545;
  --work-details-border:                 #B8C8D8;
  --work-entry-back:                     #F8FBFD;
  --work-entry-fore:                     #243545;

  --cal-day-fore:                        #243545;
  --cal-day-hover-fore:                  #243545;
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 1px 2px rgba(36, 53, 69, 0.08);
  --cal-day-hover-glow:                  0 0 0 2px rgba(63, 111, 169, 0.24);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #EEF3F7;
  --color-bg-soft:                       color-mix(in srgb, #EEF3F7 86%, #DDE5EE);
  --color-bg-elevated:                   color-mix(in srgb, #F3F8FB 90%, #DDE5EE);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #F3F8FB;
  --color-surface-muted:                 color-mix(in srgb, #F3F8FB 90%, #DDE5EE);
  --color-surface-strong:                #DDE5EE;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #B8C8D8 72%, #F3F8FB);
  --color-border-strong:                 color-mix(in srgb, #B8C8D8 84%, black);

  --color-text:                          #243545;
  --color-text-muted:                    #355060;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #D5E2F0;
  --color-primary-active:                color-mix(in srgb, #3F6FA9 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #3F6FA9 18%, #F3F8FB);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #B86E62;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, #3F6FA9 24%, #EEF3F7);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #F3F8FB 92%, #DDE5EE);
  --elevation-2-bg:                      color-mix(in srgb, #F3F8FB 84%, #DDE5EE);
  --elevation-3-bg:                      color-mix(in srgb, #F3F8FB 76%, #DDE5EE);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #DCE6EF;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #243545;
  --button-border:                       #A8BACB;
  --button-border-active:                #2E5C90;
  --button-primary-bg:                   #3F6FA9;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #D5E0EA;
  --button-secondary-text:               #243545;
  --button-danger-text:                  #B86E62;

  --panel-bg:                            #F3F8FB;
  --panel-text:                          #243545;
  --panel-border:                        #B8C8D8;
  --panel-head-bg:                       linear-gradient(180deg, #E6EDF4 0%, #D8E2EB 100%);
  --panel-head-text:                     #243545;

  --dialog-bg:                           #F8FBFD;
  --dialog-text:                         #243545;
  --dialog-border:                       #B8C8D8;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #EEF3F7;
  --calendar-border:                     #B8C8D8;
  --calendar-day-bg:                     #F8FBFD;
  --calendar-day-hover:                  #E2EBF4;
  --calendar-day-today:                  color-mix(in srgb, #3F6FA9 12%, #F8FBFD);
  --calendar-day-selected:               color-mix(in srgb, #3F6FA9 22%, #F8FBFD);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #243545);
  --calendar-range-bg:                   color-mix(in srgb, #3F6FA9 18%, #EEF3F7);
}


