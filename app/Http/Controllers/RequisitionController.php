<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;
use App\libraries\Utility;
use App\LocatedItem;
use App\Box;

class RequisitionController extends Controller
{


    public function get_item_info_by_item(Request $request)
    {
        try {
            if (empty($request->item))
                Utility::_throw_exception('Roll no is required!');

            if (empty($request->order_no))
                Utility::_throw_exception('Order No is Required!');

            if (empty($request->color))
                Utility::_throw_exception('Color is Required!');

            if (empty($request->batch_no))
                Utility::_throw_exception('batch no is Required!');

            if (empty($request->fabric_type))
                Utility::_throw_exception('Fabric Type is Required!');

            $fabric_types = explode(',', $request->fabric_type);

            $located_item = LocatedItem::where('item', $request->item)->first();

            if (isset($located_item)) {
                if ($located_item->is_delivered) {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'message' => 'Roll is allready Delivered!'
                    ]);
                }
                $box = Box::where('identifier', $located_item->location)->first();

                $item_details = json_decode($located_item->item_detail);

                if (strtolower($item_details->order_no) != strtolower($request->order_no)) {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'message' => 'Order Not Matched!'
                    ]);
                }

                if (strtolower($request->color) != strtolower($item_details->color)) {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'message' => 'Color Not Matched!'
                    ]);
                }

                $ft_result = null;
                foreach ($fabric_types as $ft) {

                    if (strtolower($item_details->fabric_type) == strtolower($ft)) {
                        $ft_result = true;
                        break;
                    } else {
                        $ft_result = false;
                    }
                }

                if (!$ft_result) {
                    return response()->json([
                        'status' => 400,
                        'success' => false,
                        'message' => 'Fabric Type Not Matched!'
                    ]);
                }

                /* update delivery info */
                DB::beginTransaction();
                try {
                    $box->number_of_items -= 1;
                    $box->occupied -= $item_details->weight ? $item_details->weight : 0;
                    $box->actual_free_space = $box->capacity + $box->occupied;
                    $box->bookable_free_space = $box->capacity + $box->occupied;
                    $box->save();

                    // update data into locatedItems table
                    $located_item->batch_no = $request->batch_no;
                    $located_item->is_delivered = 1;
                    $located_item->delivered_at = date('Y-m-d H:i:s');
                    $located_item->delivered_by = null;
                    $located_item->save();
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                }

                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Roll Delivered!',
                    'data' => $located_item
                ]);
            }

            return response()->json([
                'status' => 400,
                'success' => false,
                'message' => 'Unable to Delivered!'
            ]);
        } catch (Exception $e) {
            return [
                'status' => 403,
                'success' => false,
                'message' => @explode('|', @$e->getMessage())[0],
                'description' => @explode('|', @$e->getMessage())[1],
            ];
        }
    }
}
