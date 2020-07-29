<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 5/1/2019
 * Time: 6:39 PM
 */


namespace App\Http\Controllers;

use App\Box;
use App\DeliveredItem;
use App\Delivery;
use \Exception;

use Illuminate\Support\Facades\DB;
use App\libraries\CustomFunction;
use App\LocatedItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RandomDeliveryController extends Controller
{

    public function pendingDeliveries()
    {
        $all = Delivery::with('delivered_items')->where('delivery_confirmed', 0)->get();
        return $all;
    }

    function create_delivery(Request $request)
    {
        try {
            if (empty($request->ref_no))
                $this->_throw_exception('Invalid Reference!', 'Please scan barcode or input valid reference number');

            $ref_exists_in_db = Delivery::where('reference', "{$request->ref_no}")->first();
            if ($ref_exists_in_db)
                $this->_throw_exception(($ref_exists_in_db->delivery_confirmed ? "Reference is already in delivery queue : {$request->ref_no}!" : 'Already scanned!'));

            $delivery_exists_in_database = Delivery::where('reference', $request->ref_no)->first();
            if ($delivery_exists_in_database)
                $this->_throw_exception('Reference is already delivered!');

            $save = Delivery::create([
                'reference' => $request->ref_no,
            ]);

            if ($save)
                return [
                    'status' => 200,
                    'success' => true,
                    'message' => 'Delivery created!',
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
            if (empty($request->ref_no))
                unset($_SESSION['delivery']);
            // $this->_throw_exception('Input valid reference number.');

            elseif (!isset($_SESSION['delivery']["{$request->ref_no}"]))
                $this->_throw_exception('Delivery not exists!.');

            unset($_SESSION['delivery']["{$request->ref_no}"]);

            return [
                'status' => 200,
                'success' => true,
                'message' => 'Delivery has been removed!',
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
                    $delivery = Delivery::where('reference', "{$request->ref_no}")->first();
                    $delivery->delivery_confirmed = 1;
                    $delivery->delivery_confirmed_at = date('Y-m-d H:i:s');
                    $delivery->save();

                    $delivery_items = DeliveredItem::where('delivery_reference', $request->ref_no)->get();
                    foreach ($delivery_items as $item) {
                        $roll_info = LocatedItem::where('item', $item->item_identifier)->first();
                        $roll_info->is_delivered = 1;
                        $roll_info->delivered_at = date('h-m-d h:i:s');
                        $roll_info->delivered_by = null;
                        $roll_info->save();

                        // box
                        $box_info = Box::where('identifier', $roll_info->location)->first();

                        if ($box_info->identifier != 'ds-dr-ds-db') {
                            $box_info->number_of_items = $box_info->number_of_items - 1;
                            $box_info->occupied = $box_info->occupied - $roll_info->weight;
                            $box_info->actual_free_space = $box_info->capacity - $box_info->occupied;
                            $box_info->save();
                        }
                    }
                    DB::commit();

                } catch (Exception $e) {
                    DB::rollBack();
                }

                if ($delivery)
                    return [
                        'status' => 200,
                        'success' => true,
                        'message' => 'inserting into database...',
                        'description' => '...',
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