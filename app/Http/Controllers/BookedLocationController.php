<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 3/22/2019
 * Time: 12:14 PM
 */

namespace App\Http\Controllers;

use App\BookedLocation;
use App\Box;
use App\Logs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;
use App\Libraries\BarcodeGenerator;

class BookedLocationController extends Controller
{
    public function index()
    {
        try {

            $data = DB::table('booked_locations')
                ->join('boxes', 'booked_locations.box_barcode', '=', 'boxes.identifier')
//                ->join('located_items','boxes.identifier','=','located_items.location')
                ->select(
                    'booked_locations.*',
                    'boxes.identifier as box',
                    'boxes.capacity as box_capacity',
                    'boxes.occupied as box_occupied',
                    'boxes.number_of_items as stock_rolls'
//                    'located_items.boxed_at as stock_date'
                )
//                ->orderBy('located_items.id','asc')
//                ->limit('located_items.id',1)
                ->get();

            return response()->json($data);

        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), 403);
        }
    }

    public function create(Request $request)
    {
        try {
            $rules = array(
                "batch_barcode" => 'required',
                "box_barcode" => 'required'
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json = [
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 401);
            }
            // box
            $box = Box::where('identifier', '=', $request->box_barcode);
            if ($box) {
                $box->bookable_free_space = $box->capacity - $box->booked_quantity;
                $box->save();
                $data['batch_barcode'] = $request->input('batch_barcode');
                $data['box_barcode'] = $request->input('box_barcode');
                $bookedLocation = BookedLocation::create($data);
                $output = [
                    'bookedLocation' => $bookedLocation,
                    'box' => $box
                ];
                return response()->json($output, 200);
            }
            return response()->json('Box Not Found..!', 401);

        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), 403);
        }
    }

    public function getBatchInfoByBatch($batch)
    {
        try {
            $json = [
                'status' => 200,
                'success' => null,
                'message' => '',
                'location' => '',
                'batch' => '',
            ];
            $bookedLocation = BookedLocation::where('batch_barcode', $batch)->first();
            if ($bookedLocation) {
                $json['success'] = true;
                $json['message'] = 'Location :' . $bookedLocation->box_barcode;
                $json['batch'] = $bookedLocation->batch_barcode;
                $json['location'] = $bookedLocation->box_barcode;
            } else {
                $json['success'] = false;
                $json['message'] = 'Location Not Booked For this Batch.';
                $json['batch'] = $batch;
                $json['location'] = 'Not Found!';
            }
            return response()->json($json); //403
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function destroy($id)
    {
        try {
            $bookedLocation = BookedLocation::find($id);
            $bookedLocation->delete();
            return response()->json($bookedLocation, 200);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rules = array(
                "batch_barcode" => 'required',
                "box_barcode" => 'required'
            );

            $validator = Validator::make($request->all(), $rules);
            $bookedLocation = BookedLocation::find($id);

            if ($validator->fails()) {
                $json = [
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 401);
            }
            $bookedLocation->batch_barcode = $request->input('batch_barcode');
            $bookedLocation->box_barcode = $request->input('box_barcode');
            $bookedLocation->save();

            return response()->json($bookedLocation, 200);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

    public function bookedLocation(Request $request)
    {
        // $log = new Logs([
        //     'type'      => 'bookedLocation',
        //     'title'     => 'Requesting to know/book Box location for Batch!',
        // ]);

        if (
            !isset($request->batch_no) || empty($request->batch_no)
            || !isset($request->total_weight) || empty($request->total_weight)
            || !isset($request->no_of_items) || empty($request->no_of_items)
        ) {
            $output = [
                "msg" => "Batch , Weight & Number of Rolls is Required!"
            ];

            // Logging in DB
            $log = new Logs([
                'type' => 'bookedLocation',
                'title' => 'Booking failure due to: Batch , Weight & Number of Rolls is Required',
            ]);

            return (json_encode($output));
        }

        try {
            $expected_wt_per_roll = null;
            $shelf_loc = null;
//            if ($request->no_of_items > 0) {
//                $expected_wt_per_roll = $request->total_weight / $request->no_of_items;
//                $shelf_loc = $expected_wt_per_roll >= 40 ? 'S1' : '*';
//            }

            $json = [
                'status' => 200,
                'success' => true,
                'message' => '',
                'location' => '',
                'description' => '',
            ];
            $bookedLocationCheck = BookedLocation::where('batch_barcode', $request->batch_no)->first();
            // return $bookedLocationCheck;
            if ($bookedLocationCheck) {
                $json['message'] = 'Location ' . $bookedLocationCheck->box_barcode . ' is Already Booked for the Batch : ' . $request->batch_no;
                $json['location'] = $bookedLocationCheck->box_barcode;

                // Logging in DB
                $log = new Logs([
                    'type' => 'bookedLocation',
                    'title' => $json['message'],
                ]);

                return response()->json($json);
            }
            $data = null;
            $box = null;
            $bookedLocation = null;
            if (env('RACKTYPE') == 'title_wise') {

                if ($request->no_of_items > 0 && $request->total_weight && ($request->total_weight / $request->no_of_items) >= 40) {

                    $box = DB::table('boxes')
                        ->join('shelves', 'boxes.shelf_id', '=', 'shelves.id')
                        ->select('boxes.*', 'shelves.title as shelf')
                        ->where('shelves.title', '=', 'S1')
                        ->where('boxes.capacity', '>=', $request->total_weight)
                        ->where('boxes.is_active', '=', 1)
                        ->orderBy('boxes.title', 'asc')
                        ->where('boxes.batch_barcode', '=', null)
                        ->first();
                }
                else{
                    $box = DB::table('boxes')
                        ->select('boxes.*')
                        ->where('capacity', '>=', $request->total_weight)
                        ->where('is_active', '=', 1)
                        ->orderBy('title', 'asc')
                        ->where('batch_barcode', '=', null)
                        ->first();
                }
            } elseif (env('RACKTYPE') == 'id_wise') {
                $box = DB::table('boxes')
                    ->join('shelves', 'boxes.shelf_id', '=', 'shelves.id')
                    ->select('boxes.*', 'shelves.title as shelf')
                    ->where('shelves.title', '=', $shelf_loc)
                    ->where('boxes.capacity', '>=', $request->total_weight)
                    ->where('boxes.is_active', '=', 1)
                    ->where('boxes.batch_barcode', '=', null)
                    ->first();
            }

            if ($box) {
                DB::beginTransaction();
                try {

                    // update booked quantity in boxes table

                    DB::table('boxes')
                        ->where('id', $box->id)
                        ->update([
                            'batch_barcode' => $request->batch_no,
                            'batch_rolls' => $request->no_of_items,
                            'booked_quantity' => $box->booked_quantity + $request->total_weight,
                            'bookable_free_space' => $box->capacity - $box->booked_quantity,
                        ]);

                    // store data in bookedLocations table
                    $data['batch_barcode'] = $request->batch_no;
                    $data['box_barcode'] = $box->identifier;
                    $data['batch_weight'] = $request->total_weight;
                    $data['batch_detail'] = json_encode($request->all());
                    $data['number_of_items'] = $request->no_of_items;
                    $bookedLocation = BookedLocation::create($data);
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                }
                $json['message'] = 'Location Booked!';
                $json['location'] = $bookedLocation->box_barcode;
            }
            else {
                $json['status'] = 200;
                $json['success'] = false;
                $json['message'] = 'Box Not Available!';
            }

            // Logging in DB
            $log = new Logs([
                'type' => 'bookedLocation',
                'title' => $json['message'],
            ]);

            return response()->json($json);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function dummyBatchInfo($batch)
    {
        $batch_info = [
            "id" => '11',
            "batch_no" => $batch,
            "total_weight" => 1800,
            "order_no" => 4574398,
            "no_of_items" => 454,
            "expected_dyeing_date" => null,
            "batch_location" => null,
        ];
        return response()->json($batch_info);
    }

}