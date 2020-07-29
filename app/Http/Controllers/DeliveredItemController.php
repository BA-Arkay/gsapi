<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 2/22/2019
 * Time: 12:34 PM
 */

namespace App\Http\Controllers;
use App\Box;
use App\DeliveredItem;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\LocatedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;


class DeliveredItemController extends Controller
{
    public function index()
    {
        try {
            $delivery = Delivery::orderBy('id', 'desc')->get();
            $deliveredItems = DeliveredItem::where('delivery_id','=',$delivery->id)->get();
            return response()->json($deliveredItems);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function showByDeliverId($reference)
    {
        try {
            // $deliveredItems = DeliveredItem::where('delivery_reference', $reference)->get();

            $deliveredItems =  DB::table('delivered_items')
                                ->join('located_items', 'delivered_items.item_identifier', '=', 'located_items.item')
                                ->select(
                                    'delivered_items.*', 
                                    'located_items.item as located_item', 
                                    'located_items.location', 
                                    'located_items.weight', 
                                    'located_items.batch_no', 
                                    'located_items.item_detail'
                                )
                                ->where('delivered_items.delivery_reference', $reference)
                                ->get();
            
            return response()->json($deliveredItems);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }
}