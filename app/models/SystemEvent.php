<?php

class SystemEvent extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'events';


    public static function register(array $event_array)
    {
        $required_keys = [
            'type',
            'details',
        ];

        foreach($required_keys as $required_key)
        {
            if(!isset($event_array[$required_key]))
            {
                App::abort(400, '
Arkade Loyalty Platform Exception:

SystemEvent::register($event_array) input array requires "type" and "details" parameters. One of them is missing.

You may add any other attribute you like to the array for more verbose event logging.

Please see https://arkade.atlassian.net/wiki/pages/viewpage.action?pageId=6127708 for more details.');
            }
        }

        // If we get this far, then all is good.
        $event = new SystemEvent();

        // Add a datetime
        $event_array['datetime'] = date("Y-m-d H:i:s");

        $event->json_data = json_encode($event_array);
        $event->save();

        // Push the event to real-time clients
        $event->push_event_to_console($event);
    }

    /**
     * Push an event to the API management console
     *
     * This function leverages Pusher Websockets.
     * The Management console can be run up locally with a different repo - see here:
     *
     *   https://bitbucket.org/arkadedigital/arkade_laravel41_loyaltymanagementwebapp
     *
     * @param $event_object
     * @return bool|string
     */
    public function push_event_to_console($event_object)
    {
        // Set the channel and event type
        $pusher_channel = 'logs_channel';
        $pusher_event   = 'universal_log_event';

        // Pop the event data into an array
        $pusher_event_info                  = [];
        $pusher_event_info['event_id']      = $event_object->id;
        $pusher_event_info['timestamp']     = $event_object->created_at;

        // Grab the json_data field and pop the details into the array being sent over
        $event_data_object                  = json_decode($event_object->json_data);
        $pusher_event_info['type']          = $event_data_object->type;
        $pusher_event_info['details']       = $event_data_object->details;

        // Send the event.
        $pusher = new PusherWebSockets();
        $success = $pusher->push_event($pusher_channel, $pusher_event, (array) $pusher_event_info);

        // Return the API reply.
        return $success;
    }




}
