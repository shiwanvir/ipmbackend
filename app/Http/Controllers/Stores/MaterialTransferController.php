<?php
namespace App\Http\Controllers\Stores;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Controllers\Controller;

use App\Models\Store\Stock;
use App\Models\stores\TransferLocationUpdate;
use App\models\stores\GatePassHeader;
use App\models\stores\GatePassDetails;
use App\models\store\StockTransaction;

/**
 *
 */
class MaterialTransferController extends Controller
{

  function __construct()
  {
    //add functions names to 'except' paramert to skip authentication
    $this->middleware('jwt.verify', ['except' => ['index']]);
  }


  public function index(Request $request)
  {

    $type = $request->type;

    if($type == 'datatable')   {
      $data = $request->all();
      return response($this->datatable_search($data));
    }
    else if($type == 'auto')    {
      $search = $request->search;
      return response($this->autocomplete_search($search));
    }
    else if($type=='loadDetails'){
      $gatepassNo=$request->gatePassNo;
      return response(['data'=>$this->tabaleLoad($gatepassNo)]);
    }

      else{
      $active = $request->active;
      $fields = $request->fields;
      return response([
        'data' => $this->list($active , $fields)
      ]);
    }
  }



  private function datatable_search($data)
  {


    $start = $data['start'];
    $length = $data['length'];
    $draw = $data['draw'];
    $search = $data['search']['value'];
    $order = $data['order'][0];
    $order_column = $data['columns'][$order['column']]['data'];
    $order_type = $order['dir'];



    $gatePassDetails_list= GatePassHeader::join('org_company as t', 't.company_id', '=', 'store_gate_pass_header.transfer_location')
    ->join('org_company as r', 'r.company_id', '=', 'store_gate_pass_header.receiver_location')
    ->select('store_gate_pass_header.*','t.company_name as loc_transfer','r.company_name as loc_receiver')
    ->where('gate_pass_no','like',$search.'%')
    ->orWhere('r.company_name', 'like', $search.'%')
    ->orWhere('t.company_name', 'like', $search.'%')
    ->orWhere('store_gate_pass_header.created_date', 'like', $search.'%')
    ->orderBy($order_column, $order_type)
    ->offset($start)->limit($length)->get();

     $gatePassDetails_list_count= GatePassHeader::join('org_company as t', 't.company_id', '=', 'store_gate_pass_header.transfer_location')
     ->join('org_company as r', 'r.company_id', '=', 'store_gate_pass_header.receiver_location')
     ->select('store_gate_pass_header.*','t.company_name as loc_transfer','r.company_name as loc_receiver')
     ->where('gate_pass_no','like',$search.'%')
     ->orWhere('r.company_name', 'like', $search.'%')
     ->orWhere('t.company_name', 'like', $search.'%')
     ->orWhere('store_gate_pass_header.created_date', 'like', $search.'%')
    ->count();
    return [
        "draw" => $draw,
        "recordsTotal" =>  $gatePassDetails_list_count,
        "recordsFiltered" => $gatePassDetails_list_count,
        "data" =>$gatePassDetails_list
    ];


  }

  private function tabaleLoad($gatepassNo){

    //$user = auth()->user();
    //$user_location=$user->location;



    $details=GatePassHeader::join('store_gate_pass_details','store_gate_pass_header.gate_pass_id','=','store_gate_pass_details.gate_pass_id')
                    ->join('merc_customer_order_details','store_gate_pass_details.customer_po_id','=','merc_customer_order_details.order_id')
                    ->join('merc_customer_order_header','merc_customer_order_details.order_id','=','merc_customer_order_header.order_id')
                    ->join('style_creation','store_gate_pass_details.style_id','=','style_creation.style_id')
                    ->join('item_master','item_master.master_id','=','store_gate_pass_details.item_id')
                    ->join('org_color','org_color.color_id','=','store_gate_pass_details.color_id')
                    ->join('org_size','org_size.size_id','=','store_gate_pass_details.size_id')
                    ->join('org_store_bin','org_store_bin.store_bin_id','=','store_gate_pass_details.bin_id')
                    ->join('org_uom','org_uom.uom_id','=','store_gate_pass_details.uom_id')
                    ->join('store_stock','store_stock.customer_po_id','=','store_gate_pass_details.customer_po_id')
                    ->select('item_master.master_code','item_master.master_description','style_creation.style_no','store_gate_pass_details.trns_qty','merc_customer_order_header.order_code','org_color.color_name','org_size.size_name','org_store_bin.store_bin_name','org_uom.uom_code','store_stock.total_qty','store_stock.id')
                    //->select('*')
                    ->where('store_gate_pass_header.gate_pass_no','=',$gatepassNo)
                    //->where('store_stock.status','=',1)
                    //echo $details->toSql();
                    ->get();
                    return $details;
                    //$this->setStatuszero($details);


  }





}
