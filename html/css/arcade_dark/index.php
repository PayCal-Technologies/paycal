<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Arcade Dark Theme: Neon, bold, 80s-inspired */
  --cal-day-fore: #FF00CC;
  --cal-day-hover-fore: #66661A;
  --cal-day-radius: 12px;
  --cal-day-shadow: 0 0 10px #00FFD0;
  --cal-day-hover-glow: 0 0 8px 2px #FF00CC;
  --border-size: 3px;
  --border-radius: 30px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --work-back: #1B1B2F;
  --work-fore: #FF00CC;
  --work-details-border: #00FFD0;
  --work-entry-back: #6A0F58;
  --work-entry-fore: #00FFD0;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #0A0A23;
  --color-bg-soft:                       color-mix(in srgb, #0A0A23 86%, #0A0A23);
  --color-bg-elevated:                   color-mix(in srgb, #1B1B2F 90%, #0A0A23);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #1B1B2F;
  --color-surface-muted:                 color-mix(in srgb, #1B1B2F 90%, #0A0A23);
  --color-surface-strong:                #0A0A23;
  --input-bg:                            var(--color-surface);

  --color-border:                        #00FFD0;
  --color-border-soft:                   color-mix(in srgb, #00FFD0 72%, #1B1B2F);
  --color-border-strong:                 color-mix(in srgb, #00FFD0 84%, black);

  --color-text:                          #FF00CC;
  --color-text-muted:                    var(--color-text);
  --color-text-inverse:                  #002921;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #FF00CC;
  --color-primary-hover:                 #FF00CC;
  --color-primary-active:                color-mix(in srgb, #FF00CC 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #FF00CC 18%, #1B1B2F);
  --color-on-primary:                    #002921;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #FF0041;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #FF00CC;
  --color-selection:                     color-mix(in srgb, #FF00CC 24%, #0A0A23);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #1B1B2F 92%, #0A0A23);
  --elevation-2-bg:                      color-mix(in srgb, #1B1B2F 84%, #0A0A23);
  --elevation-3-bg:                      color-mix(in srgb, #1B1B2F 76%, #0A0A23);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #00FFD0;
  --button-bg-hover:                     #00FFD0;
  --button-bg-active:                    #FF00CC;
  --button-text:                         #000000;
  --button-border:                       #00FFD0 #FF00CC #00FFD0 #FF00CC;
  --button-border-active:                #00FF41 #FF0041 #00FF41 #FF0041;
  --button-primary-bg:                   #FF00CC;
  --button-primary-text:                 #000000;
  --button-secondary-bg:                 #1B1B2F;
  --button-secondary-text:               #FF00CC;
  --button-danger-text:                  #FF0041;
  --btn-back:                            var(--button-bg);
  --btn-back-hover:                      var(--button-bg-hover);
  --btn-selected-back:                   color-mix(in srgb, #FF00CC 20%, #1B1B2F);
  --btn-selected-fore:                   #F6F0FF;
  --fore:                                var(--panel-text);
  --fore-muted:                          color-mix(in srgb, #FF00CC 62%, #1B1B2F);
  --back-light:                          color-mix(in srgb, #1B1B2F 88%, #0A0A23);

  --panel-bg:                            #1B1B2F;
  --panel-text:                          #FF00CC;
  --panel-border:                        #00FFD0;
  --panel-head-bg:                       #FF00CC;
  --panel-head-text:                     #0A0A23;
  --nav-menu-back:                       #FF00CC;
  --nav-menu-fore:                       #0A0A23;

  --dialog-bg:                           #1B1B2F;
  --dialog-text:                         #FF00CC;
  --dialog-border:                       #00FFD0;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #0A0A23;
  --calendar-border:                     #00FFD0;
  --calendar-day-bg:                     #0A0A23;
  --calendar-day-hover:                  #00FFD0;
  --calendar-day-today:                  color-mix(in srgb, #FF00CC 12%, #0A0A23);
  --calendar-day-selected:               color-mix(in srgb, #FF00CC 22%, #0A0A23);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #FF00CC);
  --calendar-range-bg:                   color-mix(in srgb, #FF00CC 18%, #0A0A23);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


