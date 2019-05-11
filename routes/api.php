<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'auth'], function () {
	Route::post('login', 'api\LoginController@login');
	Route::post('register', 'api\LoginController@register');

	Route::group(['middleware' => 'auth:api'], function() {
		Route::get('logout', 'api\LoginController@logout');
		Route::get('user', 'api\LoginController@user');
		Route::post('user', 'api\LoginController@changeuser');
	});
});

Route::group(['prefix' => 'product', 'middleware' => 'auth:api'], function () {
	Route::get('', 'api\ProductController@list');
	Route::post('add', 'api\ProductController@new');
	Route::get('{id}', 'api\ProductController@single');
	Route::post('{id}/update', 'api\ProductController@update');
	Route::delete('{id}', 'api\ProductController@delete');
});

Route::group(['prefix' => 'supplier', 'middleware' => 'auth:api'], function () {
	Route::get('', 'api\SupplierController@list');
	Route::post('add', 'api\SupplierController@new');
	Route::post('link', 'api\SupplierController@link');
	Route::post('unlink', 'api\SupplierController@unlink');
	Route::get('{id}', 'api\SupplierController@single');
	Route::post('{id}/update', 'api\SupplierController@update');
	Route::delete('{id}', 'api\SupplierController@delete');
});

Route::group(['prefix' => 'cart', 'middleware' => 'auth:api'], function () {
	Route::get('list', 'api\CartController@list');
	Route::post('add', 'api\CartController@cart');
	Route::post('update', 'api\CartController@update');
	Route::post('remove', 'api\CartController@remove');
	Route::post('checkout', 'api\CartController@checkout');
});


Route::get('login', ['uses' => 'api\LoginController@loginTest', 'as' => 'api.login']);