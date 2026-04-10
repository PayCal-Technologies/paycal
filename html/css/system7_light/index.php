<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Mac System 7 (Platinum): neutral grays, beveled edges, zero-radius controls */





  --text-color:                          #000000;
  --text-muted:                          #303030;

  --dialog-shadow:                       rgba(0, 0, 0, 0.30);



  --panel-footer-back:                   #D2D2D2;




  --border-size:                         1px;
  --border-radius:                       0;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #7A7A7A;

  --work-back:                           #D8D8D8;
  --work-fore:                           #000000;
  --work-details-border:                 #7A7A7A;
  --work-entry-back:                     #EDEDED;
  --work-entry-fore:                     #000000;

  --cal-day-fore:                        #000000;
  --cal-day-hover-fore:                  #000000;
  --cal-day-radius:                      0;
  --cal-day-shadow:                      none;
  --cal-day-hover-glow:                  none;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #BFBFBF;
  --color-bg-soft:                       color-mix(in srgb, #BFBFBF 86%, #DDDDDD);
  --color-bg-elevated:                   color-mix(in srgb, #DDDDDD 90%, #DDDDDD);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #DDDDDD;
  --color-surface-muted:                 color-mix(in srgb, #DDDDDD 90%, #DDDDDD);
  --color-surface-strong:                #DDDDDD;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #7A7A7A 72%, #DDDDDD);
  --color-border-strong:                 color-mix(in srgb, #7A7A7A 84%, black);

  --color-text:                          #000000;
  --color-text-muted:                    #000000;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       #3A3A3A;
  --color-primary-hover:                 #C9C9C9;
  --color-primary-active:                color-mix(in srgb, #3A3A3A 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #3A3A3A 18%, #DDDDDD);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #B00000;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #3A3A3A;
  --color-selection:                     color-mix(in srgb, #3A3A3A 24%, #BFBFBF);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #DDDDDD 92%, #DDDDDD);
  --elevation-2-bg:                      color-mix(in srgb, #DDDDDD 84%, #DDDDDD);
  --elevation-3-bg:                      color-mix(in srgb, #DDDDDD 76%, #DDDDDD);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #DDDDDD;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 25%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #000000;
  --button-border:                       #FFFFFF #6E6E6E #6E6E6E #FFFFFF;
  --button-border-active:                #6E6E6E #FFFFFF #FFFFFF #6E6E6E;
  --button-primary-bg:                   #3A3A3A;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #E5E5E5;
  --button-secondary-text:               #000000;
  --button-danger-text:                  #B00000;

  --panel-bg:                            #DDDDDD;
  --panel-text:                          #000000;
  --panel-border:                        #7A7A7A;
  --panel-head-bg:                       linear-gradient(180deg, #EFEFEF 0%, #C9C9C9 100%);
  --panel-head-text:                     #000000;

  --dialog-bg:                           #DDDDDD;
  --dialog-text:                         #000000;
  --dialog-border:                       #7A7A7A;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #BFBFBF;
  --calendar-border:                     #7A7A7A;
  --calendar-day-bg:                     #EDEDED;
  --calendar-day-hover:                  #F6F6F6;
  --calendar-day-today:                  color-mix(in srgb, #3A3A3A 12%, #EDEDED);
  --calendar-day-selected:               color-mix(in srgb, #3A3A3A 22%, #EDEDED);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #000000);
  --calendar-range-bg:                   color-mix(in srgb, #3A3A3A 18%, #BFBFBF);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


