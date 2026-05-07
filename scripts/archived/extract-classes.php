<?php
/**
 * Extract Classes, Methods, and Docblocks from PHP files
 * Generates a comprehensive markdown documentation file
 */

$workspaceDir = '/private/var/www/paycal/dev/html';
$outputFile = '/private/var/www/paycal/dev/CLASSES_MASTER_DOCUMENTATION.md';

// Find all PHP files in Classes, Controllers, and Enums directories
$directories = [
    $workspaceDir . '/Classes',
    $workspaceDir . '/Controllers',
    $workspaceDir . '/Enums',
];

$documentation = [];
$classFiles = [];

// Gather all PHP files
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $classFiles[] = $file->getRealPath();
            }
        }
    }
}

sort($classFiles);

// Parse each file
foreach ($classFiles as $filePath) {
    $relPath = str_replace($workspaceDir . '/', '', $filePath);
    $content = file_get_contents($filePath);
    
    $fileDoc = [
        'path' => $relPath,
        'fileDocblock' => extractFileDocblock($content),
        'classes' => extractClasses($content),
    ];
    
    $documentation[] = $fileDoc;
}

// Generate markdown
$markdown = generateMarkdown($documentation);

// Write to file
file_put_contents($outputFile, $markdown);
echo "Documentation generated: $outputFile\n";

function extractFileDocblock($content) {
    $pattern = '/^<\?php\s*\/\*\*(.*?)\*\//s';
    if (preg_match($pattern, $content, $matches)) {
        $docblock = trim($matches[1]);
        // Clean up the docblock
        $docblock = preg_replace('/^\s*\*\s?/m', '', $docblock);
        $docblock = trim($docblock);
        return $docblock;
    }
    return '';
}

function extractClasses($content) {
    $classes = [];
    
    // Match class definitions (including abstract, final, etc.)
    $pattern = '/(?:abstract\s+|final\s+)?class\s+(\w+)(?:\s+extends\s+(\w+))?(?:\s+implements\s+([\w\\, \s]+))?\s*\{/i';
    
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $i => $classMatch) {
            $className = $classMatch[0];
            $parent = $matches[2][$i][0] ?? null;
            $implements = $matches[3][$i][0] ?? null;
            
            // Find the class docblock
            $classPos = $classMatch[1];
            $docblock = findDocblockBefore($content, $classPos);
            
            // Extract methods
            $methods = extractMethods($content, $classPos);
            
            $classes[] = [
                'name' => $className,
                'extends' => $parent ?: null,
                'implements' => $implements ? explode(',', $implements) : [],
                'docblock' => $docblock,
                'methods' => $methods,
            ];
        }
    }
    
    // Also look for interfaces
    $pattern = '/(?:abstract\s+)?interface\s+(\w+)(?:\s+extends\s+([\w\\, \s]+))?\s*\{/i';
    
    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $i => $classMatch) {
            $className = $classMatch[0];
            $parent = $matches[2][$i][0] ?? null;
            
            $classPos = $classMatch[1];
            $docblock = findDocblockBefore($content, $classPos);
            $methods = extractMethods($content, $classPos, true);
            
            $classes[] = [
                'name' => $className,
                'type' => 'interface',
                'extends' => $parent ? explode(',', $parent) : [],
                'docblock' => $docblock,
                'methods' => $methods,
            ];
        }
    }
    
    return $classes;
}

function findDocblockBefore($content, $pos) {
    // Look backwards from position for /** ... */
    $searchStart = max(0, $pos - 1500);
    $searchContent = substr($content, $searchStart, $pos - $searchStart);
    
    $pattern = '/\/\*\*(.*?)\*\/\s*$/s';
    if (preg_match($pattern, $searchContent, $matches)) {
        $docblock = trim($matches[1]);
        $docblock = preg_replace('/^\s*\*\s?/m', '', $docblock);
        $docblock = trim($docblock);
        return $docblock;
    }
    
    return '';
}

