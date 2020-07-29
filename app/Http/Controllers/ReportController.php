<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;

use App\LocatedItem;
use App\libraries\Utility;
use App\BookedLocation;
use App\Delivery;

class ReportController extends Controller
{

    public function batch_wise_report()
    {

        try {

            $data = LocatedItem::groupBy('batch_no')
                ->selectRaw("
                    batch_no, 
                    ROUND(SUM(`located_items`.`weight`), 2) as gs_total_weight,
                    COUNT(`located_items`.`item`) as gs_total_items,
                
                    ROUND(SUM(if(`located_items`.`is_delivered`=0, `located_items`.`weight`,0)), 2) as gs_total_stock_weight,
                    COUNT(if(`located_items`.`is_delivered`=0, 1,0)) as gs_total_stock_items,
                
                    SUM(IF(`located_items`.`is_delivered`=1, `located_items`.`weight`,0)) as gs_total_delivered_weight
                   -- COUNT(if(`located_items`.`is_delivered`=1, 1,0)) as gs_total_delivered_items
                ")
                // ->limit(500)
                ->orderBy('id', 'DESC')
                ->paginate(100);
                // ->get();
            // $delivered_batch = Delivery::where('batch_barcode', '10954')->get();
            // $booked_location = BookedLocation::where('batch_barcode', '10010')->first();


            foreach ($data as $key => $item) {
                $item_detail = LocatedItem::select('item_detail')->where('batch_no', $item->batch_no)->first();
                $booked_location = BookedLocation::where('batch_barcode', $item->batch_no)->first();
                $delivered_batch = Delivery::where('batch_barcode', $item->batch_no)->first();
                if (isset($item_detail)) {
                    $item->item_detail = $item_detail['item_detail'];
                } else {
                    $item->item_detail = "";
                }
                if (isset($booked_location)) {
                    $item->batch_total_weight = $booked_location->batch_weight;
                    $item->batch_total_items = $booked_location->number_of_items;
                    $item->batch_detail = $booked_location->batch_detail;
                    $item->location = $booked_location->box_barcode;
                } else {
                    $item->batch_total_weight = 0;
                    $item->batch_total_items = 0;
                    $item->batch_detail = "";
                    $item->location = "";
                }

                if (isset($delivered_batch)) {
                    $item->delivered_batch = $delivered_batch->batch_barcode;
                    $item->delivery_confirmed = $delivered_batch->delivery_confirmed ? true : false;
                } else {
                    $item->delivered_batch = "";
                    $item->delivery_confirmed = false;
                }
            }
            $output_header = [
                'status' => 200,
                'success' => true,
                'message' => 'Stock Report Found',
                'description' => ''
            ];

            return Utility::api_output($output_header, $data);
        } catch (Exception $e) {
            return response()->json($e->getMessage());


            $api_header['status'] = 403;
            $api_header['success'] = false;
            $api_header['message'] = $e->getMessage();
            return Utility::api_output($output_header);
        }
    }



    public function batch_wise_report_copy()
    {

        try {

            $data = LocatedItem::groupBy('batch_no')
                ->selectRaw("
                    batch_no, 
                    ROUND(SUM(`located_items`.`weight`), 2) as gs_total_weight,
                    COUNT(`located_items`.`item`) as gs_total_items,
                
                    ROUND(SUM(if(`located_items`.`is_delivered`=0, `located_items`.`weight`,0)), 2) as gs_total_stock_weight,
                    COUNT(if(`located_items`.`is_delivered`=0, 1,0)) as gs_total_stock_items,
                
                    SUM(IF(`located_items`.`is_delivered`=1, `located_items`.`weight`,0)) as gs_total_delivered_weight
                   -- COUNT(if(`located_items`.`is_delivered`=1, 1,0)) as gs_total_delivered_items
                ")
                // ->limit(1000)
                ->orderBy('id', 'DESC')
                ->get();

            // $delivered_batch = Delivery::where('batch_barcode', '10954')->get();
            // $booked_location = BookedLocation::where('batch_barcode', '10010')->first();


            $index = 0;
            foreach ($data as $key => $item) {
                $item_detail = LocatedItem::select('item_detail')->where('batch_no', $item->batch_no)->first();
                $booked_location = BookedLocation::where('batch_barcode', $item->batch_no)->first();

                if (isset($item_detail)) {
                    $item->item_detail = $item_detail['item_detail'];
                } else {
                    $item->item_detail = "";
                }
                if (isset($booked_location)) {
                    $item->batch_total_weight = $booked_location->batch_weight;
                    $item->batch_total_items = $booked_location->number_of_items;
                    $item->location = $booked_location->box_barcode;
                } else {
                    $item->batch_total_weight = "";
                    $item->batch_total_items = "";
                }

                $delivered_batch = Delivery::where('batch_barcode', $item->batch_no)->first();
                if (isset($delivered_batch)) {
                    if ($key == $index) {
                        unset($data[$index]);
                    }
                }
                $index++;
            }
            $output_header = [
                'status' => 200,
                'success' => true,
                'message' => 'Stock Report Found',
                'description' => ''
            ];

            return Utility::api_output($output_header, $data);
        } catch (Exception $e) {
            return response()->json($e->getMessage());


            $api_header['status'] = 403;
            $api_header['success'] = false;
            $api_header['message'] = $e->getMessage();
            return Utility::api_output($output_header);
        }
    }
}
