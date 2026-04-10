<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* PAYCAL BLUE LIGHT */
:root {
  /* Foundation palette — cool paper whites with PayCal sky-blue accent */
  --foundation-paper-000:                #f4f8ff;
  --foundation-paper-100:                #eaf1fc;
  --foundation-paper-200:                #dce8f8;
  --foundation-paper-300:                #b8cce8;
  --foundation-ink-000:                  #0a1628;
  --foundation-ink-100:                  #1a2c4f;
  --foundation-ink-200:                  #2d3f5c;
  --foundation-border-100:               #9bb8d9;
  --foundation-accent-100:               #2a7dce;
  --foundation-accent-200:               #1e6ab8;
  --foundation-danger-100:               #c62828;
  --foundation-overlay-100:              rgba(255, 255, 255, 0.90);

  /* Semantic tokens */
  --color-bg:                            var(--foundation-paper-000);
  --color-bg-soft:                       #edf4ff;
  --color-bg-elevated:                   var(--foundation-paper-200);
  --color-bg-overlay:                    rgba(10, 22, 40, 0.22);

  --color-surface:                       var(--foundation-paper-100);
  --color-surface-muted:                 #e4edf9;
  --color-surface-strong:                var(--foundation-paper-200);
  --input-bg:                            var(--foundation-paper-000);

  --color-border:                        #000000;
  --color-border-soft:                   #8aa8c8;
  --color-border-strong:                 #587ea7;

  --color-text:                          var(--foundation-ink-000);
  --color-text-muted:                    var(--foundation-ink-100);
  --color-text-inverse:                  #f4f8ff;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 var(--foundation-accent-200);
  --color-primary-active:                #155fa0;
  --color-primary-soft:                  rgba(42, 125, 206, 0.14);
  --color-on-primary:                    #f4f8ff;

  --color-success:                       #1b6b38;
  --color-warning:                       #a35e00;
  --color-danger:                        var(--foundation-danger-100);
  --color-info:                          #1565c0;

  --color-hover:                         rgba(42, 125, 206, 0.08);
  --color-active:                        rgba(42, 125, 206, 0.14);
  --color-focus-ring:                    #000000;
  --color-selection:                     rgba(42, 125, 206, 0.20);
  --color-highlight:                     rgba(42, 125, 206, 0.12);
  --color-disabled-bg:                   rgba(0, 0, 0, 0.05);

  --elevation-1-bg:                      #f0f6ff;
  --elevation-2-bg:                      var(--foundation-paper-100);
  --elevation-3-bg:                      var(--foundation-paper-200);
  --overlay-backdrop:                    rgba(10, 22, 40, 0.38);

  --shadow-sm:                           0 1px 2px rgba(10, 22, 40, 0.10);
  --shadow-md:                           0 6px 16px rgba(10, 22, 40, 0.14);
  --shadow-lg:                           0 14px 32px rgba(10, 22, 40, 0.18);

  /* Component tokens */
  --button-bg:                           var(--foundation-paper-200);
  --button-bg-hover:                     var(--color-text);
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 70%, var(--color-primary-soft));
  --button-text:                         var(--color-text);
  --button-border:                       var(--color-border);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   #206cb9;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 var(--color-surface-strong);
  --button-secondary-text:               var(--color-text-muted);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       var(--color-surface);
  --panel-head-text:                     var(--foundation-accent-200);

  --dialog-bg:                           var(--foundation-paper-000);
  --dialog-text:                         var(--color-text-muted);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     #f8fbff;
  --calendar-day-hover:                  #cfe0f5;
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 15%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 20%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   rgba(42, 125, 206, 0.12);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);

  --dialog-shadow:                       var(--foundation-overlay-100);

  --btn-back-linear-gradient:            linear-gradient(135deg, var(--color-bg) 99%, #dce8f8 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(0deg, #b8cce8 0%, #f4f8ff 100%);
  --btn-back-hover-box-shadow:           inset 1px 0 0 rgba(42, 125, 206, 0.2), inset -1px 0 0 rgba(42, 125, 206, 0.2), 0 0 4px 0 rgba(155, 184, 217, 0.6), 0 0 6px 2px rgba(155, 184, 217, 0.6);

  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  var(--foundation-border-100);

  --work-back:                           linear-gradient(135deg, #eaf1fc 20%, #e4edf9 30%);
  --work-fore:                           var(--color-text-muted);

  --border-scaling-factor:               1;
  --border-bottom:                       calc(var(--border-size) * var(--border-scaling-factor)) solid var(--color-text-muted);

  --cal-day-fore:                        var(--color-text);

  --cal-day-hover-glow:                  0 0 1px 5px rgba(155, 184, 217, 0.6);

  --cal-day-padding:                     var(--pad-sm);
  --cal-day-margin:                      var(--mar-sm) var(--gap-md);
  --cal-day-radius:                      var(--border-radius);
  --cal-day-shadow:                      0 0.05rem 0.05rem rgba(10, 22, 40, 0.06);

  --retroGreen:                          #007700;

  --button-text-hover: #ffffff;
}
