<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderInDetail extends Model
{
    protected $table = 'order_in_detail';

    public function header()
	{
		return $this->hasMany('App\Model\OrderIn','order_in_id');
	}

	public function product()
	{
		return $this->belongsTo('App\Model\Product','product_id');
	}
}
