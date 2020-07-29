<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */
$config['app_key'] = '1234';
$router->get('/', function () use ($router) {
    return '<span>Welcome to Store-API(1.0)</span>';
});

$router->get('/barcodeList', 'BoxController@getBarcodes');
//get accept item location
$router->get('/getAcceptedItemLocation/{item}/{weight}', 'BoxController@getAcceptedItemLocation');

// stores
$router->group(['prefix' => 'stores'], function () use ($router) {

    $router->get('/', 'StoreController@index');

    $router->get('/index', 'StoreController@indexDataTable');

    $router->get('/{id}', 'StoreController@show');

    $router->post('/', 'StoreController@store');

    $router->put('/{id}', 'StoreController@update');

    $router->delete('/{id}', 'StoreController@destroy');
});
// racks
$router->group(['prefix' => 'racks'], function () use ($router) {

    $router->get('/', 'RackController@index');

    $router->get('/index', 'RackController@indexDataTable');

    $router->get('/{id}', 'RackController@show');

    $router->post('/', 'RackController@store');

    $router->put('/{id}', 'RackController@update');

    $router->delete('/{id}', 'RackController@destroy');

    $router->get('/rackInfo/{id}', 'RackController@racksByStore');
});
// shelf
$router->group(['prefix' => 'shelves'], function () use ($router) {

    $router->get('/', 'ShelfController@index');

    $router->get('/index', 'ShelfController@indexDataTable');

    $router->get('/{id}', 'ShelfController@show');

    $router->post('/', 'ShelfController@store');

    $router->put('/{id}', 'ShelfController@update');

    $router->delete('/{id}', 'ShelfController@destroy');

    $router->get('/shelfInfo/{id}', 'ShelfController@shelvesByRack');

});
// boxes
$router->group(['prefix' => 'boxes'], function () use ($router) {

    $router->get('/', 'BoxController@index');

    $router->get('/dash/', 'BoxController@indexForDashboard');

    $router->get('/index', 'BoxController@indexDataTable');

    $router->get('/{id}', 'BoxController@show');

    $router->get('/boxByIdentifier/{identifier}', 'BoxController@boxByIdentifier');

    $router->post('/', 'BoxController@store');

    $router->post('/forge_remove_booking', 'BoxController@forge_remove_booking');

    $router->put('/{id}', 'BoxController@update');

    $router->put('/active/{id}', 'BoxController@changeToActive');
    $router->put('/disable/{id}', 'BoxController@changeToDisable');

    $router->delete('/{id}', 'BoxController@destroy');

    $router->get('/boxInfo/{id}', 'BoxController@boxesByShelf');

     $router->put('/updateOccupied/{identifier}', 'BoxController@updateOccupied');
});
/* summery of store */
$router->get('/summery_of_store', 'BoxController@summery_of_store');


// resources
$router->group(['prefix' => 'receivedItem'], function () use ($router) {

    $router->get('/', 'ReceivedItemController@index');

    $router->get('/{id}', 'ReceivedItemController@show');

    $router->post('/', 'ReceivedItemController@store');

    $router->put('/{id}', 'ReceivedItemController@update');

    $router->delete('/{id}', 'ReceivedItemController@destroy');

});
$router->group(['prefix' => 'settings'], function () use ($router) {

    $router->get('/barcodeConfig', 'SettingsController@barcodeConfig');

    $router->get('/unitConfig', 'SettingsController@unitConfig');

    $router->get('/apiItemWeightConfig', 'SettingsController@apiItemWeightConfig');

    $router->get('/itemAgeConfig', 'SettingsController@itemAgeConfig');

    $router->get('/itemAgeUnitConfig', 'SettingsController@itemAgeUnitConfig');

//    $router->get('/{id}', 'SettingsController@show');

    $router->post('/', 'SettingsController@changeSettings');

//    $router->put('/{id}', 'SettingsController@update');

//    $router->delete('/{id}', 'SettingsController@destroy');

});
//located item
$router->group(['prefix' => 'locatedItem'], function () use ($router) {

    $router->get('/', 'LocatedItemController@index');

    $router->get('/receive', 'LocatedItemController@receiveIndex');

    $router->get('/receiveItems', 'LocatedItemController@receiveItems');

    $router->get('/box', 'LocatedItemController@boxIndex');

    $router->get('/move', 'LocatedItemController@moveIndex');

    $router->get('/deliver', 'LocatedItemController@deliverIndex');

    $router->get('/check_item', 'LocatedItemController@checkItem');


    $router->post('/receive', 'LocatedItemController@receive');

    $router->post('/box', 'LocatedItemController@box');

    $router->post('/bulk', 'LocatedItemController@bulkBox');

    $router->post('/move', 'LocatedItemController@move');

    $router->post('/deliver', 'LocatedItemController@deliver');

    $router->put('/updateWeight/{item}','LocatedItemController@updateWeight');

    $router->get('/itemsByLocation/{item}', 'LocatedItemController@getItemsByLocation');

    $router->get('/dummy/{item}', 'LocatedItemController@dummyItemInfo');

});

