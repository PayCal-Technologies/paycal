<?php

declare(strict_types=1);

final class CodeMetrics
{
  private string $projectRoot;
  private string $metricsPath;

  public function __construct()
  {
    $this->projectRoot = realpath(__DIR__ . "/../dev/html") ?: "";
    $this->metricsPath = realpath(__DIR__ . "/../metrics")
      ?: __DIR__ . "/../metrics";
  }

  public function run(array $argv): void
  {
    $mode = $argv[1] ?? null;

    match ($mode) {
      "--collect" => $this->collect(),
      "--analyze" => $this->analyze(),
      "--trend"   => $this->trend(),
      default     => $this->usage(),
    };
  }

  private function collect(): void
  {
    if (!is_dir($this->projectRoot)) {
      echo "Project root not found.\n";
      return;
    }

    $data = $this->scan();
    $data["phpstan"] = $this->runPhpStan();
    $data["timestamp"] = gmdate("c");

    if (!is_dir($this->metricsPath)) {
      mkdir($this->metricsPath, 0777, true);
    }

    $file = $this->metricsPath . "/" . date("Y-m-d_H-i-s") . ".json";
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

    $this->printSummary($data);
  }

  private function scan(): array
  {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
        $this->projectRoot,
        FilesystemIterator::SKIP_DOTS
      )
    );

    $files = 0;
    $locPhysical = 0;
    $locLogical = 0;
    $classes = 0;
    $traits = 0;
    $enums = 0;
    $methods = 0;
    $functions = 0;

    $largestFile = ["path" => "", "loc" => 0];
    $largeFiles = [];
    $classMethodCounts = [];
    $largeMethods = [];
    $complexMethods = [];
    $totalComplexity = 0;

    foreach ($iterator as $file) {
      if (!$file->isFile() || $file->getExtension() !== "php") {
        continue;
      }

      $path = $file->getPathname();
      $relative = str_replace($this->projectRoot, "", $path);

      if (preg_match("#^/(vendor|var|cache|node_modules|\.git)/#", $relative)) {
        continue;
      }

      $files++;
      $content = file_get_contents($path);
      $lines = explode("\n", (string)$content);

      $fileLoc = count($lines);
      $locPhysical += $fileLoc;

      if ($fileLoc > $largestFile["loc"]) {
        $largestFile = ["path" => $relative, "loc" => $fileLoc];
      }

      if ($fileLoc > 500) {
        $largeFiles[] = ["path" => $relative, "loc" => $fileLoc];
      }

      foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim !== "" &&
            !str_starts_with($trim, "//") &&
            !str_starts_with($trim, "/*") &&
            !str_starts_with($trim, "*")) {
          $locLogical++;
        }
      }

      $tokens = token_get_all($content);
      $currentClass = null;
      $braceDepth = 0;

      foreach ($tokens as $i => $token) {
        if (is_array($token)) {
          if ($token[0] === T_CLASS) {
            $classes++;
            $currentClass = $tokens[$i + 2][1] ?? "unknown";
            $classMethodCounts[$currentClass] = 0;
          }

          if ($token[0] === T_TRAIT) {
            $traits++;
          }

          if ($token[0] === T_ENUM) {
            $enums++;
          }

          if ($token[0] === T_FUNCTION) {
            $complexity = $this->calculateComplexity($tokens, $i);
            $totalComplexity += $complexity;

            if ($currentClass !== null) {
              $methods++;
              $classMethodCounts[$currentClass]++;
              $methodLoc = $this->estimateMethodLength($tokens, $i);

              if ($methodLoc > 80) {
                $largeMethods[] = [
                  "class" => $currentClass,
                  "loc" => $methodLoc
                ];
              }

              $complexMethods[] = [
                "class" => $currentClass,
                "complexity" => $complexity
              ];
            } else {
              $functions++;
            }
          }
        }

        if ($token === "{") {
          $braceDepth++;
        }

        if ($token === "}") {
          $braceDepth--;
          if ($braceDepth === 0) {
            $currentClass = null;
          }
        }
      }
    }

    arsort($classMethodCounts);
    usort($complexMethods, fn($a, $b) => $b["complexity"] <=> $a["complexity"]);

    $avgComplexity = $methods > 0
      ? round($totalComplexity / $methods, 2)
      : 0;

    $health = $this->calculateHealthScore(
      $avgComplexity,
      count($largeFiles),
      count($largeMethods)
    );

    return [
      "files" => $files,
      "loc_physical" => $locPhysical,
      "loc_logical" => $locLogical,
      "classes" => $classes,
      "traits" => $traits,
      "enums" => $enums,
      "methods" => $methods,
      "functions" => $functions,
      "avg_methods_per_class" => $classes > 0
        ? round($methods / $classes, 2)
        : 0,
      "largest_file" => $largestFile,
      "files_over_500_loc" => $largeFiles,
      "top_5_classes_by_methods" => array_slice($classMethodCounts, 0, 5, true),
      "methods_over_80_loc" => $largeMethods,
      "avg_method_complexity" => $avgComplexity,
      "top_5_complex_methods" => array_slice($complexMethods, 0, 5),
      "architecture_health_score" => $health
    ];
  }

  private function calculateComplexity(array $tokens, int $start): int
  {
    $complexity = 1;
    $braceCount = 0;

    for ($i = $start; $i < count($tokens); $i++) {
      $token = $tokens[$i];

      if (is_array($token)) {
        if (in_array($token[0], [
          T_IF, T_ELSEIF, T_FOR, T_FOREACH,
          T_WHILE, T_CASE, T_CATCH
        ], true)) {
          $complexity++;
        }
      }

      if ($token === "&&" || $token === "||") {
        $complexity++;
      }

      if ($token === "{") {
        $braceCount++;
      }

      if ($token === "}") {
        $braceCount--;
        if ($braceCount === 0) {
          break;
        }
      }
    }

    return $complexity;
  }

  private function estimateMethodLength(array $tokens, int $start): int
  {
    $braceCount = 0;
    $lines = 0;

    for ($i = $start; $i < count($tokens); $i++) {
      $token = $tokens[$i];

      if (is_array($token)) {
        $lines += substr_count($token[1], "\n");
      }

      if ($token === "{") {
        $braceCount++;
      }

      if ($token === "}") {
        $braceCount--;
        if ($braceCount === 0) {
          break;
        }
      }
    }

    return $lines;
  }

  private function calculateHealthScore(
    float $avgComplexity,
    int $largeFiles,
    int $largeMethods
  ): int {
    $score = 100;

    if ($avgComplexity > 10) $score -= 20;
    elseif ($avgComplexity > 7) $score -= 10;

    $score -= min(20, $largeFiles * 2);
    $score -= min(20, $largeMethods * 2);

    return max(0, $score);
  }

  private function runPhpStan(): array
  {
    $cmd = sprintf(
      "cd %s && vendor/bin/phpstan analyse %s --error-format=json --no-progress --memory-limit=512M",
      escapeshellarg($this->projectRoot),
      escapeshellarg($this->projectRoot)
    );

    exec($cmd, $output);
    $json = json_decode(implode("\n", $output), true);

    return [
      "errors" => $json["totals"]["errors"] ?? 0,
      "memory_mb" => isset($json["memoryUsage"])
        ? round($json["memoryUsage"] / 1024 / 1024)
        : 0
    ];
  }

  private function printSummary(array $d): void
  {
    echo "\033[36mCode Metrics Snapshot\033[0m\n";

    foreach ($d as $k => $v) {
      if (is_array($v) || $k === "timestamp" || $k === "phpstan") {
        continue;
      }

      printf("%-28s %10s\n", $k, (string)$v);
    }

    echo "\n\033[36mTop 5 Most Complex Methods\033[0m\n";
    foreach ($d["top_5_complex_methods"] as $m) {
      printf("%-30s %5d\n",
        $m["class"],
        $m["complexity"]
      );
    }

    echo "\n\033[36mArchitecture Health Score\033[0m\n";
    $color = $d["architecture_health_score"] > 80 ? "32"
      : ($d["architecture_health_score"] > 60 ? "33" : "31");

    printf(
      "\033[%sm%d / 100\033[0m\n",
      $color,
      $d["architecture_health_score"]
    );
  }

  private function analyze(): void {}
  private function trend(): void {}
  private function usage(): void {}
}

(new CodeMetrics())->run($argv);
