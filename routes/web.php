<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome'); 
});


Route::get( '/api/login', 'apisController@login' );
Route::get( '/api/google-request', 'apisController@googleRequest' );
Route::get( '/api/google-callback', 'apisController@googleCallback' );
Route::get( '/api/google-refresh', 'apisController@googleRefresh' );
// Route::get( '/api/google-access-token', 'apisController@googleAccessToken' );
Route::get( '/api/details', 'apisController@details' );
Route::get( '/api/gcp/printers', 'apisController@googleCloudPrinterList' );
Route::get( '/api/google-revoke', 'apisController@googleRevoke' );

Route::get( '/error/{code}/{slug?}', function( $code ) {
    return $code;
});

Route::post( '/api/gcp/submit-print-job/{printer_id}', 'apisController@googleCloudSubmitPrintJob' );
Route::get( '/api/auth', 'apisController@auth' );
Route::get( '/api/envato-licence-check/{licence}', 'apisController@envatoCheck' );
