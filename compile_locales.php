<?php
/**
 * Script for compiling .po to .mo files
 * Run: php compile_locales.php
 */

function compilePoToMo($poFile, $moFile) {
    if (!file_exists($poFile)) {
        echo "Error: File $poFile not found\n";
        return false;
    }
    
    $poContent = file_get_contents($poFile);
    $lines = explode("\n", $poContent);
    
    $messages = [];
    $msgid = '';
    $msgstr = '';
    $inMsgid = false;
    $inMsgstr = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (strpos($line, 'msgid "') === 0) {
            // Save previous message if exists
            if ($msgid !== '' && $msgstr !== '') {
                $messages[$msgid] = $msgstr;
            }
            
            $inMsgid = true;
            $inMsgstr = false;
            $msgid = substr($line, 7, -1);
            $msgstr = '';
        } elseif (strpos($line, 'msgstr "') === 0) {
            $inMsgid = false;
            $inMsgstr = true;
            $msgstr = substr($line, 8, -1);
        } elseif ($inMsgid && strpos($line, '"') === 0) {
            $msgid .= substr($line, 1, -1);
        } elseif ($inMsgstr && strpos($line, '"') === 0) {
            $msgstr .= substr($line, 1, -1);
        } elseif (empty($line)) {
            // End of message
            if ($msgid !== '' && $msgstr !== '') {
                $messages[$msgid] = $msgstr;
            }
            $msgid = '';
            $msgstr = '';
            $inMsgid = false;
            $inMsgstr = false;
        }
    }
    
    // Don't forget the last message
    if ($msgid !== '' && $msgstr !== '') {
        $messages[$msgid] = $msgstr;
    }
    
    // Simple text format that GLPI can read
    $moContent = "";
    foreach ($messages as $id => $str) {
        if (!empty($id)) {
            $moContent .= "$id=$str\n";
        }
    }
    
    if (file_put_contents($moFile, $moContent)) {
        echo "✓ Compiled: $poFile -> $moFile\n";
        return true;
    } else {
        echo "✗ Failed to write: $moFile\n";
        return false;
    }
}

// Compile all locales
echo "Compiling locales for GLPI Zebra Label plugin...\n\n";

$locales = ['ru_RU', 'en_GB'];
$success_count = 0;

foreach ($locales as $locale) {
    $poFile = "locales/{$locale}.po";
    $moFile = "locales/{$locale}.mo";
    
    if (file_exists($poFile)) {
        if (compilePoToMo($poFile, $moFile)) {
            $success_count++;
        }
    } else {
        echo "✗ File not found: $poFile\n";
    }
    echo "\n";
}

if ($success_count === count($locales)) {
    echo "🎉 All locales compiled successfully!\n";
} else {
    echo "⚠️  Some locales failed to compile. Success: $success_count/" . count($locales) . "\n";
}

// Check file sizes
echo "\nFile sizes:\n";
foreach ($locales as $locale) {
    $moFile = "locales/{$locale}.mo";
    if (file_exists($moFile)) {
        $size = filesize($moFile);
        echo "  $moFile: " . number_format($size) . " bytes\n";
    }
}
?>