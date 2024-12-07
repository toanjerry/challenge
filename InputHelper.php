<?php

    const END_LINE = ["\r\n", "\n", "\r"];
    const NUM_REGEX = '/[-+]?\d+/';
    const STR_REGEX = '/[A-z]*/';
    const CHAR_REGEX = '/[0-9A-z]*/';

    class InputHelper {
        const WRAP_MAP = [
            '\(' => '\)',
            '\[' => '\]',
            '{' => '}',
            '<' => '>',
            '"' => '"',
            "'" => "'",
        ];

        public static function getValues ($str, $pattern = '') {
            if (!$str) {
                return [];
            }

            preg_match_all($pattern, $str, $matches);

            return $matches[0];
        }

        public static function getNumbers ($str) {

            $values = self::getValues($str, NUM_REGEX);

            return array_map('floatval', $values);
        }

        public static function getMaps ($str, $key_pattern = STR_REGEX, $val_pattern = NUM_REGEX, $revert = false) {
            preg_match_all("/({$key_pattern})\s*({$val_pattern})/", $str, $matches);

            $rs = [];
            if ($revert) {
                foreach($matches[2] ?? [] as $idx => $key) {
                    if (!$key) {
                        $key = $idx;
                    }
                    $rs[$key] = $matches[1][$idx] ?? 0;
                };
            } else {
                foreach($matches[1] ?? [] as $idx => $key) {
                    if (!$key) {
                        $key = $idx;
                    }
                    $rs[$key] = $matches[2][$idx] ?? 0;
                };
            }

            return $rs;
        }

        public static function getUnits ($str, $val_pattern = NUM_REGEX, $unit_pattern = STR_REGEX) {
            return self::getMaps($str, $val_pattern, $unit_pattern, true);
        }

        public static function getValueWrapped ($str, $w_open = '\(', $w_close = null) {
            if (!$w_close) {
                $w_close = self::WRAP_MAP[$w_open] ?? $w_open;
            }

            preg_match_all("/{$w_open}([^{$w_open}{$w_close}]+){$w_close}/", $str, $matches);

            return $matches[1][0] ?? "";
        }

        public static function parseString ($str, $parser) {
            if (is_callable($parser)) {
                return ['value' => $parser($str)];
            }

            if (count($parser['wrapped'] ?? []) > 0) {
                $str = InputHelper::getValueWrapped($str, $parser['wrapped'][0], $parser['wrapped'][1] ?? null);
            }

            $key_builder = is_callable($parser['key'] ?? null) ? $parser['key'] : null;
            $value_builder = is_callable($parser['value'] ?? null) ? $parser['value'] : null;

            $sep = $parser['sep'] ?? null;
            if ($sep === null) {
                return [
                    'value' => $value_builder ? $value_builder($str) : $str,
                    'key' => $key_builder ? $key_builder([$str]) : null
                ];
            }

            if (is_array($sep)) {
                $sep = implode('|', $sep);
            }

            $value = [$str];
            if ($sep) {
                $value = preg_split("/($sep)/", $str);
            } else {
                $value = str_split($str);
            }

            $rs = [
                'key' => $key_builder ? $key_builder($value) : null,
                'value' => [],
            ];

            if ($value_builder) {
                $value = $value_builder($value);
            }
            if (is_array($value)) {
                foreach ($value as $idx => $val) {
                    if (!$val && !is_numeric($val)) {
                        continue;
                    }
                    $parser_el = $parser["parser_{$idx}"] ?? $parser["parser"] ?? null;
                    if ($parser_el) {
                        $val = self::parseString($val, $parser_el);
                        $key = $val['key'] ?? $idx;
                        $val = $val['value'];
                    }

                    if ($val === null) {
                        continue;
                    }

                    $rs['value'][$key ?? $idx] = $val;
                }
            } else {
                $parser_el = $parser["parser"] ?? null;
                if ($parser_el) {
                    $obj = self::parseString($value, $parser_el);
                    $key = $obj['key'] ?? null;
                    $value = $obj['value'] ?? $value;
                }

                if (isset($key)) {
                    $rs['value'][$key] = $value;
                } else {
                    $rs['value'] = $value;
                }
            }

            return $rs;
        }
    }

?>
