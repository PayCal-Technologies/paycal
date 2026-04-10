<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* BEOS LIGHT */
:root {
  /* BeOS-inspired light chrome with warm yellow accent */
  --color-bg:                            #d7e0e8;
  --color-bg-soft:                       #e1e8ee;
  --color-bg-elevated:                   #e9eff4;
  --color-bg-overlay:                    rgba(10, 22, 32, 0.24);

  --color-surface:                       #e9eff4;
  --color-surface-muted:                 #eef3f7;
  --color-surface-strong:                #dce5ed;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   #a9bac8;
  --color-border-strong:                 #6d8396;

  --color-text:                          #1f2f3d;
  --color-text-muted:                    #34495b;
  --color-text-inverse:                  #f5fbff;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #3f93ce;
  --color-primary-active:                #23628f;
  --color-primary-soft:                  rgba(47, 125, 184, 0.20);
  --color-on-primary:                    #f5fbff;

  --color-success:                       #2e7d32;
  --color-warning:                       #ef6c00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288d1;

  --color-hover:                         rgba(31, 47, 61, 0.10);
  --color-active:                        rgba(31, 47, 61, 0.16);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     rgba(217, 184, 77, 0.24);
  --color-highlight:                     rgba(217, 184, 77, 0.30);
  --color-disabled-bg:                   rgba(31, 47, 61, 0.06);

  --elevation-1-bg:                      #e4eaef;
  --elevation-2-bg:                      #eef3f7;
  --elevation-3-bg:                      #f4f8fb;
  --overlay-backdrop:                    rgba(0, 0, 0, 0.24);

  --shadow-sm:                           0 1px 2px rgba(17, 32, 44, 0.14);
  --shadow-md:                           0 8px 18px rgba(17, 32, 44, 0.22);
  --shadow-lg:                           0 16px 34px rgba(17, 32, 44, 0.28);

  --button-bg:                           #eef3f7;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 90%, black);
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 82%, black);
  --button-text:                         #1f2f3d;
  --button-border:                       #ffffff #7c8f9f #7c8f9f #ffffff;
  --button-border-active:                #d9b84d;
  --button-primary-bg:                   #23628f;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #dce5ed;
  --button-secondary-text:               #1f2f3d;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #e9eff4;
  --panel-text:                          #1f2f3d;
  --panel-border:                        #95a9ba;
  --panel-head-bg:                       linear-gradient(180deg, #f8fbfd 0%, #ccd7e2 100%);
  --panel-head-text:                     #4f3a00;

  --dialog-bg:                           #eef3f7;
  --dialog-text:                         #1f2f3d;
  --dialog-border:                       #95a9ba;
  --dialog-shadow:                       rgba(17, 32, 44, 0.22);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #d7e0e8;
  --calendar-border:                     #95a9ba;
  --calendar-day-bg:                     #eef3f7;
  --calendar-day-hover:                  color-mix(in srgb, var(--color-primary) 22%, var(--calendar-day-bg));
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, #d9b84d 24%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 #1f2f3d;
  --calendar-range-bg:                   rgba(217, 184, 77, 0.22);

  --heading-accent-color:                #8f6a00;

  --theme-signature-color:               var(--heading-accent-color);  --nav-menu-back:                       var(--color-surface-strong);
  --nav-menu-fore:                       var(--color-text);
  --system-tray-back:                    var(--color-surface-strong);
  --panel-footer-back:                   #dce5ed;

  --btn-back-linear-gradient:            linear-gradient(145deg, #f4f8fb 0%, #dce5ed 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #eaf1f6 0%, #d2dde7 100%);

  --border-size:                         1px;
  --border-radius:                       3px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #9ab0c1;

  --work-back:                           #dde5ec;
  --work-fore:                           #1f2f3d;
  --work-details-border:                 #95a9ba;
  --work-entry-back:                     #eef3f7;
  --work-entry-fore:                     #1f2f3d;

  --cal-day-fore:                        #1f2f3d;
  --cal-day-hover-fore:                  #f5fbff;
  --cal-day-hover-glow:                  0 0 0 2px rgba(217, 184, 77, 0.25);
  --cal-day-radius:                      3px;
  --cal-day-shadow:                      0 1px 2px rgba(17, 32, 44, 0.16);
}


