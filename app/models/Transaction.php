<?php


class Transaction extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'transactions';

    protected $softDelete = true;
}
