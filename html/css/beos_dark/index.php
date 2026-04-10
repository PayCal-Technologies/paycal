<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* BEOS DARK */
:root {
  /* BeOS-inspired steel/chrome base with classic yellow highlight */
  --color-bg:                            #18212a;
  --color-bg-soft:                       #1d2731;
  --color-bg-elevated:                   #22303c;
  --color-bg-overlay:                    rgba(0, 0, 0, 0.42);

  --color-surface:                       #22303c;
  --color-surface-muted:                 #263746;
  --color-surface-strong:                #2b3d4d;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   #607d94;
  --color-border-strong:                 #35495a;

  --color-text:                          #e8f0f6;
  --color-text-muted:                    #b8cad8;
  --color-text-inverse:                  #121a21;
  --color-text-disabled:                 #8ea2b2;

  --color-primary:                       #ffffff;
  --color-primary-hover:                 #4094d4;
  --color-primary-active:                #246593;
  --color-primary-soft:                  rgba(47, 125, 184, 0.24);
  --color-on-primary:                    #f5fbff;

  --color-success:                       #2e7d32;
  --color-warning:                       #ef6c00;
  --color-danger:                        #ff5a5a;
  --color-info:                          #0288d1;

  --color-hover:                         rgba(232, 240, 246, 0.10);
  --color-active:                        rgba(232, 240, 246, 0.16);
  --color-focus-ring:                    #f2cc4d;
  --color-selection:                     rgba(242, 204, 77, 0.26);
  --color-highlight:                     rgba(242, 204, 77, 0.30);
  --color-disabled-bg:                   rgba(255, 255, 255, 0.06);

  --elevation-1-bg:                      #243442;
  --elevation-2-bg:                      #2a3a49;
  --elevation-3-bg:                      #324555;
  --overlay-backdrop:                    rgba(0, 0, 0, 0.60);

  --shadow-sm:                           0 1px 2px rgba(0, 0, 0, 0.30);
  --shadow-md:                           0 8px 18px rgba(0, 0, 0, 0.36);
  --shadow-lg:                           0 18px 42px rgba(0, 0, 0, 0.42);

  --button-bg:                           #2a3a49;
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 82%, white);
  --button-bg-active:                    color-mix(in srgb, var(--button-bg) 82%, black);
  --button-text:                         #ffffff;
  --button-border:                       #6d8aa2;
  --button-border-active:                #f2cc4d;
  --button-primary-bg:                   #23628f;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #34495b;
  --button-secondary-text:               var(--color-text);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       linear-gradient(180deg, #41596e 0%, #2b3d4d 100%);
  --panel-head-text:                     #f6d76f;

  --dialog-bg:                           #2a3a49;
  --dialog-text:                         var(--color-text);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       rgba(0, 0, 0, 0.72);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     #1c2731;
  --calendar-day-hover:                  color-mix(in srgb, var(--color-primary) 28%, var(--calendar-day-bg));
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 14%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, #f2cc4d 24%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 #e8f0f6;
  --calendar-range-bg:                   rgba(242, 204, 77, 0.24);

  --heading-accent-color:                #f2cc4d;

  --theme-signature-color:               var(--heading-accent-color);  --nav-menu-back:                       var(--color-surface-strong);
  --nav-menu-fore:                       var(--color-text);
  --system-tray-back:                    var(--color-surface-strong);
  --panel-footer-back:                   #233241;

  --btn-back-linear-gradient:            linear-gradient(145deg, #2f4150 0%, #22303c 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #395063 0%, #283846 100%);

  --border-size:                         1px;
  --border-radius:                       3px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #476175;

  --work-back:                           #22303c;
  --work-fore:                           #e8f0f6;
  --work-details-border:                 #476175;
  --work-entry-back:                     #2a3a49;
  --work-entry-fore:                     #e8f0f6;

  --cal-day-fore:                        #e8f0f6;
  --cal-day-hover-fore:                  #f5fbff;
  --cal-day-hover-glow:                  0 0 0 2px rgba(242, 204, 77, 0.34);
  --cal-day-radius:                      3px;
  --cal-day-shadow:                      0 1px 2px rgba(0, 0, 0, 0.35);
}


