<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* FEDORA LIGHT */
:root {





  --dialog-shadow:                       rgba(18, 40, 74, 0.20);





  --btn-back-linear-gradient:            linear-gradient(145deg, #f7faff 0%, #ebf2fc 100%);
  --btn-back-hover-linear-gradient:      linear-gradient(145deg, #ebf2fc 0%, #dde9f9 100%);





  --border-size:                         1px;
  --border-radius:                       10px;
  --radius-button:                      calc(var(--border-radius) * 2);
  --radius-control:                     var(--border-radius);
  --radius-panel:                       var(--border-radius);
  --radius-dialog:                      var(--border-radius);
  --radius-cell:                        var(--border-radius);
  --radius-article:                     var(--border-radius);
  --border-inset-color:                  #9db7dd;

  --work-back:                           linear-gradient(150deg, #edf3fc 0%, #e2ecfa 100%);
  --work-fore:                           #1a2f4f;

  --cal-day-fore:                        #1a2f4f;
  --cal-day-hover-fore:                  #f7fbff;
  --cal-day-hover-glow:                  0 0 0 3px rgba(60, 110, 180, 0.24);

  --cal-day-radius:                      10px;
  --cal-day-shadow:                      0 0.05rem 0.1rem rgba(18, 40, 74, 0.16);

  --work-details-border:                 #9db7dd;
  --work-entry-back:                     #f7faff;
  --work-entry-fore:                     #1a2f4f;

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            #e4edf9;
  --color-bg-soft:                       color-mix(in srgb, #e4edf9 86%, #edf3fb);
  --color-bg-elevated:                   color-mix(in srgb, #eef4fd 90%, #edf3fb);
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       #eef4fd;
  --color-surface-muted:                 color-mix(in srgb, #eef4fd 90%, #edf3fb);
  --color-surface-strong:                #edf3fb;
  --input-bg:                            var(--color-surface);

  --color-border:                        var(--color-text);
  --color-border-soft:                   color-mix(in srgb, #9db7dd 72%, #eef4fd);
  --color-border-strong:                 color-mix(in srgb, #9db7dd 84%, black);

  --color-text:                          #1a2f4f;
  --color-text-muted:                    #314f7b;
  --color-text-inverse:                  #f7fbff;
  --color-text-disabled:                 #000000;

  --color-primary:                       #000000;
  --color-primary-hover:                 #5f8cc9;
  --color-primary-active:                color-mix(in srgb, #3c6eb4 82%, black);
  --color-primary-soft:                  color-mix(in srgb, #3c6eb4 18%, #eef4fd);
  --color-on-primary:                    #f7fbff;

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        #b71c1c;
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    #000000;
  --color-selection:                     color-mix(in srgb, #3c6eb4 24%, #e4edf9);
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, #eef4fd 92%, #edf3fb);
  --elevation-2-bg:                      color-mix(in srgb, #eef4fd 84%, #edf3fb);
  --elevation-3-bg:                      color-mix(in srgb, #eef4fd 76%, #edf3fb);
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           #f7faff;
  --button-bg-hover:                     color-mix(in srgb, var(--color-primary-hover) 78%, black);
  --button-bg-active:                    var(--color-primary-hover);
  --button-text:                         #1a2f4f;
  --button-border:                       #9db7dd;
  --button-border-active:                #3c6eb4;
  --button-primary-bg:                   #3c6eb4;
  --button-primary-text:                 #ffffff;
  --button-secondary-bg:                 #dce7f7;
  --button-secondary-text:               #1a2f4f;
  --button-danger-text:                  #b71c1c;

  --panel-bg:                            #eef4fd;
  --panel-text:                          #1a2f4f;
  --panel-border:                        #9db7dd;
  --panel-head-bg:                       #dce9fa;
  --panel-head-text:                     #2a5ca8;

  --dialog-bg:                           #f7faff;
  --dialog-text:                         #1a2f4f;
  --dialog-border:                       #9db7dd;
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         #e4edf9;
  --calendar-border:                     #9db7dd;
  --calendar-day-bg:                     #f9fbff;
  --calendar-day-hover:                  #3c6eb4;
  --calendar-day-today:                  color-mix(in srgb, #3c6eb4 12%, #f9fbff);
  --calendar-day-selected:               color-mix(in srgb, #3c6eb4 22%, #f9fbff);
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, #1a2f4f);
  --calendar-range-bg:                   color-mix(in srgb, #3c6eb4 18%, #e4edf9);
  --heading-accent-color:                var(--color-primary);

  --theme-signature-color:               var(--heading-accent-color);
  --button-text-hover: #ffffff;

  --button-text-active: #000000;
}