// sync stores
$router->group(['prefix' => 'syncs'], function () use ($router) {

    $router->get('/', 'SyncsController@index');

    $router->get('/{id}', 'SyncsController@show');

    $router->post('/', 'SyncsController@store');

    $router->put('/{id}', 'SyncsController@update');

    $router->delete('/{id}', 'SyncsController@destroy');

});

// test item store
$router->group(['prefix' => 'testItem'], function () use ($router) {
    $router->get('/{item}', 'TestItemStoreController@show');
});

// delivery
$router->group(['prefix' => 'delivery_ctrl'], function () use ($router) {

    $router->get('/index', 'DeliveryController@index');

    $router->get('/updateTotalQuantity', 'DeliveryController@updateTotalQuantity');

    $router->get('/{reference}', 'DeliveryController@show');

    $router->post('/', 'DeliveryController@store');

    $router->put('/{id}', 'DeliveryController@update');

    $router->delete('/{id}', 'DeliveryController@destroy');
});

// delivery item
$router->group(['prefix' => 'deliveryItem'], function () use ($router) {

    $router->get('/', 'DeliveredItemController@index');

    $router->get('/{id}', 'DeliveredItemController@showByDeliverId');

});

// stores
$router->group(['prefix' => 'bookedLocation'], function () use ($router) {
    $router->post('/', 'BookedLocationController@bookedLocation'); // booked location
    $router->get('/index', 'BookedLocationController@index');
    $router->get('/{batch}', 'BookedLocationController@getBatchInfoByBatch');
    $router->get('/dummy_batch/{batch}', 'BookedLocationController@dummyBatchInfo');
});
$router->group(['prefix' => 'report'], function () use ($router) {
    $router->get('/orders/', 'ReportController@getOrders');
    $router->get('/ordersByBuyer/{buyer_id}', 'ReportController@getOrdersByBuyer');
});
$router->group(['prefix' => 'test_route'], function () use ($router) {

    $router->get('/', 'TestController@checkSess');
    $router->get('/insertArr', 'TestController@insertArray');
    $router->post('/create_delivery', 'TestController@create_delivery');
    $router->post('/create_delivery_item', 'TestController@create_delivery_item');
});

$router->group(['prefix' => 'random_delivery'], function () use ($router) {

    $router->get('/', 'RandomDeliveryController@pendingDeliveries');
    // $router->get('/create_delivery', 'RandomDeliveryController@create_delivery');
    $router->post('/create_delivery', 'RandomDeliveryController@create_delivery');
    $router->post('/remove_pending_delivery', 'RandomDeliveryController@remove_pending_delivery');
    // $router->get('/create_delivery_item', 'RandomDeliveryController@create_delivery_item');
    $router->post('/create_delivery_item', 'RandomDeliveryController@create_delivery_item');
    $router->post('/remove_pending_delivery_item', 'RandomDeliveryController@remove_pending_delivery_item');

    $router->post('/confirm_delivery', 'RandomDeliveryController@confirm_delivery');
});


$router->group(['prefix' => 'delivery'], function () use ($router) {

    $router->get('/', 'CommonDeliveryController@pendingDeliveries');

//    $router->get('/deliveries', 'CommonDeliveryController@deliveries');

    $router->post('/create_delivery', 'CommonDeliveryController@create_delivery');

    $router->post('/remove_pending_delivery', 'CommonDeliveryController@remove_pending_delivery');

    $router->post('/create_delivery_item', 'CommonDeliveryController@create_delivery_item');

    $router->post('/remove_pending_delivery_item', 'CommonDeliveryController@remove_pending_delivery_item');

    $router->post('/confirm_delivery', 'CommonDeliveryController@confirm_delivery');
});


$router->group(['prefix' => 'report'], function () use ($router) {

    $router->get('/batch_wise', 'ReportController@batch_wise_report');

});

$router->group(['prefix' => 'requisition'], function () use ($router) {

    $router->get('/item', 'RequisitionController@get_item_info_by_item');

});


// with access token
$router->group(['middleware' => 'access'], function() use ($router) {
//    $router->get('/', 'StoreController@index');
});
$router->group(['prefix' => 'access_ctrl'], function () use ($router) {
    $router->get('/access_token', 'AccessController@getAccessToken');
});


