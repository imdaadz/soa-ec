<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
	protected $table ='supplier_products';
	public $timestamps = false;


	protected $fillable = [
		'supplier_id',
		'product_id'
	];
    public function product()
	{
		return $this->belongsTo('App\Model\Product','product_id');
	}

	 public function supplier()
	{
		return $this->belongsTo('App\Model\Supplier','supplier_id');
	}
}
