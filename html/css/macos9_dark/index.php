<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Mac OS 9 Graphite dark: muted blue-grays with crisp steel highlights */





  --text-color:                          #EAF0F8;
  --text-muted:                          #B7C5DA;

  --dialog-shadow:                       rgba(0, 0, 0, 0.52);



  --panel-footer-back:                   #202B3A;




  --border-size:                         1px;
  --border-radius:                       5px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #6B7E99;

  --work-back:                           #202937;
  --work-fore:                           #EAF0F8;
  --work-details-border:                 #6B7E99;
  --work-entry-back:                     #2A3444;
  --work-entry-fore:                     #EAF0F8;

  --cal-day-fore:                        #EAF0F8;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      5px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.42);
  --cal-day-hover-glow:                  0 0 0 2px rgba(126, 164, 216, 0.28);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #1F2631;
  --color-bg-soft:                       color-mix(in srgb, #1F2631 86%, #2A3341);
  --color-bg-elevated:                   color-mix(in srgb, #25303E 90%, #2A3341);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #25303E;
  --color-surface-muted:                 color-mix(in srgb, #25303E 90%, #2A3341);
  --color-surface-strong:                #2A3341;
  --input-bg:                            var(--color-surface);

  --color-border:                        #ffffff;
  --color-border-soft:                   color-mix(in srgb, #6B7E99 72%, #25303E);
  --color-border-strong:                 color-mix(in srgb, #6B7E99 84%, black);

  --color-text:                          #EAF0F8;
  --color-text-muted:                    #D8E2F1;
  --color-text-inverse:                  #112135;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #7EA4D8;
  --color-primary-hover:                 #3A4A60;
  --color-primary-active:                color-mix(in srgb, #7EA4D8 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #7EA4D8 18%, #25303E);
  --color-on-primary:                    #112135;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #FF6E6E;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #7EA4D8;
  --color-selection:                     color-mix(in srgb, #7EA4D8 24%, #1F2631);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #25303E 92%, #2A3341);
  --elevation-2-bg:                      color-mix(in srgb, #25303E 84%, #2A3341);
  --elevation-3-bg:                      color-mix(in srgb, #25303E 76%, #2A3341);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #313C4D;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #8FA4C1 #18202B #18202B #8FA4C1;
  --button-border-active:                #18202B #A8BDD8 #A8BDD8 #18202B;
  --button-primary-bg:                   #7EA4D8;
  --button-primary-text:                 #112135;
  --button-secondary-bg:                 #2A3445;
  --button-secondary-text:               #EAF0F8;
  --button-danger-text:                  #FF6E6E;

  --panel-bg:                            #25303E;
  --panel-text:                          #EAF0F8;
  --panel-border:                        #6B7E99;
  --panel-head-bg:                       linear-gradient(180deg, #3A4A60 0%, #283446 100%);
  --panel-head-text:                     #F7FAFF;

  --dialog-bg:                           #2A3341;
  --dialog-text:                         #EAF0F8;
  --dialog-border:                       #6B7E99;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #1F2631;
  --calendar-border:                     #6B7E99;
  --calendar-day-bg:                     #263142;
  --calendar-day-hover:                  #314157;
  --calendar-day-today:                  color-mix(in srgb, #7EA4D8 12%, #263142);
  --calendar-day-selected:               color-mix(in srgb, #7EA4D8 22%, #263142);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #EAF0F8);
  --calendar-range-bg:                   color-mix(in srgb, #7EA4D8 18%, #1F2631);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


