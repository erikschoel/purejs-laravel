<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/db/flush/{tag?}', 'DbController@flush');
Route::get('/db/all/{filter?}/{lang?}', 'DbController@all');
Route::get('/db/recursive/{record_id}', 'DbController@recursive');
Route::get('/db/tree/{record_id}/{lang?}', 'DbController@tree');

Route::get('/db/meta/{code?}', 'DbController@meta');
Route::get('/db/data/{code?}', 'DbController@data');
Route::get('/db/create/{code?}/{madi?}', 'DbController@create');
Route::get('/db/model/attributes/{code?}', 'DbController@attributes');
Route::get('/db/model/types/{code?}', 'DbController@types');
Route::get('/db/model/menus/{code?}', 'DbController@menus');
Route::get('/db/model/relations/{code?}', 'DbController@relations');
Route::get('/db/model/selector/{code?}', 'DbController@selector');

Route::get('/db/menu/{code}', 'DbController@sys_app_menu');
Route::get('/db/entity/{code}', 'DbController@sys_type_entity');
Route::get('/db/load/{code}/{madi?}', 'DbController@sys_load');
Route::get('/db/type/{type}/{code}', 'DbController@sys_type_load');
Route::post('/db/save', 'DbController@sys_save');
Route::post('/db/delete', 'DbController@sys_delete');

Route::get('/db/question/{ques_id?}', 'RestController@question');

Route::get('/rest/basket/{bask_id?}', 'RestController@basket');
Route::post('/rest/basket/{bask_id?}', 'RestController@saveBasket');

Route::get('/rest/item/{item_id?}/{output_type?}', 'RestController@item');
Route::post('/rest/item/{item_id?}', 'RestController@saveItem');

Route::get('/rest/model/{omod_id?}', 'RestController@model');
Route::post('/rest/model/{omod_id?}', 'RestController@saveModel');

Route::get('/rest/role/{orgr_id?}', 'RestController@role');
Route::post('/rest/role/{orgr_id?}', 'RestController@saveRole');

Route::get('/rest/question/list/{ques_id?}', 'RestController@question');
Route::post('/rest/question/{ques_id?}', 'RestController@saveQuestion');

Route::get('/rest/selector/{sele_id?}', 'RestController@selector');
Route::post('/rest/selector/{sele_id?}', 'RestController@saveSelector');

Route::get('/rest/program/{prog_code?}', 'RestController@program');
Route::get('/rest/questionnaire/{prog_code?}', 'RestController@questionnaire');
Route::get('/rest/question/program/{prog_code?}/{entity_id?}', 'RestController@questions');
Route::get('/rest/question/next/{prog_code?}/{after_id?}/{until_id?}/{entity_id?}', 'RestController@nextQuestion');

Route::post('/rest/answer', 'RestController@answer');

Route::get('/rest/question/reset/{entity_id?}/{prog_code?}/{after_id?}', 'RestController@resetQuestion');
Route::get('/rest/basket/items/{prog_code?}/{entity_id?}', 'RestController@basketItems');
Route::get('/rest/auth', 'RestController@auth');

Route::post('/rest/auth/login', 'RestController@login');
Route::get('/rest/auth/logout', 'RestController@logout');
Route::auth();

Route::get('/home', 'HomeController@index');
