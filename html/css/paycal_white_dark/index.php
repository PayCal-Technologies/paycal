<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL WHITE DARK */
:root {
  --foundation-paper-000:                hsl(0 0% 72%);
  --foundation-paper-100:                hsl(0 0% 69%);
  --foundation-paper-200:                hsl(0 0% 64%);
  --foundation-paper-300:                hsl(0 0% 56%);
  --foundation-ink-000:                  hsl(0 0% 8%);
  --foundation-ink-100:                  hsl(0 0% 12%);
  --foundation-ink-200:                  hsl(0 0% 20%);
  --foundation-border-100:               hsl(0 0% 36%);
  --foundation-accent-100:               hsl(0 0% 10%);
  --foundation-accent-200:               hsl(0 0% 4%);

  --color-bg:                            var(--foundation-paper-000);
  --color-bg-soft:                       hsl(0 0% 70%);
  --color-bg-elevated:                   var(--foundation-paper-200);
  --color-bg-overlay:                    hsl(0 0% 4% / 0.22);
  --color-surface:                       var(--foundation-paper-100);
  --color-surface-muted:                 hsl(0 0% 67%);
  --color-surface-strong:                var(--foundation-paper-200);
  --input-bg:                            hsl(0 0% 74%);

  --color-border:                        #000000;
  --color-border-soft:                   hsl(0 0% 46%);
  --color-border-strong:                 hsl(0 0% 30%);

  --color-text:                          var(--foundation-ink-000);
  --color-text-muted:                    var(--foundation-ink-200);
  --color-text-inverse:                  hsl(0 0% 95%);
  --color-text-disabled:                 #000000;

  --color-primary:                       var(--foundation-accent-100);
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                hsl(0 0% 2%);
  --color-primary-soft:                  hsl(0 0% 0% / 0.12);
  --color-on-primary:                    hsl(0 0% 95%);

  --color-success:                       hsl(145 50% 27%);
  --color-warning:                       hsl(34 90% 34%);
  --color-danger:                        hsl(2 68% 44%);
  --color-info:                          hsl(210 30% 34%);

  --color-hover:                         hsl(0 0% 0% / 0.08);
  --color-active:                        hsl(0 0% 0% / 0.14);
  --color-focus-ring:                    hsl(0 0% 4%);
  --color-selection:                     hsl(0 0% 0% / 0.18);
  --color-highlight:                     hsl(48 90% 62% / 0.30);
  --color-disabled-bg:                   hsl(0 0% 0% / 0.06);

  --elevation-1-bg:                      hsl(0 0% 70%);
  --elevation-2-bg:                      hsl(0 0% 72%);
  --elevation-3-bg:                      hsl(0 0% 67%);
  --overlay-backdrop:                    hsl(0 0% 0% / 0.20);
  --shadow-md:                           0 8px 18px hsl(0 0% 0% / 0.14);

  --button-bg:                           hsl(0 0% 62%);
  --button-bg-hover:                     hsl(0 0% 59%);
  --button-bg-active:                    hsl(0 0% 56%);
  --button-text:                         var(--color-text);
  --button-text-hover:                   var(--color-text);
  --button-text-active:                  var(--color-text);
  --button-border:                       var(--color-border);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   var(--color-primary);
  --button-primary-text:                 #ffffff;
  --button-primary-text-hover:           #ffffff;
  --button-primary-text-active:          #ffffff;
  --button-secondary-bg:                 hsl(0 0% 62%);
  --button-secondary-text:               var(--color-text);
  --btn-selected-fore:                   #000000;
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       var(--color-surface-strong);
  --panel-head-text:                     var(--color-primary);

  --dialog-bg:                           var(--foundation-paper-100);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     hsl(0 0% 74%);
  --calendar-day-hover:                  hsl(0 0% 59%);
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 14%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 22%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   hsl(0 0% 0% / 0.10);
  --heading-accent-color:                var(--color-primary);
  --theme-signature-color:               var(--heading-accent-color);

  --btn-back-linear-gradient:            linear-gradient(135deg, hsl(0 0% 72%) 0%, hsl(0 0% 67%) 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, hsl(0 0% 70%) 0%, hsl(0 0% 58%) 100%);

  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                       calc(var(--border-radius) * 2);
  --radius-control:                      var(--border-radius);
  --radius-panel:                        var(--border-radius);
  --radius-dialog:                       var(--border-radius);
  --radius-cell:                         var(--border-radius);
  --radius-article:                      var(--border-radius);

  --work-back:                           linear-gradient(135deg, hsl(0 0% 66%) 0%, hsl(0 0% 62%) 100%);
  --work-entry-back:                     hsl(0 0% 74%);
  --work-entry-fore:                     var(--color-text);
  --work-fore:                           var(--color-text);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text);
  --cal-day-fore:                        var(--color-text);
  --cal-day-hover-glow:                  0 0 1px 5px hsl(0 0% 8% / 0.10);
  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem hsl(0 0% 0% / 0.10);
  --retroGreen:                          #007700;
}
