<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL BLACK DARK */
:root {
  /* Foundation palette */
  --foundation-ink-000:                  #080808;
  --foundation-ink-100:                  #111111;
  --foundation-ink-200:                  #1f1f1f;
  --foundation-text-100:                 #f2f2f2;
  --foundation-text-200:                 #c7c7c7;
  --foundation-accent-100:               #d0d0d0;
  --foundation-accent-200:               #e2e2e2;
  --foundation-danger-100:               #C62828;
  --foundation-overlay-100:              rgba(0, 0, 0, 0.75);

  /* Semantic tokens */
  --color-bg:                            var(--foundation-ink-000);
  --color-bg-soft:                       #0d0d0d;
  --color-bg-elevated:                   var(--foundation-ink-100);
  --color-bg-overlay:                    rgba(8, 12, 16, 0.70);

  --color-surface:                       var(--foundation-ink-100);
  --color-surface-muted:                 #171717;
  --color-surface-strong:                #232323;
  --input-bg:                            var(--color-surface);

  --color-border:                        #ffffff;
  --color-border-soft:                   #4f4f4f;
  --color-border-strong:                 #8a8a8a;

  --color-text:                          var(--foundation-text-100);
  --color-text-muted:                    var(--foundation-text-200);
  --color-text-inverse:                  #0a0a0a;
  --color-text-disabled:                 #8a8a8a;

  --color-primary:                       var(--foundation-accent-100);
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                #b8b8b8;
  --color-primary-soft:                  rgba(255, 255, 255, 0.16);
  --color-on-primary:                    var(--foundation-ink-000);

  --color-success:                       #2e7d32;
  --color-warning:                       #ef6c00;
  --color-danger:                        var(--foundation-danger-100);
  --color-info:                          #bdbdbd;

  --color-hover:                         rgba(255, 255, 255, 0.14);
  --color-active:                        rgba(255, 255, 255, 0.20);
  --color-focus-ring:                    #f0f0f0;
  --color-selection:                     rgba(255, 255, 255, 0.28);
  --color-highlight:                     rgba(255, 231, 132, 0.20);
  --color-disabled-bg:                   rgba(255, 255, 255, 0.06);

  --elevation-1-bg:                      #161616;
  --elevation-2-bg:                      var(--foundation-ink-100);
  --elevation-3-bg:                      #202020;
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           0 1px 2px rgba(0, 0, 0, 0.20);
  --shadow-md:                           0 8px 18px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 36px rgba(0, 0, 0, 0.36);

  /* Component tokens */
  --button-bg:                           #1a1a1a;
  --button-bg-hover:                     #2a2a2a;
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 70%, var(--color-primary-soft));
  --button-text:                         #ffffff;
  --button-border:                       var(--color-border);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   var(--color-primary);
  --button-primary-text:                 var(--color-on-primary);
  --button-secondary-bg:                 var(--color-surface-strong);
  --button-secondary-text:               var(--color-text-muted);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       var(--color-surface);
  --panel-head-text:                     var(--color-primary);

  --dialog-bg:                           var(--color-surface-strong);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     #0d0d0d;
  --calendar-day-hover:                  #2d2d2d;
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 20%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 26%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   rgba(255, 255, 255, 0.14);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);





  --dialog-shadow:                       var(--foundation-overlay-100);





  --btn-back-linear-gradient:            linear-gradient(135deg, var(--color-bg) 99%, #1f1f1f 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, #2f2f2f 0%, #080808 100%);
  --btn-back-hover-box-shadow:           0.1rem 0 0.1rem 0.1rem rgba(255, 255, 255, 1);
  --btn-back-hover-box-shadow:           inset 1px 0 0 rgba(255, 255, 255, 0.2), inset -1px 0 0 rgba(255, 255, 255, 0.2), 0 0 4px 0 rgba(95, 99, 104, 0.6), 0 0 6px 2px rgba(95, 99, 104, 0.6);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  rgba(64, 64, 64, 1);  /* neutral black theme inset */

  --work-back:                           linear-gradient(135deg, #141414 20%, #1d1d1d 30%);
  --work-fore:                           var(--color-text-muted);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text-muted);

  --cal-day-fore:                        var(--color-text);


  --cal-day-hover-glow:                  0 0 1px 5px rgba(128, 128, 128, 1);

  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem rgba(0, 0, 0, 0.10);

  --retroGreen:                          #007700;
}


