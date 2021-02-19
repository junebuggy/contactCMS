<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('contacts', 'ContactsController@index');
Route::post('contacts', 'ContactsController@store');
Route::put('contacts/{id}', 'ContactsController@update');

Route::get('email_address/{id}', 'EmailAddressesController@index');
Route::post('email_address', 'EmailAddressesController@store');
Route::put('email_address/{id}', 'EmailAddressesController@update');
Route::delete('email_address/{id}','EmailAddressesController@destroy');

Route::get('phone_number/{id}', 'PhoneNumbersController@index');
Route::post('phone_number', 'PhoneNumbersController@store');
Route::put('phone_number/{id}', 'PhoneNumbersController@update');
Route::delete('phone_number/{id}','PhoneNumbersController@destroy');

Route::post('contact_merge', 'ContactsController@merge');
