<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL RED LIGHT */
:root {
  --foundation-paper-000:                hsl(8 100% 98%);
  --foundation-paper-100:                hsl(8 72% 94%);
  --foundation-paper-200:                hsl(8 58% 89%);
  --foundation-paper-300:                hsl(8 44% 79%);
  --foundation-ink-000:                  hsl(8 56% 12%);
  --foundation-ink-100:                  hsl(8 42% 20%);
  --foundation-ink-200:                  hsl(8 30% 30%);
  --foundation-border-100:               hsl(8 28% 53%);
  --foundation-accent-100:               hsl(8 66% 48%);
  --foundation-accent-200:               hsl(8 72% 40%);

  --color-bg:                            var(--foundation-paper-000);
  --color-bg-soft:                       hsl(8 78% 96%);
  --color-bg-elevated:                   var(--foundation-paper-200);
  --color-bg-overlay:                    hsl(8 56% 12% / 0.22);
  --color-surface:                       var(--foundation-paper-100);
  --color-surface-muted:                 hsl(8 56% 92%);
  --color-surface-strong:                var(--foundation-paper-200);
  --input-bg:                            hsl(8 100% 99%);

  --color-border:                        hsl(8 24% 44%);
  --color-border-soft:                   hsl(8 24% 64%);
  --color-border-strong:                 hsl(8 34% 36%);

  --color-text:                          var(--foundation-ink-000);
  --color-text-muted:                    var(--foundation-ink-100);
  --color-text-inverse:                  hsl(8 100% 98%);
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                hsl(8 76% 37%);
  --color-primary-soft:                  hsl(8 66% 49% / 0.14);
  --color-on-primary:                    hsl(8 100% 98%);

  --color-success:                       hsl(145 62% 29%);
  --color-warning:                       hsl(34 90% 34%);
  --color-danger:                        hsl(2 68% 46%);
  --color-info:                          hsl(210 72% 40%);

  --color-hover:                         hsl(8 66% 49% / 0.08);
  --color-active:                        hsl(8 66% 49% / 0.14);
  --color-focus-ring:                    #000000;
  --color-selection:                     hsl(8 66% 49% / 0.20);
  --color-highlight:                     hsl(8 66% 49% / 0.12);

  --elevation-1-bg:                      hsl(8 74% 96%);
  --elevation-2-bg:                      var(--foundation-paper-100);
  --elevation-3-bg:                      var(--foundation-paper-200);
  --overlay-backdrop:                    hsl(8 56% 12% / 0.38);
  --shadow-md:                           0 6px 16px hsl(8 56% 12% / 0.14);

  --button-bg:                           var(--foundation-paper-200);
  --button-bg-hover:                     hsl(8 44% 83%);
  --button-bg-active:                    hsl(8 40% 75%);
  --button-text:                         var(--color-text);
  --button-border:                       var(--color-border);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   var(--color-primary);
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 var(--color-surface-strong);
  --button-secondary-text:               var(--color-text-muted);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       hsl(8 60% 92%);
  --panel-head-text:                     var(--foundation-accent-200);

  --dialog-bg:                           var(--foundation-paper-000);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     hsl(8 100% 99%);
  --calendar-day-hover:                  hsl(8 56% 88%);
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 15%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 20%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   hsl(8 66% 49% / 0.12);
  --heading-accent-color:                var(--color-primary);
  --theme-signature-color:               var(--heading-accent-color);

  --btn-back-linear-gradient:            linear-gradient(135deg, hsl(8 100% 98%) 0%, hsl(8 58% 90%) 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, hsl(8 44% 79%) 0%, hsl(8 84% 97%) 100%);

  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                       calc(var(--border-radius) * 2);
  --radius-control:                      var(--border-radius);
  --radius-panel:                        var(--border-radius);
  --radius-dialog:                       var(--border-radius);
  --radius-cell:                         var(--border-radius);
  --radius-article:                      var(--border-radius);

  --work-back:                           linear-gradient(135deg, hsl(8 72% 94%) 0%, hsl(8 56% 92%) 100%);
  --work-entry-back:                     hsl(8 100% 99%);
  --work-entry-fore:                     var(--color-text);
  --work-fore:                           var(--color-text-muted);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text-muted);
  --cal-day-fore:                        var(--color-text);
  --cal-day-hover-glow:                  0 0 1px 5px hsl(8 28% 53% / 0.58);
  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem hsl(8 56% 12% / 0.07);
  --retroGreen:                          #007700;
}
