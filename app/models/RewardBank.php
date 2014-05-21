<?php

class RewardBank extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'reward_bank';

    protected $softDelete = true;


    public function create_reward_redemption_transaction($reward_id, $person_id, $amount, $amount_refunded, $remaining, $redemption_id)
    {
        $rewardbank_id = $person_id."_".date("YmdHis")."_".str_replace(" ", "", $reward_id);

        $is_refund = "0";
        if($amount_refunded != "0")
        {
            $is_refund = "1";
        }

        $json_array = [
            'id'                => $rewardbank_id,
            'redemption_id'     => $redemption_id,
            'reward_id'         => $reward_id,
            'person_id'         => $person_id,
            'amount_redeemed'   => $amount,
            'amount_refunded'   => $amount_refunded,
            'amount_remaining'  => $remaining,
            'is_refund'         => $is_refund,
            'refunded'          => $is_refund,
            'datetime'          => date("Y-m-d H:i:s"),
        ];

        $rewardbank = new RewardBank();
        $rewardbank->json_data = json_encode($json_array);

        return $rewardbank->save();
    }



    public function log_redemption_event($person_id, $amount, $api_id, $failure = null, $failure_reason = null)
    {
        $event = [];

        if($failure)
        {
            $verb = "unsuccessfully";
            $extra_type = " Failure";
        }
        else
        {
            $verb = "successfully";
            $extra_type = "";
        }

        $failure_reason_text = "";
        if($failure_reason)
        {
            $failure_reason_text = " Reason: ".$failure_reason;
        }


        $event['type']      = "Reward - Redemption".$extra_type;
        $event['details']   = "A reward of $".$amount." was ".$verb." redeemed for member ID ".$person_id.".".$failure_reason_text;
        $event['person_id'] = $person_id;
        $event['success']   = !$failure;
        $event['api_id']    = $api_id;


        SystemEvent::register($event);

    }



    public function get_unrefunded_redemption_transactions($redemption_id)
    {
        $sql = "
            SELECT
            *
            FROM
            ".$this->table."
            WHERE
            (json_data->>'redemption_id') = ?
            AND
            (json_data->>'is_refund') = '0'
            AND
            (json_data->>'refunded') = '0'
            AND
            deleted_at IS NULL
        ";

        $bind_array = [$redemption_id];

        $result = $this->run_sql_query($sql, $bind_array);

        return $result;
    }



}
