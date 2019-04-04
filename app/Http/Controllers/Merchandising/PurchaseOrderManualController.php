<?php

namespace App\Http\Controllers\Merchandising;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Merchandising\PoOrderHeader;
//use App\Libraries\UniqueIdGenerator;
use App\Models\Merchandising\bom_details;
use App\Models\Merchandising\PurchaseReqLines;

class PurchaseOrderManualController extends Controller
{
    public function __construct()
    {
      //add functions names to 'except' paramert to skip authentication
      $this->middleware('jwt.verify', ['except' => ['index']]);
    }

    //get customer list
    public function index(Request $request)
    {
      //$id_generator = new UniqueIdGenerator();
      //echo $id_generator->generateCustomerOrderId('CUSTOMER_ORDER' , 1);
      //echo UniqueIdGenerator::generateUniqueId('CUSTOMER_ORDER' , 2 , 'FDN');
      $type = $request->type;
      if($type == 'datatable') {
        $data = $request->all();
        return response($this->datatable_search($data));
      }
      else if($type == 'auto')    {
        $search = $request->search;
        return response($this->autocomplete_search($search));
      }
      else if($type == 'style')    {
        $search = $request->search;
        return response($this->style_search($search));
      }
      else{
        return response([]);
      }
    }


    //create a customer
    public function store(Request $request)
    {
      $order = new PoOrderHeader();
      if($order->validate($request->all()))
      {
        $order->fill($request->all());
        $order->po_status = 'PLANNED';
        $order->save();

        return response([ 'data' => [
          'message' => 'Purchase order was saved successfully',
          'savepo' => $order,
          'status' => 'PLANNED'
          ]
        ], Response::HTTP_CREATED );
      }
      else
      {
          $errors = $order->errors();// failure, get errors
          return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //get a customer
    public function show($id)
    {
      $customer = PoOrderHeader::with(['currency','location','supplier'])->find($id);
      if($customer == null)
        throw new ModelNotFoundException("Requested PO not found", 1);
      else
        return response([ 'data' => $customer ]);
    }


    //update a customer
   public function update(Request $request, $id)
    {
      $pOrder = PoOrderHeader::find($id);
      if($pOrder->validate($request->all()))
      {
        $pOrder->fill($request->except('customer_code'));
        $pOrder->save();

        return response([ 'data' => [
          'message' => 'Purchase order was updated successfully',
          'customer' => $pOrder,
          'savepo' => $pOrder
        ]]);
      }
      else
      {
        $errors = $pOrder->errors();// failure, get errors
        return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //deactivate a customer
    public function destroy($id)
    {
      /*$customer = Customer::where('customer_id', $id)->update(['status' => 0]);
      return response([
        'data' => [
          'message' => 'Customer was deactivated successfully.',
          'customer' => $customer
        ]
      ] , Response::HTTP_NO_CONTENT);*/
    }


    //validate anything based on requirements
    public function validate_data(Request $request){
      /*$for = $request->for;
      if($for == 'duplicate')
      {
        return response($this->validate_duplicate_code($request->customer_id , $request->customer_code));
      }*/
    }


    public function customer_divisions(Request $request) {
        /*$type = $request->type;
        $customer_id = $request->customer_id;

        if($type == 'selected')
        {
          $selected = Division::select('division_id','division_description')
          ->whereIn('division_id' , function($selected) use ($customer_id){
              $selected->select('division_id')
              ->from('org_customer_divisions')
              ->where('customer_id', $customer_id);
          })->get();
          return response([ 'data' => $selected]);
        }
        else
        {
          $notSelected = Division::select('division_id','division_description')
          ->whereNotIn('division_id' , function($notSelected) use ($customer_id){
              $notSelected->select('division_id')
              ->from('org_customer_divisions')
              ->where('customer_id', $customer_id);
          })->get();
          return response([ 'data' => $notSelected]);
        }*/

    }

    public function save_customer_divisions(Request $request)
    {
      /*$customer_id = $request->get('customer_id');
      $divisions = $request->get('divisions');
      if($customer_id != '')
      {
        DB::table('org_customer_divisions')->where('customer_id', '=', $customer_id)->delete();
        $customer = Customer::find($customer_id);
        $save_divisions = array();

        foreach($divisions as $devision)		{
          array_push($save_divisions,Division::find($devision['division_id']));
        }

        $customer->divisions()->saveMany($save_divisions);
        return response([
          'data' => [
            'customer_id' => $customer_id
          ]
        ]);
      }
      else {
        throw new ModelNotFoundException("Requested customer not found", 1);
      }*/
    }


    //check customer code already exists
    private function validate_duplicate_code($id , $code)
    {
      /*$customer = Customer::where('customer_code','=',$code)->first();
      if($customer == null){
        return ['status' => 'success'];
      }
      else if($customer->customer_id == $id){
        return ['status' => 'success'];
      }
      else {
        return ['status' => 'error','message' => 'Customer code already exists'];
      }*/
    }


    //search customer for autocomplete
    private function autocomplete_search($search)
  	{
  		/*$customer_lists = Customer::select('customer_id','customer_name')
  		->where([['customer_name', 'like', '%' . $search . '%'],]) ->get();
  		return $customer_lists;*/
  	}


    //search customer for autocomplete
    private function style_search($search)
  	{
  	/*	$style_lists = StyleCreation::select('style_id','style_no','customer_id')
  		->where([['style_no', 'like', '%' . $search . '%'],]) ->get();
  		return $style_lists;*/
  	}


    //get searched customers for datatable plugin format
    private function datatable_search($data)
    {
      $start = $data['start'];
      $length = $data['length'];
      $draw = $data['draw'];
      $search = $data['search']['value'];
      $order = $data['order'][0];
      $order_column = $data['columns'][$order['column']]['data'];
      $order_type = $order['dir'];

      $customer_list = PoOrderHeader::join('org_location', 'org_location.loc_id', '=', 'merc_po_order_header.po_deli_loc')
	  ->join('org_supplier', 'org_supplier.supplier_id', '=', 'merc_po_order_header.po_sup_code')
      ->join('fin_currency', 'fin_currency.currency_id', '=', 'merc_po_order_header.po_def_cur')
	  ->select('merc_po_order_header.*','org_location.loc_name','org_supplier.supplier_name',
          'fin_currency.currency_code')
      ->where('po_number'  , 'like', $search.'%' )
      ->orWhere('supplier_name'  , 'like', $search.'%' )

	  ->orWhere('loc_name'  , 'like', $search.'%' )
      ->orderBy($order_column, $order_type)
      ->offset($start)->limit($length)->get();

      $customer_count = PoOrderHeader::join('org_location', 'org_location.loc_id', '=', 'merc_po_order_header.po_deli_loc')
	  ->join('org_supplier', 'org_supplier.supplier_id', '=', 'merc_po_order_header.po_sup_code')
      ->join('fin_currency', 'fin_currency.currency_id', '=', 'merc_po_order_header.po_def_cur')
      ->where('po_number'  , 'like', $search.'%' )
      ->orWhere('supplier_name'  , 'like', $search.'%' )

	  ->orWhere('loc_name'  , 'like', $search.'%' )
      ->count();

      return [
          "draw" => $draw,
          "recordsTotal" => $customer_count,
          "recordsFiltered" => $customer_count,
          "data" => $customer_list
      ];
    }


    public function load_bom_Details(Request $request)
  	{
    //$customer_name = $data['customer_name'];
    $customer_name = $request->customer['customer_name'];
    //print_r($customer_name);

    $load_list = DB::select("select B.*, MCD.*, OU.uom_code, OS.size_name, OC.color_name, IM.master_description,
        SU.supplier_name, CUS.customer_name,CUS.customer_code,
        ( select Sum(MPD.req_qty) AS req_qty FROM merc_po_order_details AS MPD WHERE MPD.sc_no =  B.bom_id ) AS req_qty,
        org_location.loc_name,
        (SELECT GROUP_CONCAT(DISTINCT MPOD.po_no SEPARATOR ' | ') AS po_nos FROM
        merc_po_order_details AS MPOD WHERE MPOD.sc_no = B.bom_id ) AS po_nos
        FROM
        bom_details AS B
        INNER JOIN merc_costing_so_combine AS MC ON B.combine_id = MC.comb_id
        INNER JOIN merc_customer_order_details AS MCD ON MC.details_id = MCD.details_id
        INNER JOIN merc_customer_order_header AS MCH ON MCH.order_id = MCD.order_id
        INNER JOIN org_uom AS OU ON B.uom_id = OU.uom_id
        INNER JOIN org_size AS OS ON B.item_size = OS.size_id
        INNER JOIN org_color AS OC ON B.item_color = OC.color_id
        INNER JOIN item_master AS IM ON B.master_id = IM.master_id
        INNER JOIN org_supplier AS SU ON B.supplier_id = SU.supplier_id
        INNER JOIN cust_customer AS CUS ON MCH.order_customer = CUS.customer_id
        INNER JOIN org_location ON MCD.projection_location = org_location.loc_id
        WHERE
        CUS.customer_name LIKE '%$customer_name%' GROUP BY B.bom_id ");

       //return $customer_list;
       return response([ 'data' => [
         'load_list' => $load_list,
         'count' => sizeof($load_list)
         ]
       ], Response::HTTP_CREATED );

  	}


    public function merge_save(Request $request){
      $lines = $request->lines;
    //  print_r($lines[0]['bom_id']);
      if($lines != null && sizeof($lines) >= 1){

        $max_no = PurchaseReqLines::max('merge_no');
        $max_no = $max_no + 1;

        for($x = 0 ; $x < sizeof($lines) ; $x++){
        $temp_line = new PurchaseReqLines();

        $temp_line->bom_id = $lines[$x]['bom_id'];
        $temp_line->order_id = $lines[$x]['order_id'];
        $temp_line->cpo_no = $lines[$x]['po_no'];
        $temp_line->merge_no = $max_no;
        $temp_line->item_code = $lines[$x]['master_id'];
        $temp_line->item_desc = $lines[$x]['master_description'];
        $temp_line->item_color = $lines[$x]['item_color'];
        $temp_line->color_name = $lines[$x]['color_name'];
        $temp_line->item_size = $lines[$x]['item_size'];
        $temp_line->size_name = $lines[$x]['size_name'];
        $temp_line->uom_id = $lines[$x]['uom_id'];
        $temp_line->uom_code = $lines[$x]['uom_code'];
        $temp_line->supplier_id = $lines[$x]['supplier_id'];
        $temp_line->supplier_name = $lines[$x]['supplier_name'];
        $temp_line->unit_price = $lines[$x]['unit_price'];
        $temp_line->total_qty = $lines[$x]['total_qty'];
        $temp_line->moq = '0';
        $temp_line->mcq = '0';
        $temp_line->bal_order = $lines[$x]['bal_oder'];;
        $temp_line->po_qty = $lines[$x]['req_qty'];;
        $temp_line->status = '';

        $temp_line->save();

        }

        return response([
          'data' => [
            'status' => 'success',
            'message' => 'Lines were merged successfully.',
            'merge_no' => $max_no
          ]
        ] , 200);

      }

    }


    public function load_reqline(Request $request)
  	{
        $prl_id = $request->prl_id;

      $load_list = PurchaseReqLines::join('bom_details', 'bom_details.bom_id', '=', 'merc_purchase_req_lines.bom_id')
       ->join('item_master', 'item_master.master_id', '=', 'bom_details.master_id')
	     ->join('item_subcategory', 'item_subcategory.subcategory_id', '=', 'item_master.subcategory_id')
       ->join('item_category', 'item_category.category_id', '=', 'item_subcategory.category_id')
       ->join('org_uom', 'org_uom.uom_id', '=', 'merc_purchase_req_lines.uom_id')
       ->join('org_size', 'org_size.size_id', '=', 'merc_purchase_req_lines.item_size')
       ->join('org_color', 'org_color.color_id', '=', 'merc_purchase_req_lines.item_color')
	     ->select('item_category.*','item_master.*','org_uom.*','bom_details.*','org_color.*','org_size.*','merc_purchase_req_lines.*')
       ->where('merge_no'  , '=', $prl_id )
      // ->orWhere('supplier_name'  , 'like', $search.'%' )
	    // ->orWhere('loc_name'  , 'like', $search.'%' )
       //->groupBy('merc_costing_so_combine.comb_id')
       //->distinct()
       ->get();

       //return $customer_list;
       return response([ 'data' => [
         'load_list' => $load_list,
         'prl_id' => $prl_id,
         'count' => sizeof($load_list)
         ]
       ], Response::HTTP_CREATED );

  	}





}
