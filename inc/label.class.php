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
     * Генерация ZPL для QR-кода (QR слева увеличенный, текст справа с оптимизированным шрифтом)
     */
    static function generateQRZPL($itemtype, $items_id) {
        $item = new $itemtype();
        if (!$item->getFromDB($items_id)) {
            return false;
        }

        // Фиксированные размеры этикетки (70x30mm в точках: 8 точек на мм)
        $width_pts = 559;  // 559 точек (максимум для 70mm)
        $height_pts = 30 * 8; // 240 точек

        $scan_url = self::getScanUrl($itemtype, $items_id);
        $otherserial = $item->fields['otherserial'] ?? 'N/A';

        // Корректный ZPL код
        $zpl = "^XA\n";        // Start label
        $zpl .= "^CI28\n";     // UTF-8 encoding
        $zpl .= "^PW{$width_pts}\n"; // Label width
        $zpl .= "^LL{$height_pts}\n"; // Label length
        $zpl .= "^MMT\n";      // Print mode Tear-off

        // УВЕЛИЧЕННЫЙ QR код слева
        $qr_size = 4;
        $qr_x = 20;
        $qr_y = 20;

        $zpl .= "^FO{$qr_x},{$qr_y}^BQN,{$qr_size},4^FDQA,{$scan_url}^FS\n";

        // Текст справа с ОПТИМИЗИРОВАННЫМ ШРИФТОМ (меньше и менее жирный)
        if (!empty($otherserial)) {
            $display_inv = self::sanitizeZPL($otherserial);

            // Максимальная ширина текстовой области: 559 - 300 = 259 точек
            $max_chars_per_line = 20; // Немного увеличил из-за меньшего шрифта

            // Умный перенос - разбиваем по словам если возможно
            $lines = self::splitTextIntoLines($display_inv, $max_chars_per_line);

            $text_x = 300; // Фиксированная позиция справа

            // Распределяем строки по вертикали с ОПТИМИЗИРОВАННЫМ ШРИФТОМ
            if (count($lines) == 1) {
                // Одна строка - крупный, но не жирный шрифт
                $text_y = 75;
                $zpl .= "^FO{$text_x},{$text_y}^A0N,26,20^FB259,1,0,C^FD{$lines[0]}^FS\n";
            } else if (count($lines) == 2) {
                // Две строки - средний шрифт
                $text_y1 = 65;
                $text_y2 = 90;
                $zpl .= "^FO{$text_x},{$text_y1}^A0N,22,18^FB259,1,0,C^FD{$lines[0]}^FS\n";
                $zpl .= "^FO{$text_x},{$text_y2}^A0N,22,18^FB259,1,0,C^FD{$lines[1]}^FS\n";
            } else if (count($lines) >= 3) {
                // Три и более строк - компактный шрифт
                $text_y_start = 55;
                $line_height = 20;
                foreach ($lines as $i => $line) {
                    $text_y = $text_y_start + ($i * $line_height);
                    $zpl .= "^FO{$text_x},{$text_y}^A0N,18,15^FB259,1,0,C^FD{$line}^FS\n";
                }
            }
        }

        $zpl .= "^XZ\n";       // End label

        return $zpl;
    }

    /**
     * Генерация ZPL для штрихкода (НОВЫЙ ФОРМАТ - как в примере)
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
        $zpl .= "^CI28\n";     // UTF-8 encoding
        $zpl .= "^PW{$width_pts}\n"; // Label width
        $zpl .= "^LL{$height_pts}\n"; // Label length
        $zpl .= "^MMT\n";      // Print mode Tear-off

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