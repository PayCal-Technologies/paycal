<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* DUNE LIGHT */
:root {
  --dialog-shadow:                       rgba(49, 34, 20, 0.20);
  --btn-back-linear-gradient:            linear-gradient(145deg, #f8eedf 0%, #efdfc8 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #efdfc8 0%, #e4cfb0 100%);
  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #b7946a;
  --work-back:                           linear-gradient(150deg, #f3e5d1 0%, #e7d3b5 100%);
  --work-fore:                           #3b2d1f;
  --cal-day-fore:                        #3b2d1f;
  --cal-day-hover-fore:                  #2c1f13;
  --cal-day-hover-glow:                  0 0 0 3px rgba(214, 161, 94, 0.24);
  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(49, 34, 20, 0.14);
  --work-details-border:                 #b7946a;
  --work-entry-back:                     #f8eedf;
  --work-entry-fore:                     #3b2d1f;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #e9d8be;
  --color-bg-soft:                       color-mix(in srgb, #e9d8be 86%, #f1e4cf);
  --color-bg-elevated:                   color-mix(in srgb, #f3e5d1 90%, #f1e4cf);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #f3e5d1;
  --color-surface-muted:                 color-mix(in srgb, #f3e5d1 90%, #f1e4cf);
  --color-surface-strong:                #f1e4cf;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #b7946a 72%, #f3e5d1);
  --color-border-strong:                 color-mix(in srgb, #b7946a 84%, black);

  --color-text:                          #3b2d1f;
  --color-text-muted:                    #5a4430;
  --color-text-inverse:                  #2c1f13;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #e2b780;
  --color-primary-active:                color-mix(in srgb, #d6a15e 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #d6a15e 18%, #f3e5d1);
  --color-on-primary:                    #2c1f13;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #d6a15e 24%, #e9d8be);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #f3e5d1 92%, #f1e4cf);
  --elevation-2-bg:                      color-mix(in srgb, #f3e5d1 84%, #f1e4cf);
  --elevation-3-bg:                      color-mix(in srgb, #f3e5d1 76%, #f1e4cf);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f8eedf;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #3b2d1f;
  --button-border:                       #b7946a;
  --button-border-active:                #d6a15e;
  --button-primary-bg:                   #d6a15e;
  --button-primary-text:                 #2c1f13;
  --button-secondary-bg:                 #ebd8bd;
  --button-secondary-text:               #3b2d1f;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #f3e5d1;
  --panel-text:                          #3b2d1f;
  --panel-border:                        #b7946a;
  --panel-head-bg:                       #ead8bf;
  --panel-head-text:                     #7a5230;

  --dialog-bg:                           #f8eedf;
  --dialog-text:                         #3b2d1f;
  --dialog-border:                       #b7946a;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #e9d8be;
  --calendar-border:                     #b7946a;
  --calendar-day-bg:                     #f9f0e2;
  --calendar-day-hover:                  #d6a15e;
  --calendar-day-today:                  color-mix(in srgb, #d6a15e 12%, #f9f0e2);
  --calendar-day-selected:               color-mix(in srgb, #d6a15e 22%, #f9f0e2);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #3b2d1f);
  --calendar-range-bg:                   color-mix(in srgb, #d6a15e 18%, #e9d8be);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


