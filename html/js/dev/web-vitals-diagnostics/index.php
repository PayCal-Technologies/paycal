<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../../config.php';

Authentication::abortIfUnauthenticated();

if (!Environment::isWebVitalsDiagnosticsEnabled()) {
  http_response_code(404);
  exit;
}

CORS::handleORIGIN();
CORS::renderContentType('text/javascript');
header('Cache-Control: no-store');

$attributionUrl = Environment::appURL('js/dev/web-vitals-attribution/') . '?v=' . rawurlencode(Environment::appVersion());
$attributionUrlJs = json_encode($attributionUrl, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

echo <<<JS
import { onCLS, onINP, onLCP } from {$attributionUrlJs};

const THRESHOLDS = {
  LCP: 2500,
  CLS: 0.1,
  INP: 200,
};

const metrics = {
  lcp: null,
  cls: null,
  inp: null,
  ready: false,
  inpReady: false,
  thresholds: THRESHOLDS,
};

const corePending = new Set(['lcp', 'cls']);

function formatAttribution(name, metric) {
  const attr = metric.attribution ?? {};
  if (name === 'lcp') {
    return {
      target: attr.target ?? null,
      url: attr.url ?? null,
      elementRenderDelay: attr.elementRenderDelay ?? null,
      resourceLoadDuration: attr.resourceLoadDuration ?? null,
      timeToFirstByte: attr.timeToFirstByte ?? null,
    };
  }
  if (name === 'cls') {
    return {
      largestShiftTarget: attr.largestShiftTarget ?? null,
      largestShiftValue: attr.largestShiftValue ?? null,
      largestShiftTime: attr.largestShiftTime ?? null,
      loadState: attr.loadState ?? null,
    };
  }
  if (name === 'inp') {
    return {
      interactionTarget: attr.interactionTarget ?? null,
      interactionType: attr.interactionType ?? null,
      inputDelay: attr.inputDelay ?? null,
      processingDuration: attr.processingDuration ?? null,
      presentationDelay: attr.presentationDelay ?? null,
      loadState: attr.loadState ?? null,
    };
  }
  return attr;
}

function dispatchReady() {
  if (metrics.ready) {
    return;
  }

  metrics.ready = true;
  window.dispatchEvent(new CustomEvent('paycal:web-vitals-ready', { detail: metrics }));
}

function dispatchInpReady() {
  if (metrics.inpReady) {
    return;
  }

  metrics.inpReady = true;
  window.dispatchEvent(new CustomEvent('paycal:web-vitals-inp', { detail: metrics }));
}

function logMetric(name, metric) {
  metrics[name] = {
    name: metric.name,
    value: metric.value,
    rating: metric.rating,
    delta: metric.delta,
    id: metric.id,
    navigationType: metric.navigationType,
    attribution: formatAttribution(name, metric),
  };

  const threshold = THRESHOLDS[metric.name];
  const passes = typeof threshold === 'number' ? metric.value <= threshold : null;
  const label = metric.name.toUpperCase();
  const groupLabel = `[PayCal Web Vitals] \${label} \${metric.rating}\${passes === false ? ' (over threshold)' : ''}`;

  console.groupCollapsed(groupLabel);
  console.log('value:', metric.value, 'rating:', metric.rating, 'threshold:', threshold);
  console.log('attribution:', formatAttribution(name, metric));
  console.log('entries:', metric.entries);
  console.groupEnd();

  if (name === 'inp') {
    dispatchInpReady();
    return;
  }

  corePending.delete(name);
  if (corePending.size === 0) {
    dispatchReady();
  }
}

onLCP((metric) => logMetric('lcp', metric));
onCLS((metric) => logMetric('cls', metric), { reportAllChanges: true });
onINP((metric) => logMetric('inp', metric));

window.__paycalWebVitals = metrics;

JS;
