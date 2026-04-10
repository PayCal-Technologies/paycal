<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL BLUE DARK */
:root {
  /* Foundation palette — inspired by the auth page navy-and-sky palette */
  --foundation-blue-000:                 #090d16;
  --foundation-blue-100:                 #121b2d;
  --foundation-blue-200:                 #18253d;
  --foundation-blue-300:                 #223253;
  --foundation-blue-400:                 #2f446a;
  --foundation-blue-500:                 #466294;
  --foundation-text-100:                 #f4f8ff;
  --foundation-text-200:                 #c0cee7;
  --foundation-text-300:                 #93a8cc;
  --foundation-accent-100:               #6fb7ff;
  --foundation-accent-200:               #9bd0ff;
  --foundation-accent-ink:               #041429;
  --foundation-danger-100:               #ff8d8d;
  --foundation-overlay-100:              rgba(0, 0, 0, 0.78);

  /* Semantic tokens */
  --color-bg:                            var(--foundation-blue-000);
  --color-bg-soft:                       #0b111e;
  --color-bg-elevated:                   var(--foundation-blue-100);
  --color-bg-overlay:                    rgba(4, 9, 20, 0.75);

  --color-surface:                       var(--foundation-blue-100);
  --color-surface-muted:                 #141f33;
  --color-surface-strong:                var(--foundation-blue-200);
  --input-bg:                            #0d1625;

  --color-border:                        #ffffff;
  --color-border-soft:                   #4f6e95;
  --color-border-strong:                 #89a4c6;

  --color-text:                          var(--foundation-text-100);
  --color-text-muted:                    var(--foundation-text-200);
  --color-text-inverse:                  var(--foundation-accent-ink);
  --color-text-disabled:                 #ffffff;

  --color-primary:                       var(--foundation-accent-100);
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                #4fa0f0;
  --color-primary-soft:                  rgba(111, 183, 255, 0.20);
  --color-on-primary:                    var(--foundation-accent-ink);

  --color-success:                       #8ff5b1;
  --color-warning:                       #ffc56a;
  --color-danger:                        var(--foundation-danger-100);
  --color-info:                          #6fb7ff;

  --color-hover:                         rgba(111, 183, 255, 0.10);
  --color-active:                        rgba(111, 183, 255, 0.18);
  --color-focus-ring:                    #9bd0ff;
  --color-selection:                     rgba(111, 183, 255, 0.28);
  --color-highlight:                     rgba(155, 208, 255, 0.18);
  --color-disabled-bg:                   rgba(255, 255, 255, 0.05);

  --elevation-1-bg:                      #0f1828;
  --elevation-2-bg:                      var(--foundation-blue-100);
  --elevation-3-bg:                      var(--foundation-blue-200);
  --overlay-backdrop:                    rgba(4, 9, 20, 0.62);

  --shadow-sm:                           0 1px 3px rgba(0, 0, 0, 0.30);
  --shadow-md:                           0 8px 22px rgba(0, 0, 0, 0.42);
  --shadow-lg:                           0 18px 44px rgba(2, 6, 20, 0.55);

  /* Component tokens */
  --button-bg:                           var(--foundation-blue-200);
  --button-bg-hover:                     var(--color-text);
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

  --dialog-bg:                           var(--foundation-blue-200);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     #080d1a;
  --calendar-day-hover:                  #1a3060;
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   rgba(111, 183, 255, 0.16);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);

  --dialog-shadow:                       var(--foundation-overlay-100);

  --btn-back-linear-gradient:            linear-gradient(135deg, var(--color-bg) 99%, #1a2d4f 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, #2f446a 0%, #090d16 100%);
  --btn-back-hover-box-shadow:           inset 1px 0 0 rgba(111, 183, 255, 0.2), inset -1px 0 0 rgba(111, 183, 255, 0.2), 0 0 4px 0 rgba(47, 68, 106, 0.6), 0 0 6px 2px rgba(47, 68, 106, 0.6);

  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  var(--foundation-blue-300);

  --work-back:                           linear-gradient(135deg, #121b2d 20%, #141e30 30%);
  --work-fore:                           var(--color-text-muted);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text-muted);

  --cal-day-fore:                        var(--color-text);

  --cal-day-hover-glow:                  0 0 1px 5px rgba(47, 68, 106, 0.8);

  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem rgba(0, 0, 0, 0.15);

  --retroGreen:                          #007700;

  --button-text-hover: #000000;
}
