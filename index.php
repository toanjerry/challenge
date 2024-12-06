<?php

	function array_swap (&$arr, $key_1, $key_2) {
		$temp = $arr[$key_1];
		$arr[$key_1] = $arr[$key_2];
		$arr[$key_2] = $temp;
	}

	const END_LINE = ["\r\n", "\n", "\r"];
	const NUM_REGEX = '[-+]?\d+';
	const STR_REGEX = '[A-z]*';
	const CHAR_REGEX = '[0-9A-z]*';

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

	class Adventofcode {

		private $s_key = '';

		public $year = 2024;
		public $day = 1;
		public $level = 1;

		public $domain = 'https://adventofcode.com';

		public $input = [];

		public $result = null;

		public function __construct ($s_key, $day, $level, $year = 2024) {
			$this->s_key = $s_key;
			$this->day = $day;
			$this->level = $level;
			$this->year = $year;

			echo "Day: $this->day - $this->level\n";
		}

		public function getInputFromServer ($parser = null) {
			if ($this->input) {
				return $this;
			}
			// get input from server
			$path = "{$this->year}/day/{$this->day}/input";
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
			// echo("\nstart: ");
			// echo json_encode(reset($this->input));
			// echo("\nend: ");
			// echo json_encode(end($this->input));
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
				$parser['sep'] = END_LINE;
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
			$path = "{$this->year}/day/{$this->day}/submit";
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
					'sep' => '',
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				$check_match = function ($x, $y) use ($input) {
					$match_x1 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y][$x+$idx+1] ?? null) !== $c) {
							$match_x1 = 0;
							break;
						}
					}

					$match_x2 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y][$x-$idx-1] ?? null) !== $c) {
							$match_x2 = 0;
							break;
						}
					}

					$match_y1 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y+$idx+1][$x] ?? null) !== $c) {
							$match_y1 = 0;
							break;
						}
					}

					$match_y2 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y-$idx-1][$x] ?? null) !== $c) {
							$match_y2 = 0;
							break;
						}
					}

					$match_c1 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y-$idx-1][$x-$idx-1] ?? null) !== $c) {
							$match_c1 = 0;
							break;
						}
					}

					$match_c2 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y+$idx+1][$x+$idx+1] ?? null) !== $c) {
							$match_c2 = 0;
							break;
						}
					}

					$match_c3 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y-$idx-1][$x+$idx+1] ?? null) !== $c) {
							$match_c3 = 0;
							break;
						}
					}

					$match_c4 = 1;
					foreach (['M', 'A', 'S'] as $idx => $c) {
						if (($input[$y+$idx+1][$x-$idx-1] ?? null) !== $c) {
							$match_c4 = 0;
							break;
						}
					}

					return $match_c1 + $match_c2 + $match_c3 + $match_c4 + $match_y1 + $match_x1 + $match_y2 + $match_x2;
				};

				foreach ($input as $y => $line) {
					foreach ($line as $x => $c) {
						if ($c !== 'X') {
							continue;
						}

						$rs += $check_match($x, $y);
					}
				}

				return $rs;
			},
		],
		'4_2' => [
			'parser' => [
				'parser' => [
					'sep' => '',
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				$check_x_match = function ($x, $y) use ($input) {
					if (($input[$y+1][$x+1] ?? null) === 'S'
					&& ($input[$y+1][$x-1] ?? null) === 'S'
					&& ($input[$y-1][$x+1] ?? null) === 'M'
					&& ($input[$y-1][$x-1] ?? null) === 'M') {
						return true;
					}

					if (($input[$y-1][$x+1] ?? null) === 'S'
					&& ($input[$y-1][$x-1] ?? null) === 'S'
					&& ($input[$y+1][$x+1] ?? null) === 'M'
					&& ($input[$y+1][$x-1] ?? null) === 'M') {
						return true;
					}

					if (($input[$y-1][$x+1] ?? null) === 'S'
					&& ($input[$y+1][$x+1] ?? null) === 'S'
					&& ($input[$y-1][$x-1] ?? null) === 'M'
					&& ($input[$y+1][$x-1] ?? null) === 'M') {
						return true;
					}

					if (($input[$y-1][$x-1] ?? null) === 'S'
					&& ($input[$y+1][$x-1] ?? null) === 'S'
					&& ($input[$y-1][$x+1] ?? null) === 'M'
					&& ($input[$y+1][$x+1] ?? null) === 'M') {
						return true;
					}

					return false;
				};

				foreach ($input as $y => $line) {
					foreach ($line as $x => $c) {
						if ($c !== 'A') {
							continue;
						}

						if ($check_x_match($x, $y)) {
							$rs++;
						};
					}
				}

				return $rs;
			},
		],
		'5_1' => [
			'parser' => [
				'sep' => "\n\n",
				'parser_0' => [
					'sep' => END_LINE,
					'parser' => "InputHelper::getNumbers",
				],
				'parser_1' => [
					'sep' => END_LINE,
					'parser' => "InputHelper::getNumbers",
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				$check_in_rule = function ($arr, $rule) {
					$key_1 = array_search($rule[0], $arr);
					if ($key_1 === false) {
						return true;
					}
					$key_2 = array_search($rule[1], $arr);
					if ($key_2 === false) {
						return true;
					}

					return $key_1 <= $key_2;
				};

				foreach ($input[1] as $pages) {
					$in_rule = true;
					foreach ($input[0] as $rule) {
						if (!$check_in_rule($pages, $rule)) {
							$in_rule = false;
							break;
						}
					}

					if ($in_rule) {
						$rs += $pages[(count($pages)-1)/2];
					}
				}

				return $rs;
			},
		],
		'5_2' => [
			'parser' => [
				'sep' => "\n\n",
				'parser_0' => [
					'sep' => END_LINE,
					'parser' => "InputHelper::getNumbers",
				],
				'parser_1' => [
					'sep' => END_LINE,
					'parser' => "InputHelper::getNumbers",
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				$check_rule = function ($arr, $rule, &$in_rule) {
					$idx_0 = array_search($rule[0], $arr);
					$idx_1 = array_search($rule[1], $arr);
					if ($idx_0 !== false && $idx_1 !== false) {
						$in_rule = $idx_0 <= $idx_1;

						return true;
					};

					$in_rule = true;

					return false;
				};

				$order = function (&$pages, $rules) use (&$order) {
					$in_all_rule = true;
					foreach ($rules as $rule) {
						$idx_0 = array_search($rule[0], $pages);
						$idx_1 = array_search($rule[1], $pages);
						if ($idx_0 > $idx_1) {
							$in_all_rule = false;
							array_swap($pages, $idx_0, $idx_1);
						}
					}

					if ($in_all_rule) {
						return;
					}

					$order($pages, $rules);
				};

				foreach ($input[1] as $pages) {
					$rules = [];
					$in_all_rule = true;
					foreach ($input[0] as $rule) {
						$in_rule = true;
						if ($check_rule($pages, $rule, $in_rule)) {
							$rules[] = $rule;

							if (!$in_rule) {
								$in_all_rule = false;
							}
						}
					}

					if ($in_all_rule) {
						continue;
					}

					$order($pages, $rules);

					$rs += $pages[(count($pages)-1)/2];
				}

				return $rs;
			},
		],
		'6_1' => [
			'parser' => [
				'parser' => [
					'sep' => '',
				]
			],
			'resolver' => function ($input) {

				$step = [[0, -1], [1, 0], [0, 1], [-1, 0]];

				$turn = function (&$x, &$y, $step, &$count_po, &$out) use (&$input) {
					do {
						$x += $step[0];
						$y += $step[1];
						if (!isset($input[$y][$x])) {
							$out = true;
							break;
						}
						if ($input[$y][$x] === '.') {
							$input[$y][$x] = '_';
							$count_po++;
						} else if ($input[$y][$x] === '_') {
							continue;
						} else {
							$x -= $step[0];
							$y -= $step[1];
							break;
						}
					} while (true);

					return $count_po;
				};

				$p_x = null;
				$p_y = null;
				foreach ($input as $y => $line) {
					foreach ($line as $x => $c) {
						if ($c === '^') {
							$p_x = $x;
							break;
						}
					}

					if ($p_x) {
						$p_y = $y;
						break;
					}
				}

				$rs = 1;
				$input[$y][$x] = '_';

				$turn_count = 0;
				$out = false;
				do {
					$turn($p_x, $p_y, $step[$turn_count%4], $rs, $out);
					$turn_count++;
				} while (!$out);

				return $rs;
			},
		],
		'6_2' => [
			'parser' => [
				'parser' => [
					'sep' => '',
				]
			],
			'resolver' => function ($input) {

				$steps = [[0, -1], [1, 0], [0, 1], [-1, 0]];

				$walk = function (&$x, &$y, $step, $update = true) use (&$input) {
					$next = $input[$y+$step[1]][$x+$step[0]] ?? null;

					if (!$next) {
						return 'out';
					}
					if ($next === '#' || $next === 'O') {
						return 'stuck';
					}

					if ($update) {
						$input[$y][$x] = $step[0] ? '-' : '|';
					}
					$x += $step[0];
					$y += $step[1];

					return '';
				};

				$run = function (&$x, &$y, $step) use (&$walk) {
					do {
						$status = $walk($x, $y, $step, false);
						if ($status) {
							return $status;
						}
					} while (true);
				};

				$check_loop = function ($x, $y, $count_turn) use ($steps, &$run) {
					$througth = [];

					while (true) {
						$througth["{$x}-{$y}-".implode(" ", $steps[$count_turn%4])] = 1;

						$count_turn++;
						$step = $steps[$count_turn%4];

						$status = $run($x, $y, $step);
						if ($status === 'out') {
							return false;
						}

						if (isset($througth["{$x}-{$y}-".implode(" ", $step)])) {
							return true;
						}
					}
				};

				$finded = false;
				foreach ($input as $y => $line) {
					foreach ($line as $x => $c) {
						if ($c === '^') {
							$finded = true;
							break;
						}
					}

					if ($finded) {
						break;
					}
				}

				$rs = 0;

				$turn_count = 0;
				$idx = 0;
				do {
					$step = $steps[$turn_count%4];
					echo "$idx -> ".$input[$y][$x]."\n";
					$status = $walk($x, $y, $step);
					if ($status === 'stuck') {
						$turn_count++;
					} else if ($status === 'out') {
						break;
					} else {
						$input[$y][$x] = "O";
						if ($check_loop($x - $step[0], $y - $step[1], $turn_count)) {
							$rs++;
						}
						$input[$y][$x] = ".";
					}

					$idx++;

				} while (true);

				return $rs;
			},
		],
	];

	$year = 2024;
	$challenge = new Adventofcode($s_key, $day, $level, $year);
	$parser = $resolvers["{$day}_{$level}"]['parser'] ?? null;
	$resolver = $resolvers["{$day}_{$level}"]['resolver'] ?? null;


	// 2022: 5, 11, 16
	// $parser = [
	// 	'sep' => "\n\n",
	// 	'parser_0' => [
	// 		'sep' => END_LINE,
	// 		'parser' => [
	// 			'sep' => "\s{1}",
	// 			'parser' => function ($str) {
	// 				return InputHelper::getValues($str, '/[\dA-Z]/');
	// 			},
	// 		]
	// 	],
	// 	'parser_1' => [
	// 		'sep' => END_LINE,
	// 		'parser' => "InputHelper::getMaps",
	// 	]
	// ];
	// $resolver = function ($input) {

	// };

// 	$challenge->setInput("....#.....
// .........#
// ..........
// ..#.......
// .......#..
// ..........
// .#..^.....
// ........#.
// #.........
// ......#...", $parser);

	$challenge->getInputFromServer($parser)->resolve($resolver);

	// $challenge->submit();

?>
