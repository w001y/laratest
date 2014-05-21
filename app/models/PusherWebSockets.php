<?php

class PusherWebSockets {


    public $app_id;
    public $app_key;
    public $app_secret;
    public $pusher;

    public function __construct()
    {
        $this->app_id       = $_ENV['PUSHER_APP_ID'];
        $this->app_key      = $_ENV['PUSHER_APP_KEY'];
        $this->app_secret   = $_ENV['PUSHER_APP_SECRET'];

        $this->pusher = new Pusher( $this->app_key, $this->app_secret, $this->app_id );
    }


    public function push_event($channel, $event, array $payload)
    {
        return $this->pusher->trigger( $channel, $event, $payload );
    }



}