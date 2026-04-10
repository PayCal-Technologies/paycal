<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Star Trek II (Wrath of Khan) Light: parchment decks, maroon trims, brass accents */




  --nav-menu-back:                       #D6BCC5;
  --nav-menu-fore:                       #2D1E24;
  --system-tray-back:                    #CFB4BC;

  --heading-accent-color:                #6D2230;

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          #2D1E24;
  --text-muted:                          #5B4A4E;

  --dialog-shadow:                       rgba(16, 26, 36, 0.20);



  --panel-footer-back:                   #E7D6C2;




  --border-size:                         1px;
  --border-radius:                       12px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #C2A494;

  --work-back:                           #F0E0D0;
  --work-fore:                           #2D1E24;
  --work-details-border:                 #C2A494;
  --work-entry-back:                     #FAF2E9;
  --work-entry-fore:                     #2D1E24;

  --cal-day-fore:                        #2D1E24;
  --cal-day-hover-fore:                  #2D1E24;
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 1px 2px rgba(16, 26, 36, 0.12);
  --cal-day-hover-glow:                  0 0 0 2px rgba(139, 47, 63, 0.22);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #F4E8D8;
  --color-bg-soft:                       color-mix(in srgb, #F4E8D8 86%, #E9D8C0);
  --color-bg-elevated:                   color-mix(in srgb, #F7EEE2 90%, #E9D8C0);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #F7EEE2;
  --color-surface-muted:                 color-mix(in srgb, #F7EEE2 90%, #E9D8C0);
  --color-surface-strong:                #E9D8C0;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #C2A494 72%, #F7EEE2);
  --color-border-strong:                 color-mix(in srgb, #C2A494 84%, black);

  --color-text:                          #2D1E24;
  --color-text-muted:                    #2D1E24;
  --color-text-inverse:                  #FDF4E7;
  --color-text-disabled:                 #000000;

  --color-primary:                       #8B2F3F;
  --color-primary-hover:                 #EBD7C3;
  --color-primary-active:                color-mix(in srgb, #8B2F3F 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #8B2F3F 18%, #F7EEE2);
  --color-on-primary:                    #FDF4E7;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #9C352A;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #8B2F3F;
  --color-selection:                     color-mix(in srgb, #8B2F3F 24%, #F4E8D8);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #F7EEE2 92%, #E9D8C0);
  --elevation-2-bg:                      color-mix(in srgb, #F7EEE2 84%, #E9D8C0);
  --elevation-3-bg:                      color-mix(in srgb, #F7EEE2 76%, #E9D8C0);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #E5D0BC;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, white);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #2D1E24;
  --button-border:                       #B08874;
  --button-border-active:                #7A2634;
  --button-primary-bg:                   #8B2F3F;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #DFCBB7;
  --button-secondary-text:               #2D1E24;
  --button-danger-text:                  #9C352A;

  --panel-bg:                            #F7EEE2;
  --panel-text:                          #2D1E24;
  --panel-border:                        #C2A494;
  --panel-head-bg:                       linear-gradient(90deg, #D6B067 0%, #B86A5C 48%, #9482A5 100%);
  --panel-head-text:                     #000000;

  --dialog-bg:                           #F8EFE3;
  --dialog-text:                         #2D1E24;
  --dialog-border:                       #C2A494;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #F4E8D8;
  --calendar-border:                     #C2A494;
  --calendar-day-bg:                     #FAF2E9;
  --calendar-day-hover:                  #EEDAC7;
  --calendar-day-today:                  color-mix(in srgb, #8B2F3F 12%, #FAF2E9);
  --calendar-day-selected:               color-mix(in srgb, #8B2F3F 22%, #FAF2E9);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #2D1E24);
  --calendar-range-bg:                   color-mix(in srgb, #8B2F3F 18%, #F4E8D8);
}


