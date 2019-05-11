<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator;
use Illuminate\Validation\Rule;

use App\Model\Product;

class ProductController extends Controller
{
	function list(Request $request){
		$page = !empty($request->page) ? $request->page : 1;
		$limit = !empty($request->limit) ? $request->limit : 30;
		$offset = ($page * $limit) - $limit;
		$product = new Product();
		return response()->json([
			'success' => true,
			'message' => 'Successfully retrieved product',
			'data' => $product->mapping(Product::orderBy('id', 'desc')->skip($offset)->take($limit)->get())
		]);

	}
	
	function new(Request $request)
	{
		$user = $request->user();

		$credentials = $request->only('name', 'sku', 'qty', 'price', 'desc', 'photo');
		$sku = $request->sku;
		$rules = [
			'name' => 'required|max:255', 
			'sku' => [
				'required',
				'max:255',
				Rule::unique('products','product_sku')->where(function ($query) use($user) {
					return $query->where('user_id', $user->id);
				}),
			],
			'qty' => 'required|max:255|integer', 
			'price' => 'required|max:255|integer', 
			'desc' => 'required|max:255', 
			'photo' => 'max:255', 
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}
		$product_model = new Product();
	
		$product = Product::create([
			'user_id' => $user->id, 
			'product_name' => $request->name, 
			'product_sku' => $request->sku, 
			'product_qty' => $request->qty, 
			'product_price' => $request->price, 
			'product_desc' => $request->desc, 
			'product_photo' => $request->photo, 
		]);
		

		return response()->json([
			'success' => true,
			'message' => 'Successfully create product',
			'data' => $product_model->mapping($product)
		]);
	}
	
	function single(Request $request, $id)
	{
		$user = $request->user();
		$product = Product::find($id);
		$product_model = new Product();
		if(!$product or $product->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}


		return response()->json([
			'success' => true,
			'message' => 'Successfully get product',
			'data' => $product_model->mapping($product)
		]);
	}

	function delete(Request $request, $id)
	{
		$user = $request->user();
		$product = Product::find($id);

		if(!$product or $product->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}

		$product->delete();

		return response()->json([
			'success' => true,
			'message' => 'Successfully delete product'
		]);
	}


	
	function update(Request $request, $id)
	{
		$user = $request->user();

		$credentials = $request->only('name', 'sku', 'price', 'desc', 'photo');
		
		$product = Product::find($id);
		if(!$product or $product->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}

		if ($product->product_sku == $request->sku) {
			$rules = [
				'name' => 'required|max:255', 
				'sku' => 'required|max:255',
				'price' => 'required|max:255|integer', 
				'desc' => 'required|max:255', 
				'photo' => 'max:255', 
			];
		}else{
			 $rules = [
				'name' => 'required|max:255', 
				'sku' => [
					'required',
					'max:255',
					Rule::unique('products','product_sku')->where(function ($query) use($user) {
						return $query->where('user_id', $user->id);
					}),
				],
				'price' => 'required|max:255|integer', 
				'desc' => 'required|max:255', 
				'photo' => 'max:255', 
			];
		}

		
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}
		$product_model = new Product();
	
		$product->product_name = $request->name;
		$product->product_sku = $request->sku;
		$product->product_price = $request->price;
		$product->product_desc = $request->desc;
		$product->product_photo = $request->photo;
		$product->save();

		return response()->json([
			'success' => true,
			'message' => 'Successfully update product',
			'data' => $product_model->mapping($product)
		]);
	}
}