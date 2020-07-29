<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 5/1/2019
 * Time: 6:39 PM
 */


namespace App\Http\Controllers;
/*this controller used for batch delivery*/

use App\Box;
use App\DeliveredItem;
use App\Delivery;
use App\BookedLocation;
use \Exception;

use Illuminate\Support\Facades\DB;
use  Illuminate\Database\MySqlConnection;
use App\libraries\CustomFunction;
use App\LocatedItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CommonDeliveryController extends Controller
{

    public function pendingDeliveries()
    {
        $all = Delivery::with('delivered_items')
            ->where('delivery_confirmed', 0)
            ->get();
        return $all;
    }

    public function deliveries()
    {
        $all = Delivery::with('delivered_items')
            ->where('delivery_confirmed', 1)
            ->get();
        return $all;
    }


    function create_delivery(Request $request)
    {
        try {
            $booked_batch = null;
            if (empty($request->ref_no))
                $this->_throw_exception('Invalid Reference!', 'Please scan barcode or input valid reference number');
            /*
            if (!empty($request->batch_no)) {
                $booked_batch = BookedLocation::where('batch_barcode', $request->batch_no)->first();
                if (empty($booked_batch)) {
                    $this->_throw_exception("Batch no : {$request->batch_no} is not Booked!", '');
                }
            } */
            $ref_exists_in_db = Delivery::where('reference', "{$request->ref_no}")->first();
            if ($ref_exists_in_db)
                $this->_throw_exception(($ref_exists_in_db->delivery_confirmed ? "Reference is already in delivery queue : {$request->ref_no}!" : 'Already scanned!'));

            $delivery_exists_in_database = Delivery::where('reference', $request->ref_no)->first();
            if ($delivery_exists_in_database)
                $this->_throw_exception('Reference is already delivered!');

            $stock_batch_info = DB::table('located_items')
                ->select(DB::raw('count(*) as number_of_items, sum(weight) as batch_weight'))
                ->where('is_delivered', 0)
                ->where('batch_no', $request->batch_no)->first();
            $booked_batch = BookedLocation::where('batch_barcode', $request->batch_no)->first();
            $booked_batch_detail = json_decode($booked_batch->batch_detail);
            $output = [
                'status' => null,
                'success' => null,
                'message' => '',
                'description' => '',
                'data' => [
                    "stock_batch" => !$stock_batch_info ? [] : $stock_batch_info,
                    "booked_batch" => !$booked_batch_detail ? [] : $booked_batch_detail
                ]
            ];

            if ($stock_batch_info->number_of_items >= $booked_batch_detail->no_of_items) {
                $save = Delivery::create([
                    'reference' => $request->ref_no,
                    'batch_barcode' => $request->batch_no,
                ]);
                if ($save){
                    $output['status']= 200;
                    $output['success']= true;
                    $output['message'] = 'Delivery created!';
                }
            }
            else{
                $output['status']= 400;
                $output['success']= false;
                $output['message'] = "Batch no : {$request->batch_no} Not Ready For Delivery!";
            }
            return response()->json($output);

        } catch (Exception $e) {
            return [
                'status' => 400,
                'success' => false,
                'message' => explode('|', $e->getMessage())[0],
//                'description' => explode('|', $e->getMessage())[1],
            ];
        }
    }

    function create_delivery_item(Request $request)
    {
        try {
            if (empty($request->ref_no) || empty($request->roll_no))
                $this->_throw_exception('Input Valid Reference no. & Roll no.');

            $ref_exists_in_db = Delivery::where('reference', "{$request->ref_no}")->first();
            if (!$ref_exists_in_db)
                $this->_throw_exception(($ref_exists_in_db->delivery_confirmed ? 'Reference is already delivered!' : 'Already scanned!'));

            $roll_exists_in_rcv = LocatedItem::where('item', "{$request->roll_no}")->first();
            if (!$roll_exists_in_rcv)
                $this->_throw_exception('Roll has not received yet!');

            if (
                is_object($ref_exists_in_db)
                && isset($ref_exists_in_db->batch_barcode)
                && !empty($ref_exists_in_db->batch_barcode)
                && is_object($roll_exists_in_rcv)
                && isset($roll_exists_in_rcv->item_detail)
            ) {
                $roll_info = json_decode($roll_exists_in_rcv->item_detail);
                if (
                    isset($roll_info->batch_no)
                    && !empty($roll_info->batch_no)
                    && $ref_exists_in_db->batch_barcode == $roll_info->batch_no
                ) {
                    // BATCH matched!
                } else {
                    $this->_throw_exception('Roll does not belongs to this BATCH');
                }

            }

            // check if roll is already exists in another delivery
            $roll_exists_in_db = DeliveredItem::where('item_identifier', "{$request->roll_no}")->first();
            if ($roll_exists_in_db)
                $this->_throw_exception("Already in delivery queue: Ref. no. : {$roll_exists_in_db->delivery_reference}");

            $save = DeliveredItem::create([
                'delivery_id' => $ref_exists_in_db->id,
                'delivery_reference' => $request->ref_no,
                'item_identifier' => $request->roll_no,
                'quantity' => $roll_exists_in_rcv->weight,
            ]);

            if ($save)
                return [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Roll has been added for delivery!',
                    'description' => '',
                    'data' => $roll_exists_in_rcv,
                ];

        } catch (Exception $e) {
            return [
                'status' => 400,
                'success' => false,
                'message' => explode('|', $e->getMessage())[0],
                'description' => explode('|', $e->getMessage())[1],
            ];
        }
    }

    function remove_pending_delivery(Request $request)
    {
        try {
            if ($request->ref_no == '*') {
                $res = DB::delete('delete from deliveries where delivery_confirmed=0');
                if ($res)
                    $_msg = 'All pending deliveries has been cleared!';
                else
                    $this->_throw_exception('Unable to clear all pending pending deliveries!');
            } elseif (!empty($request->ref_no)) {
                $res = DB::delete("delete from deliveries where delivery_confirmed=0 AND reference='{$request->ref_no}'");
                if ($res)
                    $_msg = "All pending delivery ({$request->ref_no}) has been cleared!";
                else
                    $this->_throw_exception("Unable to remove pending delivery: {$request->ref_no}!");
            } else {
                $this->_throw_exception('Input valid Ref. no.');
                die;
            }
            return [
                'status' => 200,
                'success' => true,
                'message' => $_msg,
                'description' => '',
            ];
        } catch (Exception $e) {
            return [
                'status' => 400,
                'success' => false,
                'message' => explode('|', $e->getMessage())[0],
                'description' => explode('|', $e->getMessage())[1],
            ];
        }

    }

    function remove_pending_delivery_item(Request $request)
    {
        try {
            if (empty($request->roll_no))
                $this->_throw_exception('Input valid Roll no.');

            $if_roll_exists = CustomFunction::roll_exists_in_delivery("{$request->roll_no}");

            if (!$if_roll_exists)
                $this->_throw_exception('Roll hasn\'t been added in delivery yet!');

            unset($_SESSION['delivery']["{$if_roll_exists}"]["{$request->roll_no}"]);

            return [
                'status' => 200,
                'success' => true,
                'message' => 'Roll has been removed from pending delivery!',
                'description' => '',
            ];
        } catch (Exception $e) {
            return [
                'status' => 400,
                'success' => false,
                'message' => explode('|', $e->getMessage())[0],
                'description' => explode('|', $e->getMessage())[1],
            ];
        }
    }

    function confirm_delivery(Request $request)
    {
        try {
            if (empty($request->ref_no))
                $this->_throw_exception('Please input ref. no.');

            $ref_exists_in_db = Delivery::where('reference', "{$request->ref_no}")->first();

            if (!$ref_exists_in_db)
                $this->_throw_exception('Reference does not exist!');

            if ($ref_exists_in_db->delivery_confirmed)
                $this->_throw_exception('Reference already delivered!');

            $roll_exists_for_delivery = DeliveredItem::where('delivery_reference', "{$request->ref_no}")->count();
            // return $roll_exists_for_delivery;

            if ($roll_exists_for_delivery) {
                // return $ref_exists_in_db;
                $delivery = null;
                $delivery_items = null;

                DB::beginTransaction();
                try {
                    $total_qty = 0;
                    $delivery_items = DeliveredItem::where('delivery_reference', $request->ref_no)->get();
                    foreach ($delivery_items as $item) {
                        $total_qty += $item->quantity;
                        $roll_info = LocatedItem::where('item', $item->item_identifier)->first();
                        $roll_info->is_delivered = 1;
                        $roll_info->delivered_at = date('h-m-d h:i:s');
                        $roll_info->delivered_by = null;
                        $roll_info->save();

                        // box
                        $box_info = Box::where('identifier', $roll_info->location)->first();

                        if ($box_info->identifier != 'ds-dr-ds-db') {
                            $box_info->number_of_items -= 1;
                            $box_info->occupied -= $roll_info->weight;
                            $box_info->actual_free_space = $box_info->capacity - $box_info->occupied;
                            $box_info->bookable_free_space = $box_info->capacity - $box_info->occupied;
                            $box_info->save();
                        }
                    }
                    $batch_info = DB::table('located_items')
                        ->select(DB::raw('count(*) as number_of_items'))
                        ->where('is_delivered', 0)
                        ->where('batch_no', $request->batch_no)->first();
                    if (isset($batch_info->number_of_items) && $batch_info->number_of_items == 0) {
                        $booked_box = Box::where('batch_barcode', $request->batch_no)->first();
                        if (!empty($booked_box)) {
                            $booked_box->occupied = 0;
                            $booked_box->actual_free_space = $booked_box->capacity;
                            $booked_box->booked_quantity = 0;
                            $booked_box->bookable_free_space = $booked_box->capacity;
                            $booked_box->batch_barcode = null;
                            $booked_box->batch_rolls = 0;
                            $booked_box->number_of_items = 0;
                            $booked_box->save();
                        }
                    }
                    $delivery = Delivery::where('reference', "{$request->ref_no}")->first();
                    $delivery->total_qty = $total_qty;
                    $delivery->delivery_confirmed = 1;
                    $delivery->delivery_confirmed_at = date('Y-m-d H:i:s');
                    $delivery->save();
                    DB::commit();

                } catch (Exception $e) {
                    DB::rollBack();
                }

                if ($delivery)
                    return [
                        'status' => 200,
                        'success' => true,
                        'message' => 'Delivery Successfully Confirmed!',
                        'description' => '...',
                        'data' => $delivery
                    ];
                else
                    return [
                        'status' => 400,
                        'success' => false,
                        'message' => 'Unable to confirm delivery',
                        'description' => 'Database error!',
                    ];
            } else {
                $this->_throw_exception('No rolls exist in delivery queue!');
            }
        } catch (Exception $e) {
            return [
                'status' => 400,
                'success' => false,
                'message' => @explode('|', @$e->getMessage())[0],
                'description' => @explode('|', @$e->getMessage())[1],
            ];
        }
    }

    private function _throw_exception($msg_title, $msg_desc = null)
    {
        throw new Exception("{$msg_title}|{$msg_desc}");
    }

}