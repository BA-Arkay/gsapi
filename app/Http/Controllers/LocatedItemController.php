<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 2/11/2019
 * Time: 7:52 PM
 */

namespace App\Http\Controllers;


use App\BookedLocation;
use App\Box;
use App\libraries\Utility;
use App\LocatedItem;
use App\Logs;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;

class LocatedItemController extends Controller
{

    public function index()
    {
        try {
            $items = LocatedItem::all();
            return response()->json($items);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function receiveIndex()
    {
        try {
            $items = LocatedItem::where('is_received', '=', 1)
                ->where('is_boxed', '=', 0)
                ->where('is_delivered', '=', 0)
                ->orderBy('id', 'desc')
                ->get();
            return response()->json($items);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function boxIndex()
    {
        try {
            $items = LocatedItem::where('is_boxed', '=', 1)
                ->where('is_delivered', '=', 0)
                ->orderBy('id', 'desc')
                ->get();
            return response()->json($items);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function moveIndex()
    {
        try {
            $items = LocatedItem::where('is_moved', '=', 1)
                ->orderBy('id', 'desc')
                ->get();
            return response()->json($items);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function deliverIndex()
    {
        try {
            $items = LocatedItem::where('is_delivered', '=', 1)
                ->orderBy('id', 'desc')
                ->get();
            return response()->json($items);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    // after scan received to temp table
    public function receive(Request $request)
    {
        $output = [
            'status' => 400,
            'success' => false,
            'message' => '',
            'message_icon' => 'error',
            'message_type' => 'warning',
            'table_icon' => 'window-close',
            'table_class' => 'danger',
            'location' => null,
            'description' => ''
        ];

        if (!isset($request->item) || !isset($request->item_detail)) {
            $output['message'] = 'Item & Item_detail are required';
            return response()->json($output);
        }
        try {
            $rules = array(
                "item" => 'unique:located_items,item',
                "item_detail" => 'required'
            );

            $itemInfo = null;
            $item_batch = null;
            $booked_location = null;
            $box = null;
            $item_weight = null;
            $item = [];
            $roll_no = [];
            $auto_rack = [
                'item' => $request->item,
                'location' => ''
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $item = LocatedItem::where('item', $request->item)->first();
                $item->message_icon = 'error';
                $item->message_type = 'danger';
                $item->table_icon = 'times';
                $item->table_class = 'danger';
                $item->status = 400;
                $item->success = false;
                $output = $item;
                $output['message'] = 'Roll is Already Received!';
                $output['location'] = ($item->location == 'ds-dr-ds-db') ? "" : $item->location;
                return response()->json($output);
            }
            $itemInfo = json_decode($request->item_detail);
            $item_weight = isset($itemInfo->weight) ? $itemInfo->weight : 0.0;

            if (env('FACTORY') == 'Keya') {
                $item_batch = isset($itemInfo->batch_no) ? $itemInfo->batch_no : 'UNDEFINED';
                $booked_location = BookedLocation::where('batch_barcode', $item_batch)->first();
                if ($booked_location) {
                    $auto_rack['location'] = $booked_location->box_barcode;
                    $box = Box::where('identifier', $booked_location->box_barcode)->first();

                }
            }
            $data = $request->all();
            $data['location'] = 'ds-dr-ds-db';
            $data['batch_no'] = $item_batch;
            $data['weight'] = $item_weight;
            $data['is_received'] = 1;
            $data['received_at'] = date('Y-m-d H:i:s');
            $data['received_by'] = null;

            if (env('AUTORACK') === true && $booked_location) {
                DB::beginTransaction();
                try {
                    $box->number_of_items += 1;
                    $box->occupied += $item_weight;
                    $box->actual_free_space -= $box->occupied;
                    $box->save();

                    // update data into locatedItems table
                    $data['location'] = $auto_rack['location'];
                    $data['is_boxed'] = 1;
                    $data['boxed_at'] = date('Y-m-d H:i:s');
                    $data['boxed_by'] = null;
                    $roll_no = LocatedItem::create($data);
                    $roll_no->message = 'Roll has been Racked!';
                    $roll_no->location = $auto_rack['location'];

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                }
            } else {
                $roll_no = LocatedItem::create($data);
                $roll_no->message = 'Roll has been Received!';
                $roll_no->location = 'Floor';
                $roll_no->description = "Roll has been Received at default store.";
            }
            if (isset($roll_no) && !empty($roll_no)) {
                $roll_no->status = 201;
                $roll_no->success = true;
                $roll_no->message_icon = 'success';
                $roll_no->message_type = 'success';
                $roll_no->table_icon = 'check';
                $roll_no->table_class = 'success';
                return response()->json($roll_no);
            }
            $output['message'] = 'Unable to insert data!';
            return response()->json($output);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function box(Request $request)
    {
        try {
            $output = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'message_icon' => 'error',
                'message_type' => 'warning',
                'table_icon' => 'remove',
                'table_class' => 'warning',
                'location' => '',
                'description' => 'item & Location field required'
            ];
            $rules = array(
                "item" => 'required',
                "location" => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $output['message'] = 'item & Location field required';
                return response()->json($output);

            }
            $bookedLocation = null;
            $item = LocatedItem::where('item', $request->item)->first();
            $box = Box::where('identifier', $request->location)->first();
            if (empty($item)) {
                $output['message'] = 'Invalid Roll!';
                $output['item'] = $request->item;
                return response()->json($output);
            }
            $itemInfo = json_decode($item->item_detail);
            $item_batch = null;
            $item_weight = $itemInfo->weight ? $itemInfo->weight : 0.0;
            if (env('FACTORY') == 'Keya') {
                $item_batch = $itemInfo->batch_no ? $itemInfo->batch_no : 'UNDEFINED';
                $bookedLocation = BookedLocation::where('batch_barcode', $item_batch)->first();
                if (empty($bookedLocation)) {
                    $output['message'] = 'Batch Not Booked';
                    $output['batch_no'] = $item_batch;
                    $output['item'] = $request->item;
                    $output['description'] = 'Batch Not Booked For This Roll!';
                    return response()->json($output);
                }
            }
            if (empty($box)) {
                $output['message'] = 'Invalid Location!';
                $output['batch_no'] = $item_batch;
                $output['item'] = $request->item;
                $output['weight'] = $item_weight;
                return response()->json($output);
            }
            if ($box->is_active === 0) { //$box->is_active === 0
                $output['message'] = 'Location is not active!';
                $output['batch_no'] = $item_batch;
                $output['item'] = $request->item;
                $output['weight'] = $item_weight;
                return response()->json($output);
            }
            if ($item->is_recevied === 0) {
                $output['message'] = 'Roll is not Received!';
                $output['batch_no'] = $item_batch;
                $output['item'] = $request->item;
                $output['weight'] = $item_weight;
                $output['location'] = env('FACTORY') == 'Keya' ? $bookedLocation->box_barcode : '';
                return response()->json($output);
            }
            if ($item->is_boxed === 1) {
                $item->status = 200;
                $item->success = false;
                $item->message_icon = 'error';
                $item->message_type = 'error';
                $item->table_icon = 'times';
                $item->table_class = 'danger';
                $item->message = 'Roll has been Already Racked!';
                return response()->json($item);
            }

            if (env('FACTORY') == 'Keya') {
                if ($bookedLocation->box_barcode == $box->identifier) {
                    //update number_of_items by location of box ata boxes
                    DB::beginTransaction();
                    try {
                        $box->number_of_items += 1;
                        $box->occupied += $item_weight;
                        $box->actual_free_space = $box->capacity - $box->occupied;
                        $box->bookable_free_space = $box->capacity - $box->occupied;
                        $box->save();

                        // update data into locatedItems table
                        $item->location = $request->input('location');
                        $item->is_boxed = 1;
                        $item->boxed_at = date('Y-m-d H:i:s');
                        $item->boxed_by = null;
                        $item->save();
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollBack();
                    }

                    $item->status = 201;
                    $item->success = true;
                    $item->message_icon = 'success';
                    $item->message_type = 'success';
                    $item->table_icon = 'check';
                    $item->table_class = 'success';
                    $item->location = $bookedLocation->box_barcode;
                    $item->batch_no = $item_batch;
                    $item->weight = $item_weight;
                    $item->message = 'Roll has been Racked';
                    $item->description = "Rack Location: {$bookedLocation->box_barcode}";
                    return response()->json($item);
                } elseif ($bookedLocation->box_barcode != $box->identifier) {
                    $output = [
                        'status' => 200,
                        'success' => false,
                        'message' => 'Invalid Location!',
                        'message_icon' => 'error',
                        'message_type' => 'error',
                        'table_icon' => 'remove',
                        'table_class' => 'danger',
                        'item' => $request->item,
                        'batch_no' => $item_batch,
                        'weight' => $item_weight,
                        'location' => $bookedLocation->box_barcode,
                        'description' => "Proper location is {$bookedLocation->box_barcode}"
                    ];
                    return response()->json($output);
                } else {
                    $output = [
                        'status' => 200,
                        'success' => false,
                        'message' => 'Batch Not Match!',
                        'message_icon' => 'error',
                        'message_type' => 'error',
                        'table_icon' => 'remove',
                        'table_class' => 'danger',
                        'item' => $request->item,
                        'batch_no' => $item_batch,
                        'weight' => $item_weight,
                        'location' => $bookedLocation->box_barcode,
                        'description' => 'Batch Not Match. Please Rack this roll to valid location.'
                    ];
                    return response()->json($output);
                }
            } else {
                //update number_of_items by location of box ata boxes
                DB::beginTransaction();
                try {
                    $box->number_of_items = $box->number_of_items + 1;
                    $box->occupied = $box->occupied + $item_weight;
                    $box->actual_free_space = $box->capacity - $box->occupied;
                    $box->save();

                    // update data into locatedItems table
                    $item->location = $request->input('location');
                    $item->is_boxed = 1;
                    $item->boxed_at = date('Y-m-d H:i:s');
                    $item->boxed_by = null;
                    $item->save();
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                }

                $item->status = 201;
                $item->success = true;
                $item->message_icon = 'success';
                $item->message_type = 'success';
                $item->table_icon = 'check';
                $item->table_class = 'success';
                $item->batch_no = $item_batch;
                $item->weight = $item_weight;
                $item->message = 'Roll has been Racked';
                $item->description = "Rack Location: {$item->location}";
                return response()->json($item);
            }

            return response()->json($output);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function move(Request $request)
    {
        try {
            $api_output_header = [
                'status' => 400,
                'success' => false,
                'message' => '',
                'description' => '',
            ];
            $api_output_data = [];
            $rules = array(
                "item" => 'required',
                "location" => 'required',
            );
            $newBox = null;
            $done = null;
            $previousBox = null;
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $api_output_header['message'] = 'Roll no & Location Required!';
                $log = new Logs([
                    'type' => 'moving_item',
                    'title' => $api_output_header['message'],
                    'description' => $api_output_header['message']
                ]);
                return Utility::api_output($api_output_header);
            }
            $item = LocatedItem::where('item', $request->input('item'))->first();
            $newBox = Box::where('identifier', '=', $request->location)->first();
            if (isset($item) && !empty($item)){
                $previousBox = Box::where('identifier', $item->location)->first();
                $itemInfo = json_decode($item->item_detail);
                $item_weight = $itemInfo->weight ? $itemInfo->weight : 0.0;
                if (isset($newBox) && $newBox->is_active == 1 ){

                    DB::beginTransaction();
                    try{
                        // change to moved_from box at boxes
                        $previousBox->number_of_items = $previousBox->number_of_items - 1;
                        $previousBox->occupied = $previousBox->occupied - $item_weight;
                        $previousBox->actual_free_space = $previousBox->capacity + $previousBox->occupied;
                        $previousBox->save();

                        // change to moved_to box at boxes
                        $newBox->number_of_items = $newBox->number_of_items + 1;
                        $newBox->occupied = $newBox->occupied + $item_weight;
                        $newBox->actual_free_space = $newBox->capacity - $newBox->occupied;
                        $newBox->save();

                        // update data at locatedItems
                        $item->moved_from = $item->location;
                        $item->location = $request->input('location');
                        $item->is_moved = 1;
                        $item->moved_at = date('Y-m-d H:i:s');
                        $item->moved_by = null;
                        $done = $item->save();

                        DB::commit();
                    }
                    catch (Exception $exception){
                        DB::rollBack();
                    }
                    if ($done){
                        $api_output_header['status']= 200;
                        $api_output_header['success']= true;
                        $api_output_header['message']= "Item Moved From: {$newBox->identifier} TO: {$previousBox->identifier}";
                        $api_output_data = [
                            'from_box' => !$previousBox->identifier ? '' : $previousBox->identifier,
                            'to_box' => !$newBox->identifier ? '' : $newBox->identifier,
                            'item' => !$item ? '' : $item,
                        ];
                    }
                    else{
                        $api_output_header['message'] = 'Moving Failed!';
                    }
                }
                else{
                    if ($newBox->is_active == 0){
                        $api_output_header['message'] = 'Box is Inactive!';
                    }else{
                        $api_output_header['message'] = 'Box Not found!';
                    }
                }
            }
            else{
                $api_output_header['message'] = 'Roll no Not Found!';
            }
            $log = new Logs([
                'type' => 'moving_item',
                'title' => $api_output_header['message'],
                'description' => $api_output_header['message']
            ]);
            return Utility::api_output($api_output_header, $api_output_data);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

    public function checkItem(Request $request)
    {
        $output = [
            'status' => 200,
            'success' => false,
            'message' => '',
            'item' => null,
            'description' => ''
        ];
        if (!isset($request->item)) {
            $output['message'] = 'Item is Required!';
            return response()->json($output);
        }
        try {
            $item = LocatedItem::where('item', $request->item)->first();
            if (empty($item)) {
                $output['message'] = 'Invalid Roll no!';
            } elseif ($item->is_boxed == 1) {
                $output['message'] = 'Roll has already been Racked!';
            } else {
                $output['message'] = 'Perfect Roll no!';
                $output['status'] = 200;
                $output['success'] = true;
            }
            $output['item'] = $request->item;
            return response()->json($output);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function deliver1(Request $request)
    {
        try {
            $rules = array(
                "item" => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $item = LocatedItem::where('item', $request->input('item'))->first();
            if ($item) {
                if ($item->is_received == 1 && $item->is_boxed == 1) {
                    if ($item->is_delivered == 0) {
                        $item->is_delivered = 1;
                        $item->delivered_at = date('Y-m-d H:i:s');
                        $item->delivered_by = null;
                        $item->save();
                        return response()->json($item);
                    } else {
                        $msg = 'The Item is already Delivered';
                        return response()->json($msg, 401);
                    }
                } else {
                    $msg = 'Please Enter Racked Item';
                    return response()->json($msg, 401);
                }
            } else {
                $msg = 'Please Enter Received Item';
                return response()->json($msg, 401);
            }

        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

    public static function deliver($dataItem, $batch_barcode)
    {
        try {
            $json = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'description' => '',
                'message_icon' => 'error',
            ];
            $item = LocatedItem::where('item', $dataItem)->first();
            $itemInfo = json_decode($item->item_detail);
            $item_batch = $itemInfo->batch_no ? $itemInfo->batch_no : 'UNDEFINED';
            if (empty($item)) {
                $json['message'] = 'Roll not found!';
                return response()->json($json);
            }
            if ($item->is_delivered == 1) {
                $json['message'] = 'Roll has Already been Issued!';
                return response()->json($json);
            }
            if ($item_batch != $batch_barcode) {
                $json['message'] = 'Batch Not Matched!';
                return response()->json($json);
            }

            $item->is_delivered = 1;
            $item->delivered_at = date('Y-m-d H:i:s');
            $item->delivered_by = null;
            $item->save();

            $item->status = 201;
            $item->success = true;
            $item->message = 'Item Issued!';
            $item->message_icon = 'success';
            return response()->json($item);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

    public function getItemsByLocation($location)
    {
        try {
            $items = LocatedItem::where('location', $location)->get();

            if (isset($items)) {
                return response()->json($items, 200);
            }
            $output = [
                'message' => 'location Not found',
                'description' => 'Location not found. Please Enter valid location..'
            ];
            return response()->json($output, 200);

        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), 403);
        }

    }

//============== for sync request=====================
    public function receiveItems()
    {
        try {
            $items = LocatedItem::where('is_received', '=', 1)
                ->where('is_delivered', '=', 0)
                ->where('weight', '=', 0)
                ->first();

            return response()->json($items);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function updateWeight(Request $request, $item)
    {
        try {
            $rules = array(
                "item" => 'required',
                "weight" => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $data = LocatedItem::where('item', '=', $item)->first();
            if ($data->weight == 0) {
                $data->weight = $request->weight;
                $data->save();
            }

            return response()->json($item);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

//===================================
//====== extra(if need) ======//
    public function destroy($id)
    {
        try {
            $item = ReceivedItem::find($id);
            $item->delete();
            return response()->json($item);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rules = array(
                "item" => 'required',
                "box_identifier" => 'required',
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $item = ReceivedItem::find($id);

            $item->item = $request->input('item');
            $item->box_identifier = $request->input('box_identifier');

            $item->save();

            return response()->json($item);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

    public function dummyItemInfo($item)
    {
        try {
            $data = [];
            $data['roll_no'] = $item;
            $data['weight'] = 30;/*rand(10.3, 25.4);*/
            $data['buyer_name'] = 'KAMP EUROPE ' . rand(10, 20);
            $data['order_no'] = 'PO1-18Y-' . rand(1, 100) . '(CHUVI)';
            $data['batch_no'] = 'batch3';
            $data['size'] = 'M\/42 CM';
            $data['color'] = 'LIME (USA)';
            $data['color_type'] = 'DEEP SHADE';
            $data['created'] = '2018-11-' . rand(1, 30) . ' 06:15:30';
            $data['display_date'] = '2018-11-' . rand(1, 30) . ' 00:00:00';
            $data['shift_id'] = rand(1, 12);
            $data['style_no'] = rand(10, 122);
            $data['expected_machine_dia'] = rand(13, 20);
            $data['actual_machine_dia'] = rand(13, 20);
            $data['machine_gauge'] = rand(24, 32);
            $data['finished_width'] = rand(16.50, 21.4);
            $data['gsm'] = rand(135, 176);
            $data['fabric_type'] = 'SL';
            $data['shift_id'] = '3';
            $data['success'] = true;
            return response()->json($data, 200);

        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), 403);
        }
    }

}