<?php

class Reward extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'rewards';

    protected $softDelete = true;



    public function get_member_reward_balance($person_id)
    {
        $now = date("Y-m-d H:i:s");

        $sql = "
            SELECT
            SUM((json_data->>'amount_remaining')::float) as total
            FROM
            rewards
            WHERE
            (json_data->>'person_id') = ?
            AND
            (json_data->>'expiry_date') >= ?
            AND
            deleted_at IS NULL
        ";

        $bind_array = [$person_id, $now];

        $result = $this->run_sql_query($sql, $bind_array);

        if($result)
        {
            return $result[0]->total;
        }

        return "0";

    }


    public function get_member_redeemable_rewards($person_id)
    {
        $now = date("Y-m-d H:i:s");

        $sql = "
            SELECT
            *
            FROM
            rewards
            WHERE
            (json_data->>'person_id') = ?
            AND
            (json_data->>'expiry_date') >= ?
            AND
            (json_data->>'amount_remaining')::float > 0
            AND
            deleted_at IS NULL
            ORDER BY (json_data->>'issue_date') ASC
        ";

        $bind_array = [$person_id, $now];

        $result = $this->run_sql_query($sql, $bind_array);

        return $result;
    }



}
