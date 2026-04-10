<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL GREEN DARK */
:root {
  --foundation-green-000:                hsl(145 30% 6%);
  --foundation-green-100:                hsl(145 28% 9%);
  --foundation-green-200:                hsl(145 24% 13%);
  --foundation-green-300:                hsl(145 20% 19%);
  --foundation-text-100:                 hsl(145 100% 96%);
  --foundation-text-200:                 hsl(145 30% 82%);
  --foundation-accent-100:               hsl(145 72% 64%);
  --foundation-accent-200:               hsl(145 80% 76%);

  --color-bg:                            var(--foundation-green-000);
  --color-bg-soft:                       hsl(145 28% 7%);
  --color-bg-elevated:                   var(--foundation-green-100);
  --color-bg-overlay:                    hsl(145 34% 5% / 0.74);
  --color-surface:                       var(--foundation-green-100);
  --color-surface-muted:                 hsl(145 24% 11%);
  --color-surface-strong:                var(--foundation-green-200);
  --input-bg:                            hsl(145 28% 8%);

  --color-border:                        hsl(145 14% 50%);
  --color-border-soft:                   hsl(145 12% 40%);
  --color-border-strong:                 hsl(145 18% 64%);

  --color-text:                          var(--foundation-text-100);
  --color-text-muted:                    var(--foundation-text-200);
  --color-text-inverse:                  hsl(145 34% 7%);
  --color-text-disabled:                 hsl(145 12% 56%);

  --color-primary:                       var(--foundation-accent-100);
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                hsl(145 62% 57%);
  --color-primary-soft:                  hsl(145 72% 64% / 0.20);
  --color-on-primary:                    hsl(145 34% 7%);

  --color-success:                       hsl(145 82% 72%);
  --color-warning:                       hsl(38 94% 70%);
  --color-danger:                        hsl(2 88% 76%);
  --color-info:                          hsl(210 80% 72%);

  --color-hover:                         hsl(145 72% 64% / 0.10);
  --color-active:                        hsl(145 72% 64% / 0.16);
  --color-focus-ring:                    var(--foundation-accent-200);
  --color-selection:                     hsl(145 72% 64% / 0.25);
  --color-highlight:                     hsl(48 90% 70% / 0.18);

  --elevation-1-bg:                      hsl(145 28% 10%);
  --elevation-2-bg:                      var(--foundation-green-100);
  --elevation-3-bg:                      var(--foundation-green-200);
  --overlay-backdrop:                    hsl(145 34% 5% / 0.62);
  --shadow-md:                           0 8px 22px hsl(145 40% 2% / 0.42);

  --button-bg:                           var(--foundation-green-200);
  --button-bg-hover:                     hsl(145 18% 24%);
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 74%, var(--color-primary-soft));
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
  --panel-head-bg:                       hsl(145 30% 10%);
  --panel-head-text:                     var(--color-primary);

  --dialog-bg:                           var(--foundation-green-200);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     hsl(145 30% 6%);
  --calendar-day-hover:                  hsl(145 30% 20%);
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   hsl(145 72% 64% / 0.16);
  --heading-accent-color:                var(--color-primary);
  --theme-signature-color:               var(--heading-accent-color);

  --btn-back-linear-gradient:            linear-gradient(135deg, hsl(145 30% 6%) 0%, hsl(145 28% 9%) 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, hsl(145 18% 24%) 0%, hsl(145 28% 7%) 100%);

  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                       calc(var(--border-radius) * 2);
  --radius-control:                      var(--border-radius);
  --radius-panel:                        var(--border-radius);
  --radius-dialog:                       var(--border-radius);
  --radius-cell:                         var(--border-radius);
  --radius-article:                      var(--border-radius);

  --work-back:                           linear-gradient(135deg, hsl(145 28% 9%) 0%, hsl(145 24% 11%) 100%);
  --work-entry-back:                     hsl(145 28% 10%);
  --work-entry-fore:                     var(--color-text);
  --work-fore:                           var(--color-text-muted);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text-muted);
  --cal-day-fore:                        var(--color-text);
  --cal-day-hover-glow:                  0 0 1px 5px hsl(145 17% 30% / 0.78);
  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem hsl(145 40% 2% / 0.16);
  --retroGreen:                          #007700;
}
