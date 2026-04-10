<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Mac OS 9 (Aqua-era transition): cool graphite chrome + blue highlight accents */





  --text-color:                          #1B2430;
  --text-muted:                          #34445A;

  --dialog-shadow:                       rgba(12, 22, 37, 0.26);



  --panel-footer-back:                   #D5DEEB;




  --border-size:                         1px;
  --border-radius:                       5px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #8798B1;

  --work-back:                           #DDE5F1;
  --work-fore:                           #182231;
  --work-details-border:                 #8798B1;
  --work-entry-back:                     #EEF3FA;
  --work-entry-fore:                     #182231;

  --cal-day-fore:                        #182231;
  --cal-day-hover-fore:                  #12253F;
  --cal-day-radius:                      5px;
  --cal-day-shadow:                      0 1px 2px rgba(24, 34, 49, 0.18);
  --cal-day-hover-glow:                  0 0 0 2px rgba(63, 109, 170, 0.22);

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #B9C3D1;
  --color-bg-soft:                       color-mix(in srgb, #B9C3D1 86%, #D8DEE8);
  --color-bg-elevated:                   color-mix(in srgb, #E4EAF4 90%, #D8DEE8);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #E4EAF4;
  --color-surface-muted:                 color-mix(in srgb, #E4EAF4 90%, #D8DEE8);
  --color-surface-strong:                #D8DEE8;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #8594AA 72%, #E4EAF4);
  --color-border-strong:                 color-mix(in srgb, #8594AA 84%, black);

  --color-text:                          #182231;
  --color-text-muted:                    #1B2430;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 #CAD8EE;
  --color-primary-active:                color-mix(in srgb, #3F6DAA 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #3F6DAA 18%, #E4EAF4);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #B72626;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     color-mix(in srgb, #3F6DAA 24%, #B9C3D1);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #E4EAF4 92%, #D8DEE8);
  --elevation-2-bg:                      color-mix(in srgb, #E4EAF4 84%, #D8DEE8);
  --elevation-3-bg:                      color-mix(in srgb, #E4EAF4 76%, #D8DEE8);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #E6EBF4;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #182231;
  --button-border:                       #FFFFFF #7A8EA9 #7A8EA9 #FFFFFF;
  --button-border-active:                #6C7F9C #F7FAFF #F7FAFF #6C7F9C;
  --button-primary-bg:                   #3F6DAA;
  --button-primary-text:                 #FFFFFF;
  --button-secondary-bg:                 #DCE4F0;
  --button-secondary-text:               #182231;
  --button-danger-text:                  #B72626;

  --panel-bg:                            #E4EAF4;
  --panel-text:                          #182231;
  --panel-border:                        #8594AA;
  --panel-head-bg:                       linear-gradient(180deg, #F3F7FC 0%, #CAD5E7 100%);
  --panel-head-text:                     #1B2430;

  --dialog-bg:                           #E6EBF4;
  --dialog-text:                         #182231;
  --dialog-border:                       #8594AA;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #B9C3D1;
  --calendar-border:                     #8594AA;
  --calendar-day-bg:                     #EDF2F9;
  --calendar-day-hover:                  #D8E4F5;
  --calendar-day-today:                  color-mix(in srgb, #3F6DAA 12%, #EDF2F9);
  --calendar-day-selected:               color-mix(in srgb, #3F6DAA 22%, #EDF2F9);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #182231);
  --calendar-range-bg:                   color-mix(in srgb, #3F6DAA 18%, #B9C3D1);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);}


