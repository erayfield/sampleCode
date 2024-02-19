<?php

/**
 * @param $number integer to test if is key in array
 * @param $map array
 * @return string
 */
function fizzBuzz($number, $map) {
    $retVal = 'Not a dang thing';
    //check and make sure the number is not larger than the array size
    if (is_integer($number) && is_array($map) && !empty($map)) {

        if (array_key_exists($number, $map)) {
            $retVal =  ' <br>the key,  ' . $number .', exists in the array. Value to displayed by ' . $number . $map[$number];
        } else {
            $retVal = '  $map[ ' . $number . ' ] key does not exist in the array.';
        }
    } elseif (is_integer($number) === false) {
        $retVal = 'this $number is not an integer';
    }elseif ( empty($map) ) {
        $retVal = 'The values for $map is not an array or is an empty array';
    }
    return $retVal ;
}

/**
 * empty() returns true if the given variable is empty (null, false, 0, '',)
an empty string)
0 (0 as an integer)
0.0 (0 as a float)
"0" (0 as a string)
NULL
FALSE
array() (an empty array)
$var; (a variable declared, but without a value)
 **/



//$sam = fizzBuzz (2, $map);
//print_r('</br>number sent in  is 2</br>');
//print_r('</br>$map value is an array</br>');
//print_r('</br>Just right</br>'.$sam);
//unset($sam);
//echo PHP_EOL;
//
//$sam = fizzBuzz (102, $map);
//print_r('</br>number sent in  is 102</br>');
//print_r('</br>$map value is an array</br>');
//print_r('</br>number too big</br>'.$sam);
//unset($sam);
//echo PHP_EOL;
//
//$sam = fizzBuzz ('nan', $map);
//print_r('</br>number sent in  is NOT A NUMBER</br>');
//print_r('</br>$map value is an array</br>');
//print_r('</br>not a number</br>'.$sam);
//
//
//$sam = fizzBuzz (5, []);
//print_r('</br>number sent in  is 5</br>');
//print_r('</br>$map value is an EMPTY array</br>');
//print_r('</br>empty array</br>'.$sam);


$sam = fizzBuzz (15, [25]);
print_r('</br>number sent in  is 15</br>');
print_r('</br>$map value is an EMPTY array</br>');
print_r('</br>empty array</br>'.$sam);


$map = [1 => 'a',
    2 => 'batterup',
    3 => 'chodor',
    4 => 'dhodor',
    5 => 'ehodor',
    6 => 'fhodor',
    7 => 'hhodor',
    8 => 'ihodor',
    9 => 'jhodor',
    10 => 'khodor',
    11 => 'Lhodor',
    12 => 'mhodor',
    13 => 'nhodor',
    14 => 'ohodor',
    15 => 'phodor',
    16 => 'qhodor',
    17 => 'rhodor',
    18 => 'shodor',
    19 => 'thodor',
    20 => 'uhodor',
    21 => 'vhodor',
    22 => 'whodor',
    23 => 'xhodor',
    24 => 'yhodor',
    25 => 'zhodor',
    26 => 'aa',
    27 => 'bb',
    28 => 'cc',
    29 => 'dd',
    30 => 'ee',
    31 => 'ff',
    32 => 'gg',
    33 => 'hh',
    34 => 'ii',
    35 => 'jjj',
    36 => 'k3',
    37 => 'L',
    38 => 'mmm',
    39 => 'nnn',
    40 => 'ooo',
    41 => 'pp',
    42 => 'QQQ',
    43 => 'rr',
    44 => 'ss',
    45 => 'tt',
    46 => 'uu',
    47 => 'vv',
    48 => 'ww',
    49 => 'xx',
    50 => 'yy',
    51 => 'zz',
    52 => 'aaaa',
    53 => 'bbbb',
    54 => 'cccc',
    55 => 'dddd',
    56 => 'eeee',
    57 => 'ffff',
    58 => 'hhhh',
    59 => 'iiii',
    60 => 'jjjj',
    61 => 'k4'];