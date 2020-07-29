<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\libraries\Utility;
use App\LocatedItem;
use App\Logs;
use App\Settings;
use App\Shelf;
use App\Store;
use Illuminate\Http\Request;
use App\Box;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;
use App\Rack;
use App\Libraries\BarcodeGenerator;

class BoxController extends Controller
{

    public function index()
    {
        try {
            $boxes = Shelf::with('store', 'rack', 'boxes')
                ->where('id', '>', 1)
                ->get();
            return response()->json($boxes);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function indexForDashboard()
    {
        try {
            $boxes = Store::with(['racks' => function ($query) {
                $query->with(['shelves' => function ($query1) {
                    $query1->with('boxes');
                }]);
            }])->where('id', '>', 1)->get();

            return response()->json($boxes);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function indexDataTable()
    {
        try {
            $shelves = DB::table('boxes')
                ->join('stores', 'stores.id', '=', 'boxes.store_id')
                ->join('racks', 'racks.id', '=', 'boxes.rack_id')
                ->join('shelves', 'shelves.id', '=', 'boxes.shelf_id')
                ->select('boxes.*', 'racks.title as rack', 'stores.title as store', 'shelves.title as shelf')
                ->where('boxes.id', '>', 1)
                ->get();
            return response()->json($shelves);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function show($id)
    {
        try {
            $boxes = Box::find($id);
            return response()->json($boxes);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function boxByIdentifier($identifier)
    {
        try {
            $json = [
                'success' => true,
                'status' => 200,
                'message' => ''
            ];

            $box = Box::where('identifier', $identifier)->first();

            if (isset($box) && !empty($box) && $box->is_active == 1) {
                $json['data'] = $box;
                $json['message'] = 'Perfect!';
            } elseif (isset($box) && !empty($box) && $box->is_active == 0) {
                $json['success'] = false;
                $json['status'] = 400;
                $json['message'] = 'Box Not Active!';
            } else {
                $json['success'] = false;
                $json['status'] = 400;
                $json['message'] = 'Box Not found!';
            }

            return response()->json($json);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function store(Request $request)
    {
        try {
            $titleRule = (env('FACTORY') == 'Epyllion') ? 'required' : 'unique:boxes,title';
            $rules = array(
                'store_id' => 'required',
                'rack_id' => 'required',
                'shelf_id' => 'required',
                'title' => $titleRule,
                'capacity' => 'required',
            );

            $json = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'description' => '',
                'message_icon' => 'error',
                'message_type' => 'error',
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = (env('FACTORY') == 'Epyllion') ? 'Store, Rack, Shelf & Title Required!' : 'Title has already been taken!';
                $json['message'] = $message;
                $json['description'] = $validator->messages();

                return response()->json($json);
            }
            //barcode settings
            $setting = Settings::where('key', 'barcode')->first();
            if (env('FACTORY') == 'Epyllion') {
                $uniqueCheck = Box::where('store_id', $request->store_id)
                    ->where('rack_id', $request->rack_id)
                    ->where('shelf_id', $request->shelf_id)
                    ->where('title', $request->title)->first();

                if ($uniqueCheck) {
                    $json['message'] = 'Title has already been taken!';
                    $json['message_type'] = 'danger';
                    return response()->json($json);
                }
            }
            // for identifier
            $store = Store::where('id', $request->store_id)->first()->title;
            $shelve = Shelf::where('id', $request->shelf_id)->first()->title;
            $rack = Rack::where('id', $request->rack_id)->first()->title;
            $latestBox = Box::orderBy('id', 'desc')->first();
            $latestBox ? $latestBox = $latestBox->id + 1 : $latestBox = 1;

            $data['store_id'] = $request->input('store_id');
            $data['rack_id'] = $request->input('rack_id');
            $data['shelf_id'] = $request->input('shelf_id');
            $data['title'] = ucfirst(strtoupper($request->input('title')));
            $data['capacity'] = $request->input('capacity');
            //new
            $data['actual_free_space'] = $request->input('capacity');
            $data['bookable_free_space'] = $request->input('capacity');
            $data['is_active'] = 1;

            $barcodeGenerator = new BarcodeGenerator();
            $data['barcode'] = '';
            $data['identifier'] = "";
            // config barcode
            if ($setting->value == 'title') {
                $barcodeTitle = '';
                if (env('FACTORY') == 'Epyllion') {
                    $barcodeTitle = str_replace(' ', '', strtoupper($rack . $shelve . $request->title));
                } elseif (env('FACTORY') == 'Keya') {
                    $barcodeTitle = str_replace(' ', '', strtoupper($request->title));
                } else {
                    $barcodeTitle = str_replace(' ', '', strtoupper($request->title));
                }
                // $barcodeTitle = strtoupper($request->title);
                $data['barcode'] = $barcodeGenerator->generate_barcode(str_replace('-', '', $barcodeTitle));
                $data['identifier'] = str_replace('-', '', $barcodeTitle);
            } else {
                $identifierTitle = strtoupper('s' . $request->store_id . 'r' . $request->rack_id . 's' . $request->shelf_id . 'b' . $latestBox);
                $data['identifier'] = $identifierTitle;
                $data['barcode'] = $barcodeGenerator->generate_barcode(str_replace('-', '', $identifierTitle));
            }
            $box = Box::create($data);

            $box->status = 201;
            $box->success = true;
            $box->message = 'Box Created!';
            $box->message_icon = 'success';
            $box->store = $store;
            $box->rack = $rack;
            $box->shelf = $shelve;

            return response()->json($box);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function destroy($id)
    {
        try {
            $output = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'message_icon' => 'success',
                'box' => '',
                'description' => ''
            ];
            $box = Box::find($id);
            $output['box'] = $box->identifier;
            if ($box->number_of_items) {
                $output['message_icon'] = 'error';
                $output['message'] = "This box contain {$box->number_of_items} Roll!";
                return response()->json($output);
            }
            $path = app()->basePath('public/barcodes/' . $box->barcode);
            $box->delete();
            unlink($path);

            $output['message'] = 'Box Removed!';
            $output['success'] = true;
            return response()->json($output);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $titleRule = (env('FACTORY') == 'Epyllion') ? 'required' : 'unique:boxes,title';
            $rules = array(
                'title' => $titleRule,
                'capacity' => 'required',
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages(),
                ];
                return response()->json($json, 400);
            }
            //barcode settings
            $setting = Settings::where('key', 'barcode')->first();
            $box = Box::find($id);

            $uniqueCheck = Box::where('title', $request->title)->first();

            $store = Store::where('id', $box->store_id)->first()->title;
            $shelve = Shelf::where('id', $box->shelf_id)->first()->title;
            $rack = Rack::where('id', $box->rack_id)->first()->title;

            $barcodeGenerator = new BarcodeGenerator();
            $barcode = '';
            $barcodeTitle = '';
            $identifier = '';
            if ($setting->value == 'title') {
                (env('FACTORY') == 'Epyllion') ? $barcodeTitle = str_replace(' ', '', strtoupper($rack . $shelve . $request->title)) : $barcodeTitle = str_replace(' ', '', strtoupper($request->title));
                $barcode = $barcodeGenerator->generate_barcode(str_replace(' ', '', str_replace('-', '', $barcodeTitle)));
                $identifier = str_replace('-', '', $barcodeTitle);

            } else {
                $barcode = $barcodeGenerator->generate_barcode($box->identifier);
            }

            if ($uniqueCheck) {
                //return "match";
                if ($request->title == $uniqueCheck->title && $request->capacity != $box->capacity) {
                    $box->capacity = $request->input('capacity');
                    $box->save();
                    return response()->json($box);
                } elseif ($request->title == $uniqueCheck->title && $request->capacity == $box->capacity) {
                    $msg = "The Title is already Taken..";
                    return response()->json($msg, 401);
                } else {
                    $box->capacity = $request->input('capacity');
                    $box->title = ucfirst(strtoupper($request->input('title')));
                    $box->barcode = $barcode;
                    $box->save();
                    return response()->json($box);
                }
            } else {
                $box->capacity = $request->input('capacity');
                $box->title = ucfirst(strtoupper($request->input('title')));
                $box->barcode = $barcode;
                $box->identifier = $identifier;
                $box->save();
            }
            return response()->json($box);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function boxesByShelf($shelfId)
    {
        try {
            $data = Box::where('shelf_id', $shelfId)->get();
            return response()->json($data);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function getBarcodes()
    {
        try {
            $boxes = Box::where('id', '>', 1)->get();

            return response()->json($boxes);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function updateOccupied(Request $request, $identifier)
    {
        try {
            $rules = array(
                'weight' => 'required',
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }

            $data = Box::where('identifier', '=', $identifier)->first();

            $data->occupied = $data->occupied + $request->weight;
            $data->actual_free_space = $data->capacity - $data->occupied;
            if ($data->occupied < $data->capacity) {
                $data->save();
                return response()->json($data);
            } else {
                $json = 'The box is already full..';
                return response()->json($json, 401);
            }
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function getExpectedItemLocation($item, $weight)
    {
        try {
            $item = LocatedItem::where('item', '=', $item)->first();
            if (!$item) {
                //$identifier = Box::where('capacity', '>', "occupied+$weight")->first();
                $expectedLocation = Box::where('actual_free_space', '>=', $weight)->first();
                return response()->json($expectedLocation);
            }
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function changeToActive($id)
    {
        try {
            $data = Box::where('id', '=', $id)->first();
            $data->is_active = 1;
            $data->save();
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function changeToDisable($id)
    {
        try {
            $data = Box::where('id', '=', $id)->first();
            $locatedItemLocationCheck = LocatedItem::where('location', $data->identifier)->first();
            if ($locatedItemLocationCheck) {
                $json = "There is item in the box. So don't be able to disable this box.";
                return response()->json($json, 401);
            }
            $data->is_active = 0;
            $data->save();
            return response()->json($data);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function forge_remove_booking(Request $request)
    {
        try {
            $api_output_header = [
                'status' => 400,
                'success' => false,
                'message' => '',
                'description' => '',
            ];
            if (empty($request->identifier)) {
                $api_output_header['message'] = 'Box Identifier is Required!';
                // Logging in DB
                $log = new Logs([
                    'type' => 'Forge_Remove_Booking',
                    'title' => 'Booking Remove failure',
                    'description' => $api_output_header['message']
                ]);
                return Utility::api_output($api_output_header);
            }

            $box = Box::where('identifier', $request->identifier)->first();
            $batch_no = '';
            $done = null;
            $rolls = LocatedItem::where('location', $request->identifier)->where('is_delivered', 0)->get();

            if (isset($box) && !empty($box)) {
                $batch_no = $box->batch_barcode;

                DB::beginTransaction();
                try {
                    $box->batch_barcode = null;
                    $box->occupied = 0;
                    $box->actual_free_space = $box->capacity;
                    $box->booked_quantity = 0;
                    $box->bookable_free_space = $box->capacity;
                    $box->batch_rolls = 0;
                    $box->number_of_items = 0;
                    $done = $box->save();

                    if (isset($rolls) && !empty($rolls)) {
                        foreach ($rolls as $roll) {
                            $roll->location = 'ds-dr-ds-db';
                            $roll->save();
                        }
                    }
                    DB::commit();
                } catch (Exception $exception) {
                    DB::rollBack();
                }
                if (isset($done) && !empty($done)){
                    $api_output_header['message'] = 'Booking Removed!';
                    $api_output_header['success'] = true;
                    $api_output_header['status'] = 200;
                }else{
                    $api_output_header['message'] = 'Booking Removed Failed!';
                    $api_output_header['success'] = false;
                    $api_output_header['status'] = 400;
                }

            } else {
                $api_output_header['message'] = 'Box Not Found!';
                $api_output_header['success'] = false;
                $api_output_header['status'] = 400;
            }
            $api_output_data = [
                'rolls' => !$rolls ? [] : $rolls,
                'box' => !$request->identifier ? null : $request->identifier,
                'batch_no' => !$batch_no ? '' : $batch_no,
            ];
            $log = new Logs([
                'type' => 'Forge_Remove_Booking',
                'title' => "Booking Removed of box {$request->identifier}",
                'description' => $api_output_header['message']
            ]);

            return Utility::api_output($api_output_header, $api_output_data);
        } catch (Exception $exception) {

        }
    }

    public function summery_of_store(){
        try{
            $data = [];
            $api_output_header = [
                'status' => 200,
                'success' => true,
                'message' => '',
                'description' => '',
            ];
            $data['total_box'] = Box::where('id','>', 1)->count();
            $data['total_booked_batch'] = Box::where('batch_barcode','!=', '')->count();
            $data['total_ready_batch'] = Box::where('batch_rolls','<=', 'number_of_items')
                                            ->where('id','>', 1)
                                            ->count();
            $data['total_running_batch'] = Box::where('batch_rolls','>', 'number_of_items')
                                                ->where('id','>', 1)
                                                ->where('number_of_items','>=', 1)
                                                ->count();
            $data['total_empy_batch'] = $data['total_booked_batch'] - ($data['total_ready_batch'] + $data['total_running_batch']);

            $data['total_empty_box'] = Box::where('batch_rolls', 0)
                                                ->where('id','>', 1)
                                                ->count();

            if (!empty($data)) {
                $api_output_header['status'] = 200;
                $api_output_header['success'] = true;
                $api_output_header['message'] = "Grey Store Summery";
            }
            else {
                $api_output_header['status'] = 400;
                $api_output_header['success'] = false;
                $api_output_header['message'] = "No Data Found!";
            }

            return Utility::api_output($api_output_header, $data);
        }
        catch(Exception $exception){
            return $exception;
        }
    }
}
