<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Windows 10 Dark: graphite surfaces with restrained bright accent */




  --nav-menu-back:                       #202020;
  --nav-menu-fore:                       #F2F2F2;
  --system-tray-back:                    #191919;


  --text-color:                          #EDEDED;
  --text-muted:                          #A8A8A8;

  --dialog-shadow:                       rgba(0, 0, 0, 0.50);



  --panel-footer-back:                   #202429;




  --border-size:                         1px;
  --border-radius:                       4px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #3A3F45;

  --work-back:                           #181B1F;
  --work-fore:                           #EDEDED;
  --work-details-border:                 #3A3F45;
  --work-entry-back:                     #22262B;
  --work-entry-fore:                     #EDEDED;

  --cal-day-fore:                        #EDEDED;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      4px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.40);
  --cal-day-hover-glow:                  0 0 0 2px rgba(58, 160, 243, 0.32);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #111315;
  --color-bg-soft:                       color-mix(in srgb, #111315 86%, #202020);
  --color-bg-elevated:                   color-mix(in srgb, #1C1F23 90%, #202020);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #1C1F23;
  --color-surface-muted:                 color-mix(in srgb, #1C1F23 90%, #202020);
  --color-surface-strong:                #202020;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #3A3F45 72%, #1C1F23);
  --color-border-strong:                 color-mix(in srgb, #3A3F45 84%, black);

  --color-text:                          #EDEDED;
  --color-text-muted:                    #C8C8C8;
  --color-text-inverse:                  #0D1A26;
  --color-text-disabled:                 color-mix(in srgb, #EDEDED 56%, #1C1F23);

  --color-primary:                       #57B3FF;
  --color-primary-hover:                 #1A334A;
  --color-primary-active:                color-mix(in srgb, #57B3FF 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #57B3FF 18%, #1C1F23);
  --color-on-primary:                    #0D1A26;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #FF5F56;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #57B3FF;
  --color-selection:                     color-mix(in srgb, #57B3FF 24%, #111315);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #1C1F23 92%, #202020);
  --elevation-2-bg:                      color-mix(in srgb, #1C1F23 84%, #202020);
  --elevation-3-bg:                      color-mix(in srgb, #1C1F23 76%, #202020);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #2A2E33;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 88%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #525A63;
  --button-border-active:                #73BCF7;
  --button-primary-bg:                   #57B3FF;
  --button-primary-text:                 #0D1A26;
  --button-secondary-bg:                 #23262A;
  --button-secondary-text:               #EDEDED;
  --button-danger-text:                  #FF5F56;

  --panel-bg:                            #1C1F23;
  --panel-text:                          #EDEDED;
  --panel-border:                        #3A3F45;
  --panel-head-bg:                       linear-gradient(180deg, #2A2D31 0%, #202429 100%);
  --panel-head-text:                     #F2F2F2;

  --dialog-bg:                           #23262A;
  --dialog-text:                         #EDEDED;
  --dialog-border:                       #3A3F45;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #111315;
  --calendar-border:                     #3A3F45;
  --calendar-day-bg:                     #1C1F23;
  --calendar-day-hover:                  #263546;
  --calendar-day-today:                  color-mix(in srgb, #57B3FF 12%, #1C1F23);
  --calendar-day-selected:               color-mix(in srgb, #57B3FF 22%, #1C1F23);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #EDEDED);
  --calendar-range-bg:                   color-mix(in srgb, #57B3FF 18%, #111315);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


