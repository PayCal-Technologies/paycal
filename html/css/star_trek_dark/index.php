<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Star Trek II (Wrath of Khan) Dark: maroon command uniforms, brass details, naval shadows */




  --nav-menu-back:                       #3A232B;
  --nav-menu-fore:                       #F2EADF;
  --system-tray-back:                    #251920;

  --heading-accent-color:                #D8B46B;

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          #F2EADF;
  --text-muted:                          #B9ABA0;

  --dialog-shadow:                       rgba(0, 0, 0, 0.50);



  --panel-footer-back:                   #312029;




  --border-size:                         1px;
  --border-radius:                       12px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #5A3C46;

  --work-back:                           #1C151B;
  --work-fore:                           #F2EADF;
  --work-details-border:                 #5A3C46;
  --work-entry-back:                     #2A1D24;
  --work-entry-fore:                     #F2EADF;

  --cal-day-fore:                        #F2EADF;
  --cal-day-hover-fore:                  #FFFFFF;
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.36);
  --cal-day-hover-glow:                  0 0 0 2px rgba(200, 154, 60, 0.30);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #171218;
  --color-bg-soft:                       color-mix(in srgb, #171218 86%, #231A1E);
  --color-bg-elevated:                   color-mix(in srgb, #241920 90%, #231A1E);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #241920;
  --color-surface-muted:                 color-mix(in srgb, #241920 90%, #231A1E);
  --color-surface-strong:                #231A1E;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #5A3C46 72%, #241920);
  --color-border-strong:                 color-mix(in srgb, #5A3C46 84%, black);

  --color-text:                          #F2EADF;
  --color-text-muted:                    #D8CCBF;
  --color-text-inverse:                  #F9EFE0;
  --color-text-disabled:                 color-mix(in srgb, #F2EADF 56%, #241920);

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #4D3640;
  --color-primary-active:                color-mix(in srgb, #8B2F3F 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #8B2F3F 18%, #241920);
  --color-on-primary:                    #F9EFE0;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #F07C63;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #8B2F3F 24%, #171218);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #241920 92%, #231A1E);
  --elevation-2-bg:                      color-mix(in srgb, #241920 84%, #231A1E);
  --elevation-3-bg:                      color-mix(in srgb, #241920 76%, #231A1E);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #36252D;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #79535D;
  --button-border-active:                #D8B46B;
  --button-primary-bg:                   #8B2F3F;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #3A2A31;
  --button-secondary-text:               #F2EADF;
  --button-danger-text:                  #F07C63;

  --panel-bg:                            #241920;
  --panel-text:                          #F2EADF;
  --panel-border:                        #5A3C46;
  --panel-head-bg:                       linear-gradient(90deg, #6B2C3A 0%, #4C2B43 56%, #2A3954 100%);
  --panel-head-text:                     #F6E7C9;

  --dialog-bg:                           #241A21;
  --dialog-text:                         #F2EADF;
  --dialog-border:                       #5A3C46;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #171218;
  --calendar-border:                     #5A3C46;
  --calendar-day-bg:                     #2A1D24;
  --calendar-day-hover:                  #4B3039;
  --calendar-day-today:                  color-mix(in srgb, #8B2F3F 12%, #2A1D24);
  --calendar-day-selected:               color-mix(in srgb, #8B2F3F 22%, #2A1D24);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #F2EADF);
  --calendar-range-bg:                   color-mix(in srgb, #8B2F3F 18%, #171218);
}


