<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderOutDetail extends Model
{
    protected $table = 'order_out_details';

    public function header()
	{
		return $this->hasMany('App\Model\OrderOut','order_out_id');
	}

	 public function product()
	{
		return $this->belongsTo('App\Model\Product','product_id');
	}
}
