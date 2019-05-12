<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator, Cart, DB;
use Illuminate\Validation\Rule;

use App\Model\Product;

class AnalyticsController extends Controller
{
	public function most(Request $request){
		$user = $request->user();
		$credentials = $request->only('start','end', 'limit','page');

		$rules = [
			'start' => 'date|max:255', 
			'end' => 'date|max:255', 
			'limit' => 'integer|max:255', 
			'page' => 'integer|max:255', 
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}

		$page = !empty($request->page) ? $request->page : 1;
		$limit = !empty($request->limit) ? $request->limit : 30;
		$offset = ($page * $limit) - $limit;

		$product_model = new Product();
		if(!empty($request->start) AND !empty($request->end)){
			$forecast = $product_model->most($user->id, $limit, $offset,$request->start,$request->end);
		}else{
			$forecast = $product_model->most($user->id, $limit, $offset);
		}

		
		return response()->json([
			'success' => true,
			'message' => 'Successfully retrieved most order Product',
			'data' => $forecast
		]);
	}
	public function forecast(Request $request){
		$user = $request->user();
		$credentials = $request->only('product_id','limit','page');
		$rules = [
			'product_id' => 'integer|max:255', 
			'limit' => 'integer|max:255', 
			'page' => 'integer|max:255', 
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}

		$page = !empty($request->page) ? $request->page : 1;
		$limit = !empty($request->limit) ? $request->limit : 30;
		$offset = ($page * $limit) - $limit;

		$product_model = new Product();
		if(!empty($request->product_id)){
			$product = Product::find($request->product_id);
			if(!$product or $product->user_id != $user->id){
				return response()->json([
					'success' => false,
					'message' => 'Product not found'
				]);
			}
			$forecast = $product_model->forecast($user->id, $limit, $offset ,$request->product_id);
		}else{
			$forecast = $product_model->forecast($user->id, $limit, $offset );
		}

		
		return response()->json([
			'success' => true,
			'message' => 'Successfully retrieved stock prediction',
			'data' => $forecast
		]);
	}
}