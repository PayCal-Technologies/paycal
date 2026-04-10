<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Windows XP Luna light: bright blue chrome with neutral workspace surfaces */





  --text-color:                          #0F1A2A;
  --text-muted:                          #2E4768;

  --dialog-shadow:                       rgba(0, 0, 0, 0.30);



  --panel-footer-back:                   #E4DFCD;




  --border-size:                         1px;
  --border-radius:                       6px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #7A96DF;

  --work-back:                           #F5F3EA;
  --work-fore:                           #111827;
  --work-details-border:                 #9DB1E5;
  --work-entry-back:                     #F4F1E8;
  --work-entry-fore:                     #111827;

  --cal-day-fore:                        #111827;
  --cal-day-hover-fore:                  #0B1F42;
  --cal-day-radius:                      6px;
  --cal-day-shadow:                      0 1px 2px rgba(17, 24, 39, 0.20);
  --cal-day-hover-glow:                  0 0 0 2px rgba(36, 94, 219, 0.22);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #6EA8FE;
  --color-bg-soft:                       color-mix(in srgb, #6EA8FE 86%, #245EDB);
  --color-bg-elevated:                   color-mix(in srgb, #ECE9D8 90%, #245EDB);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #ECE9D8;
  --color-surface-muted:                 color-mix(in srgb, #ECE9D8 90%, #245EDB);
  --color-surface-strong:                #245EDB;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #7A96DF 72%, #ECE9D8);
  --color-border-strong:                 color-mix(in srgb, #7A96DF 84%, black);

  --color-text:                          #111827;
  --color-text-muted:                    var(--color-text);
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #DCE7FF;
  --color-primary-active:                color-mix(in srgb, #2B6CE3 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #2B6CE3 18%, #ECE9D8);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #C12A1A;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #2B6CE3 24%, #6EA8FE);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #ECE9D8 92%, #245EDB);
  --elevation-2-bg:                      color-mix(in srgb, #ECE9D8 84%, #245EDB);
  --elevation-3-bg:                      color-mix(in srgb, #ECE9D8 76%, #245EDB);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #ECE9D8;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #111827;
  --button-border:                       #FFFFFF #7D8FB3 #7D8FB3 #FFFFFF;
  --button-border-active:                #7D8FB3 #FFFFFF #FFFFFF #7D8FB3;
  --button-primary-bg:                   #2B6CE3;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #F2F0E6;
  --button-secondary-text:               #1F2937;
  --button-danger-text:                  #C12A1A;
  --btn-selected-back:                   #245EDB;
  --btn-selected-fore:                   #FFFFFF;

  --panel-bg:                            #ECE9D8;
  --panel-text:                          #111827;
  --panel-border:                        #7A96DF;
  --panel-head-bg:                       linear-gradient(180deg, #255fca 0%, #1b4fb5 100%);
  --panel-head-text:                     #FFFFFF;

  --dialog-bg:                           #ECE9D8;
  --dialog-text:                         #111827;
  --dialog-border:                       #7A96DF;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #6EA8FE;
  --calendar-border:                     #7A96DF;
  --calendar-day-bg:                     #F4F1E8;
  --calendar-day-hover:                  #E5EEFF;
  --calendar-day-today:                  color-mix(in srgb, #2B6CE3 12%, #FFFFFF);
  --calendar-day-selected:               color-mix(in srgb, #2B6CE3 22%, #FFFFFF);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #111827);
  --calendar-range-bg:                   color-mix(in srgb, #2B6CE3 18%, #6EA8FE);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


