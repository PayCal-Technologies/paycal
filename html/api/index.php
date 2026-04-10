<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\ApiControllerRegistry;
use PayCal\Domain\Attributes\Route;
use PayCal\Observability\Lens;

require_once __DIR__ . '/../config.php';

// Initialize Lens observability (DEV-only)
$requestURI = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
  ? $_SERVER['REQUEST_URI']
  : '';
Lens::boot('api/' . ltrim($requestURI, '/'));

CORS::handleOPTIONS();

$debug = [];

$controllers = ApiControllerRegistry::controllers();

$routes = [];

foreach ($controllers as $controllerClass) {

  if (!class_exists($controllerClass)) {
    $debug['missing_controllers'][] = $controllerClass;
    continue;
  }

  $reflection = new \ReflectionClass($controllerClass);

  foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {

    $attributes = $method->getAttributes(Route::class);

    foreach ($attributes as $attribute) {

      $route = $attribute->newInstance();

      $normalizedPath = trim((string) $route->path, '/');

      foreach ((array) $route->methods as $httpMethod) {

        $routes[] = [
          'method' => strtoupper((string) $httpMethod),
          'path' => $normalizedPath,
          'controller' => $controllerClass,
          'action' => $method->getName(),
        ];
      }
    }
  }
}

$requestURI = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
  ? $_SERVER['REQUEST_URI']
  : '';
$requestMethodRaw = isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])
  ? $_SERVER['REQUEST_METHOD']
  : 'GET';
$requestMethod = strtoupper($requestMethodRaw);

$debug['request'] = [
  'raw_uri' => $requestURI,
  'method' => $requestMethod,
  'api_version' => Environment::apiVersion(),
];

$apiBase = '/api/';
$version = Environment::apiVersion();
$versionedPrefix = $apiBase . $version . '/';

if (!str_starts_with($requestURI, $apiBase)) {
  $debug['error'] = 'Invalid API base';
  Response::json('debug', '[API] Invalid endpoint.', 404, $debug);
  exit;
}

if (!str_starts_with($requestURI, $versionedPrefix)) {
  $debug['error'] = 'Version prefix mismatch';
  $debug['expected_prefix'] = $versionedPrefix;
  Response::json('debug', '[API] API version missing or unsupported.', 404, $debug);
  exit;
}

$routePath = substr($requestURI, strlen($versionedPrefix));

$queryPos = strpos($routePath, '?');
if ($queryPos !== false) {
  $routePath = substr($routePath, 0, $queryPos);
}

$routePath = trim($routePath, '/');

$debug['normalized'] = [
  'routePath' => $routePath,
];

$debug['routes_discovered'] = $routes;

foreach ($routes as $route) {

  $comparison = [
    'checking_route' => $route,
    'method_match' => ($requestMethod === $route['method']),
  ];

  if ($requestMethod !== $route['method']) {
    $debug['comparisons'][] = $comparison;
    continue;
  }

  $routeSegments = $route['path'] === ''
    ? []
    : explode('/', $route['path']);

  $requestSegments = $routePath === ''
    ? []
    : explode('/', $routePath);

  $comparison['routeSegments'] = $routeSegments;
  $comparison['requestSegments'] = $requestSegments;

  if (count($routeSegments) !== count($requestSegments)) {
    $comparison['segment_count_match'] = false;
    $debug['comparisons'][] = $comparison;
    continue;
  }

  $params = [];
  $matched = true;

  foreach ($routeSegments as $i => $segment) {

    if (
      str_starts_with($segment, '{') &&
      str_ends_with($segment, '}')
    ) {
      $paramName = substr($segment, 1, -1);
      $params[$paramName] = $requestSegments[$i];
      continue;
    }

    if ($segment !== $requestSegments[$i]) {
      $comparison['segment_mismatch_at'] = $i;
      $comparison['expected'] = $segment;
      $comparison['actual'] = $requestSegments[$i];
      $matched = false;
      break;
    }
  }

  if (!$matched) {
    $debug['comparisons'][] = $comparison;
    continue;
  }

  $comparison['matched'] = true;
  $comparison['params'] = $params;
  $debug['matched_route'] = $comparison;

  ProtectedMode::enforceStepUpForSensitiveRoute($requestMethod, $routePath);

  $controllerClass = $route['controller'];
  $action = $route['action'];

  if (!class_exists($controllerClass)) {
    $debug['error'] = 'Controller class missing at dispatch';
    Response::json('debug', '[API] Controller missing.', 500, $debug);
    exit;
  }

  if (!method_exists($controllerClass, $action)) {
    $debug['error'] = 'Controller method missing at dispatch';
    Response::json('debug', '[API] Method missing.', 500, $debug);
    exit;
  }

  $controller = new $controllerClass();
  $controller->{$action}(...array_values($params));

  exit;
}

$debug['error'] = 'Route not found';
Response::json('debug', '[API] Route not found.', 404, $debug);
exit;
