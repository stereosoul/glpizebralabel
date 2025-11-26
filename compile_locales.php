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
            $inMsgid = true;
            $inMsgstr = false;
            $msgid = substr($line, 7, -1);
        } elseif (strpos($line, 'msgstr "') === 0) {
            $inMsgid = false;
            $inMsgstr = true;
            $msgstr = substr($line, 8, -1);
        } elseif ($inMsgid && strpos($line, '"') === 0) {
            $msgid .= substr($line, 1, -1);
        } elseif ($inMsgstr && strpos($line, '"') === 0) {
            $msgstr .= substr($line, 1, -1);
        } elseif (empty($line) && $msgid !== '') {
            if ($msgid !== '') {
                $messages[$msgid] = $msgstr;
            }
            $msgid = '';
            $msgstr = '';
            $inMsgid = false;
            $inMsgstr = false;
        }
    }
    
    // Save as .mo file (simplified version)
    $moContent = "";
    foreach ($messages as $id => $str) {
        if (!empty($id)) {
            $moContent .= $id . "=" . $str . "\n";
        }
    }
    
    file_put_contents($moFile, $moContent);
    echo "Compiled: $poFile -> $moFile\n";
    return true;
}

// Compile all locales
$locales = ['ru_RU', 'en_GB'];
foreach ($locales as $locale) {
    $poFile = "locales/{$locale}.po";
    $moFile = "locales/{$locale}.mo";
    compilePoToMo($poFile, $moFile);
}

echo "Locale compilation completed!\n";