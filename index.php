<?php

	class Adventofcode {

		private $s_key = '';

		public $day = 1;

		public $domain = 'https://adventofcode.com/2023';

		public $input = [];

		public $result = null;

		public function __construct ($s_key, $day) {
			$this->s_key = $s_key;
			$this->day = $day;
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
			$this->input = $this->parseInput(strip_tags($raw_input), $parser);

			// echo some data support check by eye
			echo('--------------------------');
			echo("\nnum row: " . count($this->input));
			echo("\nstart: ");
			print_r($this->input[0] ?? '');
			echo("\nend: ");
			print_r(end($this->input));
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

			$lines = explode("\n", $str);

			if (!is_array($parser)) {
				return $lines;
			}

			foreach ($lines as $idx => &$line) {
				if (!$line && !is_numeric($line)) {
					unset($lines[$idx]);
					continue;
				}
				$line = $this->parseString($line, $parser);
			}

			return $lines;
		}

		private function parseString ($str, $config) {
			if (!isset($config['sep'])) {
				return $str;
			}

			$rs = [$str];
			if ($config['sep']) {
				$rs = explode($config['sep'], $str);
			} else {
				$rs = str_split($str);
			}

			$format = is_callable($config['format'] ?? null) ? $config['format'] : null;

			foreach ($rs as $idx => $val) {
				if ($format) {
					$val = $format($val);
				}

				if (!empty($config[$idx])) {
					$val = $this->parseString($val, $config[$idx]);
				}
				if ($val === null) {
					unset($rs[$idx]);
					continue;
				}

				$rs[$idx] = $val;
			}

			return array_values($rs);
		}

		public function resolve (Callable $cb) {
			if (!$cb || !is_callable($cb)) {
				echo "\nInvalid resolver";
				exit();
			}

			$this->result = $cb($this->input);

			echo "\nResult: ";
			print_r($this->result);

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

	$s_key = '53616c7465645f5fad0f59e9eb5a7b720981a3e5dd7fefe4e30dec53a59f0d838f9fb66cc71fe8fa89755f74fffec68910db125989074aa0ae6a4f56e9ba235a';

	$resolvers = [
		// day => resolver
		1 => [
			'parser' => [
				"sep" => '',
				"format" => function ($val) {
					if (!is_numeric($val)) {
						return null;
					}

					return intval($val);
				},
			],
			'resolver' => function ($input) {
				$rs = 0;
				foreach ($input as $line) {
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
	];

	$challenge = new Adventofcode($s_key, $day);
	$parser = $resolvers[$day]['parser'] ?? null;
	$resolver = $resolvers[$day]['resolver'] ?? null;
	$challenge->getInputFromServer($parser)->resolve($resolver);

	// $challenge->submit();

?>
