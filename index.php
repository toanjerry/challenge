<?php

	require_once('helper.php');
	require_once('InputHelper.php');

	class Adventofcode {

		private $s_key = '';

		public $year = 2024;
		public $day = 1;
		public $level = 1;

		public $domain = 'https://adventofcode.com';

		private $raw_input = "";

		public $input = [];

		public $result = null;

		public function __construct ($s_key, $day, $level, $year = 2024) {
			$this->s_key = $s_key;
			$this->day = $day;
			$this->level = $level;
			$this->year = $year;

			echo "Day: $this->day/$this->year - Part: $this->level\n";
		}

		public function getInputFromServer ($force = false) {
			$dir = "input/{$this->year}";
			if (!is_dir($dir)) {
				mkdir($dir);       
			}

			$file_name = "input/{$this->year}/{$this->day}.txt";
			if (file_exists($file_name) && !$force) {
				$raw_input = file_get_contents($file_name);
			} else {
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

				file_put_contents($file_name, $raw_input);
			}

			$this->raw_input = $raw_input;

			return $this;
		}

		public function setInput ($raw_input, $parser = null) {
			if ($raw_input) {
				$this->raw_input = $raw_input;
			}
			// parse input
			$this->input = $this->parseInput($parser)['value'];

			// echo some data support check by eye
			$this->printInput();

			return $this;
		}

		private function printInput () {
			echo('--------------------------');
			echo("\nnum row: " . count($this->input));
			echo("\nstart: ");
			echo json_encode(reset($this->input));
			echo("\n\nend: ");
			echo json_encode(end($this->input));
			echo("\n--------------------------");
		}

		private function parseInput ($parser = null) {
			$str = $this->raw_input;

			if ($parser && is_callable($parser)) {
				return $parser($str);
			}

			if (!is_array($parser)) {
				return $str;
			}

			if (!isset($parser['sep'])) {
				$parser['sep'] = END_LINE;
			}

			return InputHelper::parseString($str, $parser);
		}

		public function resolve (Callable $cb) {
			if (!$cb || !is_callable($cb)) {
				echo "\nInvalid resolver";
				exit();
			}

			$start_time = microtime(true);
			echo "\nStart time: ".date('H:i:s', $start_time);

			$this->result = $cb($this->input);

			$end_time = microtime(true);
			echo "\nEnd time: ".date('H:i:s', $end_time);
			echo "\nTotal: ".($end_time - $start_time)." s";
			echo "\n--------------------------";

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
	if (!is_numeric($day) || $day < 1) {
		echo "\nInvalid day\n";
		exit();
	}

	$level = intval($_SERVER['argv'][2] ?? null);
	if (!$level) {
		$level = 1;
	}

	if (!is_numeric($level) || $level > 2) {
		echo "\nInvalid level\n";
		exit();
	}

	$year = intval($_SERVER['argv'][3] ?? null);
	if (!$year) {
		$year = date('Y');
	}

	// get session key
	$config = parse_ini_file('.env');
	$s_key = $config['SESSION_KEY'] ?? null;
	if (!$s_key) {
		echo "\nInvalid session key\n";
		exit();
	}

	$challenge = new Adventofcode($s_key, $day, $level, $year);

	$input = $resolvers["{$day}_{$level}"]['input'] ?? [];
	if (!$input) {
		$challenge->getInputFromServer();
	}

	// get config parser and resolver input
	$resolvers = require_once("resolve/$year.php");
	$parser = $resolvers["{$day}_{$level}"]['parser'] ?? null;
	if (!$parser) {
		echo "\nInvalid parser\n";
		exit();
	}
	$resolver = $resolvers["{$day}_{$level}"]['resolver'] ?? null;
	if (!$resolver) {
		echo "\nInvalid resolver\n";
		exit();
	}

	$challenge->setInput($input, $parser);

	$challenge->resolve($resolver);

?>
