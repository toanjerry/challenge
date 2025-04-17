<?php

// '_1' => [
//     'parser' => [
//         'parser' => [
//             'sep' => '',
//         ],
//     ],
//     'resolver' => function ($input) {
//         $rs = 0;

//         return $rs;

//     }
// ],

return [
	'12_1' => [
		'input' => "",
		'parser' => [
			'parser' => [
				'sep' => '',
			],
		],
		'resolver' => function ($input) {
			$rs = 0;

			$region = function ($po, &$map) use ($re) {

			};

			foreach ($input as $y => $line) {
				foreach ($line as $x => $val) {
					if ($val != '.') {
						$region([$x, $y], $input);
					} 
				}
			}

			return $rs;

		}
	],
	'11_2' => [
		// 'input' => "125 17",
		'parser' => [
			'value' => function ($val) {
				return $val[0];
			},
			'parser' => "InputHelper::getNumbers",
		],
		'resolver' => function ($input) {
			$rs = 0;

			$blink_hash = [];

			$hash_blink = function ($val, $num) use (&$blink_hash, &$hash_blink) {
				if (!isset($blink_hash[$val])) {
					$blink_hash[$val] = [];
				}

				if (isset($blink_hash[$val][$num])) {
					return $blink_hash[$val][$num];
				}
				
				if ($num === 1) {
					$val = "$val";
					$len = strlen($val);
					if ($val == 0) {
						$blink_hash[$val][1] = [1, [1]];
					} else if ($len%2 === 0) {
						$blink_hash[$val][1] = [2, [floatval(substr($val, 0, $len/2)), floatval(substr($val, $len/2, $len))]];
					} else {
						$blink_hash[$val][1] = [1, [$val*2024]];
					}
					return $blink_hash[$val][1];
				}

				$hash_blink($val, $num - 1);
				$blink_hash[$val][$num] = [0, []];
				foreach ($blink_hash[$val][$num-1][1] as $v) {
					$blink_val = $hash_blink($v, 1);
					$blink_hash[$val][$num][0] += $blink_val[0];
					$blink_hash[$val][$num][1] = array_merge($blink_hash[$val][$num][1], $blink_val[1]);
				}
			};

			$blink = function ($val, $num_blink) use (&$blink_hash, &$blink, &$hash_blink) {
				$max_hash = 15;
				if (!isset($blink_hash[$val])) {
					$hash_blink($val, $max_hash);
				} else if (!isset($blink_hash[$val][$max_hash])) {
					$hash_blink($val, $max_hash);
				}

				if ($num_blink <= $max_hash) {
					return $blink_hash[$val][$num_blink][0];
				}

				$num = 0;
				foreach ($blink_hash[$val][$max_hash][1] as $v) {
					$num += $blink($v, $num_blink-$max_hash);
				}

				return $num;
			};

			// foreach (range(0, 9999) as $val) {
			// 	$blink($val, 30);
			// }

			for ($i = 0; $i < 920022389; $i++) {
				$rs++;
			}

			// $stones = $input;
			// foreach ($stones as $val) {
			// 	$rs += $blink($val, 45);
			// }

			return $rs;
		}
	],
	'11_1' => [
		'parser' => [
			'value' => function ($val) {
				return $val[0];
			},
			'parser' => "InputHelper::getNumbers",
		],
		'resolver' => function ($input) {
			$rs = 0;

			$blink = function ($stones) {
				$new_stones = [];
				foreach ($stones as $val) {
					$val = "$val";
					$len = strlen($val);
					if ($val == 0) {
						$new_stones[] = 1;
					} else if ($len%2 === 0) {
						$new_stones[] = floatval(substr($val, 0, $len/2));
						$new_stones[] = floatval(substr($val, $len/2, $len));
					} else {
						$new_stones[] = $val*2024;
					}
				}

				return $new_stones;
			};

			$stones = $input;
			foreach (range(1, 25) as $num) {
				$stones = $blink($stones);
			}

			return count($stones);
		}
	],
	'10_2' => [
        'parser' => [
			'parser' => [
				'sep' => '',
			],
        ],
        'resolver' => function ($input) {
            $rs = 0;

			$score = function ($po, $map, &$checked = []) use (&$score) {
				if (!inMap($po, $map)) {
					return;
				}

				foreach (STEPS as $step) {
					$face_po = step($po, $step);
					if (valPo($face_po, $map) == 9 && valPo($po, $map) == 8) {
						if (!isset($checked[poKey($face_po)])) {
							$checked[poKey($face_po)] = 0;
						}

						$checked[poKey($face_po)]++;
						continue;
					}

					if (valPo($face_po, $map) == valPo($po, $map) + 1) {
						$score($face_po, $map, $checked);
					}
				}
			};

			foreach ($input as $y => $line) {
				foreach ($line as $x => $val) {
					if ($val != 0) {
						continue;
					}

					$checked = [];
					$score([$x, $y], $input, $checked);
					$rs += array_sum($checked);
				}
			}

            return $rs;
        }
    ],
	'10_1' => [
        'parser' => [
			'parser' => [
				'sep' => '',
			],
        ],
        'resolver' => function ($input) {
            $rs = 0;

			$score = function ($po, $map, &$checked = []) use (&$score) {
				if (!inMap($po, $map)) {
					return;
				}

				foreach (STEPS as $step) {
					$face_po = step($po, $step);
					if (valPo($face_po, $map) == 9 && valPo($po, $map) == 8) {
						if (!isset($checked[poKey($face_po)])) {
							$checked[poKey($face_po)] = 1;
						}
						continue;
					}

					if (valPo($face_po, $map) == valPo($po, $map) + 1) {
						$score($face_po, $map, $checked);
					}
				}
			};

			foreach ($input as $y => $line) {
				foreach ($line as $x => $val) {
					if ($val != 0) {
						continue;
					}

					$checked = [];
					$score([$x, $y], $input, $checked);
					$rs += count($checked);
				}
			}

            return $rs;
        }
    ],
	'9_2' => [
        'parser' => [
			'sep' => '',
        ],
        'resolver' => function ($input) {
            $rs = 0;

			$hash = [];
			$empty_slot = [];
			$file_slot = [];
			foreach ($input as $idx => $num_slot) {
				if ($num_slot == 0) {
					continue;
				}
				if ($idx%2 === 0) {
					$file_slot[count($hash)] = $num_slot;
					foreach (range(1, $num_slot) as $n) {
						$hash[] =  $idx/2 ;
					}
				} else {
					$empty_slot[] = [
						'po' => count($hash),
						'num' => $num_slot,
					];
					foreach (range(1, $num_slot) as $n) {
						$hash[] =  '.';
					}
				}
			}

			$file_slot = array_reverse($file_slot, true);

			foreach ($file_slot as $f_po => $f_num_slot) {
				if ($f_num_slot === 0) {
					continue;
				}
				$po_fit = null;
				foreach ($empty_slot as $idx => &$empty) {
					if ($empty['num'] === 0) {
						continue;
					}
					if ($f_num_slot <= $empty['num']) {
						$po_fit = $empty['po'];
						if ($f_num_slot == $empty['num']) {
							unset($empty_slot[$idx]);
						} else {
							$empty['po'] += $f_num_slot;
							$empty['num'] -= $f_num_slot;
						}
						break;
					}
				}

				if (!$po_fit || $po_fit >= $f_po) {
					continue;
				}

				foreach (range(0, $f_num_slot - 1) as $n) {
					swapArray($hash, $po_fit + $n, $f_po + $n);
				}
			}

			foreach ($hash as $idx => $v) {
				if ($v === '.') {
					continue;
				}

				$rs += $v*$idx;
			}

            return $rs;
        }
    ],
    '9_1' => [
        'parser' => [
			'sep' => '',
        ],
        'resolver' => function ($input) {
            $rs = 0;

			$hash = [];
			foreach ($input as $idx => $num_slot) {
				if ($num_slot == 0) {
					continue;
				}
				foreach (range(1, $num_slot) as $n) {
					$hash[] = $idx%2 === 0 ? $idx/2 : '.';
				}
			}

			$start = 0;
			$end = count($hash) - 1;
			while ($start < $end) {
				while($hash[$start] != '.') {
					$start++;
				};
				while($hash[$end] == '.') {
					$end--;
				}

				if ($start >= $end) {
					break;
				}

				swapArray($hash, $start, $end);
			};

			foreach ($hash as $idx => $v) {
				if ($v === '.') {
					break;
				}

				$rs += $v*$idx;
			}

            return $rs;
        }
    ],
    '8_1' => [
        'parser' => [
            'parser' => [
                'sep' => '',
            ],
        ],
        'resolver' => function ($input) {
            $rs = [];
            
            $hash = groupMap($input);

            foreach ($hash as $val => $pos) {
                if ($val === '.') {
                    continue;
                }
                for ($i = 0; $i < count($pos) - 1; $i++) {
                    $p1 = $pos[$i];
                    for ($j = $i+1; $j < count($pos); $j++) {
                        $p2 = $pos[$j];

                        $op = stepOpposite($p1, $p2);
                        if (inMap($op, $input)) {
                            if (!isset($rs[poKey($op)])) {
                                $rs[poKey($op)] = 1;
                            }
                        }

                        $op = stepOpposite($p2, $p1);
                        if (inMap($op, $input)) {
                            if (!isset($rs[poKey($op)])) {
                                $rs[poKey($op)] = 1;
                            }
                        }
                    }
                }
            }

            return count($rs);
        },
    ],
    '8_2' => [
        'parser' => [
            'parser' => [
                'sep' => '',
            ],
        ],
        'resolver' => function ($input) {
            $rs = [];
            
            $hash = groupMap($input);

            foreach ($hash as $val => $pos) {
                if ($val === '.') {
                    continue;
                }
                for ($i = 0; $i < count($pos) - 1; $i++) {
                    $p1 = $pos[$i];
                    for ($j = $i+1; $j < count($pos); $j++) {
                        $p2 = $pos[$j];
                        $step = [$p1[0] - $p2[0], $p1[1] - $p2[1]];

                        $po_anti = run($p1, $step, $input);

                        $step = [-$step[0], -$step[1]];
                        do {
                            if (!inMap(step($po_anti, $step, true), $input)) {
                                break;
                            }
                            if (!isset($rs[poKey($po_anti)])) {
                                $rs[poKey($po_anti)] = 1;
                            }
                        } while (true);
                    }
                }
            }

            return count($rs);
        },
    ],
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
            $po = poOfVal('^', $input);

            updatePo($po, $input, '-');
            $rs = 1;

            $step = [0, -1];
            do {
                run($po, $step, $input, true, function ($val) {
                    return $val === '#';
                }, function ($val) use (&$rs) {
                    if ($val === '.') {
                        $rs++;
                        return '-';
                    }

                    return null;
                });

                if (!inMap($po, $input)) {
                    break;
                }
                back($po, $step, true);

                turn($step);
            } while (true);

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
            $rs = [];

            $po = poOfVal('^', $input);
            updatePo($po, $input, '-');
            $step = [0, -1];

            $check_loop = function ($po, $step, $map) {
                $througth = [];

                do {
                    $key = poKey($po).'/'.implode("/", $step);
                    if (isset($througth[$key])) {
                        return true;
                    }
                    $througth[$key] = 1;

                    turn($step);
                    run($po, $step, $map, true, function ($val) {
                        return $val === '#';
                    });
                    if (!inMap($po, $map)) {
                        return false;
                    }
                    back($po, $step, true);
                } while (true);
            };

            do {
                run($po, $step, $input, true, function ($val) {
                    return $val === '#';
                }, function ($val, $po, $map, $step) use (&$rs, &$check_loop) {
                    if ($val === '.') {
                        $key = poKey($po)."/".implode("/", $step);
                        if (!isset($rs[$key])) {
                            updatePo($po, $map, '#');
                            if ($check_loop(back($po, $step), $step, $map)) {
                                $rs[$key] = 1;
                            }
                        }

                        return '-';
                    }

                    return null;
                });

                if (!inMap($po, $input)) {
                    break;
                }
                back($po, $step, true);

                turn($step);
            } while (true);

            return count($rs);
        },
    ],
    '7_1' => [
        'parser' => [
            'parser' => [
                'sep' => ':',
                'parser_0' => "floatval",
                'parser_1' => "InputHelper::getNumbers",
            ]
        ],
        'resolver' => function ($input) {
            $rs = 0;

            $get_cases = function ($num) use (&$get_cases) {
                if ($num === 1) {
                    return [['+'], ['*']];
                }

                $rs = [];
                foreach ($get_cases($num - 1) as $case) {
                    $case[] = '+';
                    $rs[] = $case;
                    $case[$num-1] = '*';
                    $rs[] = $case;
                }

                return $rs;
            };

            $check = function ($arr, $val) use (&$get_cases) {
                $cases = $get_cases(count($arr) - 1);

                foreach ($cases as $case) {
                    $temp_val = $arr[0];
                    foreach ($case as $idx => $op) {
                        if ($op == '+') {
                            $temp_val += $arr[$idx+1];
                        } else {
                            $temp_val *= $arr[$idx+1];
                        }

                        if ($temp_val > $val) {
                            break;
                        }
                    }

                    if ($temp_val === $val) {
                        return true;
                    }
                }

                return false;
            };

            foreach ($input as $line) {
                if ($check($line[1], $line[0])) {
                    $rs += $line[0];
                }
            }

            return $rs;
        },
    ],
    '7_2' => [
        'parser' => [
            'parser' => [
                'sep' => ':',
                'parser_0' => "floatval",
                'parser_1' => "InputHelper::getNumbers",
            ]
        ],
        'resolver' => function ($input) {
            $rs = 0;

            $get_cases = function ($num) use (&$get_cases) {
                if ($num === 1) {
                    return [['+'], ['*'], ['||']];
                }

                $rs = [];
                foreach ($get_cases($num -1) as $case) {
                    $case[] = '+';
                    $rs[] = $case;
                    $case[$num-1] = '*';
                    $rs[] = $case;
                    $case[$num-1] = '||';
                    $rs[] = $case;
                }

                return $rs;
            };

            $check = function ($arr, $val) use (&$get_cases) {
                $cases = $get_cases(count($arr) - 1);

                foreach ($cases as $case) {
                    $temp_val = $arr[0];
                    foreach ($case as $idx => $op) {
                        if ($op == '+') {
                            $temp_val += $arr[$idx+1];
                        } else if ($op === '*') {
                            $temp_val *= $arr[$idx+1];
                        } else {
                            $temp_val = floatval("$temp_val{$arr[$idx+1]}");
                        }

                        if ($temp_val > $val) {
                            break;
                        }
                    }

                    if ($temp_val === $val) {
                        return true;
                    }
                }

                return false;
            };

            foreach ($input as $line) {
                if ($check($line[1], $line[0])) {
                    $rs += $line[0];
                }
            }

            return $rs;
        },
    ],
]

?>