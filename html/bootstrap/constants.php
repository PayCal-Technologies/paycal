<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../vendor/autoload.php';

if (class_exists('Dotenv\\Dotenv')) {
	$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
	$dotenv->safeLoad();
}

if (class_exists('PayCal\\Domain\\Environment')) {
	$bootstrapEnv = [];
	foreach ($_ENV as $key => $value) {
		if (!is_string($key) || !is_scalar($value)) {
			continue;
		}

		$bootstrapEnv[$key] = (string) $value;
	}

	Environment::bootstrap($bootstrapEnv);
}

require_once __DIR__.'/../src/session.php';
