<?php

class Member extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'members';

    protected $softDelete = true;


    /**
     * Member All-time spend
     *
     * @param $person_id
     * @return mixed
     */
    public function get_member_net_spend($person_id)
    {
        $sql = "
            SELECT
            SUM((json_data->>'total')::float) AS total
            FROM
            transactions
            WHERE
            (json_data->>'person_id') = ?
            AND
            deleted_at IS NULL
        ";

        $bind_array = [$person_id];

        $result = $this->run_sql_query($sql, $bind_array);

        return $result;
    }



    public function get_member_net_spend_last_x_days($person_id)
    {

        $now = date("Y-m-d H:i:s");
        $days_start = date("Y-m-d H:i:s", strtotime($now."-".$_ENV['DAYS_THRESHOLD']." days"));

        $sql = "
            SELECT
            SUM((json_data->>'total')::float) as total
            FROM
            transactions
            WHERE
            (json_data->>'person_id') = ?
            AND
            (json_data->>'datetime_sold') >= ?
            AND
            deleted_at IS NULL
        ";

        $bind_array = [$person_id, $days_start];

        $result = $this->run_sql_query($sql, $bind_array);

        return $result;
    }




    public function get_all_member_rows($table, $person_id, $order_by = null, $asc_desc = "ASC")
    {

        $orderby_field = "id";
        if($order_by != null)
        {
            $orderby_field = $order_by;
        }

        $sql = "
            SELECT *
            FROM
            ".$table."
            WHERE
            (json_data->>'person_id') = ?
            AND
            deleted_at IS NULL
            ORDER BY ".$orderby_field."
            ".$asc_desc."
        ";

        $bind_array = [$person_id];

        $result = $this->run_sql_query($sql, $bind_array);

        return $result;
    }


    public function get_member_tier($person_id)
    {

        $table = 'customer_journeys';

        $sql = "
            SELECT *
            FROM
            ".$table."
            WHERE
            (json_data->>'person_id') = ?
            AND
            deleted_at IS NULL
            ORDER BY (json_data->>'id') DESC LIMIT 1
        ";

        $bind_array = [$person_id];

        $result = $this->run_sql_query($sql, $bind_array);



        return $result;
    }



}
