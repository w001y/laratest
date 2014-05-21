<?php

class RewardRedemption extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'reward_redemptions';

    protected $softDelete = true;



    public function get_redemption_by_json_id($redemption_id)
    {
        $sql = "
            SELECT
            *
            FROM
            ".$this->table."
            WHERE
            (json_data->>'id') = ?
            AND
            deleted_at IS NULL
        ";

        $bind_array = [$redemption_id];

        $result = $this->run_sql_query($sql, $bind_array);

        return $result;
    }


}
