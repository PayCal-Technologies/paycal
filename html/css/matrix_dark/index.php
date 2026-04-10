<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* MATRIX DARK */
:root {
  --dialog-shadow:                       rgba(0, 0, 0, 0.78);
  --btn-back-linear-gradient:            linear-gradient(145deg, #132517 0%, #0c180f 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #1a3421 0%, #102218 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #2f6640;
  --work-back:                           linear-gradient(150deg, #0c180f 0%, #09130c 100%);
  --work-fore:                           #c6efcd;
  --cal-day-fore:                        #c6efcd;
  --cal-day-hover-fore:                  #07220f;
  --cal-day-hover-glow:                  0 0 0 3px rgba(0, 177, 64, 0.38);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);
  --work-details-border:                 #2f6640;
  --work-entry-back:                     #0f1d12;
  --work-entry-fore:                     #c6efcd;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #060d08;
  --color-bg-soft:                       color-mix(in srgb, #060d08 86%, #09120b);
  --color-bg-elevated:                   color-mix(in srgb, #0c180f 90%, #09120b);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #0c180f;
  --color-surface-muted:                 color-mix(in srgb, #0c180f 90%, #09120b);
  --color-surface-strong:                #09120b;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #2f6640 72%, #0c180f);
  --color-border-strong:                 color-mix(in srgb, #2f6640 84%, black);

  --color-text:                          #c6efcd;
  --color-text-muted:                    #90bf98;
  --color-text-inverse:                  #07220f;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #00b140;
  --color-primary-hover:                 #53c877;
  --color-primary-active:                color-mix(in srgb, #00b140 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #00b140 18%, #0c180f);
  --color-on-primary:                    #07220f;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #00b140;
  --color-selection:                     color-mix(in srgb, #00b140 24%, #060d08);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #0c180f 92%, #09120b);
  --elevation-2-bg:                      color-mix(in srgb, #0c180f 84%, #09120b);
  --elevation-3-bg:                      color-mix(in srgb, #0c180f 76%, #09120b);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #0f1d12;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #3d7d52;
  --button-border-active:                #53c877;
  --button-primary-bg:                   #00b140;
  --button-primary-text:                 #07220f;
  --button-secondary-bg:                 #1d3a25;
  --button-secondary-text:               #c6efcd;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #0c180f;
  --panel-text:                          #c6efcd;
  --panel-border:                        #2f6640;
  --panel-head-bg:                       #14291a;
  --panel-head-text:                     #53c877;

  --dialog-bg:                           #0f1d12;
  --dialog-text:                         #c6efcd;
  --dialog-border:                       #2f6640;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #060d08;
  --calendar-border:                     #2f6640;
  --calendar-day-bg:                     #09130c;
  --calendar-day-hover:                  #00b140;
  --calendar-day-today:                  color-mix(in srgb, #00b140 12%, #09130c);
  --calendar-day-selected:               color-mix(in srgb, #00b140 22%, #09130c);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #c6efcd);
  --calendar-range-bg:                   color-mix(in srgb, #00b140 18%, #060d08);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


