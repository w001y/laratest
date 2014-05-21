<?php

class BaseModel extends \Eloquent {
	protected $fillable = [];


    /**
     * Run a SQL query
     *
     * @param $sql
     * @param $bind_array
     * @return mixed
     */
    public function run_sql_query($sql, $bind_array = [])
    {
        return DB::select(DB::raw($sql), $bind_array);
    }


    /**
     * @param $data_type
     * @param $table
     * @param $json_key
     * @param $input_array_value
     * @return mixed
     */
    public function match_filter_to_schema($data_type, $table, $json_key, $input_array_value)
    {
        $sql = "SELECT id FROM ".$table." WHERE (json_data->>'".$json_key."')::".$data_type." = ? LIMIT 1";

        $bind_array = [$input_array_value];

        $reply = $this->run_sql_query($sql, $bind_array);

        return $reply;
    }



    /**
     * Find a row within a table based on json_data->>'id'
     *
     * Note that it's not searching based on the table's standard 'id' primary key - it's an id within the json.
     *
     * @param $table
     * @param $id
     * @return bool|mixed
     */
    public function find_resource_by_json_id($table, $id)
    {
        // Set the SQL and the bind array
        $sql        = "SELECT id, json_data FROM ".$table."
            WHERE json_data->>'id'=?
            AND deleted_at IS NULL
            ORDER BY json_data->>'id' ASC
            LIMIT 1";
        $bind_array = [$id];

        // Run the query
        $reply      = $this->run_sql_query($sql, $bind_array);

        if(count($reply) == 1)
        {
            // Return the JSON data.
            $returned_data = [];
            foreach($reply as $subreply)
            {
                $subreply_id = $subreply->id;

                $returned_data[$subreply_id] = json_decode($subreply->json_data);

                break;
            }


            return (object) $returned_data;
        }
        return false;
    }


    /**
     * Find all resources based on a specific attribute value
     *
     *
     * @param $table
     * @param $key
     * @param $value
     * @return mixed
     */
    public function find_resources_by_json_attribute($table, $key, $value)
    {
        // Set the SQL and the bind array
        $sql        = "SELECT * FROM ".$table."
            WHERE json_data->>'".$key."'=?
            AND deleted_at IS NULL
            ORDER BY json_data->>'id' ASC
            LIMIT 1";
        $bind_array = [$value];

        // Run the query
        $reply      = $this->run_sql_query($sql, $bind_array);

        return $reply;
    }



    /**
     * Taking incoming parameters from a POST, save the details to a specific model (already instantiated)
     *
     * @param $model
     * An already-instantiated data model
     *
     *
     * @param $model_name
     * The name of the model - used in call_user_func() so it can be called statically
     *
     * @param $table
     * The name of the DB table
     *
     * @param $params
     * The incoming POST array
     *
     * @return array
     */
    public function create_or_update($model, $model_name, $table, $params)
    {

        /**
         * Set some defaults.
         */
        $resource           = $model;
        $key                = 'resource_id';
        $action             = 'created';
        $existing_resource_data  = [];


        /**
         * If 'id' is set in incoming params set, search for it in the table's json_data field.
         * If found, update the record.
         * If not found, create a new record.
         */
        if(isset($params['id']))
        {
            $resource_exists = $this->find_resource_by_json_id($table, $params['id']);

            if($resource_exists)
            {
                foreach($resource_exists as $resource_id => $actual_resource)
                {
                    $resource       = call_user_func(array($model_name, 'find'), $resource_id);
                    break;
                }

                $existing_resource_data  = (array) json_decode($resource->json_data);

                $key                = 'resource_id_updated';
                $action             = 'updated';
            }
        }




        // Recast $params to be appended to/updated
        $params             = array_merge($existing_resource_data, $params);


        // Save the resource
        $resource->json_data = json_encode($params);
        $resource->save();


        // Save the change in data, in case it's necessary to check on data versions
        $this->save_data_version($table, $resource->id, $params['id'], $existing_resource_data, $params);


        // Save a generic event for the action based on the data at hand
        $event_array = $this->set_crud_action_event_array($model_name, $resource, $action);
        SystemEvent::register($event_array);


        /**
         * Set a reply.
         */
        return $reply = [
            $key => $resource->id
        ];
    }




    /**
     * Save all versions of all API data.
     *
     * @param $table
     * @param $resource_id
     * @param $client_id
     * @param array $original
     * @param array $new
     */
    private function save_data_version($table, $resource_id, $client_id, array $original, array $new)
    {
        $update = [];
        $update['api_account_id']   = $this->api_account_id;
        $update['table']            = $table;
        $update['resource_id']      = $resource_id;
        $update['client_identifier'] = $client_id;
        $update['datetime']         = date("Y-m-d H:i:s");
        $update['original']         = $original;
        $update['new']              = $new;

        $updates = new DataVersion();
        $updates->json_data         = json_encode($update);
        $updates->save();
    }


    /**
     * Set an event array for a generic API object create/update/delete.
     *
     * @param $model_name
     * @param $resource
     * @param $action
     * @return array
     */
    protected function set_crud_action_event_array($model_name, $resource, $action)
    {
        $event_array = [
            'type'              => $model_name." - ".substr($action,0, -1),         // 'created' => 'create', 'updated' => 'update', 'deleted' => 'delete',
            'details'           => $model_name." Resource ID ".$resource->id." ".$action." by API Account ID ".$this->api_account_id." from IP ".$_SERVER['REMOTE_ADDR'],
            'resource_id'       => $resource->id,
            'endpoint'          => $this->endpoint,
            'api_account_id'    => $this->api_account_id,
            'ip'                => $_SERVER['REMOTE_ADDR'],

            // Place more info in here as you see fit - possibly a bit more info on the API client whodunit?
        ];

        return $event_array;
    }


}