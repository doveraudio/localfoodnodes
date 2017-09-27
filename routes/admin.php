<?php

Route::get('/', 'Admin\AdminController@index');
Route::get('/users', 'Admin\AdminController@users');
Route::get('/orders', 'Admin\AdminController@orders');
Route::get('/api', 'Admin\AdminController@api');

// Email
Route::group(['prefix' => '/email'], function () {
    Route::get('/user/activation/{userId}', 'Admin\EmailController@userActivation');
    Route::get('/user/reset-password/{userId}', 'Admin\EmailController@resetPassword');
    Route::get('/order/producer', 'Admin\EmailController@orderProducer');
    Route::get('/order/customer', 'Admin\EmailController@orderCustomer');
});