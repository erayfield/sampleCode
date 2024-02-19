<?php


$travelTime[] = [
    45 => ["San Mateo", "Mountain View", 15]
];
$travelTime[] = [
    45 => ["San Mateo", "Mountain View", 15]
];
$travelTime[] = [
    27 => ["San Antonio", "Schertz", 15]
];
$travelTimeArray[] = [];
$x = 0;
foreach ($travelTime as $key => $value) {
//   print_r(key($value) . ' <<<<<VALUE' . PHP_EOL);

    if (key($value) == 45) {
//        print_r(key($value). PHP_EOL);
        $userId = key($value);
        foreach ($value as $k => $v) {

//            echo PHP_EOL . '=============================' . PHP_EOL;
            if ($v[1] == 'Mountain View') {

                $travelTimeArray[$x] = [$userId, $v[0], $v[1], $v[2]];
         //       print_r($travelTimeArray[$x]);
                $x++;

                echo PHP_EOL . '<<<<<<<<' . PHP_EOL;
            }

            echo 'NEXT' . PHP_EOL . PHP_EOL;
        }

        echo PHP_EOL . '**********************************' . PHP_EOL;

    }
    echo PHP_EOL. '************ End foreach **********************'.PHP_EOL;
}
print_r($travelTimeArray);
echo PHP_EOL. '************ DONE **********************'.PHP_EOL;
//echo PHP_EOL;
//print_r($travelTime);
