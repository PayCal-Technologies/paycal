<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL BLACK LIGHT */
:root {
  /* Foundation palette */
  --foundation-paper-000:                #f5f5f5;
  --foundation-paper-100:                #ececec;
  --foundation-paper-200:                #dfdfdf;
  --foundation-paper-300:                #c8c8c8;
  --foundation-ink-100:                  #1a1a1a;
  --foundation-ink-200:                  #333333;
  --foundation-border-100:               #7c7c7c;
  --foundation-accent-100:               #1f1f1f;
  --foundation-accent-200:               #000000;
  --foundation-danger-100:               #C62828;

  /* Semantic tokens */
  --color-bg:                            var(--foundation-paper-000);
  --color-bg-soft:                       #efefef;
  --color-bg-elevated:                   var(--foundation-paper-200);
  --color-bg-overlay:                    rgba(15, 24, 30, 0.28);

  --color-surface:                       var(--foundation-paper-100);
  --color-surface-muted:                 #e6e6e6;
  --color-surface-strong:                var(--foundation-paper-200);
  --input-bg:                            var(--color-surface);

  --color-border:                        #000000;
  --color-border-soft:                   #c9c9c9;
  --color-border-strong:                 #666666;

  --color-text:                          var(--foundation-ink-100);
  --color-text-muted:                    var(--foundation-ink-200);
  --color-text-inverse:                  #f9f9f9;
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--foundation-accent-100);
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                #0d0d0d;
  --color-primary-soft:                  rgba(0, 0, 0, 0.12);
  --color-on-primary:                    #f5f5f5;

  --color-success:                       #2e7d32;
  --color-warning:                       #ef6c00;
  --color-danger:                        var(--foundation-danger-100);
  --color-info:                          #4a4a4a;

  --color-hover:                         rgba(0, 0, 0, 0.08);
  --color-active:                        rgba(0, 0, 0, 0.16);
  --color-focus-ring:                    #111111;
  --color-selection:                     rgba(0, 0, 0, 0.20);
  --color-highlight:                     rgba(255, 223, 130, 0.32);
  --color-disabled-bg:                   rgba(38, 50, 56, 0.06);

  --elevation-1-bg:                      var(--foundation-paper-100);
  --elevation-2-bg:                      var(--foundation-paper-000);
  --elevation-3-bg:                      #e6e6e6;
  --overlay-backdrop:                    rgba(0, 0, 0, 0.22);

  --shadow-sm:                           0 1px 2px rgba(0, 0, 0, 0.10);
  --shadow-md:                           0 8px 18px rgba(0, 0, 0, 0.14);
  --shadow-lg:                           0 16px 36px rgba(0, 0, 0, 0.18);

  /* Component tokens */
  --button-bg:                           var(--foundation-paper-200);
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 82%, black);
  --button-text:                         var(--color-text);
  --button-border:                       var(--color-border);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   var(--color-primary);
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #DCE5EB;
  --button-secondary-text:               var(--color-text);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       var(--color-surface-strong);
  --panel-head-text:                     #111111;

  --dialog-bg:                           var(--foundation-paper-100);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     var(--foundation-paper-000);
  --calendar-day-hover:                  #d8d8d8;
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 14%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 22%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   rgba(0, 0, 0, 0.10);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);





  --dialog-shadow:                       rgba(0, 0, 0, 0.15);





  --btn-back-linear-gradient:            linear-gradient(135deg, var(--color-bg) 95%, #dcdcdc 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, #f3f3f3 0%, #d5d5d5 100%);
  --btn-back-hover-box-shadow:           0.1rem 0 0.1rem 0.1rem rgba(38, 50, 56, 1);
  --btn-back-hover-box-shadow:           inset 1px 0 0 rgba(255, 255, 255, 0.2), inset -1px 0 0 rgba(255, 255, 255, 0.2), 0 0 4px 0 rgba(95, 99, 104, 0.6), 0 0 6px 2px rgba(95, 99, 104, 0.6);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  rgba(140, 140, 140, 0.75);

  --work-back:                           #d2d2d2;
  --work-entry-back:                     var(--foundation-paper-000);
  --work-entry-fore:                     var(--color-text);
  --work-fore:                           var(--color-text);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text);

  --cal-day-fore:                        var(--color-text);


  --cal-day-hover-glow:                  0 0 1px 5px rgba(38, 50, 56, 0.10);

  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem rgba(0, 0, 0, 0.10);

  --retroGreen:                          #007700;
}





