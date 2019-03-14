<?php

namespace App\Http\Controllers\Org\Cancellation;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Controllers\Controller;
use App\Models\Org\Cancellation\CancellationCategory;
use Exception;

class CancellationCategoryController extends Controller
{
    public function __construct()
    {
      //add functions names to 'except' paramert to skip authentication
      $this->middleware('jwt.verify', ['except' => ['index']]);
    }

    //get CancellationCategory list
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


    //create a Cancellation Category
    public function store(Request $request)
    {
      $category = new CancellationCategory();
      if($category->validate($request->all()))
      {
        $category->fill($request->all());
        $category->status = 1;
        $category->save();

        return response([ 'data' => [
          'message' => 'Cancellation category was saved successfully',
          'cancellationCategory' => $category
          ]
        ], Response::HTTP_CREATED );
      }
      else
      {
          $errors = $category->errors();// failure, get errors
          return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //get a CancellationCategory
    public function show($id)
    {
      $category = CancellationCategory::find($id);
      if($category == null)
        throw new ModelNotFoundException("Requested Cancellation category not found", 1);
      else
        return response([ 'data' => $category ]);
    }


    //update a Cancellation Category
    public function update(Request $request, $id)
    {
      $category = CancellationCategory::find($id);
      if($category->validate($request->all()))
      {
        $category->fill($request->except('category_code'));
        $category->save();

        return response([ 'data' => [
          'message' => 'Cancellation category was updated successfully',
          'cancellationCategory' => $category
        ]]);
      }
      else
      {
        $errors = $category->errors();// failure, get errors
        return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //deactivate a Cancellation Category
    public function destroy($id)
    {
      $category = CancellationCategory::where('category_id', $id)->update(['status' => 0]);
      return response([
        'data' => [
          'message' => 'Cancellation category was deactivated successfully.',
          'cancellationCategory' => $category
        ]
      ] , Response::HTTP_NO_CONTENT);
    }


    //validate anything based on requirements
    public function validate_data(Request $request){
      $for = $request->for;
      if($for == 'duplicate')
      {
        return response($this->validate_duplicate_code($request->category_id , $request->category_code));
      }
    }


    //check Cancellation Category code already exists
    private function validate_duplicate_code($id , $code)
    {
      $category = CancellationCategory::where('category_code','=',$code)->first();
      if($category == null){
        return ['status' => 'success'];
      }
      else if($category->category_id == $id){
        return ['status' => 'success'];
      }
      else {
        return ['status' => 'error','message' => 'Cancellation category code already exists'];
      }
    }


    //get filtered fields only
    private function list($active = 0 , $fields = null)
    {
      $query = null;
      if($fields == null || $fields == '') {
        $query = CancellationCategory::select('*');
      }
      else{
        $fields = explode(',', $fields);
        $query = CancellationCategory::select($fields);
        if($active != null && $active != ''){
          $query->where([['status', '=', $active]]);
        }
      }
      return $query->get();
    }

    //search Cancellation Category for autocomplete
    private function autocomplete_search($search)
  	{
  		$category_lists = CancellationCategory::select('category_id','category_description')
  		->where([['category_description', 'like', '%' . $search . '%'],]) ->get();
  		return $category_lists;
  	}


    //get searched Cancellation Categorys for datatable plugin format
    private function datatable_search($data)
    {
      $start = $data['start'];
      $length = $data['length'];
      $draw = $data['draw'];
      $search = $data['search']['value'];
      $order = $data['order'][0];
      $order_column = $data['columns'][$order['column']]['data'];
      $order_type = $order['dir'];

      $category_list = CancellationCategory::select('*')
      ->where('category_code'  , 'like', $search.'%' )
      ->orWhere('category_description','like',$search.'%')
      ->orderBy($order_column, $order_type)
      ->offset($start)->limit($length)->get();

      $category_count = CancellationCategory::where('category_code'  , 'like', $search.'%' )
      ->orWhere('category_description','like',$search.'%')
      ->count();

      return [
          "draw" => $draw,
          "recordsTotal" => $category_count,
          "recordsFiltered" => $category_count,
          "data" => $category_list
      ];
    }

}
