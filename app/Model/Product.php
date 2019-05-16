<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;

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

	public function sale($product_id, $start, $end){
		$query = 'SELECT
			count(*) total,
			date(o.created_at) date
		FROM
			products p
		JOIN order_out_details d ON p.id = d.product_id
		JOIN order_outs o ON o.id = order_out_id
		WHERE DATE(o.created_at) between ? AND ?
		AND p.id = ?
		GROUP BY date(o.created_at)
		ORDER BY date(o.created_at) DESC';
		$product = DB::select($query,[$start, $end, $product_id]);
		return $product;
	}

	public function po($product_id, $start, $end){
		$query = 'SELECT
			count(*) total,
			date(o.created_at) date
		FROM
			products p
		JOIN order_in_details d ON p.id = d.product_id
		JOIN order_ins o ON o.id = d.order_in_id
		WHERE DATE(o.created_at) between ? AND ?
		AND p.id = ?
		GROUP BY date(o.created_at)
		ORDER BY date(o.created_at) DESC';
		$product = DB::select($query,[$start, $end, $product_id]);
		return $product;
	}

	public function most($user_id,$limit = 30, $offset = 0, $start = null, $end= null){
		if(empty($start) and empty($end))
			$product = DB::select("SELECT
					SUM(total_qty) total,
					product_id,
					p.*
				FROM
					order_outs o 
				JOIN `order_out_details` d ON o.id = d.order_out_id
				JOIN products p ON d.product_id = p.id
				WHERE o.user_id = ?
				GROUP BY
					`product_id`
				ORDER BY 1 DESC
				LIMIT ?,?", [$user_id,$offset,$limit]);
		else
			$product = DB::select("SELECT
				SUM(total_qty) total,
				product_id,
				p.*
			FROM
				order_outs o 
			JOIN `order_out_details` d ON o.id = d.order_out_id
			JOIN products p ON d.product_id = p.id
			WHERE o.user_id = ? AND DATE(o.created_at) between ? and ?
			GROUP BY
				`product_id`
			ORDER BY 1 DESC
			LIMIT ?,?", [$user_id, $start, $end, $offset,$limit]);

		$result = array();
		foreach ($product as $row) {

			$result[$row->product_id] = array(
				'id' => $row->id,
				'name' => $row->product_name,
				'qty' => (int)$row->product_sku,
				'sku' => $row->product_qty,
				'price' => (int)$row->product_price,
				'total' => (int)$row->total
			);
		}
		return array_values($result);	
	}
	
	public function forecast($user_id,$limit = 30, $offset = 0, $id = 0){
		if(empty($id))
			$product = DB::select("SELECT
					AVG(`total_qty`) avg_sales,
					SUM(total_qty) total,
					product_id,
					p.*
				FROM
					order_outs o 
				JOIN `order_out_details` d ON o.id = d.order_out_id
				JOIN products p ON d.product_id = p.id
				WHERE o.user_id = ?
				GROUP BY
					`product_id`
				ORDER BY 2 DESC
				LIMIT ?,?", [$user_id,$offset,$limit]);
		else
			$product = DB::select("SELECT
					AVG(`total_qty`) avg_sales,
					SUM(total_qty) total,
					product_id,
					p.*
				FROM
					order_outs o 
				JOIN `order_out_details` d ON o.id = d.order_out_id
				JOIN products p ON d.product_id = p.id
				WHERE  o.user_id = ?  AND product_id = ?
				GROUP BY
				`product_id`", [$user_id,$id]);

		$result = array();
		foreach ($product as $row) {
			$ltd = 5 * $row->avg_sales;
			$ss = $ltd * 0.5;
			$reorder = $ltd + $ss;
			$forecast = sqrt((2 * $row->total * 15) / 2);

			$result[$row->product_id] = array(
				'product' => array(
					'id' => $row->id,
					'name' => $row->product_name,
					'qty' => (int) $row->product_sku,
					'sku' => $row->product_qty,
					'price' => (int) $row->product_price,
				),
				'forecast' => array(
					'reorder_point' => ceil($reorder),
					'reorder_total' => ceil($forecast)
				)
			);
		}
		return array_values($result);
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
