<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
    --arcade-date-label: #2F2F4F;
  /* Arcade Light Theme: Neon, bold, 80s-inspired */
  --cal-day-fore: #00FFD0;
  --cal-day-hover-fore: #00FF41;
  --cal-day-radius: 12px;
  --cal-day-shadow: 0 0 10px #FF00CC;
  --cal-day-hover-glow: 0 0 8px 2px #00FFD0;
  --border-size: 3px;
  --border-radius: 30px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --work-back: #2C3146;
  --work-fore: #F8F8F8;
  --work-details-border: #FF00CC;
  --work-entry-back: #C3D7D8;
  --work-entry-fore: #1B1B2F;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #ECEAF0;
  --color-bg-soft:                       color-mix(in srgb, #ECEAF0 86%, #F4F1F6);
  --color-bg-elevated:                   color-mix(in srgb, #D8DFE9 90%, #ECEAF0);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #D8DFE9;
  --color-surface-muted:                 color-mix(in srgb, #D8DFE9 90%, #ECEAF0);
  --color-surface-strong:                #C9D3E0;
  --input-bg:                            var(--color-surface);

  --color-border:                        #B10084;
  --color-border-soft:                   color-mix(in srgb, #B10084 72%, #D8DFE9);
  --color-border-strong:                 color-mix(in srgb, #B10084 84%, black);

  --color-text:                          #1B1B2F;
  --color-text-muted:                    #2F2F4F;
  --color-text-inverse:                  #F8F8F8;
  --color-text-disabled:                 #000000;

  --color-primary:                       #B10084;
  --color-primary-hover:                 #00FF41;
  --color-primary-active:                color-mix(in srgb, #00FFD0 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #00FFD0 18%, #D8DFE9);
  --color-on-primary:                    #F8F8F8;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #FF0041;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--work-back);
  --color-selection:                     color-mix(in srgb, #00FFD0 24%, #F8F8F8);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #D8DFE9 92%, #ECEAF0);
  --elevation-2-bg:                      color-mix(in srgb, #D8DFE9 84%, #ECEAF0);
  --elevation-3-bg:                      color-mix(in srgb, #D8DFE9 76%, #ECEAF0);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #F062CF;
  --button-bg-hover:                     #FF72D7;
  --button-bg-active:                    #00E1B9;
  --button-text:                         #1B1B2F;
  --button-border:                       #FF00CC #00FFD0 #FF00CC #00FFD0;
  --button-border-active:                #00FF41 #FF0041 #00FF41 #FF0041;
  --button-primary-bg:                   #00FFD0;
  --button-primary-text:                 #1B1B2F;
  --button-secondary-bg:                 #2F2F4F;
  --button-secondary-text:               #F8F8F8;
  --button-danger-text:                  #FF0041;

  --panel-bg:                            #D8DFE9;
  --panel-text:                          #1B1B2F;
  --panel-border:                        #FF00CC;
  --panel-head-bg:                       #00FFD0;
  --panel-head-text:                     #1B1B2F;

  --dialog-bg:                           #DDE3EC;
  --dialog-text:                         #1B1B2F;
  --dialog-border:                       #FF00CC;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #ECEAF0;
  --calendar-border:                     #FF00CC;
  --calendar-day-bg:                     #C9D2DD;
  --calendar-day-hover:                  #FF00CC;
  --calendar-day-today:                  color-mix(in srgb, #00FFD0 12%, #C5CBD6);
  --calendar-day-selected:               color-mix(in srgb, #00FFD0 22%, #C5CBD6);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #F8F8F8);
  --calendar-range-bg:                   color-mix(in srgb, #00FFD0 18%, #F8F8F8);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


