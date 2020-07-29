<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 4/6/2019
 * Time: 6:45 PM
 */

namespace App\Http\Controllers;


use App\Box;
use App\DeliveredItem;
use App\Delivery;
use App\LocatedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException as Exception;

class BatchDeliveryItemController extends Controller
{
    public function index()
    {
        try {
            $delivery = Delivery::orderBy('id', 'desc')->limit(1)->first();
            $deliveredItems = DeliveredItem::where('delivery_id', '=', $delivery->id)->get();
            return response()->json($deliveredItems);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function showByDeliverId($id)
    {
        try {
            $deliveredItems = DeliveredItem::where('delivery_id', '=', $id)->get();
            return response()->json($deliveredItems);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function create(Request $request)
    {
        try {

            $rules = array(
                "item_identifier" => 'required',
            );
            $json = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'description' => '',
                'message_icon' => 'error',
            ];
            $deliveryItem = null;
            $delivery = null;
            $item = null;
            $data = null;
            $validator = Validator::make($request->all(), $rules);

            $item = LocatedItem::where('item', $request->item_identifier)->first();
            if (empty($item)) {
                $json['message'] = 'Roll not found!';
                return response()->json($json);
            }
            $itemInfo = json_decode($item->item_detail);
            $item_batch = $itemInfo->batch_no ? $itemInfo->batch_no : 'UNDEFINED';
            $item_weight = $itemInfo->weight ? $itemInfo->weight : 00.00;

            $delivery = Delivery::where('batch_barcode', $request->batch_barcode)->first();

            if (empty($delivery)) {
                $json['message'] = 'Calan no Not Found!';
                return response()->json($json);
            }
            if ($validator->fails()) {
                $json['message'] = 'Roll Required';
                $json['description'] = $validator->messages();
                return response()->json($json);
            }
            if ($item->is_received == 0) {
                $json['message'] = 'Roll is not Received!';
                return response()->json($json);
            }
            if ($item->is_delivered == 1) {
                $json['message'] = 'Roll has Already been Issued!';
                return response()->json($json);
            }
            if ($delivery->batch_barcode != $item_batch) {
                $json['message'] = 'Roll does not belongs to this Batch!';
                return response()->json($json);
            }
            $box = Box::where('identifier', $item->location)->first();
            DB::beginTransaction();
            try {
                // create data at deliveryItems
                $data['item_identifier'] = $request->input('item_identifier');
                $data['quantity'] = $item_weight;
                $data['delivery_id'] = $delivery->id;
                $data['delivery_reference'] = $delivery->reference;
                $deliveryItem = DeliveredItem::create($data);
                //update data at locatedItems
                $item->is_delivered = 1;
                $item->delivered_at = date('Y-m-d H:i:s');
                $item->save();
                //update to box table
                $box->number_of_items = $box->number_of_items - 1;
                $box->occupied = $box->occupied - $item_weight;
                $box->actual_free_space = $box->capacity - $box->occupied;
                $box->save();

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
            }

            $deliveryItem->status = 201;
            $deliveryItem->success = true;
            $deliveryItem->message_icon = 'success';
            $deliveryItem->message = 'OK!';
            $deliveryItem->description = 'Roll has been successfully added.';
            return response()->json($deliveryItem);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    public function destroy($id)
    {
        try {
            $deliveredItem = DeliveredItem::find($id);
            $item = LocatedItem::where('item', '=', $deliveredItem->item_identifier)->first();
            $item->is_delivered = 0;
            $item->delivered_at = date('Y-m-d H:i:s');
            $item->save();
            $deliveredItem->delete();
            return response()->json($deliveredItem);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rules = array(
                "item_identifier" => 'required',
                "quantity" => 'required'
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $deliveredItems = DeliveredItem::find($id);

            $deliveredItems->item_identifier = $request->input('item_identifier');
            $deliveredItems->quantity = $request->input('quantity');

            $deliveredItems->save();

            return response()->json($deliveredItems);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

    public function save()
    {
        try {
            $json = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'description' => '',
                'message_icon' => 'error',
            ];
            $batch_delivery = null;
            $batch_delivery_items = null;

            //$batch_delivery = Delivery::where('reference', $request->reference)->where('batch_barcode', $request->batch_barcode)->first();
            //
            $batch_delivery = Delivery::orderBy('id', 'desc')->first();
            if (empty($batch_delivery)) {
                $json['message'] = 'Calan no Not Found!';
                return response()->json($json);
            }
            $total_quantity = DeliveredItem::where('delivery_id', '=', $batch_delivery->id)->sum('quantity');
            $box = Box::where('batch_barcode', $batch_delivery->batch_barcode)->first();
            DB::beginTransaction();
            try {
                if ($box->number_of_items == 0) {
                    $box->batch_barcode = null;
                    $box->save();
                }
                $batch_delivery->total_qty = $batch_delivery->total_qty + $total_quantity;
                $batch_delivery->save();
                DB::commit();
            } catch (Excepiton $e) {
                DB::rollBack();
            }

            $batch_delivery->status = 201;
            $batch_delivery->success = true;
            $batch_delivery->message = 'Issued Completed!';
            $batch_delivery->message_icon = 'success';

            return response()->json($batch_delivery);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

}

//$batch_delivery_items = DeliveredItem::where('delivery_id', $batch_delivery->id)->get();

/*if (empty($batch_delivery_items)) {
    $json['message'] = 'Roll Not Found!';
    return response()->json($json);
}*/