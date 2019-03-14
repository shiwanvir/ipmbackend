<?php

namespace App\Http\Controllers\Org;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Controllers\Controller;
use App\Models\Org\Designation;
use Exception;

class DesignationController extends Controller
{
    public function __construct()
    {
      //add functions names to 'except' paramert to skip authentication
      $this->middleware('jwt.verify', ['except' => ['index']]);
    }

    //get Department list
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
      else {
        $active = $request->active;
        $fields = $request->fields;
        return response([
          'data' => $this->list($active , $fields)
        ]);
      }
    }


    //create a Department
    public function store(Request $request)
    {
      $designation = new Designation();
      if($designation->validate($request->all()))
      {
        $designation->fill($request->all());
        $designation->status = 1;
        $designation->save();

        return response([ 'data' => [
          'message' => 'Department was saved successfully',
          'designation' => $designation
          ]
        ], Response::HTTP_CREATED );
      }
      else
      {
          $errors = $designation->errors();// failure, get errors
          return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //get a Department
    public function show($id)
    {
    // $error = 'Always throw this error';
    //  throw new Exception($error);
      $designation = Designation::find($id);
      if($designation == null)
        throw new ModelNotFoundException("Requested Designation not found", 1);
      else
        return response([ 'data' => $designation ]);
    }


    //update a Designation
    public function update(Request $request, $id)
    {
      $designation = Designation::find($id);
      if($designation->validate($request->all()))
      {
        $designation->fill($request->except('des_code'));
        $designation->save();

        return response([ 'data' => [
          'message' => 'Designation was updated successfully',
          'designation' => $designation
        ]]);
      }
      else
      {
        $errors = $designation->errors();// failure, get errors
        return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //deactivate a Designation
    public function destroy($id)
    {
      $designation = Designation::where('des_id', $id)->update(['status' => 0]);
      return response([
        'data' => [
          'message' => 'Designation was deactivated successfully.',
          'department' => $designation
        ]
      ] , Response::HTTP_NO_CONTENT);
    }


    //validate anything based on requirements
    public function validate_data(Request $request){
      $for = $request->for;
      if($for == 'duplicate')
      {
        return response($this->validate_duplicate_code($request->des_id , $request->des_code));
      }
    }


    //check Designation code already exists
    private function validate_duplicate_code($id , $code)
    {
      $designation = Designation::where('des_code','=',$code)->first();
      if($designation == null){
        return ['status' => 'success'];
      }
      else if($designation->des_id == $id){
        return ['status' => 'success'];
      }
      else {
        return ['status' => 'error','message' => 'Designation code already exists'];
      }
    }


    //get filtered fields only
    private function list($active = 0 , $fields = null)
    {
      $query = null;
      if($fields == null || $fields == '') {
        $query = Designation::select('*');
      }
      else{
        $fields = explode(',', $fields);
        $query = Designation::select($fields);
        if($active != null && $active != ''){
          $query->where([['status', '=', $active]]);
        }
      }
      return $query->get();
    }

    //search Designation for autocomplete
    private function autocomplete_search($search)
  	{
  		$designation_lists = Designation::select('des_id','des_name')
  		->where([['des_name', 'like', '%' . $search . '%'],]) ->get();
  		return $designation_lists;
  	}


    //get searched Designations for datatable plugin format
    private function datatable_search($data)
    {
      $start = $data['start'];
      $length = $data['length'];
      $draw = $data['draw'];
      $search = $data['search']['value'];
      $order = $data['order'][0];
      $order_column = $data['columns'][$order['column']]['data'];
      $order_type = $order['dir'];

      $des_list = Designation::select('*')
      ->where('des_code'  , 'like', $search.'%' )
      ->orWhere('des_name','like',$search.'%')
      ->orderBy($order_column, $order_type)
      ->offset($start)->limit($length)->get();

      $des_count = Designation::where('des_code'  , 'like', $search.'%' )
      ->orWhere('des_name','like',$search.'%')
      ->count();

      return [
          "draw" => $draw,
          "recordsTotal" => $des_count,
          "recordsFiltered" => $des_count,
          "data" => $des_list
      ];
    }

}
