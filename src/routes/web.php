<?php

Route::group(['namespace' => 'Abs\WarrantyPolicyPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'warranty-policy-pkg'], function () {
	Route::get('/warranty-policy/get-list', 'WarrantyPolicyController@getWarrantyPolicyList')->name('getWarrantyPolicyList');
	Route::get('/warranty-policy/get-form-data/{id?}', 'WarrantyPolicyController@getWarrantyPolicyFormData')->name('getWarrantyPolicyFormData');
	Route::post('/warranty-policy/save', 'WarrantyPolicyController@saveWarrantyPolicy')->name('saveWarrantyPolicy');
	Route::get('/warranty-policy/delete/{id}', 'WarrantyPolicyController@deleteWarrantyPolicy')->name('deleteWarrantyPolicy');
});