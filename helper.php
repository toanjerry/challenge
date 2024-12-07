<?php

    function array_swap (&$arr, $key_1, $key_2) {
        $temp = $arr[$key_1];
        $arr[$key_1] = $arr[$key_2];
        $arr[$key_2] = $temp;
    }

?>