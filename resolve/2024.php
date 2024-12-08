<?php

return [
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
                $next = $input[$y+$step[1]][$x+$step[0]] ?? '';

                if (!$next) {
                    return '';
                }
                if ($next === '#') {
                    return '#';
                }

                if ($update) {
                    $input[$y][$x] = '-';
                }

                $x += $step[0];
                $y += $step[1];

                return $next;
            };

            $run = function (&$x, &$y, $step) use (&$walk) {
                do {
                    $next = $walk($x, $y, $step, false);
                    if (!$next || $next === '#') {
                        return $next;
                    }
                } while (true);
            };

            $check_loop = function ($x, $y, $count_turn) use ($steps, &$run) {
                $througth = [];

                $first_key = "{$x}/{$y}/".implode(" ", $steps[$count_turn%4]);
                $througth[$first_key] = 1;

                do {
                    $step = $steps[++$count_turn%4];

                    $last = $run($x, $y, $step);
                    if (!$last) {
                        return false;
                    }

                    $key = "{$x}/{$y}/".implode(" ", $step);
                    if (isset($througth[$key])) {
                        // if ($key === $first_key) {
                        // 	return true;
                        // }
                        // return false;
                        return true;
                    }

                    $througth[$key] = 1;
                } while (true);
            };

            $o_x = null;
            $o_y = null;
            foreach ($input as $y => $line) {
                foreach ($line as $x => $c) {
                    if ($c === '^') {
                        $o_x = $x;
                        break;
                    }
                }

                if ($o_x) {
                    $o_y = $y;
                    break;
                }
            }

            $rs = [];

            // $input[$y][$x] = '-';

            $turn_count = 0;
            do {
                $step = $steps[$turn_count%4];
                $next = $walk($x, $y, $step);
                if (!$next) {
                    break;
                }
                if ($next === '#') {
                    $turn_count++;
                } else if ($next === '.') {
                    if (!isset($rs["{$x}/{$y}"]) && ($x !== $o_x || $y !== $o_y)) {
                        $input[$y][$x] = "#";
                        if ($check_loop($x - $step[0], $y - $step[1], $turn_count)) {
                            $rs["{$x}/{$y}"] = 1;
                        }
                        $input[$y][$x] = ".";
                    }
                }
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