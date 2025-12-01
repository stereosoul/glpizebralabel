<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginGlpizebralabelLabel extends CommonDBTM {

    static $rightname = 'config';

    static function getTypeName($nb = 0) {
        return __('Zebra Label', 'glpizebralabel');
    }

    static function postItemForm($params) {
        $item = $params['item'] ?? null;
        if (!is_object($item) || !$item->getID()) {
            return;
        }

        $supported_types = ['Computer', 'Monitor', 'NetworkEquipment', 'Printer', 'Phone', 'Peripheral'];
        if (!in_array($item->getType(), $supported_types)) {
            return;
        }

        $itemtype = $item->getType();
        $items_id = $item->getID();

        $url = Plugin::getWebDir('glpizebralabel') . "/front/print.php?itemtype=$itemtype&items_id=$items_id";

        echo "<div class='d-inline-block ms-2'>";
        echo "<a href='$url' class='btn btn-sm btn-outline-secondary zebralabel-btn' target='_blank' title='" . __s('Print label for this item', 'glpizebralabel') . "'>";
        echo "<i class='fas fa-print me-1'></i>" . __s('Print label', 'glpizebralabel');
        echo "</a>";
        echo "</div>";
    }

    /**
     * Генерация ZPL для QR-кода (КОМПАКТНЫЙ ВАРИАНТ - QR слева, текст справа)
     */
    static function generateQRZPL($itemtype, $items_id) {
        $item = new $itemtype();
        if (!$item->getFromDB($items_id)) {
            return false;
        }

        $width_pts = 559;
        $height_pts = 240;
        
        $name = $item->fields['name'] ?? 'N/A';
        $serial = $item->fields['serial'] ?? 'N/A';
        $otherserial = $item->fields['otherserial'] ?? 'N/A';
        $id = $item->fields['id'];
        $scan_url = self::getScanUrl($itemtype, $items_id);

        $zpl = "^XA\n";        // Start label
        $zpl .= "^JUS\n";      // Reset all settings to default
        $zpl .= "^LRN\n";      // Reverse print = No
        $zpl .= "^CI28\n";     // UTF-8 encoding
        $zpl .= "^PW{$width_pts}\n"; // Label width
        $zpl .= "^LL{$height_pts}\n"; // Label length
        $zpl .= "^MMT\n";      // Print mode Tear-off
        $zpl .= "^PON\n";      // Print orientation Normal

        // QR слева (компактный)
        $qr_x = 20;
        $qr_y = 20;
        $zpl .= "^FO{$qr_x},{$qr_y}^BQN,4,3^FDQA,{$scan_url}^FS\n";

        // Текст справа от QR - три строки
        $text_x = 220;
        $text_y1 = 40;
        $text_y2 = 65;
        $text_y3 = 90;

        // ID и название
        $id_text = "ID: {$id} " . self::sanitizeZPL($name);
        if (strlen($id_text) > 25) {
            $id_text = substr($id_text, 0, 25);
        }
        $zpl .= "^FO{$text_x},{$text_y1}^A0N,22,22^FD" . $id_text . "^FS\n";

        // Инвентарный номер
        $inv_text = "INV: " . self::sanitizeZPL($otherserial ?: 'N/A');
        if (strlen($inv_text) > 25) {
            $inv_text = substr($inv_text, 0, 25);
        }
        $zpl .= "^FO{$text_x},{$text_y2}^A0N,22,22^FD" . $inv_text . "^FS\n";

        // Серийный номер
        if (!empty($serial)) {
            $serial_text = "SN: " . self::sanitizeZPL($serial);
            if (strlen($serial_text) > 25) {
                $serial_text = substr($serial_text, 0, 25);
            }
            $zpl .= "^FO{$text_x},{$text_y3}^A0N,22,22^FD" . $serial_text . "^FS\n";
        }

        $zpl .= "^PQ1,0,1,Y\n";
        $zpl .= "^XZ\n";

        return $zpl;
    }

    /**
     * Генерация ZPL для штрихкода (НОВЫЙ ФОРМАТ)
     */
    static function generateBarcodeZPL($itemtype, $items_id) {
        $item = new $itemtype();
        if (!$item->getFromDB($items_id)) {
            return false;
        }

        // Фиксированные размеры этикетки
        $width_pts = 559;  // 559 точек (максимум для 70mm)
        $height_pts = 240; // 240 точек (30mm)

        $name = $item->fields['name'] ?? 'N/A';
        $serial = $item->fields['serial'] ?? 'N/A';
        $otherserial = $item->fields['otherserial'] ?? 'N/A';
        $id = $item->fields['id'];

        // Данные для штрихкода
        $barcode_data = !empty($otherserial) ? $otherserial : "GLPI-$itemtype-$id";

        $zpl = "^XA\n";        // Start label
        $zpl .= "^JUS\n";      // Reset all settings to default
        $zpl .= "^LRN\n";      // Reverse print = No
        $zpl .= "^CI28\n";     // UTF-8 encoding
        $zpl .= "^PW{$width_pts}\n"; // Label width
        $zpl .= "^LL{$height_pts}\n"; // Label length
        $zpl .= "^MMT\n";      // Print mode Tear-off
        $zpl .= "^PON\n";      // Print orientation Normal

        // ШТРИХКОД по новому формату
        $barcode_x = 50;
        $barcode_y = 15;
        $barcode_height = 80;
        
        $zpl .= "^FO{$barcode_x},{$barcode_y}^BY2,2,{$barcode_height}^BCN,,Y,N,N^FD" . self::sanitizeZPL($barcode_data) . "^FS\n";

        // ТЕКСТ под штрихкодом по новому формату
        $text_y1 = 140;
        $text_y2 = 165;
        
        // Формируем текст для ID
        $id_text = "ID: {$id} " . self::sanitizeZPL($name);
        // Обрезаем если слишком длинный
        if (strlen($id_text) > 35) {
            $id_text = substr($id_text, 0, 35);
        }
        
        $zpl .= "^FO{$barcode_x},{$text_y1}^A0N,22,22^FD" . $id_text . "^FS\n";

        // Формируем текст для серийного номера
        if (!empty($serial)) {
            $serial_text = "SN: " . self::sanitizeZPL($serial);
            // Обрезаем если слишком длинный
            if (strlen($serial_text) > 35) {
                $serial_text = substr($serial_text, 0, 35);
            }
            $zpl .= "^FO{$barcode_x},{$text_y2}^A0N,22,22^FD" . $serial_text . "^FS\n";
        }

        $zpl .= "^PQ1,0,1,Y\n"; // Настройки печати
        $zpl .= "^XZ\n";       // End label

        return $zpl;
    }

    /**
     * Отправка ZPL кода на принтер
     */
    static function sendToPrinter($zpl, $config) {
        $printer_ip = $config['printer_ip'] ?? '';
        $printer_port = $config['printer_port'] ?? 9100;
        
        if (empty($printer_ip)) {
            return ['success' => false, 'error' => 'Printer IP not configured'];
        }
        
        try {
            // Создаем сокет
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false) {
                return ['success' => false, 'error' => 'Failed to create socket'];
            }
            
            // Устанавливаем таймауты
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
            
            // Подключаемся к принтеру
            $result = socket_connect($socket, $printer_ip, $printer_port);
            if ($result === false) {
                socket_close($socket);
                return ['success' => false, 'error' => "Failed to connect to printer {$printer_ip}:{$printer_port}"];
            }
            
            // Отправляем ZPL код
            $bytes_written = socket_write($socket, $zpl, strlen($zpl));
            if ($bytes_written === false) {
                socket_close($socket);
                return ['success' => false, 'error' => 'Failed to send data to printer'];
            }
            
            socket_close($socket);
            
            return [
                'success' => true, 
                'message' => "Label sent to {$printer_ip}:{$printer_port}",
                'bytes_sent' => $bytes_written
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Print error: ' . $e->getMessage()];
        }
    }

    /**
     * Детальная проверка доступности принтера
     */
    static function detailedPrinterCheck($config) {
        $printer_ip = $config['printer_ip'] ?? '';
        $printer_port = $config['printer_port'] ?? 9100;
        $results = [];
        
        if (empty($printer_ip)) {
            return ['success' => false, 'error' => 'Printer IP not configured'];
        }
        
        // 1. Проверка DNS/разрешения имени
        $results['dns'] = [
            'test' => 'DNS Resolution',
            'status' => 'unknown',
            'message' => ''
        ];
        
        if (filter_var($printer_ip, FILTER_VALIDATE_IP)) {
            $results['dns']['status'] = 'success';
            $results['dns']['message'] = 'Valid IP address';
        } else {
            $ip = gethostbyname($printer_ip);
            if ($ip !== $printer_ip) {
                $results['dns']['status'] = 'success';
                $results['dns']['message'] = "Resolved to: $ip";
            } else {
                $results['dns']['status'] = 'error';
                $results['dns']['message'] = 'DNS resolution failed';
            }
        }
        
        // 2. Проверка пинга (если доступно)
        $results['ping'] = [
            'test' => 'Ping Test',
            'status' => 'unknown', 
            'message' => ''
        ];
        
        if (function_exists('exec')) {
            $command = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
                ? "ping -n 3 -w 2000 " . escapeshellarg($printer_ip)
                : "ping -c 3 -W 2 " . escapeshellarg($printer_ip);
            
            exec("$command 2>&1", $output, $result);
            if ($result === 0) {
                $results['ping']['status'] = 'success';
                $results['ping']['message'] = 'Device responds to ping';
            } else {
                $results['ping']['status'] = 'warning';
                $results['ping']['message'] = 'No ping response (may be firewall)';
            }
        } else {
            $results['ping']['status'] = 'warning';
            $results['ping']['message'] = 'Ping test unavailable (exec disabled)';
        }
        
        // 3. Проверка порта
        $results['port'] = [
            'test' => 'Port 9100 Check',
            'status' => 'unknown',
            'message' => ''
        ];
        
        try {
            $socket = @fsockopen($printer_ip, $printer_port, $errno, $errstr, 5);
            if ($socket) {
                $results['port']['status'] = 'success';
                $results['port']['message'] = "Port $printer_port is open";
                fclose($socket);
                
                // 4. Тестовая отправка ZPL
                $results['zpl'] = [
                    'test' => 'ZPL Communication Test',
                    'status' => 'unknown',
                    'message' => ''
                ];
                
                $test_zpl = "^XA^FO50,50^A0N,25,25^FDConnection Test^FS^XZ";
                $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 3, 'usec' => 0));
                socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));
                
                if (socket_connect($sock, $printer_ip, $printer_port)) {
                    $bytes = socket_write($sock, $test_zpl);
                    if ($bytes > 0) {
                        $results['zpl']['status'] = 'success';
                        $results['zpl']['message'] = "ZPL sent successfully ($bytes bytes)";
                    } else {
                        $results['zpl']['status'] = 'error';
                        $results['zpl']['message'] = 'Failed to send ZPL data';
                    }
                    socket_close($sock);
                } else {
                    $results['zpl']['status'] = 'error';
                    $results['zpl']['message'] = 'Failed to connect for ZPL test';
                }
                
            } else {
                $results['port']['status'] = 'error';
                $results['port']['message'] = "Port $printer_port closed: $errstr ($errno)";
            }
        } catch (Exception $e) {
            $results['port']['status'] = 'error';
            $results['port']['message'] = 'Socket error: ' . $e->getMessage();
        }
        
        // Определяем общий статус
        $has_error = false;
        $has_warning = false;
        foreach ($results as $test) {
            if ($test['status'] === 'error') $has_error = true;
            if ($test['status'] === 'warning') $has_warning = true;
        }
        
        return [
            'success' => !$has_error,
            'has_warnings' => $has_warning,
            'tests' => $results
        ];
    }

    /**
     * Умное разбиение текста на строки
     */
    static function splitTextIntoLines($text, $max_chars_per_line) {
        $words = explode(' ', $text);
        $lines = [];
        $current_line = '';
        
        foreach ($words as $word) {
            // Если добавление слова не превышает лимит
            if (strlen($current_line . ' ' . $word) <= $max_chars_per_line) {
                $current_line .= ($current_line ? ' ' : '') . $word;
            } else {
                // Если текущая строка не пустая, сохраняем ее
                if (!empty($current_line)) {
                    $lines[] = $current_line;
                }
                // Если слово само по себе длиннее лимита - разбиваем принудительно
                if (strlen($word) > $max_chars_per_line) {
                    $chunks = str_split($word, $max_chars_per_line - 3);
                    foreach ($chunks as $chunk) {
                        $lines[] = $chunk;
                    }
                    $current_line = '';
                } else {
                    $current_line = $word;
                }
            }
        }
        
        // Добавляем последнюю строку
        if (!empty($current_line)) {
            $lines[] = $current_line;
        }
        
        // Ограничиваем максимум 3 строками
        return array_slice($lines, 0, 3);
    }

    /**
     * Генерация URL для сканирования
     */
    static function getScanUrl($itemtype, $items_id) {
        global $CFG_GLPI;
        
        $base_url = rtrim($CFG_GLPI['url_base'], '/');
        $plugin_path = ltrim(Plugin::getWebDir('glpizebralabel', false), '/');
        
        return $base_url . '/' . $plugin_path . "/front/scan.php?itemtype=$itemtype&items_id=$items_id";
    }

    /**
     * Улучшенная санитизация ZPL - экранируем все спецсимволы
     */
    static function sanitizeZPL($string) {
        if (empty($string)) {
            return 'N/A';
        }
        
        // Экранируем все специальные символы ZPL
        $special_chars = ['^', '~', '\\', '_', '`', '{', '}', '[', ']', '|', '<', '>'];
        $replacements = [
            '^' => '\\^',  // Экранирование каретки
            '~' => '\\~',  // Экранирование тильды
            '\\' => '\\\\', // Экранирование обратного слеша
            '_' => ' ',    // Замена подчеркивания на пробел
            '`' => "'",    // Замена обратной кавычки
            '{' => '(',    // Замена фигурных скобок
            '}' => ')',
            '[' => '(',
            ']' => ')',
            '|' => 'I',    // Замена вертикальной черты
            '<' => '(',    // Замена угловых скобок
            '>' => ')'
        ];
        
        $string = str_replace(array_keys($replacements), array_values($replacements), $string);
        
        // Удаляем непечатаемые символы
        $string = preg_replace('/[^\x20-\x7E]/', '', $string);
        
        return trim($string);
    }
}
?>