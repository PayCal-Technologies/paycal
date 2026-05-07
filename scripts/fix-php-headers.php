<?php declare(strict_types=1);

/**
 * PhpHeaderFixer
 */
class PhpHeaderFixer
{
    private array $stats = [
        'processed' => 0,
        'modified' => 0,
        'errors' => 0,
        'skipped' => 0,
    ];

    private array $paycalClasses = [];

    public function __construct()
    {
        $this->loadPayCalClasses();
    }

    /**
     * Load all PayCal classes for reference
     */
    private function loadPayCalClasses(): void
    {
        $srcDir = __DIR__ . '/../html/src';
        if (!is_dir($srcDir)) {
            echo "Warning: src directory not found at $srcDir\n";
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                
                // Extract namespace and class/interface/trait name
                if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
                    if (preg_match('/(?:class|interface|trait|enum)\s+(\w+)/', $content, $classMatch)) {
                        $fqcn = $nsMatch[1] . '\\' . $classMatch[1];
                        $this->paycalClasses[$classMatch[1]] = $fqcn;
                    }
                }
            }
        }

        echo "Loaded " . count($this->paycalClasses) . " PayCal classes\n\n";
    }

    /**
     * Process a single PHP file
     */
    public function processFile(string $filePath): void
    {
        $this->stats['processed']++;
        
        $relativePath = str_replace(__DIR__ . '/../', '', $filePath);
        echo "Processing: $relativePath\n";

        try {
            $originalContent = file_get_contents($filePath);
            $newContent = $this->fixFile($originalContent, $filePath);

            if ($newContent !== $originalContent) {
                file_put_contents($filePath, $newContent);
                $this->stats['modified']++;
                echo "  ✓ Modified\n";
            } else {
                $this->stats['skipped']++;
                echo "  - No changes needed\n";
            }
        } catch (Exception $e) {
            $this->stats['errors']++;
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Fix a PHP file's content
     */
    private function fixFile(string $content, string $filePath): string
    {
        // Skip simple template/CSS serving files without classes
        if ((str_contains($filePath, '/templates/') || 
             str_contains($filePath, '/css/') || 
             str_contains($filePath, '/js/') ||
             str_contains($filePath, '/faq/') ||
             str_contains($filePath, '/help/') ||
             str_contains($filePath, '/about/') ||
             str_contains($filePath, '/contact/')) && 
            !str_contains($content, 'namespace') && 
            !str_contains($content, 'class ')) {
            
            // Still ensure these have declare(strict_types=1)
            if (!str_contains($content, 'declare(strict_types=1)')) {
                $content = preg_replace('/^<\?php\s*/', "<?php declare(strict_types=1);\n\n", $content);
            }
            return $content;
        }

        // Parse existing content structure
        $existingNamespace = '';
        $existingUses = [];
        $hasFileDocblock = false;
        
        if (preg_match('/namespace\s+([^;]+);/', $content, $match)) {
            $existingNamespace = $match[1];
        }
        
        if (preg_match_all('/^use\s+([^;]+);/m', $content, $matches)) {
            $existingUses = $matches[1];
        }
        
        if (preg_match('/\/\*\*.*?@package/s', $content)) {
            $hasFileDocblock = true;
        }

        // Find classes used in the code
        $usedClasses = $this->findUsedClasses($content, $existingNamespace);
        
        // Merge with existing uses - deduplicate by both class name and FQCN
        $seenFqcns = [];
        foreach ($usedClasses as $fqcn) {
            $seenFqcns[$fqcn] = true;
        }
        
        foreach ($existingUses as $use) {
            $fqcn = trim($use);
            // Skip if we've already seen this exact FQCN
            if (isset($seenFqcns[$fqcn])) {
                continue;
            }
            
            $parts = explode('\\', $fqcn);
            $className = end($parts);
            
            // Only add if classname collision doesn't exist
            if (!isset($usedClasses[$className])) {
                $usedClasses[$className] = $fqcn;
                $seenFqcns[$fqcn] = true;
            }
        }

        // Build new file content
        $result = [];
        
        // 1. Opening tag with declare
        $result[] = '<?php declare(strict_types=1);';
        $result[] = '';

        // 2. Namespace (if exists)
        if ($existingNamespace) {
            $result[] = "namespace $existingNamespace;";
            $result[] = '';
        }

        // 3. Use statements
        $useStatements = $this->buildUseStatements($usedClasses, $existingNamespace);
        if (!empty($useStatements)) {
            foreach ($useStatements as $use) {
                $result[] = $use;
            }
            $result[] = '';
        }

        // 4. File docblock (if not present)
        if (!$hasFileDocblock) {
            $fileName = basename($filePath);
            $result[] = '/**';
            $result[] = ' * ' . $fileName;
            $result[] = ' *';
            $result[] = ' * @package PayCal';
            $result[] = ' */';
            $result[] = '';
        }

        // 5. Rest of the content (skip old header stuff)
        $lines = explode("\n", $content);
        $inHeader = true;
        $bufferedDocblock = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Skip header components
            if ($inHeader) {
                // Skip opening tags
                if (preg_match('/^<\?php/', $trimmed)) continue;
                // Skip old declare statements  
                if (preg_match('/^declare\s*\(/', $trimmed)) continue;
                // Skip namespace declarations
                if (preg_match('/^namespace\s+/', $trimmed)) continue;
                // Skip use statements
                if (preg_match('/^use\s+[^f]/', $trimmed)) continue; // Keep "use function"
                // Skip empty lines at start
                if ($trimmed === '') continue;
                
                // Check if we hit file docblock
                if ($trimmed === '/**') {
                    $bufferedDocblock = [$line];
                    continue;
                }
                if (!empty($bufferedDocblock)) {
                    $bufferedDocblock[] = $line;
                    if (str_contains($line, '*/')) {
                        // Check if it's a file docblock
                        $docContent = implode("\n", $bufferedDocblock);
                        if (str_contains($docContent, '@package')) {
                            // Skip file docblock, we added our own
                            $bufferedDocblock = [];
                        } else {
                            // It's probably a class docblock, keep it
                            foreach ($bufferedDocblock as $dbLine) {
                                $result[] = $dbLine;
                            }
                            $bufferedDocblock = [];
                            $inHeader = false;
                        }
                    }
                    continue;
                }
                
                // We've hit actual code
                $inHeader = false;
            }
            
            // Add class docblock if missing before class/interface/trait
            if (preg_match('/^(final\s+|abstract\s+)?(class|interface|trait|enum)\s+(\w+)/', $trimmed, $classMatch)) {
                // Look back to see if there's a docblock
                $hasClassDoc = false;
                $lookback = min(5, count($result));
                for ($i = 1; $i <= $lookback; $i++) {
                    $prevLine = $result[count($result) - $i] ?? '';
                    if (str_contains($prevLine, '/**') || str_contains($prevLine, '*/')) {
                        $hasClassDoc = true;
                        break;
                    }
                }
                
                if (!$hasClassDoc) {
                    $className = $classMatch[3];
                    $result[] = '/**';
                    $result[] = ' * ' . $className;
                    $result[] = ' */';
                }
            }
            
            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * Find all PayCal classes used in the file
     */
    private function findUsedClasses(string $content, string $currentNamespace): array
    {
        $classes = [];
        
        // Remove comments and strings to avoid false positives
        $code = preg_replace('#/\*.*?\*/#s', '', $content);
        $code = preg_replace('#//.*$#m', '', $code);
        $code = preg_replace('#["\'].*?["\']#s', '', $code);
        
        // Pattern 1: new ClassName
        if (preg_match_all('/new\s+([A-Z]\w+)/', $code, $matches)) {
            foreach ($matches[1] as $class) {
                if (isset($this->paycalClasses[$class])) {
                    $classes[$class] = $this->paycalClasses[$class];
                }
            }
        }

        // Pattern 2: ClassName::method or ClassName::$property
        if (preg_match_all('/([A-Z]\w+)::/', $code, $matches)) {
            foreach ($matches[1] as $class) {
                // Skip reserved words
                if (in_array($class, ['self', 'parent', 'static'])) continue;
                if (isset($this->paycalClasses[$class])) {
                    $classes[$class] = $this->paycalClasses[$class];
                }
            }
        }

        // Pattern 3: Type hints (ClassName $var, function(): ClassName)
        if (preg_match_all('/[:\(,]\s*\\\\?([A-Z]\w+)(?:\s|\)|,|\|)/', $code, $matches)) {
            foreach ($matches[1] as $class) {
                if (isset($this->paycalClasses[$class])) {
                    $classes[$class] = $this->paycalClasses[$class];
                }
            }
        }

        // Pattern 4: instanceof checks
        if (preg_match_all('/instanceof\s+([A-Z]\w+)/', $code, $matches)) {
            foreach ($matches[1] as $class) {
                if (isset($this->paycalClasses[$class])) {
                    $classes[$class] = $this->paycalClasses[$class];
                }
            }
        }

        // Pattern 5: Catch blocks
        if (preg_match_all('/catch\s*\(\s*([A-Z]\w+)/', $code, $matches)) {
            foreach ($matches[1] as $class) {
                if (isset($this->paycalClasses[$class])) {
                    $classes[$class] = $this->paycalClasses[$class];
                }
            }
        }

        return $classes;
    }

    /**
     * Build sorted use statements from used classes
     */
    private function buildUseStatements(array $usedClasses, string $currentNamespace): array
    {
        $statements = [];
        $seen = [];
        
        foreach ($usedClasses as $shortName => $fqcn) {
            // Skip duplicates by FQCN
            if (isset($seen[$fqcn])) {
                continue;
            }
            
            // Don't import if it's in the same namespace
            $classNamespace = '';
            if (strrpos($fqcn, '\\') !== false) {
                $classNamespace = substr($fqcn, 0, strrpos($fqcn, '\\'));
            }
            
            if ($classNamespace && $classNamespace !== $currentNamespace) {
                $statements[] = "use $fqcn;";
                $seen[$fqcn] = true;
            }
        }

        // Sort alphabetically and ensure uniqueness
        $statements = array_unique($statements);
        sort($statements);
        return $statements;
    }

    /**
     * Print statistics
     */
    public function printStats(): void
    {
        echo "\n";
        echo "=================================\n";
        echo "Statistics:\n";
        echo "=================================\n";
        echo "Processed: {$this->stats['processed']}\n";
        echo "Modified:  {$this->stats['modified']}\n";
        echo "Skipped:   {$this->stats['skipped']}\n";
        echo "Errors:    {$this->stats['errors']}\n";
        echo "=================================\n";
    }
}

// Main execution
$baseDir = __DIR__ . '/..';
$fixer = new PhpHeaderFixer();

// Find all non-vendor PHP files
$command = "find $baseDir -name '*.php' -not -path '*/vendor/*' -not -path '*/fonts/*'";
$files = explode("\n", trim(shell_exec($command) ?? ''));

foreach ($files as $file) {
    if (empty($file) || !file_exists($file)) continue;
    $fixer->processFile($file);
}

$fixer->printStats();
