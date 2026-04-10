<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* FIFTH ELEMENT DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.74);
  --btn-back-linear-gradient:            linear-gradient(145deg, #322013 0%, #23160f 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #3e2718 0%, #2b1a10 100%);
  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #71482e;
  --work-back:                           linear-gradient(150deg, #23160f 0%, #1b120c 100%);
  --work-fore:                           #ffe4c8;
  --cal-day-fore:                        #ffe4c8;
  --cal-day-hover-fore:                  #2a1607;
  --cal-day-hover-glow:                  0 0 0 3px rgba(255, 107, 0, 0.35);
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #71482e;
  --work-entry-back:                     #2c1b12;
  --work-entry-fore:                     #ffe4c8;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #160f0a;
  --color-bg-soft:                       color-mix(in srgb, #160f0a 86%, #1d130d);
  --color-bg-elevated:                   color-mix(in srgb, #23160f 90%, #1d130d);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #23160f;
  --color-surface-muted:                 color-mix(in srgb, #23160f 90%, #1d130d);
  --color-surface-strong:                #1d130d;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #71482e 72%, #23160f);
  --color-border-strong:                 color-mix(in srgb, #71482e 84%, black);

  --color-text:                          #ffe4c8;
  --color-text-muted:                    #d9b692;
  --color-text-inverse:                  #2a1607;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #ff6b00;
  --color-primary-hover:                 #ff944a;
  --color-primary-active:                color-mix(in srgb, #ff6b00 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #ff6b00 18%, #23160f);
  --color-on-primary:                    #2a1607;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #ff6b00;
  --color-selection:                     color-mix(in srgb, #ff6b00 24%, #160f0a);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #23160f 92%, #1d130d);
  --elevation-2-bg:                      color-mix(in srgb, #23160f 84%, #1d130d);
  --elevation-3-bg:                      color-mix(in srgb, #23160f 76%, #1d130d);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #2c1b12;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #8d5938;
  --button-border-active:                #ffab70;
  --button-primary-bg:                   #ff6b00;
  --button-primary-text:                 #2a1607;
  --button-secondary-bg:                 #452a1a;
  --button-secondary-text:               #ffe4c8;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #23160f;
  --panel-text:                          #ffe4c8;
  --panel-border:                        #71482e;
  --panel-head-bg:                       #372115;
  --panel-head-text:                     #ffab70;

  --dialog-bg:                           #2c1b12;
  --dialog-text:                         #ffe4c8;
  --dialog-border:                       #71482e;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #160f0a;
  --calendar-border:                     #71482e;
  --calendar-day-bg:                     #1b120c;
  --calendar-day-hover:                  #ff6b00;
  --calendar-day-today:                  color-mix(in srgb, #ff6b00 12%, #1b120c);
  --calendar-day-selected:               color-mix(in srgb, #ff6b00 22%, #1b120c);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #ffe4c8);
  --calendar-range-bg:                   color-mix(in srgb, #ff6b00 18%, #160f0a);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


