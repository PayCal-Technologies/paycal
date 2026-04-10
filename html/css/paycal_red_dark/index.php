<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL RED DARK */
:root {
  --foundation-red-000:                  hsl(8 34% 7%);
  --foundation-red-100:                  hsl(8 30% 10%);
  --foundation-red-200:                  hsl(8 26% 14%);
  --foundation-red-300:                  hsl(8 22% 20%);
  --foundation-text-100:                 hsl(10 100% 96%);
  --foundation-text-200:                 hsl(10 34% 81%);
  --foundation-accent-100:               hsl(8 88% 70%);
  --foundation-accent-200:               hsl(8 92% 80%);

  --color-bg:                            var(--foundation-red-000);
  --color-bg-soft:                       hsl(8 32% 8%);
  --color-bg-elevated:                   var(--foundation-red-100);
  --color-bg-overlay:                    hsl(8 36% 6% / 0.74);
  --color-surface:                       var(--foundation-red-100);
  --color-surface-muted:                 hsl(8 28% 12%);
  --color-surface-strong:                var(--foundation-red-200);
  --input-bg:                            hsl(8 30% 9%);

  --color-border:                        #ffffff;
  --color-border-soft:                   hsl(8 18% 42%);
  --color-border-strong:                 hsl(8 24% 66%);

  --color-text:                          var(--foundation-text-100);
  --color-text-muted:                    var(--foundation-text-200);
  --color-text-inverse:                  hsl(8 34% 8%);
  --color-text-disabled:                 hsl(8 16% 56%);

  --color-primary:                       var(--foundation-accent-100);
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                hsl(8 78% 64%);
  --color-primary-soft:                  hsl(8 88% 70% / 0.20);
  --color-on-primary:                    hsl(8 34% 8%);

  --color-success:                       hsl(145 68% 74%);
  --color-warning:                       hsl(36 94% 70%);
  --color-danger:                        hsl(2 88% 76%);
  --color-info:                          hsl(212 80% 72%);

  --color-hover:                         hsl(8 88% 70% / 0.10);
  --color-active:                        hsl(8 88% 70% / 0.16);
  --color-focus-ring:                    var(--foundation-accent-200);
  --color-selection:                     hsl(8 88% 70% / 0.25);
  --color-highlight:                     hsl(48 90% 70% / 0.18);
  --color-disabled-bg:                   hsl(0 0% 100% / 0.06);

  --elevation-1-bg:                      hsl(8 30% 11%);
  --elevation-2-bg:                      var(--foundation-red-100);
  --elevation-3-bg:                      var(--foundation-red-200);
  --overlay-backdrop:                    hsl(8 36% 6% / 0.62);
  --shadow-md:                           0 8px 22px hsl(8 40% 3% / 0.42);

  --button-bg:                           var(--foundation-red-200);
  --button-bg-hover:                     hsl(8 20% 25%);
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
  --panel-head-bg:                       hsl(8 32% 11%);
  --panel-head-text:                     var(--color-primary);

  --dialog-bg:                           var(--foundation-red-200);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     hsl(8 34% 7%);
  --calendar-day-hover:                  hsl(8 34% 22%);
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   hsl(8 88% 70% / 0.16);
  --heading-accent-color:                var(--color-primary);
  --theme-signature-color:               var(--heading-accent-color);

  --btn-back-linear-gradient:            linear-gradient(135deg, hsl(8 34% 7%) 0%, hsl(8 30% 10%) 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, hsl(8 20% 25%) 0%, hsl(8 32% 8%) 100%);

  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                       calc(var(--border-radius) * 2);
  --radius-control:                      var(--border-radius);
  --radius-panel:                        var(--border-radius);
  --radius-dialog:                       var(--border-radius);
  --radius-cell:                         var(--border-radius);
  --radius-article:                      var(--border-radius);

  --work-back:                           linear-gradient(135deg, hsl(8 30% 10%) 0%, hsl(8 28% 12%) 100%);
  --work-entry-back:                     hsl(8 30% 11%);
  --work-entry-fore:                     var(--color-text);
  --work-fore:                           var(--color-text-muted);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text-muted);
  --cal-day-fore:                        var(--color-text);
  --cal-day-hover-glow:                  0 0 1px 5px hsl(8 20% 30% / 0.78);
  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem hsl(8 40% 3% / 0.16);
  --retroGreen:                          #007700;
}
