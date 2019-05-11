<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator, Cart, DB;
use Illuminate\Validation\Rule;


use App\Model\Supplier;
use App\Model\Product;
use App\Model\SupplierProduct;
use App\Model\OrderOut;
use App\Model\OrderOutDetail;

class CartController extends Controller
{
	public function checkout(Request $request){
		$user = $request->user();

		// make sure product is same with database
		$this->list($request);
		Cart::instance('out')->restore($user->id);
		$cart = Cart::content();
		
		DB::beginTransaction();
		$success = false;

		if(Cart::count() > 0){
			try {
				$out = new OrderOut();
				$out->user_id = $user->id;
				$out->save();
				$insert = array();
				foreach ($cart as $row) {
					$insert[] = array(
						'order_out_id' => $out->id,
						'product_id' => $row->id,
						'total_qty' => $row->qty,
					);
					Product::find($row->id)->decrement('product_qty', $row->qty);

				}
				OrderOutDetail::insert($insert);

				DB::commit();
				$success = true;
			} catch (\Exception $e) {
				$success = false;
				print_r($e->getMessage());
				DB::rollback();
			}
		}

		if ($success) {
			Cart::destroy();
			Cart::instance('out')->store($user->id);
			return response()->json([
				'success' => true,
				'message' => 'Successfully checkout'
			]);		
		}else{
			return response()->json([
				'success' => false,
				'message' => 'Something wrong'
			]);
		}

	}
	public function cart(Request $request){
		$user = $request->user();
		$credentials = $request->only('product_id','qty');
		$rules = [
			'product_id' => 'required|integer|max:255', 
			'qty' => 'required|integer|max:255', 
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}

		$product = Product::find($request->product_id);
		if(!$product or $product->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}
		if($product->product_qty < $request->qty){
			return response()->json([
				'success' => false,
				'message' => 'Quantity is sufficient'
			]);
		}


		Cart::instance('out')->restore($user->id);
		Cart::add($product->id, $product->product_name, $request->qty, $product->product_price);
		Cart::instance('out')->store($user->id);

		return response()->json([
			'success' => true,
			'message' => 'Successfully add to cart'
		]);		
	}

	public function list(Request $request){
		$user = $request->user();
		$product_model = new Product();
		Cart::instance('out')->restore($user->id);
		$cart = Cart::content();
		$product = array();
		$change = array();
		foreach($cart as $row) {
			$single = $product_model->mapping(Product::find($row->id),false);
			if(empty($single)){
				Cart::remove($row->rowId);
			}else{
				$single['qty'] = (int) $row->qty;
				$single['total'] = $row->qty * $single['price'];
				if($single['price'] != $row->price){
					$change[$row->rowId] = $single['price'];
				}
				$product[] = $single;
			}
		}
		if(!empty($change)){
			foreach ($change as $key => $value) {
				Cart::update($key, ['price' => $value]); 
			}
		}
		Cart::instance('out')->store($user->id);
		return response()->json([
			'success' => true,
			'message' => 'Successfully retrived cart',
			'data'  => $product
		]);	
	}

	public function update(Request $request){
		$user = $request->user();
		$credentials = $request->only('product_id','qty');

		$rules = [
			'product_id' => 'required|integer|max:255',
			'qty' => 'required|integer|max:255',
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}

		Cart::instance('out')->restore($user->id);
		$cart = Cart::content();
		$product_id = $request->product_id;
		$cart_id = Cart::content()->where('id', $product_id);
		$product = Product::find($product_id);

		if(empty($cart_id) or !$product){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}
		
		
		if($product->product_qty < $request->qty){
			return response()->json([
				'success' => false,
				'message' => 'Quantity is sufficient'
			]);
		}
		$id = '';
		foreach ($cart_id as $row) {
			$id = $row->rowId;
			break;
		}
		Cart::update($id, ['qty' => $request->qty]); 
		
		Cart::instance('out')->store($user->id);

		return response()->json([
			'success' => true,
			'message' => 'Successfully update product from cart'
		]);	
	}

	public function remove(Request $request){
		$user = $request->user();
		$credentials = $request->only('product_id');

		$rules = [
			'product_id' => 'required|integer|max:255',
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}


		Cart::instance('out')->restore($user->id);
		$cart = Cart::content();
		$cart_id = Cart::content()->where('id', $product_id);
		if(empty($cart_id)){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}
		Cart::remove($cart_id);
		Cart::instance('out')->store($user->id);

		return response()->json([
			'success' => true,
			'message' => 'Successfully remove product from cart'
		]);	
	}
}