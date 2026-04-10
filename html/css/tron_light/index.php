<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* TRON LIGHT */
:root {
  --dialog-shadow:                       rgba(2, 31, 49, 0.20);
  --btn-back-linear-gradient:            linear-gradient(145deg, #f3fbff 0%, #e5f7ff 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #e5f7ff 0%, #d6f0fd 100%);
  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #88c7e1;
  --work-back:                           linear-gradient(150deg, #ebf8ff 0%, #dff2fe 100%);
  --work-fore:                           #062a3f;
  --cal-day-fore:                        #062a3f;
  --cal-day-hover-fore:                  #03202f;
  --cal-day-hover-glow:                  0 0 0 3px rgba(0, 200, 255, 0.26);
  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(2, 31, 49, 0.14);
  --work-details-border:                 #88c7e1;
  --work-entry-back:                     #f3fbff;
  --work-entry-fore:                     #062a3f;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #def3ff;
  --color-bg-soft:                       color-mix(in srgb, #def3ff 86%, #e7f7ff);
  --color-bg-elevated:                   color-mix(in srgb, #eaf8ff 90%, #e7f7ff);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #eaf8ff;
  --color-surface-muted:                 color-mix(in srgb, #eaf8ff 90%, #e7f7ff);
  --color-surface-strong:                #e7f7ff;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #88c7e1 72%, #eaf8ff);
  --color-border-strong:                 color-mix(in srgb, #88c7e1 84%, black);

  --color-text:                          #062a3f;
  --color-text-muted:                    #1f4c66;
  --color-text-inverse:                  #03202f;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #66ddff;
  --color-primary-active:                color-mix(in srgb, #00c8ff 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #00c8ff 18%, #eaf8ff);
  --color-on-primary:                    #03202f;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #00c8ff 24%, #def3ff);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #eaf8ff 92%, #e7f7ff);
  --elevation-2-bg:                      color-mix(in srgb, #eaf8ff 84%, #e7f7ff);
  --elevation-3-bg:                      color-mix(in srgb, #eaf8ff 76%, #e7f7ff);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f3fbff;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #062a3f;
  --button-border:                       #88c7e1;
  --button-border-active:                #00c8ff;
  --button-primary-bg:                   #00c8ff;
  --button-primary-text:                 #03202f;
  --button-secondary-bg:                 #d9eef9;
  --button-secondary-text:               #062a3f;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #eaf8ff;
  --panel-text:                          #062a3f;
  --panel-border:                        #88c7e1;
  --panel-head-bg:                       #d8f2ff;
  --panel-head-text:                     #00607f;

  --dialog-bg:                           #f3fbff;
  --dialog-text:                         #062a3f;
  --dialog-border:                       #88c7e1;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #def3ff;
  --calendar-border:                     #88c7e1;
  --calendar-day-bg:                     #f5fcff;
  --calendar-day-hover:                  #00c8ff;
  --calendar-day-today:                  color-mix(in srgb, #00c8ff 12%, #f5fcff);
  --calendar-day-selected:               color-mix(in srgb, #00c8ff 22%, #f5fcff);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #062a3f);
  --calendar-range-bg:                   color-mix(in srgb, #00c8ff 18%, #def3ff);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


