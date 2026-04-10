<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* FIFTH ELEMENT LIGHT */
:root {
  --dialog-shadow:                       rgba(53, 31, 14, 0.20);
  --btn-back-linear-gradient:            linear-gradient(145deg, #fff8ed 0%, #ffecd5 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #ffecd5 0%, #ffe0bf 100%);
  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #d9a373;
  --work-back:                           linear-gradient(150deg, #fff0dd 0%, #ffe5ca 100%);
  --work-fore:                           #3e2a1e;
  --cal-day-fore:                        #3e2a1e;
  --cal-day-hover-fore:                  #2a1607;
  --cal-day-hover-glow:                  0 0 0 3px rgba(255, 107, 0, 0.25);
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(53, 31, 14, 0.14);
  --work-details-border:                 #d9a373;
  --work-entry-back:                     #fff8ed;
  --work-entry-fore:                     #3e2a1e;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #ffe8cf;
  --color-bg-soft:                       color-mix(in srgb, #ffe8cf 86%, #fff2e0);
  --color-bg-elevated:                   color-mix(in srgb, #fff0dd 90%, #fff2e0);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #fff0dd;
  --color-surface-muted:                 color-mix(in srgb, #fff0dd 90%, #fff2e0);
  --color-surface-strong:                #fff2e0;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #d9a373 72%, #fff0dd);
  --color-border-strong:                 color-mix(in srgb, #d9a373 84%, black);

  --color-text:                          #3e2a1e;
  --color-text-muted:                    #5d402d;
  --color-text-inverse:                  #2a1607;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #ff944a;
  --color-primary-active:                color-mix(in srgb, #ff6b00 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #ff6b00 18%, #fff0dd);
  --color-on-primary:                    #2a1607;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #ff6b00 24%, #ffe8cf);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #fff0dd 92%, #fff2e0);
  --elevation-2-bg:                      color-mix(in srgb, #fff0dd 84%, #fff2e0);
  --elevation-3-bg:                      color-mix(in srgb, #fff0dd 76%, #fff2e0);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #fff8ed;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #3e2a1e;
  --button-border:                       #d9a373;
  --button-border-active:                #ff6b00;
  --button-primary-bg:                   #ff6b00;
  --button-primary-text:                 #2a1607;
  --button-secondary-bg:                 #f9dfc2;
  --button-secondary-text:               #3e2a1e;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #fff0dd;
  --panel-text:                          #3e2a1e;
  --panel-border:                        #d9a373;
  --panel-head-bg:                       #ffe4c6;
  --panel-head-text:                     #a44500;

  --dialog-bg:                           #fff8ed;
  --dialog-text:                         #3e2a1e;
  --dialog-border:                       #d9a373;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #ffe8cf;
  --calendar-border:                     #d9a373;
  --calendar-day-bg:                     #fff9ef;
  --calendar-day-hover:                  #ff6b00;
  --calendar-day-today:                  color-mix(in srgb, #ff6b00 12%, #fff9ef);
  --calendar-day-selected:               color-mix(in srgb, #ff6b00 22%, #fff9ef);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #3e2a1e);
  --calendar-range-bg:                   color-mix(in srgb, #ff6b00 18%, #ffe8cf);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;
}


