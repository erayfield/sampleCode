<?php

    /**
     * Created by erayfield
     * User: order api
     *
     * The view_order function polls a subscriber’s system and returns order details.
     *
     * Calls to view_order should be HTTP POST’d to
     * https://xxx.xxxxxx.com/[alias]/api/order/view_order
     *
     * The following parameters should be included as fields inside the request’s data object:
     *
     * Field               Description                Notes
     * patient_nid     The unique id of a patient.    Required. Numeric.
     * order_id                                       Optional.  If no order_id is passed in,
     *                                                then we will return all orders for this patient.
     *
     * Upon successful request, the following data will be returned in the response_details object, in JSON format.
     *
     * Field     Description    Notes
     * order_info   A JSON Array of order_info objects
     * success      Returns true if successful.
     *
     * The order_info object contains the following fields to define the orders associated to this patient.
     * Field                Description              Notes
     * order_id                                      Numeric.NOTE:  Only completed orders will be returned.
     * order_status         Status of the order
     * order_location_nid    Location that the       Numeric.  Returns the unique ID of the location.
     *                      order was placed at.
     * order_location_name  Name of the location     Alpha-numeric.
     *                      that the order was
     *                      placed at.
     * completed_date       Date order completed     Format: YYYY-MM-DD.  This will be NULL if the order is not completed.
     * order_amount         Total that the patient   This is the final order amount.  This is after taxes.
     *                      paid for the order
     * order_taxes          Total taxes on order
     * order_products       A JSON Array of order_products objects
     *
     * The order_products object contains the following fields to define the products on a specific order.
     * Field                   Description              Notes
     * inventory_nid           Unique ID for each       Numeric
     *                         inventory item.
     * inventory_name          Name of inventory item.  Optional
     * product_sku             SKU for the product      Required.
     * quantity                Quantity of the product  Numeric.  This will be NULL if this is a weight-priced product.
     * sold_weight_dispensed   Sold weight dispensed    This may be NULL is this is NOT a weight-priced product.
     *                         to the patient.
     * actual_weight_dispensed Actual weight dispensed  This might differ from the sold weight dispensed if the patient was
     *                         to the patient.          dispensed more than what they were charged for.
     *                                                  This may be NULL is this is NOT a weight-priced product.
     *
     * In the event of an error, the following error codes may be returned:
     * Error ID        Description
     * BADPATIENTID    The patient ID given was not a valid ID existing in the system.
     * BADORDERID      The Order ID given was not a valid ID existing in the system.
     */
    class OrderViewOrder extends mj_integration_api_handler
    {
        private $bad_patient_id       = 'BADPATIENTID';
        private $bad_patient_id_blurb = 'The patient ID given was not a valid ID existing in the system.';

        private $bad_order_id       = 'BADORDERID';
        private $bad_order_id_blurb = 'The Order ID given was not a valid ID existing in the system.';

        /**
         * clean up
         */
        public function __destruct() {
            foreach ($this as $obj) {
                unset($obj);
            }
        }

        /**
         * jason decodes the POST data and sets data
         */
        function process_post_variables() {
            $this->data = $_POST;

        }

        /**
         * validates all required parameters
         * gets called from
         * @return bool
         */
        function validate() {
            $this->error_code = null;

            /**
             * patient_nid is required
             */
            $patient_nid = $this->qualify($this->data, 'patient_nid', 'number');
            $retBool = $this->verifyValues($patient_nid);

            if ($retBool) {
                //check patient id
                $valid_patient = $this->valid_patient_nid($patient_nid);
                if ($valid_patient && !empty($this->data['order_id'])) {

                    /**
                     * order_id is optional
                     */
                    $order_id = $this->qualify($this->data, 'order_id', 'number');
                    if (!is_null($order_id)) {
                        $retBool = $this->verify_order_id($order_id, $patient_nid);
                    }
                }
            }

            return $retBool;
        }

        /**
         * this is the 'controller' and or constructor
         * @return array
         */
        function process() {
            $patient_nid = trim($this->data['patient_nid']);
            $order_id = trim($this->data['order_id']);
            $output = array();
            $allOrders = null;
            $order_info = null;


            $security_key = mj_encrypt_get_key();
            //please note, queries are at end of class
            if (!empty($order_id)) {

                $allOrders = db_query($this->sqlWithOrderId, $order_id);
            } else {echo $patient_nid.'  '.$order_id;
                $allOrders = db_query($this->sqlNoOrderId, $patient_nid);
            }

            if ($allOrders !== false) {
                $output = $this->get_return_array($allOrders, $order_info);
                //if(count($output) >=1) $output->success = true;
            } else {
                $this->error_code = $this->error_id;
                $this->validation_msg = $this->PRIVATE_TO_CLIENT($this->error_message);
                $output->success = false;
            }

            $output->response_details['show_output'] = true;
            //And this API is defined to always return in JSON form
            $this->format = 'JSON';

            return $output;
        }

        /**
         * verifies if value is null, if so sets the error messages
         *
         * @param mixed int/null $patient_nid
         *
         * @return bool
         */
        private function verifyValues($patient_nid) {
            $retBool = true;
            if (is_null($patient_nid)) {
                $this->error_code = $this->bad_patient_id;
                $this->validation_msg = $this->PRIVATE_TO_CLIENT($this->bad_patient_id_blurb);
                $retBool = false;
            }
            return $retBool;
        }

        /**
         * @param $patient_nid
         *
         * @return bool
         */
        private function valid_patient_nid($patient_nid) {
            $retBool = true;
            $sql = <<<mysql1
                SELECT
                    field_profile_active_value
                FROM
                    content_type_profile
                WHERE
                    nid = %d
mysql1;

            //verify is active and a patient profile, return 0 or 1
            $existing = db_fetch_object(db_query($sql, $patient_nid));
            if (!$existing) {
                $this->error_code = $this->bad_patient_id;
                $this->validation_msg = $this->PRIVATE_TO_CLIENT($this->bad_patient_id_blurb);
                $retBool = false;
            }
            return $retBool;
        }

        /**
         * verifies order id exists    8  12 43  45  order id , nid 466
         *
         * @param integer $order_id
         * @param integer $patient_nid
         *
         * @return bool
         */
        private function verify_order_id($order_id, $patient_nid) {
            $retBool = false;
            $sql = <<<mysql2
                SELECT
                  `uc_orders`.`order_id`
                FROM
                  `uc_orders`
                  INNER JOIN `node` ON (`uc_orders`.`uid` = `node`.`uid`)
                WHERE
                  `node`.`nid` = %d AND
                  `uc_orders`.`order_id` = %d

mysql2;

            $queryRes = db_fetch_object(db_query($sql, $patient_nid, $order_id));
            //make sure the requested time is not in the past
            if ($queryRes) {
                $retBool = true;
            } else //order id isn't right
            {
                $this->error_code = $this->bad_order_id;
                $this->validation_msg = $this->PRIVATE_TO_CLIENT($this->bad_order_id_blurb);
            }

            return $retBool;
        }

        /**
         * @param \xxx $
         */
        private function PRIVATE_TO_CLIENT($xxx)
        {
            //commented out, private to client
        }

        /**
         * @param array $dataArray    POST or GET array (generally)
         * @param string $valueString the key being checked in the array
         * @param string $type        the type of key to check for, if string is 'number' then
         *                            will check if numeric, if string is 'bool' then will verify boolean, otherwise
         *                            just checks that there is data
         *                            Will return value from the POST/GET or NULL depending of if the key is correct
         * @param mixed               array or null if there are only certain types of string which the $valueString must
         *                                  be, a single array of strings which will be compared (case-insensitive, binary
         *                                  save) the $valueString to the items in array, if there is a match, then that
         *                                  value will be returned, otherwise null will be returned
         *
         * @return bool|int|null|string
         */
        private function qualify($dataArray, $valueString, $type, $options = null) {
            $retVal = NULL;
            $tstVal = trim($valueString);

            switch ($type) {
                case 'number':
                    $retVal = array_key_exists($tstVal, $dataArray) && !empty($dataArray[$tstVal]) && is_numeric($dataArray[$tstVal]) ?
                        $dataArray[$tstVal] : $retVal;
                break;
                case 'bool':
                    $retVal = array_key_exists($tstVal, $dataArray) && is_bool($dataArray[$tstVal]) ?
                        $dataArray[$tstVal] : $retVal;
                break;
                case 'string_with_options':

                    if (!empty($options) && is_array($options)) {
                        //first make sure have a value to compare
                        $retValString = $this->qualify($dataArray, $valueString, 'string');

                        //if do, then loop through the array
                        if (!is_null($retValString)) {
                            foreach ($options as $match) {
                                if (strcasecmp(trim($match), trim($retValString)) == 0) {
                                    $retVal = $retValString;
                                }
                            }
                        }
                    }
                break;
                case 'string':
                    $retVal = array_key_exists($tstVal, $dataArray) && !empty($dataArray[$tstVal]) ?
                        $dataArray[$tstVal] : $retVal;
                break;
                default:
                    $retVal = array_key_exists($tstVal, $dataArray) && !empty($dataArray[$tstVal]) ?
                        $dataArray[$tstVal] : $retVal;
            }
            return $retVal;
        }

        /**
         * sets up the return array, based on query results
         *
         * @param $allOrders
         * @param $order_info
         *
         * @return object $order_info
         */
        private function get_return_array($allOrders, $order_info) {
            $i = -1;
            $y = 0;
            $ckOrderId = 0; echo __METHOD__.'<br>';
            while ($res = db_fetch_object($allOrders)) {

                if ($ckOrderId != $res->order_id) {
                    $i++;
                    $order_info->response_details[$i]['order_id'] = $res->order_id;

                    $temp_status = str_ireplace('_', ' ', $res->order_status);
                    $order_info->response_details[$i]['order_status'] = $res->$temp_status;
                    $order_info->response_details[$i]['order_location_nid'] = $res->order_location_nid;
                    $order_info->response_details[$i]['order_location_name'] = $res->order_location_name;
                    $temp_comp_date = null;
                    if (!empty($res->completed_date)) {
                        $temp_comp_date = date("Y-m-d", $res->completed_date);
                    }
                    $order_info->response_details[$i]['completed_date'] = $temp_comp_date;
                    $order_info->response_details[$i]['order_amount'] = number_format($res->order_amount, 2);
                    $order_info->response_details[$i]['order_taxes'] = number_format($res->order_taxes, 2);
                    $ckOrderId = $res->order_id;

                    $y = 0;
                }

                //                --------------  a json array of order_product objects which go with the order id
                $order_info->response_details[$i]['order_product'][$y]['inventory_nid'] = $res->inventory_nid;
                $order_info->response_details[$i]['order_product'][$y]['order_product_id'] = $res->order_product_id;
                $order_info->response_details[$i]['order_product'][$y]['inventory_name'] = $res->inventory_name;
                $order_info->response_details[$i]['order_product'][$y]['product_sku'] = $res->product_sku;
                $order_info->response_details[$i]['order_product'][$y]['quantity'] = $res->quantity;
                $order_info->response_details[$i]['order_product'][$y]['sold_weight_dispensed'] = $res->sold_weight_dispensed;
                $order_info->response_details[$i]['order_product'][$y]['actual_weight_dispensed_uom'] = $res->actual_weight_dispensed_uom;
                $order_info->response_details[$i]['order_product'][$y]['sold_weight_dispened_uom'] = $res->sold_weight_dispened_uom;

                $y++;
            }
            if ($i > 0) {
                $order_info->success = true;
            } else {
                $order_info->success = false;
                $this->error_code = $this->bad_order_id;
                $this->validation_msg = PRIVATE_TO_CLIENT($this->bad_order_id_blurb);
            }
            return $order_info;
        }


        private $sqlWithOrderId = <<<query
    SELECT
      `uco`.`order_id`,
      `node1`.`title` AS `order_location_name`,
      `uco`.`order_status`,
      `uco`.`location_nid` AS `order_location_nid`,
      `mjo`.`completed_date`,
      `uco`.`order_total` AS `order_amount`,
      `uop`.`order_product_id` AS `order_product_id`,
      `uop`.`nid` AS `inventory_nid`,
      `uop`.`title` AS `inventory_name`,
      `uop`.`model` AS `product_sku`,
      `uop`.`qty` AS `quantity`,
      `weight`.`weight` AS `sold_weight_dispensed`,
      `weight`.`adj_weight` AS `actual_weight_dispensed`,
      `weight`.`weight_uom` AS `sold_weight_dispensed_uom`,
      `weight`.`adj_weight_uom` AS `actual_weight_dispensed_uom`,
      SUM(`uc_order_line_items`.`amount`) AS `order_taxes`
    FROM
      `uc_orders` `uco`
      INNER JOIN `mj_orders` `mjo` ON (`mjo`.`order_id` = `uco`.`order_id`)
      INNER JOIN `uc_order_products` `uop` ON (`uco`.`order_id` = `uop`.`order_id`)
      INNER JOIN `node` `node1` ON (`uco`.`location_nid` = `node1`.`nid`)
      INNER JOIN `mj_order_product_weights` `weight` ON (`uop`.`order_product_id` = `weight`.`order_product_id`)
      INNER JOIN `uc_order_line_items` ON (`uco`.`order_id` = `uc_order_line_items`.`order_id`)
    WHERE
      `uc_order_line_items`.`type` = 'tax' AND
      `uco`.`order_id` = %d
    GROUP BY
      `uco`.`order_id`,
      `node1`.`title`,
      `uco`.`order_status`,
      `uco`.`location_nid`,
      `mjo`.`completed_date`,
      `uco`.`order_total`,
      `uop`.`order_product_id`,
      `uop`.`nid`,
      `uop`.`title`,
      `uop`.`model`,
      `uop`.`qty`,
      `weight`.`weight`,
      `weight`.`adj_weight`,
      `weight`.`weight_uom`,
      `weight`.`adj_weight_uom`

query;


        private $sqlNoOrderId = <<<query2
    SELECT
      `uco`.`order_id`,
       `node`.`uid` ,
      `node1`.`title` AS `order_location_name`,
      `uco`.`order_status`,
      `uco`.`location_nid` AS `order_location_nid`,
      `mjo`.`completed_date`,
      `uco`.`order_total` AS `order_amount`,
      `uop`.`order_product_id` AS `order_product_id`,
      `uop`.`nid` AS `inventory_nid`,
      `uop`.`title` AS `inventory_name`,
      `uop`.`model` AS `product_sku`,
      `uop`.`qty` AS `quantity`,
      `weight`.`weight` AS `sold_weight_dispensed`,
      `weight`.`adj_weight` AS `actual_weight_dispensed`,
      `weight`.`weight_uom` AS `sold_weight_dispensed_uom`,
      `weight`.`adj_weight_uom` AS `actual_weight_dispensed_uom`,
      SUM(`uc_order_line_items`.`amount`) AS `order_taxes`,
      `node`.`type`
    FROM
      `uc_orders` `uco`
      INNER JOIN `mj_orders` `mjo` ON (`mjo`.`order_id` = `uco`.`order_id`)
      INNER JOIN `uc_order_products` `uop` ON (`uco`.`order_id` = `uop`.`order_id`)
      INNER JOIN `node` `node1` ON (`uco`.`location_nid` = `node1`.`nid`)
      INNER JOIN `mj_order_product_weights` `weight` ON (`uop`.`order_product_id` = `weight`.`order_product_id`)
      INNER JOIN `uc_order_line_items` ON (`uco`.`order_id` = `uc_order_line_items`.`order_id`)
      INNER JOIN `node` ON (`uco`.`uid` = `node`.`uid`)
    WHERE
      `node`.`type` = 'profile' AND
      `uc_order_line_items`.`type` = 'tax' AND
      `node`.`nid` = %d
    GROUP BY
      `uco`.`order_id`,
      `node1`.`title`,
      `uco`.`order_status`,
      `uco`.`location_nid`,
      `mjo`.`completed_date`,
      `uco`.`order_total`,
      `uop`.`order_product_id`,
      `uop`.`nid`,
      `uop`.`title`,
      `uop`.`model`,
      `uop`.`qty`,
      `weight`.`weight`,
      `weight`.`adj_weight`,
      `weight`.`weight_uom`,
      `weight`.`adj_weight_uom`,
      `node`.`type`

query2;

    }
