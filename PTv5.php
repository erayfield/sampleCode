<?php
    /**
     * Name: PTv5.php
     * Created by : ERayfield
     */
    require_once('Helper/PTv5Helper.php');

    class PTv5 {
        /**
         * Base url for the PivotalTracker service api.
         */
        private $api_url;

        /**
         * Name of the context project.
         *
         * @var string
         */
        private $project;

        /**
         * Used client to perform rest operations.
         *
         * @var PivotalTrackerV5\Rest\Client
         */
        private $client;

        /**
         * @param string $apiKey API Token provided by PivotalTracking
         * @param string $project Project ID
         */
        public function __construct($apiKey, $project) {

            $this->client = new PTv5Helper('https://www.pivotaltracker.com/services/v5');
            $this->client->addHeader('Content-type', 'application/json');
            $this->client->addHeader('X-TrackerToken', $apiKey);
            $this->project = $project;
        }

        /**
         * clean up
         */
        public function __destruct()
        {
            foreach($this as $obj)
            {
                unset($obj);
            }
        }

        /**
         * Returns all stories for the context project.
         *
         * @param string $filter comma delimited string which may consist of the following: delivered,finished,rejected,started
         * @return object
         */
        public function getStories($filter = NULL) {
            $add = is_null($filter) ? "/projects/{$this->project}/stories" :
                "/projects/{$this->project}/stories?filter=state:" . $filter;
            return json_decode(
                $this->client->get($add
                )
            );
        }

        /**
         * Returns a list of projects for the currently authenticated user.
         *
         * @return object
         */
        public function getProjects() {
            return json_decode(
                $this->client->get(
                             "/projects"
                )
            );
        }

        /**
         * gets all the members of the project
         * @returns  array [person_id] = user name
         */
        public function getMembersByProject() {
            $retArray = array();
            $arr = json_decode(
                $this->client->get(
                             "/projects/{$this->project}/memberships"
                )
            );

            if(isset($arr->kind) && strcasecmp($arr->kind, 'error')== 0 )
               return $retArray;

            foreach ($arr as $a) {
                if ($a->project_id == $this->project) {
                    $retArray[ $a->person->id ] = $a->person->name;
                }
            }
            return $retArray;
        }

        /**
         * @param int $storyId the story id
         * @return mixed tasks or empty
         */
        public function getStoryTask($storyId) {
            return json_decode(
                $this->client->get(
                             "/projects/{$this->project}/stories/$storyId/tasks"
                )
            );
        }

        /**
         * gets the completed project(s)
         * @return mixed
         */
        public function getCompletedProjects() {
                     return json_decode(
                $this->client->get(
                             "/projects"
                )
            );
        }

        /**
         * gets all projects iterations for the project
         * @return mixed
         */
        public function getProjectIteration() {
            //"/projects/{$this->project}/iterations?limit=60"
            return json_decode(
                $this->client->get(
                             "/projects/{$this->project}/iterations?limit=60"
                )
            );
        }

        /**
         * gets all stories and activities as defined during instantiation
         * @return array
         */
        public function getStoriesAndActivities() {
            $owned_by_id = NULL;
            $requestedById = NULL;
            $retArray = array();
            //get all the users
            $project_members = $this->getMembersByProject();

            //get  projects
            $projects = $this->getProjectIteration();

            if(isset($projects->kind) && strcasecmp($projects->kind, 'error')== 0 )
               return $retArray;

            foreach ($projects as $obj) {
                //get each story
                foreach ($obj->stories as $stories) {
                    $ownedById = NULL;
                    $requestedById = NULL;
                    $point_estimate = !empty($stories->estimate) ? $stories->estimate : '0';
                    $retArray[ $stories->id ][ 'kind' ] = $stories->kind;
                    //     $retArray[ $stories->id ][ 'current_state' ] = $stories->current_state;
                    $retArray[ $stories->id ][ 'point_estimate' ] = $point_estimate;
                    if (isset($stories->owned_by_id)) {
                        $ownedById = $this->checkValue($stories->owned_by_id,
                                                       $project_members,
                                                       NULL);
                    }
                    if (isset($stories->requested_by_id)) {
                        $requestedById = $this->checkValue($stories->requested_by_id,
                                                           $project_members,
                                                           NULL);
                    }
                    //gets the owner
                    $retArray[ $stories->id ][ 'owned_by' ] = !is_null($ownedById) ?
                        'Owned By ' . $ownedById :
                        !is_null($requestedById) ? 'Requested By ' . $requestedById :
                            'No owner type found';

                    $retArray[ $stories->id ][ 'name' ] = $stories->name;
                    /**
                     * this gets the activity for each story....
                     */
                    $retArray[ $stories->id ][ 'activity' ] = $this->getStoryActivity($stories->id);
                }
            }

            return $retArray;
        }


        private function checkValue($val, $arrayName, $retValIfNotValid) {
            return array_key_exists($val, $arrayName) && !empty($arrayName[ $val ]) ?
                trim($arrayName[ $val ]) : $retValIfNotValid;
        }


        /**
         * @param int $storyId the story id
         * @param string $fields comma delimited string for data information, values may be
         * any of the following:
         * name,description,story_type,requested_by,owned_by,label_ids,comments,task
         * @return mixed|null
         */
        public function getStoryByIdAndField($storyId, $fields) {
            if (empty($storyId) || empty($fields)) {
                return NULL;
            }
            return json_decode(
                $this->client->get(
                             "/projects/{$this->project}/stories/$storyId?fields=$fields"
                )
            );
        }

        /**
         * gets activity of a particular story
         * @param  int $storyId the story id
         * @return mixed|null
         */
        public function getStoryActivity($storyId) {
            if (empty($storyId)) {
                return NULL;
            }
            return json_decode(
                $this->client->get(
                             "/projects/{$this->project}/stories/$storyId/activity"
                )
            );
        }


        public function getStatusCount($stories_activities) {

            $started = 0;
            $rejected = 0;
            $delivered = 0;
            $finished = 0;
            $unscheduled = 0;

            $undefined = 0;
            $retArray = array();
            foreach ($stories_activities as $storyObj) {

                $owner = isset($storyObj[ 'owned_by' ]) ? $storyObj[ 'owned_by' ] .'|'. time() :
                    'not_owned_' . time();
                $retArray[$owner][ 'owned_by' ] = $owner;
                $retArray[$owner ][ 'point_estimate' ] = $storyObj[ 'point_estimate' ];

                foreach ($storyObj[ 'activity' ] as $changes) {
                    $arrayChange = $changes->changes;

                    foreach ($arrayChange as $current_state) {
                        if (isset($current_state->new_values->current_state)) {
                            switch ($current_state->new_values->current_state) {
                                case 'started':
                                    $started++;
                                    break;
                                case 'finished':
                                    $finished++;
                                    break;
                                case 'delivered':
                                    $delivered++;
                                    break;
                                case 'rejected':
                                    $rejected++;
                                    break;
                                case 'unscheduled':
                                    $unscheduled++;
                                    break;
                                default:
                                    $undefined++;
                            }
                        }
                    }
                }
                $retArray[$owner ][ 'started' ] = $started;
                $retArray[ $owner ][ 'finished' ] = $finished;
                $retArray[$owner ][ 'delivered' ] = $delivered;
                $retArray[$owner ][ 'rejected' ] = $rejected;
                $retArray[$owner ][ 'unscheduled' ] = $unscheduled;
                $retArray[$owner ][ 'undefined' ] = $undefined;
            }
            return $retArray;
        }


        /**
         * gets PivotalTracker table result
         * @param array $arrayVal
         * @return string html table
         */
        public function getPivotalTrackerTbl($arrayVal)
        {
            return $this->makeTable($arrayVal);
        }

        /**
         * creates PivotalTracker table result
         * @param array $arr
         * @return string html table
         */
        private function makeTable($arr)
        {
            $retStr = '<div style="padding-top:250px;line-height: 25px;"><table>
                    <th><tr><td></td><h2 style="color: firebrick;">No Information Found</h2></td></tr></th></table></div>';
            if(!empty($arr))
            {
                $retStr = <<<header
                    <table>
                    <th><tr>
                        <td>&nbsp;&nbsp;<strong>Owned By</strong>&nbsp;&nbsp;</td>
                        <td>&nbsp;&nbsp;<strong>Point Est.</strong>&nbsp;&nbsp;</td>
                        <td>&nbsp;&nbsp;<strong># Started</strong>&nbsp;&nbsp;</td>
                        <td>&nbsp;&nbsp;<strong># Finished</strong>&nbsp;&nbsp;</td>
                        <td>&nbsp;&nbsp;<strong># Delivered</strong>&nbsp;&nbsp;</td>
                        <td>&nbsp;&nbsp;<strong># Rejected</strong>&nbsp;&nbsp;</td>
                        <td>&nbsp;&nbsp;<strong># Unscheduled</strong>&nbsp;&nbsp;</td>
                        <td>&nbsp;&nbsp;<strong>#Not Defined</strong>&nbsp;&nbsp;</td>
                    </tr></th><tbody>
header;


               foreach($arr as $k=>$v)
               {
                  $owner = explode('|',$v["owned_by"]);
                  $retStr .= '<tr>'.
                                '<td style="nowrap:nowrap;"><strong>'. $owner[0] . '</strong>&nbsp;&nbsp</td>' .
                                '<td>'. $v["point_estimate"] . '</td>' .
                                '<td>'. $v["started"] . '</td>' .
                                '<td>'. $v["finished"] . '</td>' .
                                '<td>'. $v["delivered"] . '</td>' .
                                '<td>'. $v["rejected"] . '</td>' .
                                '<td>'. $v["unscheduled"] . '</td>' .
                                '<td>'. $v["undefined"] . '</td>'.
                             '</tr>';
               }
               $retStr .= '</tbody></table>';

            }
            return $retStr;
        }
    }

    $tag =  array_key_exists('tag',$_GET) && !empty($_GET['tag'])?trim($_GET['tag']) : null;
    $echoVal = '';
    if($tag == 1)
    {
        $apiKey = array_key_exists('apiKey',$_POST) && !empty($_POST['apiKey'])?trim($_POST['apiKey']) : null;
        $projectId = array_key_exists('projectId',$_POST) && !empty($_POST['projectId']) && is_numeric($_POST['projectId'])?trim($_POST['projectId']) : null;
        if(is_null($apiKey) || is_null($projectId))
        {
            $echoVal =  '<span style="color: red; font-weight: bold; font-size: 20px;">DATA DOES NOT COMPUTE</span>';
        }
        else
        {
            $tst = new PTv5MJFreeway($apiKey, $projectId);
            $stories_and_activities = $tst->getStoriesAndActivities();

            $arrayVal = $tst->getStatusCount($stories_and_activities);
            $echoVal =   $tst->getPivotalTrackerTbl($arrayVal);
        }
    }
    elseif($tag == 2)
    {
        $startId = array_key_exists('startId',$_POST) && !empty($_POST['startId'])?trim($_POST['startId']) : null;
        $endId = array_key_exists('endId',$_POST) && !empty($_POST['endId'])?trim($_POST['endId']) : null;
         $echoVal = '<div style="padding-top:250px;line-height: 25px;"><table>'.
                    '<th><tr><td></td><h1 style="color: red;">Not Ready For Prime Time</h1></td></tr></th></table></div>';
    }

echo $echoVal;
