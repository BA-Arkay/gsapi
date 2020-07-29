<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 4/6/2019
 * Time: 6:29 PM
 */

namespace App\Http\Controllers;


use App\BookedLocation;
use App\Delivery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;

class BatchDeliveryController extends Controller
{
    public function index()
    {
        try {
            $deliveries = Delivery::all();
            return response()->json($deliveries);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function show($id)
    {
        try {
            $delivery = Delivery::find($id);
            return response()->json($delivery); //403
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function create(Request $request)
    {
        try {
            $rules = array(
                "reference" => 'required', // unique:deliveries,reference
                "batch_barcode" => 'required',
            );

            $json = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'description' => '',
                'message_icon' => 'error',
            ];
            $validator = Validator::make($request->all(), $rules);
            $booked_location = BookedLocation::where('batch_barcode',$request->batch_barcode)->first();

            if ($validator->fails()) {
                $json['message'] = 'Reference & Batch no is Required!';
                $json['description'] = $validator->messages();
                return response()->json($json);
            }
           /* if (empty($booked_location)){
                $json['message'] = 'Location Not Booked For This Batch!';
                $json['description'] = '';
                return response()->json($json);
            }*/
            $delivery = Delivery::create($request->all());
            $delivery->status = 201;
            $delivery->success = true;
            $delivery->message = 'Batch no is added';
            $delivery->message_icon = 'success';
            return response()->json($delivery);
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

    public static function updateQuantity($id,$quantity){
        try{
            $batch_delivery = Delivery::where('id',$id)->first();
            $batch_delivery->total_qty = $quantity;
            $batch_delivery->save();
            return response()->json($batch_delivery,201);
        }
        catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

}