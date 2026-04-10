<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Foundation palette */
  --foundation-desktop-100:              #007070;
  --foundation-gray-100:                 #C0C0C0;
  --foundation-gray-150:                 #D4D0C8;
  --foundation-gray-200:                 #A0A0A0;
  --foundation-gray-300:                 #808080;
  --foundation-black-100:                #000000;
  --foundation-black-200:                #404040;
  --foundation-blue-100:                 #000080;
  --foundation-blue-200:                 #1084D0;
  --foundation-danger-100:               #C00000;

  /* Semantic tokens */
  --color-bg:                            var(--foundation-desktop-100);
  --color-bg-soft:                       #0A8F8F;
  --color-bg-elevated:                   #0B6F74;
  --color-bg-overlay:                    rgba(0, 0, 0, 0.25);

  --color-surface:                       #1E4E4E;
  --color-surface-muted:                 #2A5E5E;
  --color-surface-strong:                #154545;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   #9A9A9A;
  --color-border-strong:                 var(--foundation-black-200);

  --color-text:                          #F8F8F8;
  --color-text-muted:                    #F8F8F8;
  --color-text-inverse:                  #FFFFFF;
  --color-text-disabled:                 #ffffff;

  --color-primary:                       var(--color-text);
  --color-primary-hover:                 var(--foundation-blue-200);
  --color-primary-active:                #001F66;
  --color-primary-soft:                  rgba(16, 132, 208, 0.18);
  --color-on-primary:                    #FFFFFF;

  --color-success:                       #1F6A2A;
  --color-warning:                       #A55A00;
  --color-danger:                        var(--foundation-danger-100);
  --color-info:                          #005E9E;

  --color-hover:                         rgba(0, 0, 0, 0.08);
  --color-active:                        rgba(0, 0, 0, 0.14);
  --color-focus-ring:                    var(--color-text);
  --color-selection:                     rgba(0, 0, 128, 0.24);
  --color-highlight:                     rgba(255, 236, 150, 0.38);
  --color-disabled-bg:                   rgba(0, 0, 0, 0.06);

  --elevation-1-bg:                      #CECECE;
  --elevation-2-bg:                      #D8D8D8;
  --elevation-3-bg:                      #E2E2E2;
  --overlay-backdrop:                    rgba(0, 0, 0, 0.32);

  --shadow-sm:                           0 1px 0 rgba(255, 255, 255, 0.5), 0 1px 2px rgba(0, 0, 0, 0.10);
  --shadow-md:                           0 3px 8px rgba(0, 0, 0, 0.20);
  --shadow-lg:                           0 8px 18px rgba(0, 0, 0, 0.28);

  /* Component tokens */
  --button-bg:                           var(--foundation-gray-100);
  --button-bg-hover:                     var(--foundation-gray-150);
  --button-bg-active:                    var(--foundation-gray-200);
  --button-text:                         var(--foundation-black-100);
  --button-border:                       var(--color-border);
  --button-border-active:                var(--color-primary-hover);
  --button-primary-bg:                   var(--foundation-gray-150);
  --button-primary-text:                 var(--foundation-black-100);
  --button-secondary-bg:                 var(--foundation-black-200);
  --button-secondary-text:               var(--color-text);
  --button-danger-text:                  var(--color-danger);

  --panel-bg:                            var(--color-surface);
  --panel-text:                          var(--color-text);
  --panel-border:                        var(--color-border-soft);
  --panel-head-bg:                       linear-gradient(90deg, var(--foundation-blue-100) 0%, #1560a8 100%);
  --panel-head-text:                     var(--color-on-primary);

  --dialog-bg:                           var(--color-surface);
  --dialog-text:                         var(--color-text);
  --dialog-border:                       var(--color-border-soft);
  --dialog-shadow:                       var(--shadow-md);
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--color-bg);
  --calendar-border:                     var(--color-border-soft);
  --calendar-day-bg:                     var(--color-surface-muted);
  --calendar-day-hover:                  color-mix(in srgb, var(--color-primary-hover) 10%, var(--calendar-day-bg));
  --calendar-day-today:                  color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected:               color-mix(in srgb, var(--color-primary) 20%, var(--calendar-day-bg));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--color-text);
  --calendar-range-bg:                   rgba(16, 132, 208, 0.16);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --text-color:                          #FAFAFA;
  --text-muted:                          #F2F2F2;
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
  --work-back:                           var(--color-surface-muted);
  --work-fore:                           var(--color-text);
  --work-details-border:                 var(--color-border);
  --work-entry-back:                     var(--color-surface-muted);
  --work-entry-fore:                     var(--color-text);
  --cal-day-fore:                        var(--color-text);
  --cal-day-radius:                      0;
  --cal-day-shadow:                      none;
  --cal-day-hover-glow:                  none;
}


