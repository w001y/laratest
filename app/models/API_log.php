<?php

class API_log extends BaseModel {
	protected $guarded = array();

	public static $rules = array();

    protected $table = 'api_log';


    public function push_api_request_metric($request_details = null)
    {
        // Set the channel and event type
        $pusher_channel = 'api_metrics_channel';
        $pusher_event   = 'api_call';

        $now = date("Y-m-d H:i:s");

        // Pop the event data into an array
        $pusher_event_info                  = [];
        $pusher_event_info['timestamp']     = $request_details->created_at;

        if(isset($request_details->endpoint) && (trim($request_details->endpoint) != ""))
        {
            $pusher_event_info['endpoint'] = $request_details['endpoint'];
        }

        // Send the event.
        $pusher = new PusherWebSockets();
        $success = $pusher->push_event($pusher_channel, $pusher_event, (array) $pusher_event_info);

        // Return the pusher API reply.
        return $success;
    }
}
