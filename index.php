<?php

	class InputHelper {
		const WRAP_MAP = [
			'\(' => '\)',
			'\[' => '\]',
			'{' => '}',
			'<' => '>',
			'"' => '"',
			"'" => "'",
		];

		const NEW_LINE = ["\r\n", "\n", "\r"];

		public static function getValues ($str, $pattern = '') {
			if (!$str) {
				return [];
			}

			preg_match_all($pattern, $str, $matches);

			return $matches[0];
		}

		public static function getNumbers ($str) {

			$values = self::getValues($str, '/[-|+]?\d+/');

			return array_map('floatval', $values);
		}

		public static function getUnits ($str, $val_pattern = '[-|+]?[.\d]+', $unit_pattern = '[A-z]*') {
			preg_match_all("/({$val_pattern})\s*({$unit_pattern})/", $str, $matches);

			$rs = [];
			foreach($matches[2] ?? [] as $idx => $key) {
				if (!$key) {
					$key = $idx;
				}
				$rs[$key] = $matches[1][$idx] ?? 0;
			};

			return $rs;
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

	class Adventofcode {

		private $s_key = '';

		public $day = 1;
		public $level = 1;

		public $domain = 'https://adventofcode.com/2024';

		public $input = [];

		public $result = null;

		public function __construct ($s_key, $day, $level) {
			$this->s_key = $s_key;
			$this->day = $day;
			$this->level = $level;

			echo "Day: $this->day - $this->level\n";
		}

		public function getInputFromServer ($parser = null) {
			// get input from server
			$path = "day/{$this->day}/input";
			$url = "{$this->domain}/$path";

			$curl_session = curl_init($url);
			curl_setopt($curl_session, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_session, CURLOPT_HTTPHEADER, array("Cookie: session={$this->s_key}"));

			$raw_input = curl_exec($curl_session);
			$http_code = curl_getinfo($curl_session, CURLINFO_HTTP_CODE);
			curl_close($curl_session);

			if ($http_code != 200) {
				echo "Day {$this->day}: Link input is invalid";
				exit();
			}

			$this->setInput($raw_input, $parser);

			return $this;
		}

		public function setInput ($raw_input, $parser = null) {
			// parse input
			$this->input = $this->parseInput($raw_input, $parser)['value'];

			// echo some data support check by eye
			echo('--------------------------');
			echo("\nnum row: " . count($this->input));
			echo("\nstart: ");
			echo json_encode(reset($this->input));
			echo("\nend: ");
			echo json_encode(end($this->input));
			echo("\n--------------------------");

			return $this;
		}

		private function parseInput ($str, $parser = null) {
			if (!$str) {
				return [];
			}

			if ($parser && is_callable($parser)) {
				return $parser($str);
			}

			if (!is_array($parser)) {
				return $str;
			}

			if (empty($parser['sep'])) {
				$parser['sep'] = InputHelper::NEW_LINE;
			}

			return InputHelper::parseString($str, $parser);
		}

		public function resolve (Callable $cb) {
			if (!$cb || !is_callable($cb)) {
				echo "\nInvalid resolver";
				exit();
			}

			$this->result = $cb($this->input);

			echo "\nResult: ";
			echo json_encode($this->result);
			echo "\n";

			return $this;
		}

		public function submit () {
			if (is_null($this->result)) {
				echo "\nInvalid day";
				return;
			}
			$path = "day/{$this->day}/submit";
			$url = "{$this->domain}/$path";

			$curl_session = curl_init($url);
			curl_setopt($curl_session, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_session, CURLOPT_HTTPHEADER, array("Cookie: session={$this->s_key}"));

			$content = curl_exec($curl_session);
			curl_close($curl_session);

		}
	}

	$day = intval($_SERVER['argv'][1] ?? null);
	if (!is_numeric($day)) {
		echo "\nInvalid day";
		exit();
	}

	$level = intval($_SERVER['argv'][2] ?? null);
	if (!$level) {
		$level = 1;
	}

	if (!is_numeric($level) || $level > 2) {
		echo "\nInvalid level";
		exit();
	}

	$s_key = '53616c7465645f5fad0f59e9eb5a7b720981a3e5dd7fefe4e30dec53a59f0d838f9fb66cc71fe8fa89755f74fffec68910db125989074aa0ae6a4f56e9ba235a';

	// config paser and resolver input
	$resolvers = [
		// day => resolver
		'1_1' => [
			'parser' => [
				'parser' => "InputHelper::getNumbers",
			],
			'resolver' => function ($input) {
				$sum = 0;
				for ($i = 0; $i < count($input) - 1; $i++) {
					for ($j = $i; $j < count($input); $j++) {
						if ($input[$i][0] > $input[$j][0]) {
							$temp = $input[$i][0];
							$input[$i][0] = $input[$j][0];
							$input[$j][0] = $temp;
						}
						if ($input[$i][1] > $input[$j][1]) {
							$temp = $input[$i][1];
							$input[$i][1] = $input[$j][1];
							$input[$j][1] = $temp;
						}
					}
					$sum += abs($input[$i][0] - $input[$i][1]);
				}

				$sum += abs($input[count($input) - 1][0] - $input[count($input) - 1][1]);

				return $sum;
			},
		],
		'1_2' => [
			'parser' => [
				'parser' => "InputHelper::getNumbers",
			],
			'resolver' => function ($input) {
				$rs = 0;
				$hash = [];
				for ($i = 0; $i < count($input); $i++) {
					if (isset($hash[$input[$i][1]])) {
						$hash[$input[$i][1]]++;
					} else {
						$hash[$input[$i][1]] = 1;
					}
				}

				for ($i = 0; $i < count($input); $i++) {
					$rs += $input[$i][0] * ($hash[$input[$i][0]] ?? 0);
				}

				return $rs;
			},
		],
		'2_1' => [
			'parser' => [
				'parser' => "InputHelper::getNumbers",
			],
			'resolver' => function ($input) {
				$rs = 0;

				foreach ($input as $line) {
					$is_safe = true;
					for ($i = 1; $i < count($line) - 1; $i++) {
						$sub_1 = $line[$i] - $line[$i-1];
						$sub_2 = $line[$i+1] - $line[$i];

						if (($sub_1 * $sub_2) < 0) {
							$is_safe = false;
							break;
						}
						if (abs($sub_1) > 3 || abs($sub_1) < 1 || abs($sub_2) > 3 || abs($sub_2) < 1) {
							$is_safe = false;
							break;
						}
					}

					if ($is_safe) {
						$rs++;
					}
				}

				return $rs;
			},
		],
		'2_2' => [
			'parser' => [
				'parser' => "InputHelper::getNumbers",
			],
			'resolver' => function ($input) {
				$rs = 0;

				$check_safe = function ($line) {
					for ($i = 1; $i < count($line) - 1; $i++) {
						$sub_1 = $line[$i] - $line[$i-1];
						$sub_2 = $line[$i+1] - $line[$i];

						if (($sub_1 * $sub_2) < 0) {
							return false;
						}
						if (abs($sub_1) > 3 || abs($sub_1) < 1 || abs($sub_2) > 3 || abs($sub_2) < 1) {
							return false;
						}
					}

					return true;
				};

				foreach ($input as $line) {
					if ($check_safe($line)) {
						$rs++;
						continue;
					}

					$is_safe = false;
					for ($j = 0; $j < count($line); $j++) {
						$arr = $line;
						unset($arr[$j]);
						
						if ($check_safe(array_values($arr))) {
							$is_safe = true;
							break;
						}
					}

					if ($is_safe) {
						$rs++;
					}
				}

				return $rs;
			},
		],
		'3_1' => [
			'parser' => [
				'sep' => "\n\n",
				'value' => function ($val) {
					$pattern = '/mul\(\d{1,3},\d{1,3}\)/';
					return InputHelper::getValues($val[0], $pattern);
				},
				'parser' => "InputHelper::getNumbers",
			],
			'resolver' => function ($input) {
				$rs = 0;

				foreach ($input as $mul) {
					$rs += $mul[0] * $mul[1];
				}

				return $rs;
			},
		],
		'3_2' => [
			'parser' => [
				'sep' => "do\(\)",
				'parser' => [
					'sep' => "don't\(\)",
					'value' => function ($val) {
						$pattern = '/mul\(\d{1,3},\d{1,3}\)/';
						return InputHelper::getValues($val[0], $pattern);
					},
					'parser' => "InputHelper::getNumbers",
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				foreach ($input as $do) {
					foreach ($do as $mul) {
						$rs += $mul[0] * $mul[1];
					}
				}

				return $rs;
			},
		],
		'4_1' => [
			'parser' => [
				'parser' => [
					'sep' => ':',
					'key' => function ($value) {
						return InputHelper::getNumbers($value[0])[0] ?? 0;
					},
					'value' => function ($value) {
						return $value[1];
					},
					'parser' => [
						'sep' => '\|',
						'parser' => function ($str) {
							return InputHelper::getNumbers($str);
						}
					]
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
	];

	$challenge = new Adventofcode($s_key, $day, $level);
	$parser = $resolvers["{$day}_{$level}"]['parser'] ?? null;
	$resolver = $resolvers["{$day}_{$level}"]['resolver'] ?? null;
	$challenge->getInputFromServer($parser)->resolve($resolver);

	// $challenge->submit();

?>
