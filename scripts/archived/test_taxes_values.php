<?php
require_once __DIR__ . '/tests/bootstrap.php';

$taxes = new Taxes();

$scenarios = [
  'minimum wage (20000)' => 20000,
  'median income (50000)' => 50000,
  'high income (100000)' => 100000,
  'very high income (200000)' => 200000,
];

foreach ($scenarios as $label => $income) {
  $result = $taxes->calculateTaxes($income);
  echo "$label: Total = " . round($result['totalDeductions'], 2) . PHP_EOL;
}
