<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator;
use Illuminate\Validation\Rule;

use App\Model\Supplier;
use App\Model\Product;
use App\Model\SupplierProduct;


class SupplierController extends Controller
{
	function list(Request $request){
		$user = $request->user();

		$page = !empty($request->page) ? $request->page : 1;
		$limit = !empty($request->limit) ? $request->limit : 30;
		$offset = ($page * $limit) - $limit;
		$supplier = new Supplier();
		$set = Supplier::where('user_id', $user->id)->orderBy('id', 'desc')->skip($offset)->take($limit)->get();
		$data = $supplier->mapping($set);
		
		return response()->json([
			'success' => true,
			'message' => 'Successfully retrieved supplier',
			'data' => $data
		]);

	}
	
	function new(Request $request)
	{
		$user = $request->user();

		$credentials = $request->only('name', 'code');
		$code = $request->code;
		$rules = [
			'name' => 'required|max:255', 
			'code' => [
				'required',
				'max:255',
				Rule::unique('suppliers','supplier_code')->where(function ($query) use($user) {
					return $query->where('user_id', $user->id);
				}),
			],
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}
		$supplier_model = new Supplier();
	
		$supplier = Supplier::create([
			'user_id' => $user->id, 
			'supplier_name' => $request->name, 
			'supplier_code' => $request->code,
		]);
		

		return response()->json([
			'success' => true,
			'message' => 'Successfully create supplier',
			'data' => $supplier_model->mapping($supplier)
		]);
	}
	
	function single(Request $request, $id)
	{
		$user = $request->user();
		$supplier = Supplier::find($id);
		
		if(!$supplier or $supplier->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Supplier not found'
			]);
		}
		$supplier_model = new Supplier();
		$product_model = new Product();
		
		$data = $supplier_model->mapping($supplier);
		$product = array();

		foreach ($supplier->product as $row) {
			$product[] = $product_model->mapping($row->product, false);
		}

		$data['product'] = $product;
		return response()->json([
			'success' => true,
			'message' => 'Successfully get supplier',
			'data' => $data
		]);
	}

	function delete(Request $request, $id)
	{
		$user = $request->user();
		$supplier = Supplier::find($id);
		

		if(!$supplier or $supplier->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Supplier not found'
			]);
		}

		$supplier->delete();

		return response()->json([
			'success' => true,
			'message' => 'Successfully delete supplier'
		]);
	}

	function unlink(Request $request)
	{
		$credentials = $request->only('supplier_id', 'product_id');

		$rules = [
				'supplier_id' => 'integer|required|max:255', 
				'product_id' => 'integer|required|max:255', 
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}
		$supplier_model = new Supplier();
	
		
		$user = $request->user();
		$supplier = Supplier::find($request->supplier_id);
		$product = Product::find($request->product_id);
		
		if(!$supplier or $supplier->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Supplier not found'
			]);
		}

		if(!$product or $product->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}

		$link = SupplierProduct::where('supplier_id', $request->supplier_id)->where('product_id', $request->product_id);

		if($link->get()->isEmpty()){
			return response()->json([
				'success' => false,
				'message' => 'Proudct not linked'
			]);

		}else{
			$link->delete();
			return response()->json([
				'success' => true,
				'message' => 'Successfully unlink product to supplier'
			]);
		}
	}
	function link(Request $request)
	{
		$credentials = $request->only('supplier_id', 'product_id');

		$rules = [
				'supplier_id' => 'integer|required|max:255', 
				'product_id' => 'integer|required|max:255', 
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}
		$supplier_model = new Supplier();
	
		
		$user = $request->user();
		$supplier = Supplier::find($request->supplier_id);
		$product = Product::find($request->product_id);
		if(!$supplier or $supplier->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Supplier not found'
			]);
		}

		if(empty($product) or $product->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}

		$link = SupplierProduct::where('supplier_id', $request->supplier_id)->where('product_id', $request->product_id);

		if($link->get()->isEmpty()){
			$supplier = SupplierProduct::create([
				'supplier_id' => $request->supplier_id, 
				'product_id' => $request->product_id, 
			]);
			return response()->json([
				'success' => true,
				'message' => 'Successfully link product to supplier'
			]);
		}else{
			return response()->json([
				'success' => false,
				'message' => 'Proudct already linked'
			]);
		}
	}
	
	function update(Request $request, $id)
	{
		$user = $request->user();

		$credentials = $request->only('name', 'code');
		
		$supplier = Supplier::find($id);
		if(!$supplier or $supplier->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Supplier not found'
			]);
		}
		

		if ($supplier->supplier_code == $request->code) {
			$rules = [
				'name' => 'required|max:255', 
				'code' => 'required|max:255',
			];
		}else{
			 $rules = [
				'name' => 'required|max:255', 
				'code' => [
					'required',
					'max:255',
					Rule::unique('suppliers','supplier_code')->where(function ($query) use($user) {
						return $query->where('user_id', $user->id);
					}),
				],
			];
		}

		
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}
		$supplier_model = new Supplier();
	
		$supplier->supplier_name = $request->name;
		$supplier->supplier_code = $request->code;
		$supplier->save();

		return response()->json([
			'success' => true,
			'message' => 'Successfully update supplier',
			'data' => $supplier_model->mapping($supplier)
		]);
	}
}