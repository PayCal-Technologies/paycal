<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Sweater Weather Dark: wool charcoal with muted evergreen accents */




  --nav-menu-back:                       #2D2A2E;
  --nav-menu-fore:                       #EFE9E7;
  --system-tray-back:                    #232025;


  --text-color:                          #EFE9E7;
  --text-muted:                          #B4BBB2;

  --dialog-shadow:                       rgba(0, 0, 0, 0.48);



  --panel-footer-back:                   #322D35;




  --border-size:                         1px;
  --border-radius:                       7px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #514A54;

  --work-back:                           #262229;
  --work-fore:                           #EFE9E7;
  --work-details-border:                 #514A54;
  --work-entry-back:                     #302C32;
  --work-entry-fore:                     #EFE9E7;

  --cal-day-fore:                        #EFE9E7;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      7px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.36);
  --cal-day-hover-glow:                  0 0 0 2px rgba(106, 166, 134, 0.34);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #221F23;
  --color-bg-soft:                       color-mix(in srgb, #221F23 86%, #2D2A2E);
  --color-bg-elevated:                   color-mix(in srgb, #2B272D 90%, #2D2A2E);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #2B272D;
  --color-surface-muted:                 color-mix(in srgb, #2B272D 90%, #2D2A2E);
  --color-surface-strong:                #2D2A2E;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #514A54 72%, #2B272D);
  --color-border-strong:                 color-mix(in srgb, #514A54 84%, black);

  --color-text:                          #EFE9E7;
  --color-text-muted:                    #CFC6C7;
  --color-text-inverse:                  #0D281C;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #6AA686;
  --color-primary-hover:                 #334A3D;
  --color-primary-active:                color-mix(in srgb, #6AA686 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #6AA686 18%, #2B272D);
  --color-on-primary:                    #0D281C;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #D1908A;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #6AA686;
  --color-selection:                     color-mix(in srgb, #6AA686 24%, #221F23);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #2B272D 92%, #2D2A2E);
  --elevation-2-bg:                      color-mix(in srgb, #2B272D 84%, #2D2A2E);
  --elevation-3-bg:                      color-mix(in srgb, #2B272D 76%, #2D2A2E);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #3A343D;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #546B56;
  --button-border-active:                #748B76;
  --button-primary-bg:                   #6AA686;
  --button-primary-text:                 #0D281C;
  --button-secondary-bg:                 #3D3830;
  --button-secondary-text:               #EFE9E7;
  --button-danger-text:                  #D1908A;

  --panel-bg:                            #2B272D;
  --panel-text:                          #EFE9E7;
  --panel-border:                        #514A54;
  --panel-head-bg:                       linear-gradient(180deg, #3B353D 0%, #312C34 100%);
  --panel-head-text:                     #EFE9E7;

  --dialog-bg:                           #312D33;
  --dialog-text:                         #EFE9E7;
  --dialog-border:                       #514A54;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #221F23;
  --calendar-border:                     #514A54;
  --calendar-day-bg:                     #2C282E;
  --calendar-day-hover:                  #365243;
  --calendar-day-today:                  color-mix(in srgb, #6AA686 12%, #2C282E);
  --calendar-day-selected:               color-mix(in srgb, #6AA686 22%, #2C282E);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #EFE9E7);
  --calendar-range-bg:                   color-mix(in srgb, #6AA686 18%, #221F23);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


