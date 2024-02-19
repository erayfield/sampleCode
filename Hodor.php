<?php

class Hodor
{
    public array $checkedIn;
    public int $checkedInCount;
    private int $cnt = 0;

    public function __constructor(): void
    {
        $this->checkedIn = array(array());
        $this->checkedInCount = 0;
        $this->cnt = 0;
    }

    public function checkIn(int $id, string $stationName, int $time): void
    {
        $strValue = 'User '.$id. ' You cannot be checked in at this time';
        //find if person is already here and show them checked in
        if (!empty($this->checkedIn)) {
            if (!in_array($id, $this->checkedIn)) {
                $pushIt =['userId'=>$id, 'checkedInStatus'=>1,'stationName'=>$stationName, 'time'=>$time];
                array_push($this->checkedIn,  $pushIt);
                $strValue = 'User '.$id.' You have been checked in at ' . $stationName . ' at '. $time;
            }
        } else   {
            $this->checkedIn[] =  ['userId'=>$id, 'checkedInStatus'=>true,'stationName'=>$stationName, 'time'=>$time];
            $strValue = 'User '.$id.' You have been checked in at ' . $stationName. ' at '. $time;
        }
        echo PHP_EOL. $strValue. PHP_EOL;

    }

    public function checkOut(int $id, string $stationName, int $time): void
    {
        $checkArray = $this->checkedIn;
        $checkArray2 = $this->checkedIn;
        $strValue = 'You cannot be checked OUT at this time';
        $foundOne = false;
        foreach ($checkArray2 as $key => $value) {

            if ($value['userId'] == $id) {
//                echo $key . PHP_EOL;
//                echo $id;
//                echo PHP_EOL;
                $time = $this->checkedIn[$key]['time'];
                $this->checkedIn[$key] = ['userId' => $id, 'checkedInStatus' => 0, 'stationName' => $stationName, 'time'=>$time];
                if ($this->checkedIn[$key]['checkedInStatus'] == 0 && $value['checkedInStatus'] == 1) {
                    $strValue = 'User '.$id.' You have been checked out at '. $stationName.' travel time was'. $time . PHP_EOL;
                    $foundOne = true;
                }
                else if (!$this->checkedIn[$key]['checkedInStatus'] == 1) {
                    $strValue = 'User '.$id.' You have been NOT checked out at '. $stationName.' travel time was '. $time. PHP_EOL;
                    $foundOne = true;
                    die ($strValue);
                }
            }
        }

        echo PHP_EOL.$strValue.PHP_EOL;
    }

    public function getAverageTime( string $startStation, string $endStation) {
        $tstArray = $this->checkedIn;
        $timeInArray= [[]];
        $timeOutArray= [[]];
        echo PHP_EOL. "in getAverageTime ".PHP_EOL;

        $startSataion = null;
        foreach ($tstArray as $key=>$value){
            if (strtolower($value['stationName']) == strtolower($startStation) && $value['time']){
                if ($value['checkedInStatus'] == 1) {
                    $timeInArray[] = ['startStation' => $startStation, 'time' => $value['time']];
                } else if ($value['checkedInStatus'] == 0) {
                    $timeOutArray[] = ['startStation' => $startStation, 'time' => $value['time']];
                }
            }
        }
        echo PHP_EOL. " IN getAverageTime ".PHP_EOL;
        print_r($timeInArray);
        echo PHP_EOL. " OUT getAverageTime ".PHP_EOL;
        print_r($timeOutArray);
        die(PHP_EOL.'Average Time');

//        if (is_string($startStation) && is_string($endStation))
//        {
//           echo PHP_EOL. $startStation. PHP_EOL; echo PHP_EOL. $endStation. PHP_EOL;;
//        }
//        else {
//            echo PHP_EOL.'buggers';
//        }

    }

}
// DO NOT CHANGE CODE BELOW THIS LINE

//$train = new TrainSystem;
$train = new Hodor;
$train->checkIn(45, "San Francisco", 3);
$train->checkIn(32, "San Mateo", 8);
$train->checkIn(27, "San Francisco", 10);

$train->checkOut(45, "Palo Alto", 15);
$train->checkOut(27, "Palo Alto", 20);
$train->checkOut(32, "Mountain View", 22);

print($train->getAverageTime("San Mateo", "Mountain View") . PHP_EOL); // 14
print($train->getAverageTime("San Francisco", "Palo Alto") . PHP_EOL); // 11