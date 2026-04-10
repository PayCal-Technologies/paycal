<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* MINT DARK */
:root {





  --dialog-shadow:                       rgba(0, 0, 0, 0.68);





  --btn-back-linear-gradient:            linear-gradient(145deg, #25392f 0%, #1d2c24 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #2d463a 0%, #22352b 100%);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #3f5f50;

  --work-back:                           linear-gradient(150deg, #1d2c24 0%, #16231c 100%);
  --work-fore:                           #e8f4ee;

  --cal-day-fore:                        #e8f4ee;
  --cal-day-hover-fore:                  #0b1b12;
  --cal-day-hover-glow:                  0 0 0 3px rgba(102, 187, 106, 0.32);

  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(0, 0, 0, 0.35);

  --work-details-border:                 #466a58;
  --work-entry-back:                     #25392f;
  --work-entry-fore:                     #e8f4ee;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #131e18;
  --color-bg-soft:                       color-mix(in srgb, #131e18 86%, #18261f);
  --color-bg-elevated:                   color-mix(in srgb, #1d2c24 90%, #18261f);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #1d2c24;
  --color-surface-muted:                 color-mix(in srgb, #1d2c24 90%, #18261f);
  --color-surface-strong:                #18261f;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #466a58 72%, #1d2c24);
  --color-border-strong:                 color-mix(in srgb, #466a58 84%, black);

  --color-text:                          #e8f4ee;
  --color-text-muted:                    #c1ddd0;
  --color-text-inverse:                  #0b1b12;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       #66bb6a;
  --color-primary-hover:                 #86d78a;
  --color-primary-active:                color-mix(in srgb, #66bb6a 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #66bb6a 18%, #1d2c24);
  --color-on-primary:                    #0b1b12;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #66bb6a;
  --color-selection:                     color-mix(in srgb, #66bb6a 24%, #131e18);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #1d2c24 92%, #18261f);
  --elevation-2-bg:                      color-mix(in srgb, #1d2c24 84%, #18261f);
  --elevation-3-bg:                      color-mix(in srgb, #1d2c24 76%, #18261f);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #25392f;
  --button-bg-hover:                     var(--color-primary);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #ffffff;
  --button-border:                       #5d8f76;
  --button-border-active:                #86d78a;
  --button-primary-bg:                   #66bb6a;
  --button-primary-text:                 #0b1b12;
  --button-secondary-bg:                 #324d40;
  --button-secondary-text:               #e8f4ee;
  --button-danger-text:                  #ff5a5a;

  --panel-bg:                            #1d2c24;
  --panel-text:                          #e8f4ee;
  --panel-border:                        #466a58;
  --panel-head-bg:                       #2a4135;
  --panel-head-text:                     #86d78a;

  --dialog-bg:                           #25392f;
  --dialog-text:                         #e8f4ee;
  --dialog-border:                       #466a58;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #131e18;
  --calendar-border:                     #466a58;
  --calendar-day-bg:                     #16231c;
  --calendar-day-hover:                  #66bb6a;
  --calendar-day-today:                  color-mix(in srgb, #66bb6a 12%, #16231c);
  --calendar-day-selected:               color-mix(in srgb, #66bb6a 22%, #16231c);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #e8f4ee);
  --calendar-range-bg:                   color-mix(in srgb, #66bb6a 18%, #131e18);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #000000;

  --button-text-active: #000000;
}


