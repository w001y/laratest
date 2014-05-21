<?php

class Card extends BaseModel {
	protected $guarded = [];

	public static $rules = [];

    protected $table = 'cards';

    protected $softDelete = true;
}
