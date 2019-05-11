<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
	use SoftDeletes;

	private $mapping = array(
		'id' => array(
			'field' => 'id',
			'type' => 'int'
		),
		'name' => array(
			'field' => 'product_name',
		),
		'sku' => array(
			'field' => 'product_sku',
			'type' => 'int'
		),
		'qty' => array(
			'field' => 'product_qty',
			'type' => 'int'
		),
		'price' => array(
			'field' => 'product_price',
			'type' => 'int'
		),
		'desc' => array(
			'field' => 'product_desc',
		),
		'photo' => array(
			'field' => 'product_photo',
		),
	);
	protected $fillable = [
		'user_id',
		'product_name',
		'product_sku',
		'product_qty',
		'product_price',
		'product_desc',
		'product_photo'
	];

	public function user()
	{
		return $this->belongsTo('App\User','user_id');
	}
	
	public function supplier()
	{
		return $this->hasMany('App\Model\SupplierProduct','product_id','id');
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
