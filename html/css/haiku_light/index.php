<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* HAIKU LIGHT */
:root {





  --dialog-shadow:                       rgba(10, 38, 59, 0.20);





  --btn-back-linear-gradient:            linear-gradient(145deg, #f8fbff 0%, #ebf4fc 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #ebf4fc 0%, #dcecf9 100%);





  --border-size:                         1px;
  --border-radius:                       8px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #97bad3;

  --work-back:                           linear-gradient(150deg, #eef5fc 0%, #e3eff9 100%);
  --work-fore:                           #15324a;

  --cal-day-fore:                        #15324a;
  --cal-day-hover-fore:                  #05263a;
  --cal-day-hover-glow:                  0 0 0 3px rgba(0, 160, 233, 0.25);

  --cal-day-radius:                      8px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(10, 38, 59, 0.16);

  --work-details-border:                 #97bad3;
  --work-entry-back:                     #f8fbff;
  --work-entry-fore:                     #15324a;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #e6f0f8;
  --color-bg-soft:                       color-mix(in srgb, #e6f0f8 86%, #f0f6fb);
  --color-bg-elevated:                   color-mix(in srgb, #eef5fc 90%, #f0f6fb);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #eef5fc;
  --color-surface-muted:                 color-mix(in srgb, #eef5fc 90%, #f0f6fb);
  --color-surface-strong:                #f0f6fb;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #97bad3 72%, #eef5fc);
  --color-border-strong:                 color-mix(in srgb, #97bad3 84%, black);

  --color-text:                          #15324a;
  --color-text-muted:                    #2d4f6d;
  --color-text-inverse:                  #05263a;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #6bc6ee;
  --color-primary-active:                color-mix(in srgb, #00a0e9 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #00a0e9 18%, #eef5fc);
  --color-on-primary:                    #05263a;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #00a0e9 24%, #e6f0f8);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #eef5fc 92%, #f0f6fb);
  --elevation-2-bg:                      color-mix(in srgb, #eef5fc 84%, #f0f6fb);
  --elevation-3-bg:                      color-mix(in srgb, #eef5fc 76%, #f0f6fb);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f8fbff;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #15324a;
  --button-border:                       #97bad3;
  --button-border-active:                #00a0e9;
  --button-primary-bg:                   #00a0e9;
  --button-primary-text:                 #05263a;
  --button-secondary-bg:                 #ddebf7;
  --button-secondary-text:               #15324a;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #eef5fc;
  --panel-text:                          #15324a;
  --panel-border:                        #97bad3;
  --panel-head-bg:                       #e1eef9;
  --panel-head-text:                     #006aa8;

  --dialog-bg:                           #f8fbff;
  --dialog-text:                         #15324a;
  --dialog-border:                       #97bad3;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #e6f0f8;
  --calendar-border:                     #97bad3;
  --calendar-day-bg:                     #f9fcff;
  --calendar-day-hover:                  #00a0e9;
  --calendar-day-today:                  color-mix(in srgb, #00a0e9 12%, #f9fcff);
  --calendar-day-selected:               color-mix(in srgb, #00a0e9 22%, #f9fcff);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #15324a);
  --calendar-range-bg:                   color-mix(in srgb, #00a0e9 18%, #e6f0f8);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;
}


