<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* ZETA DARK */
:root {





  --dialog-shadow:                       rgba(0, 0, 0, 0.72);





  --btn-back-linear-gradient:            linear-gradient(145deg, #2d3b61 0%, #1f2943 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #374a78 0%, #253152 100%);





  --border-size:                         1px;
  --border-radius:                       5px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #495d8c;

  --work-back:                           #1f2943;
  --work-fore:                           #e9eefc;

  --cal-day-fore:                        #e9eefc;
  --cal-day-hover-fore:                  #eef2ff;
  --cal-day-hover-glow:                  0 0 0 2px rgba(64, 93, 181, 0.36);

  --cal-day-radius:                      5px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.35);

  --work-details-border:                 #495d8c;
  --work-entry-back:                     #273354;
  --work-entry-fore:                     #e9eefc;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #151b2f;
  --color-bg-soft:                       color-mix(in srgb, #151b2f 86%, #1a2138);
  --color-bg-elevated:                   color-mix(in srgb, #1f2943 90%, #1a2138);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #1f2943;
  --color-surface-muted:                 color-mix(in srgb, #1f2943 90%, #1a2138);
  --color-surface-strong:                #1a2138;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #495d8c 72%, #1f2943);
  --color-border-strong:                 color-mix(in srgb, #495d8c 84%, black);

  --color-text:                          #e9eefc;
  --color-text-muted:                    #bcc7e3;
  --color-text-inverse:                  #eef2ff;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #6079c3;
  --color-primary-active:                color-mix(in srgb, #405db5 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #405db5 18%, #1f2943);
  --color-on-primary:                    #eef2ff;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #405db5 24%, #151b2f);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #1f2943 92%, #1a2138);
  --elevation-2-bg:                      color-mix(in srgb, #1f2943 84%, #1a2138);
  --elevation-3-bg:                      color-mix(in srgb, #1f2943 76%, #1a2138);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #273354;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 82%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #5f78b0;
  --button-border-active:                #adc0ff;
  --button-primary-bg:                   #405db5;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #324269;
  --button-secondary-text:               #e9eefc;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #1f2943;
  --panel-text:                          #e9eefc;
  --panel-border:                        #495d8c;
  --panel-head-bg:                       linear-gradient(180deg, #33436d 0%, #202945 100%);
  --panel-head-text:                     #adc0ff;

  --dialog-bg:                           #273354;
  --dialog-text:                         #e9eefc;
  --dialog-border:                       #495d8c;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #151b2f;
  --calendar-border:                     #495d8c;
  --calendar-day-bg:                     #19213a;
  --calendar-day-hover:                  #405db5;
  --calendar-day-today:                  color-mix(in srgb, #405db5 12%, #19213a);
  --calendar-day-selected:               color-mix(in srgb, #405db5 22%, #19213a);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #e9eefc);
  --calendar-range-bg:                   color-mix(in srgb, #405db5 18%, #151b2f);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-active: #000000;
}


