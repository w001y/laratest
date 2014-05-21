<?php

class EmailQueue extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'email_queue';

    protected $softDelete = true;


    public function get_queued_emails($num = 5)
    {
        $sql = "
            SELECT * FROM
            ".$this->table."
            WHERE (json_data->>'done')::int = '0'
            AND (json_data->>'retries')::int <= '3'
            ORDER BY id ASC
            LIMIT ?
        ";

        $bind_array = [$num];

        return $this->run_sql_query($sql, $bind_array);
    }
}
