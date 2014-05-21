<?php

class IronWorkerLog extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'ironworker_log';


    /**
     * REGISTER AN OUTGOING IRONWORKER QUEUE JOB
     *
     * Every time a dev creates a job that leverages Iron.io for queue management
     *
     *
     *
     * @param array $event_array
     */
    public static function register(array $event_array)
    {
        $required_keys = [
            'id',
            'description'
        ];

        foreach($required_keys as $required_key)
        {
            if(!isset($event_array[$required_key]))
            {
                App::abort(400, '
Arkade Loyalty Platform Exception:

IronWorkerLog::register($log_array) input array requires "id" (a random sha1) and "description" parameters. A parameter is missing.

You may add any other attribute you like to the array for more verbose ironworker event logging.

Please see [] for more details.');
            }
        }

        // If we get this far, then all is good.
        $event = new IronWorkerLog();

        // Add a datetime
        $event_array['sent_on'] = date("Y-m-d H:i:s");
        $event_array['done']    = "0";

        // Save the outgoing log
        $event->json_data = json_encode($event_array);
        $event->save();
    }


    /**
     * MARK AN INCOMING IRONWORKER QUEUE JOB AS DONE
     *
     * Every time ironworker sends a job back in to us.
     *
     * @param $job_id
     */
    public static function mark_done($job_id)
    {
        $model = new BaseModel();
        $resource = $model->find_resource_by_json_id('ironworker_log', $job_id);

        if(!$resource)
        {
            // Log a system error event
            Log::info('Could not find job with json id '.$job_id);
        }
        else
        {
            foreach($resource as $resource_id => $json_data)
            {

                $ironworker_job = IronWorkerLog::find($resource_id);

                if($ironworker_job)
                {
                    $json_data->received_on = date("Y-m-d H:i:s");
                    $json_data->done = '1';

                    $json_data = json_encode($json_data);
                    $ironworker_job->json_data = $json_data;
                    $ironworker_job->save();
                }
            }
        }
    }

}