function extractMethods($content, $classStart, $isInterface = false) {
    $methods = [];
    
    // Find the class body
    $openBrace = strpos($content, '{', $classStart);
    if ($openBrace === false) return [];
    
    $closeBrace = findMatchingBrace($content, $openBrace);
    if ($closeBrace === false) return [];
    
    $classBody = substr($content, $openBrace + 1, $closeBrace - $openBrace - 1);
    
    // Extract method signatures
    $pattern = '/(?:public\s+|private\s+|protected\s+|static\s+)*(?:function|const)\s+(\w+)\s*\((.*?)\)/s';
    
    if (preg_match_all($pattern, $classBody, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $i => $methodMatch) {
            $methodName = $methodMatch[0];
            $params = trim($matches[2][$i][0]);
            $methodPos = $methodMatch[1];
            
            // Find docblock for this method
            $docblock = findDocblockBefore($classBody, $methodPos);
            
            // Extract return type if present
            $methodFullText = substr($classBody, max(0, $methodPos - 100), 300);
            $returnType = '';
            if (preg_match('/:\s*([\w\[\]\\|]+)\s*(?:\{|;)/', $methodFullText, $typeMatch)) {
                $returnType = $typeMatch[1];
            }
            
            $methods[] = [
                'name' => $methodName,
                'params' => $params,
                'returnType' => $returnType,
                'docblock' => $docblock,
            ];
        }
    }
    
    return $methods;
}

function findMatchingBrace($content, $openPos) {
    $count = 1;
    $pos = $openPos + 1;
    
    while ($pos < strlen($content) && $count > 0) {
        if ($content[$pos] === '{') $count++;
        elseif ($content[$pos] === '}') $count--;
        $pos++;
    }
    
    return $count === 0 ? $pos - 1 : false;
}

function generateMarkdown($documentation) {
    $md = "# Classes, Methods, and Docblocks - Master Documentation\n\n";
    $md .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $md .= "## Table of Contents\n\n";
    
    // Generate TOC
    foreach ($documentation as $file) {
        if (!empty($file['classes'])) {
            $md .= "- [" . $file['path'] . "](#" . sanitizeAnchor($file['path']) . ")\n";
            foreach ($file['classes'] as $class) {
                $md .= "  - " . $class['name'] . "\n";
            }
        }
    }
    
    $md .= "\n---\n\n";
    
    // Generate detailed documentation
    foreach ($documentation as $file) {
        if (empty($file['classes'])) continue;
        
        $md .= "## " . $file['path'] . "\n\n";
        
        if (!empty($file['fileDocblock'])) {
            $md .= "**File Documentation:**\n\n";
            $md .= "```\n" . $file['fileDocblock'] . "\n```\n\n";
        }
        
        foreach ($file['classes'] as $class) {
            $md .= "### " . $class['name'] . "\n\n";
            
            if (isset($class['type']) && $class['type'] === 'interface') {
                $md .= "**Type:** Interface\n\n";
            } else {
                $md .= "**Type:** Class\n\n";
            }
            
            if (!empty($class['extends'])) {
                $md .= "**Extends:** " . $class['extends'] . "\n\n";
            }
            
            if (!empty($class['implements'])) {
                $md .= "**Implements:** " . implode(', ', array_map('trim', $class['implements'])) . "\n\n";
            }
            
            if (!empty($class['docblock'])) {
                $md .= "**Docblock:**\n\n";
                $md .= "```\n" . $class['docblock'] . "\n```\n\n";
            }
            
            if (!empty($class['methods'])) {
                $md .= "#### Methods\n\n";
                
                foreach ($class['methods'] as $method) {
                    $signature = $method['name'] . "(" . $method['params'] . ")";
                    if (!empty($method['returnType'])) {
                        $signature .= ": " . $method['returnType'];
                    }
                    
                    $md .= "##### `" . $signature . "`\n\n";
                    
                    if (!empty($method['docblock'])) {
                        $md .= "```\n" . $method['docblock'] . "\n```\n\n";
                    }
                }
            }
        }
        
        $md .= "---\n\n";
    }
    
    return $md;
}

function sanitizeAnchor($text) {
    return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
}
