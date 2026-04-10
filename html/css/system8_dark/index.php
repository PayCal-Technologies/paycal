<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Mac OS 8 dark interpretation: deep steel blues with classic beveled controls */





  --text-color:                          #EAF2FE;
  --text-muted:                          #B8CCE8;

  --dialog-shadow:                       rgba(0, 0, 0, 0.50);



  --panel-footer-back:                   #1E2C40;




  --border-size:                         1px;
  --border-radius:                       4px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #6782A7;

  --work-back:                           #1F2E42;
  --work-fore:                           #EAF2FE;
  --work-details-border:                 #6782A7;
  --work-entry-back:                     #2A3A50;
  --work-entry-fore:                     #EAF2FE;

  --cal-day-fore:                        #EAF2FE;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      4px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.40);
  --cal-day-hover-glow:                  0 0 0 2px rgba(115, 162, 228, 0.30);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #1C2737;
  --color-bg-soft:                       color-mix(in srgb, #1C2737 86%, #273447);
  --color-bg-elevated:                   color-mix(in srgb, #233246 90%, #273447);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #233246;
  --color-surface-muted:                 color-mix(in srgb, #233246 90%, #273447);
  --color-surface-strong:                #273447;
  --input-bg:                            var(--color-surface);

  --color-border:                        #ffffff;
  --color-border-soft:                   color-mix(in srgb, #6782A7 72%, #233246);
  --color-border-strong:                 color-mix(in srgb, #6782A7 84%, black);

  --color-text:                          #EAF2FE;
  --color-text-muted:                    #D8E5F8;
  --color-text-inverse:                  #10243C;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #73A2E4;
  --color-primary-hover:                 #3A4F6D;
  --color-primary-active:                color-mix(in srgb, #73A2E4 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #73A2E4 18%, #233246);
  --color-on-primary:                    #10243C;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #FF7474;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #73A2E4;
  --color-selection:                     color-mix(in srgb, #73A2E4 24%, #1C2737);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #233246 92%, #273447);
  --elevation-2-bg:                      color-mix(in srgb, #233246 84%, #273447);
  --elevation-3-bg:                      color-mix(in srgb, #233246 76%, #273447);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #304158;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #88A7D0 #152131 #152131 #88A7D0;
  --button-border-active:                #152131 #A7C2E4 #A7C2E4 #152131;
  --button-primary-bg:                   #73A2E4;
  --button-primary-text:                 #10243C;
  --button-secondary-bg:                 #29384D;
  --button-secondary-text:               #EAF2FE;
  --button-danger-text:                  #FF7474;

  --panel-bg:                            #233246;
  --panel-text:                          #EAF2FE;
  --panel-border:                        #6782A7;
  --panel-head-bg:                       linear-gradient(180deg, #39506F 0%, #27374C 100%);
  --panel-head-text:                     #F7FBFF;

  --dialog-bg:                           #28374B;
  --dialog-text:                         #EAF2FE;
  --dialog-border:                       #6782A7;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #1C2737;
  --calendar-border:                     #6782A7;
  --calendar-day-bg:                     #27374C;
  --calendar-day-hover:                  #324863;
  --calendar-day-today:                  color-mix(in srgb, #73A2E4 12%, #27374C);
  --calendar-day-selected:               color-mix(in srgb, #73A2E4 22%, #27374C);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #EAF2FE);
  --calendar-range-bg:                   color-mix(in srgb, #73A2E4 18%, #1C2737);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


