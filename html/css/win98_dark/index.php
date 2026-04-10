<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Foundation palette */
  --foundation-desktop-100:              #0A3F45;
  --foundation-desktop-200:              #0B525A;
  --foundation-graphite-100:             #2D2D2D;
  --foundation-graphite-200:             #1F1F1F;
  --foundation-graphite-300:             #3A3A3A;
  --foundation-border-100:               #5F5F5F;
  --foundation-text-100:                 #E6E6E6;
  --foundation-text-200:                 #B0B0B0;
  --foundation-blue-100:                 #0A2B6A;
  --foundation-blue-200:                 #1A63B5;
  --foundation-danger-100:               #FF5A5A;

  /* Semantic tokens */
  --color-bg:                            var(--foundation-desktop-100);
  --color-bg-soft:                       var(--foundation-desktop-200);
  --color-bg-elevated:                   #08353A;
  --color-bg-overlay:                    rgba(0, 0, 0, 0.42);

  --color-surface:                       var(--foundation-graphite-100);
  --color-surface-muted:                 #252525;
  --color-surface-strong:                var(--foundation-graphite-200);
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   #747474;
  --color-border-strong:                 #111111;

  --color-text:                          var(--foundation-text-100);
  --color-text-muted:                    var(--foundation-text-200);
  --color-text-inverse:                  #101010;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 var(--foundation-blue-200);
  --color-primary-active:                #081F4B;
  --color-primary-soft:                  rgba(26, 99, 181, 0.22);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #2C8A3F;
  --color-warning:                       #D88722;
  --color-danger:                        var(--foundation-danger-100);
  --color-info:                          #2B88D8;

  --color-hover:                         rgba(255, 255, 255, 0.08);
  --color-active:                        rgba(255, 255, 255, 0.14);
  --color-focus-ring:                    #ffffff;
  --color-selection:                     rgba(77, 140, 255, 0.24);
  --color-highlight:                     rgba(255, 235, 140, 0.18);
  --color-disabled-bg:                   rgba(255, 255, 255, 0.06);

  --elevation-1-bg:                      #262626;
  --elevation-2-bg:                      #2B2B2B;
  --elevation-3-bg:                      #323232;
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           0 1px 2px rgba(0, 0, 0, 0.30);
  --shadow-md:                           0 8px 18px rgba(0, 0, 0, 0.36);
  --shadow-lg:                           0 16px 36px rgba(0, 0, 0, 0.42);

  /* Component tokens */
  --button-bg:                           var(--color-surface);
  --button-bg-hover:                     color-mix(in srgb, var(--button-bg) 30%, white);
  --button-bg-active:                    var(--foundation-graphite-300);
  --button-text:                         #ffffff;
  --button-text-hover:                   var(--color-text-inverse);
  --button-border:                       var(--color-border);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   var(--foundation-blue-100);
  --button-primary-text:                 var(--color-on-primary);
  --button-secondary-bg:                 var(--color-surface);
  --button-secondary-text:               var(--color-text);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--foundation-graphite-200);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border);
  --panel-head-bg:                       linear-gradient(90deg, var(--foundation-blue-100) 0%, #134f95 100%);
  --panel-head-text:                     var(--color-on-primary);

  --dialog-bg:                           var(--color-surface);
  --dialog-text:                         var(--color-text);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border);
  --calendar-day-bg:                     var(--panel-bg);
  --calendar-day-hover:                  color-mix(in srgb, var(--color-primary-hover) 14%, var(--calendar-day-bg));
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary-hover) 22%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   rgba(77, 140, 255, 0.16);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          var(--color-text);
  --text-muted:                          var(--color-text-muted);
  --nav-menu-back:                       var(--color-surface);
  --nav-menu-fore:                       var(--color-text);
  --system-tray-back:                    var(--color-surface);
  --panel-footer-back:                   var(--color-surface);
  --border-size:                         1px;
  --border-radius:                       0;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  var(--color-border);
  --work-back:                           #242424;
  --work-fore:                           var(--color-text);
  --work-details-border:                 var(--color-border);
  --work-entry-back:                     #2B2B2B;
  --work-entry-fore:                     var(--color-text);
  --cal-day-fore:                        var(--color-text);
  --cal-day-radius:                      0;
  --cal-day-shadow:                      none;
  --cal-day-hover-glow:                  none;
}


