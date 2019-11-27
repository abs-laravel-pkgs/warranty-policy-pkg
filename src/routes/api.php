<?php
Route::group(['namespace' => 'Abs\WarrantyPolicyPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'warranty-policy-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});