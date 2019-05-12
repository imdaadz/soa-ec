<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator, Cart, DB;
use Illuminate\Validation\Rule;


use App\Model\Supplier;
use App\Model\Product;
use App\Model\SupplierProduct;
use App\Model\OrderIn;
use App\Model\OrderInDetail;

class POController extends Controller
{

	public function new(Request $request){
		$user = $request->user();
		$credentials = $request->only('supplier_id');
		$rules = [
			'supplier_id' => 'required|integer|max:255',
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}
		$supplier_model = new Supplier();
		$supplier = Supplier::find($request->supplier_id);
		if(!$supplier or $supplier->user_id != $user->id){
			return response()->json([
				'success' => false,
				'message' => 'Supplier not found'
			]);
		}

		$check_supplier = OrderIn::where('user_id', $user->id)
						->where('supplier_id',$request->supplier_id)
						->where('order_status',0)
						->count();

		if($check_supplier > 0){
			return response()->json([
				'success' => false,
				'message' => 'Purchase Order with that supplier still active'
			]);
		}

		$new = new OrderIn();
		$new->user_id = $user->id;
		$new->supplier_id = $request->supplier_id;
		$new->order_status = 0;
		$new->save();
		
		return response()->json([
			'success' => true,
			'message' => 'Successfully create new PO',
			'data' => array(
				'po_id' => $new->id,
				'supplier' => $supplier_model->mapping($supplier, false),
			)
		]);
	}

	public function checkout(Request $request, $po_id){
		$user = $request->user();
		$po = OrderIn::find($po_id);
		if(!$po or $po->user_id != $user->id or $po->order_status != 0){
			return response()->json([
				'success' => false,
				'message' => 'Purchase Order not found'
			]);
		}

		// make sure product is same with database
		$this->list($request, $po_id);
		Cart::instance('in')->restore($user->id.'-'.$request->po_id);
		$cart = Cart::content();
		
		DB::beginTransaction();
		$success = false;

		if(Cart::count() > 0){
			try {
				$insert = array();
				foreach ($cart as $row) {
					$insert[] = array(
						'order_in_id' => $po_id,
						'product_id' => $row->id,
						'total_qty' => $row->qty,
					);
					Product::find($row->id)->increment('product_qty', $row->qty);

				}
				OrderInDetail::insert($insert);
				$po->order_status = 1;
				$po->save();
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
			Cart::instance('in')->store($user->id.'-'.$request->po_id);
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
		$po = OrderIn::find($request->po_id);
		if(!$po or $po->user_id != $user->id or $po->order_status != 0){
			return response()->json([
				'success' => false,
				'message' => 'Purchase Order not found'
			]);
		}

		$supplier = new Supplier();
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
		
		$link =  $supplier->check_product($request->product_id, $po->supplier_id);
		if($link < 1){
			return response()->json([
				'success' => false,
				'message' => 'Product not linked with supplier'
			]);
		}

		if($product->product_qty < $request->qty){
			return response()->json([
				'success' => false,
				'message' => 'Quantity is sufficient'
			]);
		}


		Cart::instance('in')->restore($user->id.'-'.$request->po_id);
		Cart::add($product->id, $product->product_name, $request->qty, $product->product_price);
		Cart::instance('in')->store($user->id.'-'.$request->po_id);

		return response()->json([
			'success' => true,
			'message' => 'Successfully add to cart'
		]);		
	}

	public function list(Request $request, $po_id){
		$user = $request->user();
		$po = OrderIn::find($po_id);
		if(!$po or $po->user_id != $user->id or $po->order_status != 0){
			return response()->json([
				'success' => false,
				'message' => 'Purchase Order not found'
			]);
		}

		$product_model = new Product();
		Cart::instance('in')->restore($user->id.'-'.$po_id);
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
		Cart::instance('in')->store($user->id.'-'.$po_id);
		return response()->json([
			'success' => true,
			'message' => 'Successfully retrived cart',
			'data'  => $product
		]);	
	}

	public function update(Request $request, $po_id){
		$user = $request->user();
		$po = OrderIn::find($po_id);
		if(!$po or $po->user_id != $user->id or $po->order_status != 0){
			return response()->json([
				'success' => false,
				'message' => 'Purchase Order not found'
			]);
		}


		$credentials = $request->only('product_id','qty');
		$rules = [
			'product_id' => 'required|integer|max:255', 
			'qty' => 'required|integer|max:255', 
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}


		Cart::instance('in')->restore($user->id.'-'.$po_id);
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
		
		Cart::instance('in')->store($user->id.'-'.$po_id);

		return response()->json([
			'success' => true,
			'message' => 'Successfully update product from cart'
		]);	
	}

	public function remove(Request $request, $po_id){
		$user = $request->user();
		$po = OrderIn::find($po_id);
		if(!$po or $po->user_id != $user->id or $po->order_status != 0){
			return response()->json([
				'success' => false,
				'message' => 'Purchase Order not found'
			]);
		}

		$credentials = $request->only('product_id');

		$rules = [
			'product_id' => 'required|integer|max:255',
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails()){
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}


		Cart::instance('in')->restore($user->id.'-'.$po_id);
		$cart_id = Cart::content()->where('id', $request->product_id);
		
		if($cart_id->count() < 1){
			return response()->json([
				'success' => false,
				'message' => 'Product not found'
			]);
		}
		$cart_id = array_keys(current($cart_id));
		$cart_id = $cart_id[0];

		Cart::remove($cart_id);
		Cart::instance('in')->store($user->id.'-'.$po_id);

		return response()->json([
			'success' => true,
			'message' => 'Successfully remove product from cart'
		]);	
	}
}