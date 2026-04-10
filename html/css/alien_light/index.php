<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* ALIEN LIGHT */
:root {
  --dialog-shadow:                       rgba(23, 35, 26, 0.20);
  --btn-back-linear-gradient:            linear-gradient(145deg, #f2f7f0 0%, #e6efe3 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #e6efe3 0%, #d8e5d3 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #95a98e;
  --work-back:                           linear-gradient(150deg, #e8f0e6 0%, #dce7d8 100%);
  --work-fore:                           #213126;
  --cal-day-fore:                        #213126;
  --cal-day-hover-fore:                  #13200d;
  --cal-day-hover-glow:                  0 0 0 3px rgba(143, 191, 63, 0.24);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(23, 35, 26, 0.14);
  --work-details-border:                 #95a98e;
  --work-entry-back:                     #f2f7f0;
  --work-entry-fore:                     #213126;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #e1eade;
  --color-bg-soft:                       color-mix(in srgb, #e1eade 86%, #eaf1e8);
  --color-bg-elevated:                   color-mix(in srgb, #e8f0e6 90%, #eaf1e8);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #e8f0e6;
  --color-surface-muted:                 color-mix(in srgb, #e8f0e6 90%, #eaf1e8);
  --color-surface-strong:                #eaf1e8;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #95a98e 72%, #e8f0e6);
  --color-border-strong:                 color-mix(in srgb, #95a98e 84%, black);

  --color-text:                          #213126;
  --color-text-muted:                    #3c5542;
  --color-text-inverse:                  #13200d;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #a8d26a;
  --color-primary-active:                color-mix(in srgb, #8fbf3f 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #8fbf3f 18%, #e8f0e6);
  --color-on-primary:                    #13200d;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #8fbf3f 24%, #e1eade);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #e8f0e6 92%, #eaf1e8);
  --elevation-2-bg:                      color-mix(in srgb, #e8f0e6 84%, #eaf1e8);
  --elevation-3-bg:                      color-mix(in srgb, #e8f0e6 76%, #eaf1e8);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f2f7f0;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #213126;
  --button-border:                       #95a98e;
  --button-border-active:                #8fbf3f;
  --button-primary-bg:                   #8fbf3f;
  --button-primary-text:                 #13200d;
  --button-secondary-bg:                 #dbe6d7;
  --button-secondary-text:               #213126;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #e8f0e6;
  --panel-text:                          #213126;
  --panel-border:                        #95a98e;
  --panel-head-bg:                       #dbe7d7;
  --panel-head-text:                     #466237;

  --dialog-bg:                           #f2f7f0;
  --dialog-text:                         #213126;
  --dialog-border:                       #95a98e;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #e1eade;
  --calendar-border:                     #95a98e;
  --calendar-day-bg:                     #f4f9f2;
  --calendar-day-hover:                  #8fbf3f;
  --calendar-day-today:                  color-mix(in srgb, #8fbf3f 12%, #f4f9f2);
  --calendar-day-selected:               color-mix(in srgb, #8fbf3f 22%, #f4f9f2);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #213126);
  --calendar-range-bg:                   color-mix(in srgb, #8fbf3f 18%, #e1eade);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


