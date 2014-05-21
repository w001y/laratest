<?php

class MemberTiers extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'member_tiers';

    protected $softDelete = true;
}
