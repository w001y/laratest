<?php

class EmailPlatforms extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'email_platforms';

    protected $softDelete = true;


    public function show_active_platform()
    {
        $sql = "
        SELECT *
        FROM ".$this->table."
        WHERE (json_data->>'is_default')::bool = '1'
        ORDER BY id
        LIMIT 1";

        $replies = $this->run_sql_query($sql);

        // The response is an array, return its first reply.
        foreach($replies as $reply)
        {
            $reply_object = json_decode($reply->json_data);
            return $reply_object->platform_name;
        }

        // There's no active email platforms - throw an exception
        throw new Exception("There are no active Email platforms within the application. Please create one using the /emailplatforms endpoint.");

    }

}
