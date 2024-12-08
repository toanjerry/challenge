<?php

    function swapArray (&$arr, $key_1, $key_2) {
        $temp = $arr[$key_1];
        $arr[$key_1] = $arr[$key_2];
        $arr[$key_2] = $temp;
    };

    function hashArray ($arr, $key_builder = null, $val_builder = null) {
        if (!$key_builder) {
            $key_builder = function ($val, $key) {
                if (is_string($val) || is_numeric($val)) {
                    return $val; 
                }
        
                return json_encode($val);
            };
        }

        if (!$val_builder) {
            $val_builder = function ($val, $key) {
                return $key;
            };
        }

        $rs = [];
        foreach ($arr as $key => $val) {
            $rs[$key_builder($val, $key)] = $val_builder($val, $key);
        }

        return $rs;
    }

    function groupArray ($arr, $key_builder = null, $val_builder = null) {
        if (!$key_builder) {
            $key_builder = function ($val, $key) {
                if (is_string($val) || is_numeric($val)) {
                    return $val; 
                }
        
                return json_encode($val);
            };
        }

        if (!$val_builder) {
            $val_builder = function ($val, $key) {
                return $key;
            };
        }

        $rs = [];
        foreach ($arr as $key => $val) {
            $k = $key_builder($val, $key);
            if (!isset($rs[$k])) {
                $rs[$k] = [];
            }
            $rs[$k][] = $val_builder($val, $key);
        }

        return $rs;
    }

    // Map helper function
    // 1. process map
    function hashMap ($map, $key_builder = null, $val_builder = null) {
        if (!$key_builder) {
            $key_builder = function ($val, $po) {
                return "{$po[0]}/{$po[1]}";
            };
        }

        if (!$val_builder) {
            $val_builder = function ($val, $po) {
                return $val;
            };
        }

        $rs = [];
        foreach ($map as $y => $line) {
            foreach ($line as $x => $val) {
                $rs[$key_builder($val, [$x, $y])] = $val_builder($val, [[$x, $y]]);
            }
        }

        return $rs;
    }

    function groupMap ($map, $key_builder = null, $val_builder = null) {
        if (!$key_builder) {
            $key_builder = function ($val, $po) {
                if (is_string($val) || is_numeric($val)) {
                    return $val; 
                }
        
                return json_encode($val);
            };
        }

        if (!$val_builder) {
            $val_builder = function ($val, $po) {
                return $po;
            };
        }

        $rs = [];
        foreach ($map as $y => $line) {
            foreach ($line as $x => $val) {
                $k = $key_builder($val, [$x, $y]);
                if ($k === null) {
                    continue;
                }
                $rs[$k][] = $val_builder($val, [$x, $y]);
            }
        }

        return $rs;
    }

    function poKey ($po) {
        return "{$po[0]}/{$po[1]}";
    } 

    function inMap ($po, $map) {
        return $po[0] >= 0 && $po[1] >= 0 && $po[0] < count($map[0] ?? []) && $po[1] < count($map);
    }

    function updatePo ($po, &$map, $updater = null) {
        $po_val = posVal($po, $map);
        $map[$po[1]][$po[0]] = $updater ? $updater($po_val, $po) : null;
    }
    // 2. Step in map
    function step (&$po, $step, $update = false, &$map = [], $updater = null) {
        $new_po = [$po[0] + $step[0], $po[1] + $step[1]];

        if ($update) {
            $po[0] = $new_po[0];
            $po[1] = $new_po[1];
        }

        if ($updater && inMap($new_po, $map)) {
            updatePo($new_po, $map, $updater);
        }

        return $new_po;
    }

    function back (&$po, $step) {
        $po[0] -= $step[0];
        $po[1] -= $step[1];
    }

    function run (&$po, $step, &$map, $update = false, $stop = null, $updater = false) {
        $next_po = $po;
        do {
            $next_po = step($next_po, $step, true, $map, $updater);
            if (!inMap($next_po, $map)) {
                break; 
            }
            if ($stop && $stop(posVal($next_po, $map), $next_po)) {
                break; 
            }
        } while (true);

        if ($update) {
            $po[0] = $next_po[0];
            $po[1] = $next_po[1];
        }

        return $next_po;
    }

    function stepOpposite (&$po, $pivot, $update = false, &$map = [], $updater = null) {
        $step = [2*($pivot[0] - $po[0]), 2*($pivot[1] - $po[1])];

        return step($po, $step, $update, $map, $updater);
    }

    function turn (&$step, $direction = 'r') {
        $step_x = $step[0];
        $step_y = $step[1];
        if ($direction === 'r') {
            $step[0] = -$step_y;
            $step[1] = $step_x;
        }

        $step[0] = $step_y;
        $step[1] = -$step_x;
    }

    // 2. get value of position
    function posVal ($po, $map) {
        return $map[$po[1]][$po[0]] ?? null;
    }

    function posVals ($pos, $map) {
        $rs = [];
        foreach ($pos as $po) {
            $rs[] = posVal($map, $po);
        }

        return $rs;
    }

    function face ($po, $step, $map) {
        return posVal(step($po, $step), $map);
    }
?>
