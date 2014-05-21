<?php


/**
 * Class QueueTest
 *
 * Test the iron.io queues
 *
 */
class QueueTest {

    /**
     * Tests the queue is working by appending to a file
     *
     * @param $job
     * @param $data
     */
    public function fire($job, $data)
    {

        // Test this script anywhere (but here) by calling the following:
        // Queue::push('QueueTest', array('string' => 'Hey everyone! It is now '.date("Y-m-d H:i:s")));


        File::append(storage_path('logs/queue.txt'), $data['string'].PHP_EOL);

        // Deletes the job from the iron.io queue
        $job->delete();
    }
}


/**
 * Class SetTableJsonIndex
 *
 * Sets an index on an attribute within a table's json_data field, for ease of search.
 */
class SetTableJsonIndex {

    public function fire($job, $data)
    {
        $queued_indexes = SystemIndexes::where('queued', '=', '1')->get();


        foreach($queued_indexes as $queued_index)
        {
            $keep_index = "0";
            $set_live   = "0";

            // Does this index already exist? (If queued = 0, then it does)
            $existing_index = SystemIndexes::where('queued', '=', '0')
                ->where('table_name', '=', $queued_index->table_name)
                ->where('json_attribute', '=', $queued_index->json_attribute)
                ->first();

            if(!$existing_index)
            {

                if(Schema::hasTable($queued_index->table_name))
                {
                    // Create the index

                    try{

                        DB::statement("CREATE INDEX ON ".$queued_index->table_name."
                        ((json_data->>'".$queued_index->json_attribute."'))");

                        // Set the event details
                        $event_array = [
                            'type' => 'Database Indexes',
                            'details' => 'DB Index "'.$queued_index->json_attribute.'" created on table "'.$queued_index->table_name.'".'
                        ];

                        $set_live   = "1";
                    }
                    catch(Exception $e)
                    {
                        // Set the event details
                        $event_array = [
                            'type' => 'Database Indexes',
                            'details' => 'ERROR creating DB Index "'.$queued_index->json_attribute.'" on table "'.$queued_index->table_name.'" - "'.$e->getMessage().'".'
                        ];

                        $set_live   = "0";
                    }

                    // Keep the index - we're trying to create the index. The $set_live variable will tell us whether to set it live or not.
                    $keep_index = "1";
                }
                else
                {
                    // Set the event details
                    $event_array = [
                        'type' => 'Database Indexes',
                        'details' => 'Could not create DB Index "'.$queued_index->json_attribute.'" on table "'.$queued_index->table_name.'" - table does not exist.'
                    ];

                    // Don't keep the new listing - table doesn't exist
                    $keep_index = "0";
                }


            }
            else
            {
                // Set the event details
                $event_array = [
                    'type' => 'Database Indexes',
                    'details' => 'Could not create DB Index "'.$queued_index->json_attribute.'" on table "'.$queued_index->table_name.'" - index already exists.'
                ];

                // Don't keep the new listing - index exists already
                $keep_index = "0";
            }


            // Save the event to the DB
            SystemEvent::register($event_array);

            $queued_index_listing = SystemIndexes::find($queued_index->id);
            if($keep_index == "0")
            {
                // Don't keep the new index - delete
                $queued_index_listing->delete();
            }
            else
            {
                if($set_live == "1")
                {
                    $queued_index_listing->queued = "0";
                }
                else
                {
                    // It's already queued = 1, do nothing
                }

                $queued_index_listing->save();
            }
        }


        // Deletes the job from the iron.io queue
        $job->delete();

        // Save the incoming job's ID and mark it as done
        IronWorkerLog::mark_done($job->getJobId());

    }
}



class CustomerEvaluate {

    public function fire($job, $data)
    {

        if(!isset($data['person_id']))
        {
            exit;
        }

        $member_model = new Member();

        $person_id = $data['person_id'];
        $person_resource = $member_model->find_resource_by_json_id('members', $person_id);

        if(!$person_resource)
        {
            exit;
        }



        foreach($person_resource as $resource_id => $person_data)
        {
            $person_model = Member::find($resource_id);
            break;
        }


        if(!$person_model)
        {
            exit;
        }

        /**
         *  UNDERSCORES
         *
         * - Net spend to date
         * - Net spend last 365 in last 365
         * - Member Tier
         * - # transactions to date
         * - # trans last 365
         * - $ reward balance
         * - available rewards
         * - Tier progress (spend $50 n next X days to get to diamond)
         * - Email activity status (bounced/blacklisted etc)
         * - + loads more
         */

        // Set the reply array with some defaults
        $new_params                                 = [];
        $new_params['id']                           = $person_id;
        $new_params['_net_purchases_all_time']      = "0";
        $new_params['_net_purchases_last_365']      = "0";
        $new_params['_member_tier']                 = "Standard";
        //$new_params['_member_tier_upgrade_text']    = "Spend $500 in the next 365 days to upgrade to Gold Status";



        // Net spend to date - this might return false
        $net_purchases_all_time     = $member_model->get_member_net_spend($person_id);
        if($net_purchases_all_time)
        {
            $new_params['_net_purchases_all_time']    = $net_purchases_all_time[0]->total;
        }


        // Net spend last 365 - this might return false
        $net_purchases_last_365     = $member_model->get_member_net_spend_last_x_days($person_id);
        if($net_purchases_last_365)
        {
            $new_params['_net_purchases_last_365']    = $net_purchases_last_365[0]->total;
        }


        // Member Tier
        $customer_journey_model = new CustomerJourneys();
        $customer_journey_model->calculate_customer_journeys($person_id);

        $member_tier     = $member_model->get_member_tier($person_id);
        if($member_tier)
        {
            $json_object = json_decode($member_tier[0]->json_data);
            $new_params['_member_current_tier']    = $json_object->to;

        }



        // Set a _member_last_evaluated date
        $new_params['_member_last_evaluated']    = date("Y-m-d H:i:s");
        // Update the member
        $member_model->create_or_update($person_model, 'Member', 'members', $new_params);



        // Deletes the job from the iron.io queue
        $job->delete();

        // Save the incoming job's ID and mark it as done
        IronWorkerLog::mark_done($job->getJobId());
    }
}

class CustomerEvaluateJourneys{

    public function fire($job, $data)
    {


        if(!isset($data['person_id']))
        {
            exit;
        }

        $person_id = $data['person_id'];


        $customer_journey_model = new CustomerJourneys();
        $customer_journey_model->calculate_customer_journeys($person_id);

        // Deletes the job from the iron.io queue
        $job->delete();

        // Save the incoming job's ID and mark it as done
        IronWorkerLog::mark_done($job->getJobId());
    }
}


class CustomerApplyReward{

    public function fire($job, $data)
    {
        // Delete this when accepted
        // Q: Can returned SKUs be processed in the same transaction as a purchase SKU? YES - Confirmed by Danny




        /**
         * Required:
         * - transaction_id
         * - person_id
         * - transaction_items
         *      - product ID / sku
         *      - product category
         *      - value (+/-)
         *
         * To do:
         * - Work out what they have just purchased
         * - Check returncount against member resource
         * - Apply reward logic to purchase
         * - Apply reward - add to rewards table
         * - Create plain-english event(s) for what just happened
         * - Queue email
         */



        // Deletes the job from the iron.io queue
        $job->delete();

        // Save the incoming job's ID and mark it as done
        IronWorkerLog::mark_done($job->getJobId());
    }
}


