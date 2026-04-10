<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Star Wars Dark: Death Star graphite, hologram blue, and subtle warning crimson */




  --nav-menu-back:                       #151B22;
  --nav-menu-fore:                       #E6EDF3;
  --system-tray-back:                    #0E1319;

  --heading-accent-color:                #8ACEFF;

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          #E6EDF3;
  --text-muted:                          #98A6B3;

  --dialog-shadow:                       rgba(0, 0, 0, 0.56);



  --panel-footer-back:                   #182028;




  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #334150;

  --work-back:                           #10161D;
  --work-fore:                           #E6EDF3;
  --work-details-border:                 #334150;
  --work-entry-back:                     #18212C;
  --work-entry-fore:                     #E6EDF3;

  --cal-day-fore:                        #E6EDF3;
  --cal-day-hover-fore:                  #F4FAFF;
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.40);
  --cal-day-hover-glow:                  0 0 0 2px rgba(91, 182, 255, 0.28);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #0A0D12;
  --color-bg-soft:                       color-mix(in srgb, #0A0D12 86%, #101419);
  --color-bg-elevated:                   color-mix(in srgb, #121820 90%, #101419);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #121820;
  --color-surface-muted:                 color-mix(in srgb, #121820 90%, #101419);
  --color-surface-strong:                #101419;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #334150 72%, #121820);
  --color-border-strong:                 color-mix(in srgb, #334150 84%, black);

  --color-text:                          #E6EDF3;
  --color-text-muted:                    #BCC7D1;
  --color-text-inverse:                  #F4FAFF;
  --color-text-disabled:                 color-mix(in srgb, #E6EDF3 52%, #121820);

  --color-primary:                       #ffffff;
  --color-primary-hover:                 #243648;
  --color-primary-active:                color-mix(in srgb, #2A6FA8 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #2A6FA8 18%, #121820);
  --color-on-primary:                    #F4FAFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #F06A5F;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     color-mix(in srgb, #2A6FA8 24%, #0A0D12);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #121820 92%, #101419);
  --elevation-2-bg:                      color-mix(in srgb, #121820 84%, #101419);
  --elevation-3-bg:                      color-mix(in srgb, #121820 76%, #101419);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #1C2430;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #4A6078;
  --button-border-active:                #8ACEFF;
  --button-primary-bg:                   #2A6FA8;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #222B37;
  --button-secondary-text:               #E6EDF3;
  --button-danger-text:                  #F06A5F;

  --panel-bg:                            #121820;
  --panel-text:                          #E6EDF3;
  --panel-border:                        #334150;
  --panel-head-bg:                       linear-gradient(90deg, #162538 0%, #243d56 54%, #46627f 100%);
  --panel-head-text:                     #F4FAFF;

  --dialog-bg:                           #141A21;
  --dialog-text:                         #E6EDF3;
  --dialog-border:                       #334150;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #0A0D12;
  --calendar-border:                     #334150;
  --calendar-day-bg:                     #18212C;
  --calendar-day-hover:                  #223243;
  --calendar-day-today:                  color-mix(in srgb, #2A6FA8 12%, #18212C);
  --calendar-day-selected:               color-mix(in srgb, #2A6FA8 22%, #18212C);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #E6EDF3);
  --calendar-range-bg:                   color-mix(in srgb, #2A6FA8 18%, #0A0D12);
}


