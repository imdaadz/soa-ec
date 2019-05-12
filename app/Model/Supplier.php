<?php

namespace App\Model;

use DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
	use SoftDeletes;
	private $mapping = array(
		'id' => array(
			'field' => 'id',
			'type' => 'int'
		),
		'name' => array(
			'field' => 'supplier_name',
		),
		'code' => array(
			'field' => 'supplier_code',
		),
	);
	protected $fillable = [
		'user_id',
		'supplier_name',
		'supplier_code',
	];

	public function user()
	{
		return $this->belongsTo('App\User','user_id');
	}
	
    public function product()
	{
		return $this->hasMany('App\Model\SupplierProduct','supplier_id','id');
	}

	 public function order_in()
	{
		return $this->hasMany('App\Model\OrderIn','supplier_id','id');
	}

	public function check_product($product_id, $supplier_id){
		$total = DB::table('supplier_products')
		->where('product_id', $product_id)
		->where('supplier_id', $supplier_id)
		->count();
		return $total;
	}
	private function map_key($data, $with_user = true){
		$result = array();
		

		foreach ($this->mapping as $key => $value) {
			$selector = $value['field'];
			$field = $data->$selector;
			if(!empty($value['type']))
				settype($field,$value['type']);
			$result[$key] = $field;
			
		};
		if($with_user)
			$result['user'] = array(
				'id' => $data->user->id,
				'name' => $data->user->name,
			);
		return $result;
	}
	public function mapping($data, $with_user = true){

		$return = array();
		if($data)
			if(!empty($data[0]))
				foreach ($data as $row) {
					$return[] = $this->map_key($row, $with_user);
				}
			else
				$return = $this->map_key($data, $with_user);
		

		return $return;
	}
}
