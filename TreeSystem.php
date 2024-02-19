<?php

/**
 * Class TrainSystem
 *
 * @author <NAME>
 */
class TrainSystem
{
    /**
     * Array to store whether a user has checked in or not
     *
     * @var array
     */
    public array $hasUserCheckedIn = [];

    /**
     * Array to store the user check out information
     *
     * @var array
     */
    public array $userCheckOutStations = [];

    /**
     * Array to store the user check in information
     *
     * @var array
     */
    public array $userCheckInStations = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->hasUserCheckedIn = [];
        $this->userCheckOutStations = [];
        $this->userCheckInStations = [];
    }

    /**
     * Check in a user
     *
     * @param int $userId
     * @param string $stationName Name of the station
     * @param int $travelTime Travel time in minutes
     */
    public function checkIn(int $userId, string $stationName, int $travelTime)
    {
        $this->userCheckInStations[] = [
            'userId' => $userId,
            'stationName' => $stationName,
            'travelTime' => $travelTime,
            'isUserCheckedIn' => 'true'
        ];
        $this->hasUserCheckedIn[$userId] = 1;

    }

    /**
     * Check out a user
     *
     * @param int $userId User ID
     * @param string $stationName Name of the station
     * @param int $travelTime Travel time in minutes
     */
    public function checkOut(int $userId, string $stationName, int $travelTime)
    {
        $this->userCheckOutStations[] = [
            'userId' => $userId,
            'stationName' => $stationName,
            'travelTime' => $travelTime,
            'isUserCheckedIn' => 'false'
        ];
        $this->hasUserCheckedIn[$userId] = 0;

    }

    /**
     * Get the average time between two stations
     *
     * @param string $startStation Name of the starting station
     * @param string $endStation Name of the ending station
     * @return string Average time in minutes
     */
    public function getAverageTime(string $startStation, string $endStation): string
    {
        $tmpCkIn = [];
        $tmpCkOut = [];
        $timeDiff = [];
        $returnStr = '';
        foreach ($this->userCheckInStations as $value) {
            if ($value['stationName'] == $startStation) {
                $tmpCkIn[] = $value;
            }
        }

        foreach ($this->userCheckOutStations as $value) {
            if ($value['stationName'] == $endStation) {
                $tmpCkOut[] = $value;
            }
        }

        foreach ($tmpCkIn as $key => $value) {
            foreach ($tmpCkOut as $key2 => $value2) {
                if ($value['userId'] == $value2['userId']) {
                    $tmpCkIn[$key]['travelTime'] = $value2['travelTime'];
                    $tmpCkOut[$key2]['travelTime'] = $value['travelTime'];
                    $timeDiff[$value['userId']] = $tmpCkIn[$key]['travelTime'] - $tmpCkOut[$key2]['travelTime'];
                    $returnStr .= PHP_EOL . 'For userId (' . $value["userId"] . ') Time is ' . $timeDiff[$value['userId']] . ' using the following formula ' . PHP_EOL .
                        '$time = ' . $tmpCkIn[$key]["travelTime"] . ' - ' . $tmpCkOut[$key2]["travelTime"] . PHP_EOL;
                }
            }
        }

        return $returnStr;
    }
}


// DO NOT CHANGE CODE BELOW THIS LINE
$train = new TrainSystem;

$train->checkIn(45, "San Francisco", 3);
$train->checkIn(32, "San Mateo", 8);
$train->checkIn(27, "San Francisco", 10);

$train->checkOut(45, "Palo Alto", 15);
$train->checkOut(27, "Palo Alto", 20);
$train->checkOut(32, "Mountain View", 22);

print($train->getAverageTime("San Mateo", "Mountain View") . PHP_EOL); // 14
print($train->getAverageTime("San Francisco", "Palo Alto") . PHP_EOL); // 11