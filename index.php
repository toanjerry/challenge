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

		public static function getNumbers ($str) {
			if (!$str) {
				return [];
			}
			preg_match_all('/[-|+]?\d+/', $str, $matches);

			return array_map('floatval', $matches[0]);
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

		public $domain = 'https://adventofcode.com/2023';

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
				'sep' => "\n",
				'key' => null,
				'parser' => [
					'sep' => '',
					'parser' => function ($val) {
						if (!is_numeric($val)) {
							return null;
						}

						return intval($val);
					},
				]
			],
			'resolver' => function ($input) {
				$rs = 0;
				foreach ($input as $line) {
					$line = array_values($line);
					$num_digit = count($line);
					if ($num_digit > 1) {
						$rs += $line[0]*10 + end($line);
						continue;
					}
					if ($num_digit === 1) {
						$rs += $line[0]*10 + $line[0];
						continue;
					}
				}

				return $rs;
			},
		],
		'1_2' => [
			'parser' => [
				'parser' => [
					'sep' => '',
					'parser' => null,
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'2_1' => [
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
						'sep' => ';',
						'parser' => function ($str) {
							return InputHelper::getUnits($str);
						}
					],
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				foreach ($input as $key => $line) {
					$valid = true;
					foreach ($line as $r) {
						if (($r['red'] ?? 0) > 12 || ($r['green'] ?? 0) > 13 || ($r['blue'] ?? 0) > 14) {
							$valid = false;
							break;
						}
					}

					if ($valid) {
						$rs += $key;
					}
				}

				return $rs;
			},
		],
		'2_2' => [
			'parser' => [
				'parser' => [
					'sep' => '',
					'parser' => null,
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'3_1' => [
			'parser' => [
				'parser' => [
					'sep' => '',
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

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
		'5_1' => [
			'parser' => [
				'sep' => "\n\n",
				'parser_0' => [
					'sep' => ":",
					'key' => function ($value) {
						return trim($value[0]);
					},
					'value' => function ($value) {
						return $value[1];
					},
					'parser' => 'InputHelper::getNumbers',
				],
				'parser' => [
					'sep' => "\n",
					'key' => function ($value) {
						return str_replace(" map:", "", trim($value[0]));
					},
					'value' => function ($value) {
						array_shift($value);
						return $value;
					},
					'parser' => 'InputHelper::getNumbers',
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'6_1' => [
			'parser' => [
				'parser' => [
					'sep' => ':',
					'key' => function ($value) {
						return $value[0];
					},
					'value' => function ($value) {
						return $value[1];
					},
					'parser' => "InputHelper::getNumbers",
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'7_1' => [
			'parser' => [
				'parser' => [
					'sep' => "\s+",
					'key' => function ($value) {
						return trim($value[0]);
					},
					'value' => function ($value) {
						return intval($value[1]);
					},
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'8_1' => [
			'parser' => [
				'sep' => "\n\n",
				'parser_0' => [
					'sep' => '',
				],
				'parser_1' => [
					'sep' => "\n",
					'parser' => [
						'sep' => "\s+=\s+",
						'key' => function ($value) {
							return trim($value[0]);
						},
						'value' => function ($value) {
							return $value[1];
						},
						'parser' => [
							'sep' => ",\s+",
							'wrapped' => ["\("],
						]
					]
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'18_1' => [
			'parser' => [
				'parser' => [
					'sep' => "\s+",
					'parser_2' => [
						'wrapped' => ["\("],
					]
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'19_1' => [
			'parser' => [
				'sep' => "\n\n",
				'parser_0' => [
					'sep' => InputHelper::NEW_LINE,
					'parser' => [
						'sep' => "{",
						'key' => function ($value) {
							return trim($value[0]);
						},
						'value' => function ($value) {
							return $value[1];
						},
						'parser' => [
							'sep' => ',',
							'wrapped' => ["", "\}"],
						]
					]
				],
				'parser_1' => [
					'sep' => "\n",
					'parser' => [
						'sep' => ',',
						'wrapped' => ["{"],
						'parser' => [
							'sep' => '=',
							'key' => function ($value) {
								return $value[0];
							},
							'value' => function ($value) {
								return intval($value[1]);
							},
						]
					]
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'20_1' => [
			'parser' => [
				'parser' => [
					'sep' => "\s+->\s+",
					'key' => function ($value) {
						return trim($value[0]);
					},
					'value' => function ($value) {
						return $value[1];
					},
					'parser' => [
						'sep' => ',\s+',
						'parser' => 'trim',
					]
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'22_1' => [
			'parser' => [
				'parser' => [
					'sep' => "~",
					'parser' => [
						'sep' => ',',
						'parser' => 'intval',
					]
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'24_1' => [
			'parser' => [
				'parser' => [
					'sep' => "\s+@\s+",
					'parser' => "InputHelper::getNumbers"
				]
			],
			'resolver' => function ($input) {
				$rs = 0;

				return $rs;
			},
		],
		'25_1' => [
			'parser' => [
				'parser' => [
					'sep' => ":\s+",
					'key' => function ($val) {
						return trim($val[0]);
					},
					'value' => function ($val) {
						return $val[1];
					},
					'parser' => [
						'sep' => '\s+',
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
