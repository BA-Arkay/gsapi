<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 2/22/2019
 * Time: 12:34 PM
 */

namespace App\Http\Controllers;

use App\DeliveredItem;
use App\Delivery;
use App\Http\Controllers\Controller;
use App\Libraries\BarcodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
    public function index()
    {
        try {
            $deliveries = DB::table('deliveries')
                            ->join('booked_locations','deliveries.batch_barcode','=','booked_locations.batch_barcode')
                            ->select(
                                'deliveries.*',
                                'booked_locations.batch_detail'
                            )->get();
            return response()->json($deliveries);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function show($reference)
    {
        try {
            $delivery = DB::table('deliveries')
            ->join('booked_locations','deliveries.batch_barcode','=','booked_locations.batch_barcode')
            ->select(
                'deliveries.*',
                'booked_locations.batch_detail'
            )->where('deliveries.reference', $reference)
            ->first();

            return response()->json($delivery); //403
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = array(
                "reference" => 'required', //unique:deliveries,reference
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $store = Delivery::create($request->all());

            return response()->json($store);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }

    }

    public function destroy($id)
    {
        try {
            $delivery = Delivery::find($id);
            $delivery->delete();
            return response()->json($delivery);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function updateTotalQuantity()
    {
        try {
            $delivery = Delivery::orderBy('id', 'desc')->limit(1)->first();
            $deliverItemQuantity = DeliveredItem::where('delivery_id',$delivery->id)->sum('quantity');

            $deliveryData = Delivery::find($delivery->id);
            $deliveryData->total_qty = $deliverItemQuantity;

            $data = $deliveryData->save();
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    public function update(Request $request, $id)
    {
        try {
            $rules = array(
                "title" => 'required',
                "total_qty" => 'required'
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $barcodeGenerator = new BarcodeGenerator();

            $delivery = Delivery::find($id);

            $delivery->title = ucfirst(strtoupper($request->input('title')));
            $delivery->total_qty = ucfirst(strtoupper($request->input('total_qty')));
            $delivery->barcode = $barcodeGenerator->generate_barcode(str_replace('-', '', ucfirst(strtoupper($request->title))));

            $delivery->save();

            return response()->json($delivery);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

}