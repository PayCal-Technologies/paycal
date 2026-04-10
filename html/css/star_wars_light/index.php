<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Star Wars Light: imperial durasteel, hologram blue, and bright command-deck surfaces */




  --nav-menu-back:                       #D3DEE8;
  --nav-menu-fore:                       #182430;
  --system-tray-back:                    #CAD6E1;

  --heading-accent-color:                #1F5C8F;

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          #182430;
  --text-muted:                          #5D7284;

  --dialog-shadow:                       rgba(24, 36, 48, 0.18);



  --panel-footer-back:                   #DFE8F0;




  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #B6C7D6;

  --work-back:                           #E7EFF5;
  --work-fore:                           #182430;
  --work-details-border:                 #B6C7D6;
  --work-entry-back:                     #FBFDFF;
  --work-entry-fore:                     #182430;

  --cal-day-fore:                        #182430;
  --cal-day-hover-fore:                  #182430;
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 1px 2px rgba(24, 36, 48, 0.10);
  --cal-day-hover-glow:                  0 0 0 2px rgba(42, 111, 168, 0.22);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #EEF4F8;
  --color-bg-soft:                       color-mix(in srgb, #EEF4F8 86%, #DCE5ED);
  --color-bg-elevated:                   color-mix(in srgb, #F5FAFD 90%, #DCE5ED);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #F5FAFD;
  --color-surface-muted:                 color-mix(in srgb, #F5FAFD 90%, #DCE5ED);
  --color-surface-strong:                #DCE5ED;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #B6C7D6 72%, #F5FAFD);
  --color-border-strong:                 color-mix(in srgb, #B6C7D6 84%, black);

  --color-text:                          #182430;
  --color-text-muted:                    #2B3C4B;
  --color-text-inverse:                  #F4FAFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       #2A6FA8;
  --color-primary-hover:                 #D9E8F3;
  --color-primary-active:                color-mix(in srgb, #2A6FA8 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #2A6FA8 18%, #F5FAFD);
  --color-on-primary:                    #F4FAFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #B94B3E;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #2A6FA8;
  --color-selection:                     color-mix(in srgb, #2A6FA8 24%, #EEF4F8);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #F5FAFD 92%, #DCE5ED);
  --elevation-2-bg:                      color-mix(in srgb, #F5FAFD 84%, #DCE5ED);
  --elevation-3-bg:                      color-mix(in srgb, #F5FAFD 76%, #DCE5ED);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #E3ECF3;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #182430;
  --button-border:                       #9EB1C4;
  --button-border-active:                #5F8DB7;
  --button-primary-bg:                   #2A6FA8;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #DAE5EE;
  --button-secondary-text:               #182430;
  --button-danger-text:                  #B94B3E;

  --panel-bg:                            #F5FAFD;
  --panel-text:                          #182430;
  --panel-border:                        #B6C7D6;
  --panel-head-bg:                       linear-gradient(90deg, #C4D4E3 0%, #9EB9D3 54%, #6F92B4 100%);
  --panel-head-text:                     #102030;

  --dialog-bg:                           #F7FBFE;
  --dialog-text:                         #182430;
  --dialog-border:                       #B6C7D6;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #EEF4F8;
  --calendar-border:                     #B6C7D6;
  --calendar-day-bg:                     #FBFDFF;
  --calendar-day-hover:                  #E4EEF6;
  --calendar-day-today:                  color-mix(in srgb, #2A6FA8 12%, #FBFDFF);
  --calendar-day-selected:               color-mix(in srgb, #2A6FA8 22%, #FBFDFF);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #182430);
  --calendar-range-bg:                   color-mix(in srgb, #2A6FA8 18%, #EEF4F8);
}


