<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class OrderIn extends Model
{
    protected $table = 'order_in';

    public function user()
	{
		return $this->belongsTo('App\User','user_id');
	}

	public function detail()
	{
		return $this->hasMany('App\Model\OrderInDetail','order_in_id','id');
	}

	public function supplier()
	{
		return $this->belongsTo('App\Model\Supplier','supplier_id');
	}
}
