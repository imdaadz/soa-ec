<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderOut extends Model
{
    protected $table = 'order_outs';

    public function user()
	{
		return $this->belongsTo('App\User','user_id');
	}

    public function detail()
	{
		return $this->hasMany('App\Model\OrderOutDetail','order_out_id','id');
	}
}
