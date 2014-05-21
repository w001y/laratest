<?php

class CustomerJourneys extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'customer_journeys';

    protected $softDelete = true;





    public function calculate_customer_journeys($person_id)
    {
        /**
         * PROCESS
         * - Get all customer tiers and form into an array
         * - Get all customer journeys for the person_id
         * - Delete all customer journeys for the person_id
         * - Get all transactions for the customer. Place them into an array for comparison.
         * - Each time a transaction takes place, check their new / updated status
         *
         * For every day since then,
         * - Go through all transactions up to that day
         * - Tot up the last 365 days' worth of transactions prior to this day
         * - Evaluate what tier the user should be in after that day is over.
         * - If the tier is not the previous tier, then record a journey
         */

        /**
         * Get all customer tiers and form into an array
         */
        $membertiers = MemberTiers::where('deleted_at','=', null)->get();

        $membertiers_array = [];

        foreach($membertiers as $resource_id => $membertier_data)
        {

            $membertier_data = json_decode($membertier_data->json_data);

            $this_membertier = [];
            $this_membertier['tier_name']   = $membertier_data->tier_name;
            $this_membertier['days']        = $membertier_data->days;
            $this_membertier['spend_min']   = $membertier_data->spend_min;
            $this_membertier['spend_max']   = $membertier_data->spend_max;
            $this_membertier['tier_name_prev']   = $membertier_data->tier_name_prev;
            $this_membertier['tier_name_next']   = $membertier_data->tier_name_next;

            $membertiers_array[] = $this_membertier;
        }
        // Membertiers array is now set.


        /**
         * Get all customer journeys for the person_id
         */

        $member_model = new Member();
        $customer_journeys = $member_model->get_all_member_rows('customer_journeys', $person_id);

        if($customer_journeys)
        {
            /**
             * Delete all customer journeys for the person_id
             */
            foreach($customer_journeys as $customer_journey)
            {
                $listing = CustomerJourneys::find($customer_journey->id);
                $listing->forceDelete();  // forceDelete physically removes rows that allow for soft deleting.
            }
        }


        /**
         * Get all transactions for the customer. Place them into an array for comparison.
         */

        $transactions = $member_model->get_all_member_rows('transactions', $person_id, "(json_data->>'datetime_sold')", 'ASC');

        $total_transaction_array = [];

        if($transactions)
        {
            /**
             * Get date of first transaction.
             */
            $earliest_date = date("Y-m-d");

            $num_transactions = 0;
            foreach($transactions as $transaction)
            {
                $this_transaction = json_decode($transaction->json_data);
                $total_transaction_array[$num_transactions] = $this_transaction;

                if($num_transactions == 0)
                {
                    $earliest_date = date("Y-m-d", strtotime($this_transaction->datetime_sold));
                }


                $num_transactions++;
            }


            /**
             * For every day since then,
             * Go through all transactions up to that day
             * Tot up the last 365 days' worth of transactions
             * Evaluate what tier the user should be in after that day is over.
             * If the tier is not the previous tier, then record a journey
             */

            $today = date("Y-m-d");
            // $today = date("2015-05-05");
            $current_status = "None";
            $date_to_process = $earliest_date;
            $sum_transactions_within_last_x_days = "0";



            while($date_to_process <= $today)
            {

                // DEBUG
                //Log::info("Working on: ".$date_to_process.". Current Status is ".$current_status.". $ transactions in past ".$_ENV['DAYS_THRESHOLD']." days: ".$sum_transactions_within_last_x_days);


                // To begin, keep the new tier status the same as the old one.
                $new_status = $current_status;


                // DEBUG
                //Log::info("Total Transactions: ".count($total_transaction_array));


                $earliest_active_transaction_date = false;

                /**
                 * GO THROUGH ALL TRANSACTIONS - START
                 */

                // Go through all transactions up to that day
                foreach($total_transaction_array as $key => $transaction_details)
                {
                    //$this->line("TID:".$transaction_details->id);
                    $sum_transactions_within_last_x_days = 0;



                    $datetime_sold = date("Y-m-d", strtotime($transaction_details->datetime_sold));

                    // If there is any transactions on the date being processed:
                    if($datetime_sold == $date_to_process)
                    {
                        // DEBUG
                        //Log::info("Made a transaction for $".$transaction_details->total." on ".$datetime_sold.".");
                    }

                    // For this transaction, set a cutoff date for previous transactions.
                    $earliest_allowed = date("Y-m-d", strtotime($date_to_process."-".$_ENV['DAYS_THRESHOLD']." days"));




                    // - Tot up the last 365 days' worth of transactions prior to this day

                    for($k=0;$k<=$key;$k++)
                    {
                        $this_transactions_date = date("Y-m-d", strtotime($total_transaction_array[$k]->datetime_sold));

                        // Work out the number of days between the earliest allowed and the transaction being evaluated
                        $dStart = new DateTime($earliest_allowed);
                        $dEnd   = new DateTime($this_transactions_date);
                        $dDiff  = $dStart->diff($dEnd);
                        $dDiff->format('%R'); // use for point out relation: smaller/greater

                        $diff_between_earliest_date_and_current_transaction = $dDiff->days;


                        if(
                            ($earliest_allowed <= $this_transactions_date) &&
                            ($diff_between_earliest_date_and_current_transaction <= $_ENV['DAYS_THRESHOLD'])
                        )
                        {

                            if(!$earliest_active_transaction_date)
                            {
                                $earliest_active_transaction_date = date("Y-m-d", strtotime($total_transaction_array[$k]->datetime_sold));

                            }

                            // Reset the total sum of transactions in the past 365 days, including this transaction.
                            $sum_transactions_within_last_x_days += $total_transaction_array[$k]->total;
                        }
                    }
                }

                /**
                 * GO THROUGH ALL TRANSACTIONS - END
                 */

                //$this->line("Earliest active transaction date for $date_to_process: $earliest_active_transaction_date");



                /**
                 * EVALUATE MEMBER TIER FOR TODAY - START
                 */
                // Evaluate what tier the user should be in after this day is over.
                foreach($membertiers_array as $membertier)
                {
                    if(($membertier['spend_min'] <= $sum_transactions_within_last_x_days) && ($membertier['spend_max'] >= $sum_transactions_within_last_x_days))
                    {
                        $new_status = $membertier['tier_name'];


                        // If the tier is not the previous tier, then record a journey
                        if(($new_status != $current_status) || ($date_to_process == $today))
                        {
                            // DEBUG
                            // LOG::info("Date: ".$date_to_process." | New Status: ".$new_status.", as customer has spent $".$sum_transactions_within_last_x_days." in past ".$_ENV['DAYS_THRESHOLD']." days (which is between ".$membertier['spend_min']." and ".$membertier['spend_max'].").");


                            // If the latest datetime sold is over 365 days prior to the current date, set the spend_to_next_tier = the max possible window
                            if($date_to_process == $today)
                            {
                                $days_diff = "0";
                            }
                            else
                            {

                            }


                            // Work out the number of days between today and the currently-inspected date
                            $dStart = new DateTime($date_to_process);
                            $dEnd   = new DateTime($earliest_active_transaction_date);
                            $dDiff  = $dStart->diff($dEnd);
                            $dDiff->format('%R'); // use for point out relation: smaller/greater
                            $days_diff = $dDiff->days;


                            // Save the journey
                            $customer_journey = [
                                'id'                    => date("Ymd", strtotime($date_to_process))."_".trim(str_replace(" ", "", $person_id)),
                                'person_id'             => $person_id,
                                'date_of_journey'       => $date_to_process,
                                'from'                  => $current_status,
                                'to'                    => $new_status,
                                'tier_name_prev'        => $membertier['tier_name_prev'],
                                'tier_name_next'        => $membertier['tier_name_next'],
                                'days_threshold'        => $_ENV['DAYS_THRESHOLD'],
                                'days_to_next_tier_upgrade'   => ($_ENV['DAYS_THRESHOLD'] - $days_diff) > 0 ? ($_ENV['DAYS_THRESHOLD'] - $days_diff) : 1,
                                'spend_to_next_tier_upgrade'  => number_format(($membertier['spend_max'] - $sum_transactions_within_last_x_days), 2),
                                'accumulated_amount'    => $sum_transactions_within_last_x_days,
                            ];

                            $customer_journeys_model = new CustomerJourneys();
                            $customer_journeys_model->json_data = json_encode($customer_journey);
                            $customer_journeys_model->save();

                            // Reset the current status  to be the new status for the next day's inspection.
                            $current_status = $new_status;

                        }
                    }
                }

                /**
                 * EVALUATE MEMBER TIER FOR TODAY - END
                 */


                // Move to the next day.
                $date_to_process = date("Y-m-d", strtotime($date_to_process."+1 day"));
            }
        }
    }

}
