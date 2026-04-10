<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

:root {
  /* Foundation palette */
  --foundation-gray-100:    #C0C0C0;
  --foundation-gray-200:    #A0A0A0;
  --foundation-gray-300:    #808080;
  --foundation-gray-050:    #DFDFDF;
  --foundation-black-100:   #000000;
  --foundation-black-200:   #2A2A2A;
  --foundation-blue-100:    #000080;
  --foundation-blue-200:    #0030A0;
  --foundation-danger-100:  #C00000;

  /* Semantic tokens */
  --color-bg:               var(--foundation-gray-100);
  --color-bg-soft:          var(--foundation-gray-050);
  --color-bg-elevated:      #D4D4D4;
  --color-bg-overlay:       rgba(0, 0, 0, 0.25);

  --color-surface:          var(--foundation-gray-100);
  --color-surface-muted:    #D0D0D0;
  --color-surface-strong:   #B8B8B8;
  --input-bg:                            var(--color-surface);

  --color-border:           var(--color-text);
  --color-border-soft:      #B9B9B9;
  --color-border-strong:    #676767;

  --color-text:             var(--foundation-black-100);
  --color-text-muted:       #3A3A3A;
  --color-text-inverse:     #FFFFFF;
  --color-text-disabled:    #000000;

  --color-primary:          var(--foundation-blue-100);
  --color-primary-hover:    var(--foundation-blue-200);
  --color-primary-active:   #00195E;
  --color-primary-soft:     rgba(0, 0, 128, 0.18);
  --color-on-primary:       #FFFFFF;

  --color-success:          #1F6A2A;
  --color-warning:          #A55A00;
  --color-danger:           var(--foundation-danger-100);
  --color-info:             #005E9E;

  --color-hover:            rgba(0, 0, 0, 0.08);
  --color-active:           rgba(0, 0, 0, 0.14);
  --color-focus-ring:       #0030A0;
  --color-selection:        rgba(0, 0, 128, 0.22);
  --color-highlight:        rgba(255, 235, 140, 0.40);
  --color-disabled-bg:      rgba(0, 0, 0, 0.06);

  --elevation-1-bg:         #CECECE;
  --elevation-2-bg:         #D8D8D8;
  --elevation-3-bg:         #E2E2E2;
  --overlay-backdrop:       rgba(0, 0, 0, 0.32);

  --shadow-sm:              0 1px 0 rgba(255, 255, 255, 0.5), 0 1px 2px rgba(0, 0, 0, 0.12);
  --shadow-md:              0 3px 8px rgba(0, 0, 0, 0.20);
  --shadow-lg:              0 8px 18px rgba(0, 0, 0, 0.28);

  /* Component tokens */
  --button-bg:              var(--color-surface);
  --button-bg-hover:        color-mix(in srgb, var(--button-bg) 82%, white);
  --button-bg-active:       var(--foundation-gray-200);
  --button-text:            var(--color-text);
  --button-border:          var(--color-border);
  --button-border-active:   var(--color-primary-hover);
  --button-primary-bg:      var(--color-primary);
  --button-primary-text:    var(--color-on-primary);
  --button-secondary-bg:    var(--color-surface);
  --button-secondary-text:  var(--color-text);
  --button-danger-text:     var(--color-danger);

  --panel-bg:               var(--color-surface);
  --panel-text:             var(--color-text);
  --panel-border:           var(--color-border-soft);
  --panel-head-bg:          var(--color-primary);
  --panel-head-text:        var(--color-on-primary);

  --dialog-bg:              var(--color-surface);
  --dialog-text:            var(--color-text);
  --dialog-border:          var(--color-border-soft);
  --dialog-shadow:          var(--shadow-md);
  --dialog-overlay:         var(--overlay-backdrop);

  --calendar-bg:            var(--color-bg);
  --calendar-border:        var(--color-border-soft);
  --calendar-day-bg:        var(--color-surface);
  --calendar-day-hover:     color-mix(in srgb, var(--color-primary) 14%, var(--calendar-day-bg));
  --calendar-day-today:     color-mix(in srgb, var(--color-primary) 10%, var(--calendar-day-bg));
  --calendar-day-selected:  color-mix(in srgb, var(--color-primary) 20%, var(--calendar-day-bg));
  --calendar-event-bg:      var(--color-primary-soft);
  --calendar-event-text:    var(--color-text);
  --calendar-range-bg:      rgba(0, 0, 128, 0.14);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);


  --nav-menu-back:          var(--color-surface);
  --nav-menu-fore:          var(--color-text);
  --system-tray-back:       var(--color-surface);


  --work-back:              var(--color-bg);

  --modal-head-back:        var(--color-primary);
  --modal-head-fore:        var(--color-on-primary);






  --border-size:            1px;
  --cal-day-fore:           var(--color-text);
  --cal-day-hover-glow:     none;
  --border-radius:          0;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
}



