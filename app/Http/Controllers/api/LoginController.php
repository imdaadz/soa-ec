<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

use Validator, DB, Hash, Mail;
use Illuminate\Support\Facades\Password;


use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class LoginController extends Controller
{
	/**
	 * API Register
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public

	function register(Request $request)
	{
		$credentials = $request->only('name', 'email', 'password');
		$rules = [
			'name' => 'required|max:255', 
			'email' => 'required|email|max:255|unique:users', 
			'password' => 'required|max:255',
			'confpassword' => 'max:255|same:password'
		];
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}

		$name = $request->name;
		$email = $request->email;
		$password = $request->password;
		
		$user = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password) ]);
		
		$tokenResult = $user->createToken('Personal Access Token');
		$token = $tokenResult->token;
		if ($request->remember_me)
			$token->expires_at = Carbon::now()->addWeeks(1);
		$token->save();

		return response()->json([
			'success' => true,
			'message' => 'Successfully login',
			'data' => [
				'access_token' => $tokenResult->accessToken,
				'token_type' => 'Bearer',
				'expires_at' => Carbon::parse(
					$tokenResult->token->expires_at
				)->toDateTimeString()
			]
		]);
	}

	public function login(Request $request)
	{
		$credentials = request(['email', 'password']);
		$rules = [
			'email' => 'required|string|email',
			'password' => 'required|string',
			'remember_me' => 'boolean'
		];


		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}

		if(!Auth::guard()->attempt($credentials))
			return response()->json([
				'success' => false,
				'type' => 'Unauthorized',
				'message' => 'Email and password not found',

			], 401);
		$user = $request->user();
		$tokenResult = $user->createToken('Personal Access Token');
		$token = $tokenResult->token;
		if ($request->remember_me)
			$token->expires_at = Carbon::now()->addWeeks(1);
		$token->save();
				

		return response()->json([
			'success' => true,
			'message' => 'Successfully login',
			'data' => [
				'access_token' => $tokenResult->accessToken,
				'token_type' => 'Bearer',
				'expires_at' => Carbon::parse(
					$tokenResult->token->expires_at
				)->toDateTimeString()
			]
		]);
	}

	public function loginTest(){
		return response()->json([
			'success' => false,
			'message' => 'You\'r not authorized to access this',
			'data' => [
				'type' => 'UNAUTHORIZED',
			]
		]);
	}

	 /**
	 * Logout user (Revoke the token)
	 *
	 * @return [string] message
	 */
	public function logout(Request $request)
	{
		$request->user()->token()->revoke();
		return response()->json([
			'success' => true,
			'message' => 'Successfully logged out'
		]);
	}
  
	/**
	 * Get the authenticated User
	 *
	 * @return [json] user object
	 */
	public function user(Request $request)
	{  
		$user = $request->user();
		
		if(empty($user->avatar)){
			$user->avatar = 'avatar.jpg';
		}
		return response()->json([
			'success' => true,
			'message' => 'Successfully get user data',
			'data' => $user 
		]);
	}

	/**
	 * Get the authenticated User
	 *
	 * @return [json] user object
	 */
	public function changeuser(Request $request)
	{
		$credentials = $request->only('id', 'name', 'email', 'image', 'password', 'confpassword');
		if(empty($request->id)){
			return response()->json(['success' => false, 'message' => 'ID is required' ]);
		}
		$customer = User::find($request->id);
		if ($customer->email == $request->email) {
			$rules = [
				'id' => 'required|max:11', 
				'name' => 'required|max:255', 
				'email' => 'required|email|max:255', 
				'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
				'password' => 'max:255',
				'confpassword' => 'max:255|same:password'
			];
		}else{
			 $rules = [
				'id' => 'required|max:11', 
				'name' => 'required|max:255', 
				'email' => 'required|email|max:255|unique:customers', 
				'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
				'password' => 'max:255',
				'confpassword' => 'max:255|same:password'
			];
		}
		$validator = Validator::make($credentials, $rules);
		if ($validator->fails())
		{
			return response()->json(['success' => false, 'message' => $validator->messages() ]);
		}

		
		if(empty($customer)){
			return response()->json(['success' => false, 'message' => 'Customer not found' ]);
		}

		$customer->name = $request->name;
		$customer->email = $request->email;
		$image = $request->file('image');
	
		if(!empty($image)){
			$destinationPath = public_path('/storage/users/');

			if(!empty($customer->avatar)){
				unlink(public_path('/storage/').$customer->avatar);
			}
			$name = time().'.'.$image->getClientOriginalName();
			$image->move($destinationPath, $name);
			$customer->avatar = 'users/'.$name;
		}
		$customer->save();

		return response()->json([
			'success' => true,
			'message' => 'Successfully get user data',
			'data' => $request->user()
		]);
	}
	
}


