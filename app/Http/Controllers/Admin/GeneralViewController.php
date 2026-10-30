<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;
use Intervention\Image\Facades\Image;
use File;
use Carbon\Carbon;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isEmpty;

class GeneralViewController extends Controller
{

    public function __construct()
    {
        // Route inspection/serialization runs controllers without an active
        // request route. Keep construction safe in CLI contexts.
        $this->pageType = request()->route()?->parameter('pageType');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!$this->pageType) {
            session()->flash('message', "Does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }

        $mName = ucfirst($this->pageType);

        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        //dd($module);
        if (!$module) {
            session()->flash('message', "$mName not found.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));
        $column = $model_name->column();
        $data["page_title"] = $mName;
        $data["page_sub_title"] = "All " . $mName;
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => $data["page_title"],
                "url" => null
            ],
        ];

        if (method_exists($model_name, 'tableFilter')) {
            $data["filterTable"] =  $model_name->tableFilter();
        }

        if (method_exists($model_name, 'bulkUpdateAction')) {
            $data["bulkUpdateAction"] =  $model_name->bulkUpdateAction();
        }


        $data["ajaxUrl"] = route('admin.ajaxGetItem', $this->pageType);
        $data["createUrl"] = route('admin.AddNewItem', $this->pageType);
        $data["bulkDeleteUrl"] = route('admin.BulkDeleteItem', $this->pageType);
        $data["canCreate"] = Gate::allows('create_' . $this->pageType);
        $data["pageType"] = $this->pageType;
        $data["column"] =  $column;


        //add activity
        $ac['activity'] = "Visited $mName - ".$data["page_sub_title"]." page";
        $ac['actions'] =  "visit";
        $ac['module'] = $mName;
        $ac['user_type'] = "2";
        //addSiteActitvity(auth()->guard('admin')->user(), $ac);



        return view("admin.dashboard.item.index", $data);
    }


    public function ajax(Request $request)
    {
        $pageType = $this->pageType;
        $mName = ucfirst($pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        if (!$module) {
            return;
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));
        $model = $model_name::query()->orderBy("id","desc");

        //filter
        $filter = request()->input('filter');
        if (method_exists($model_name, 'filterMethod') && $filter!= "") {
            $model =   $model_name->filterMethod($model, $filter);
        }


        //ismart search
        $ismartSearch = request()->input('ismartSearch');
        if(method_exists($model_name, 'searchMethod') && $ismartSearch!=""){
            $model =   $model_name->searchMethod($model,$ismartSearch);
        }


        $dataTble = DataTables::eloquent($model);
        $columns = $model_name->column();
        foreach ($columns as $column) {

            $dataTble
                ->editColumn($column->column_name, function ($item) use ($column, $pageType,$model_name) {

                    if(method_exists($model_name, 'modifyColumnMethod')){
                        $item["$column->column_name"] =   $model_name->modifyColumnMethod($item,$column->column_name, $item[$column->column_name]);
                    }


                    if($column->column_name == "amount" && $item["amount"] != null){
                        $item["amount"] = getPrice($item["amount"]);
                    }else if($column->column_name == "charge" && $item["charge"] != null){
                        $item["charge"] = getPrice($item["charge"]);
                    }
                    else if($column->column_name == "balance" && $item["balance"] != null){
                        $item["balance"] = getPrice($item["balance"]);
                    }
                    return view('admin.form._columnView', compact('item', 'column', 'pageType'));
                });
        }

        if (method_exists($model_name, 'actionView')) {
            $actionView  =   $model_name->actionView();
            $dataTble->addColumn('action', function ($item) use ($pageType, $actionView) {
                return view($actionView, compact('item', 'pageType'));
            });
        }else{
            $dataTble->addColumn('action', function ($item) use ($pageType) {
                return view('admin.dashboard.item._action', compact('item', 'pageType'));
            });
        }


        $dataTble->addColumn('bulk_checkbox', function ($item) {
            return view('admin.layouts._bulk_checkbox', compact('item'));
        });





        return $dataTble->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($page = null, $id = null)
    {
        if (!$this->pageType) {
            session()->flash('message', "Does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }

        $mName = ucfirst($this->pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        //dd($module);
        if (!$module) {
            session()->flash('message', "$mName not found.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));
        $column = $model_name->column();

        if($id != null){
            $item =  $model_name->where("id", $id)->first();
            if (!$item) {
                session()->flash('message', "Item not exists.$id");
                Session::flash('type', 'error');
                Session::flash('title', 'Error');
                return redirect()->route("admin.index");
            }
            $data["data"] = $item;
            $data["copy"] = true;
        }

        $data["page_title"] = $mName;
        $data["page_sub_title"] = "Add New $mName";
        $data["form_url"] = route('admin.AddNewItem', $this->pageType);
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => $mName,
                "url" => route('admin.ViewItem', $this->pageType)
            ],
            [
                "title" => "Add New $mName",
                "url" => null
            ]
        ];
        $data["column"] = $column;

        //add activity
        $ac['activity'] = "Visited $mName create page";
        $ac['actions'] =  "visit";
        $ac['module'] = $mName;
        $ac['user_type'] = "2";
        //addSiteActitvity(auth()->guard('admin')->user(), $ac);



        if (method_exists($model_name, 'createMethod')) {
            $data=  array_merge($data,$model_name->createMethod());
          return view($data['view'], $data);
        }else{
            return view('admin.form.index', $data);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$this->pageType) {
            session()->flash('message', "Does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $pageType  = $this->pageType;
        $mName = ucfirst($this->pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        //dd($module);
        if (!$module) {
            session()->flash('message', "$mName not found.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));

        $this->validate($request, $model_name->rules());

        $data = request()->except('_method', '_token');
        
        


        //check for field with image
        foreach ($model_name->column() as $column) {

            if ($column->input_type == "7") {
                
                 
               
                 
                 
                if (request($column->column_name)) {
                    $imagePath = request($column->column_name)->store(strtolower($pageType), 'public');
                    if (isset($column->image_pros) &&  $column->image_pros != null  && $column->image_pros != [] && $column->image_pros->resize) {
                        if ($column->image_pros->mode == "fit") {
                            //fit
                            $image = Image::make(public_path("storage/{$imagePath}"))->fit($column->image_pros->width, $column->image_pros->height)
                                ->brightness(10) // increase brightness of image
                                ->sharpen(15); // Also sharpen the image
                        } else {
                            //resize
                            $image = Image::make(public_path("storage/{$imagePath}"))->resize(
                                $column->image_pros->width,
                                $column->image_pros->height,
                                function ($constraint) {
                                    $constraint->aspectRatio();
                                }
                            )->brightness(10) // increase brightness of image
                                ->sharpen(15);
                        }
                    } else {
                        $image = Image::make(public_path("storage/{$imagePath}"))
                            ->brightness(10) // increase brightness of image
                            ->sharpen(15); // Also sharpen the image
                    }

                    $image->save();
                    $data[$column->column_name] = $imagePath;
                }
            }

            //check if addMore Column
            elseif(isset($column->addMore)){
               //check if the column is requested
               if(request($column->column_name) && request($column->column_name)[0] != null){
                //check if its has a value
            if($column->addMore->hasValue){
                $addMoreColumn =  request($column->column_name);
                $addMoreValue = request($column->column_name."_value");
                $addMoreData = array_combine($addMoreColumn, $addMoreValue);

                if($column->addMore->toJson){
                    $addMoreData = json_encode($addMoreData);
                }

                $data[$column->column_name] = $addMoreData;
            }else{
                $addMoreData = request($column->column_name);
                if($column->addMore->toJson){
                    $addMoreData = json_encode($addMoreData);
                }
                $data[$column->column_name] = $addMoreData;
            }
            }else{
                unset($data[$column->column_name]);
            }
                unset($data[$column->column_name."_value"]);
            }

        }

        if (method_exists($model_name, 'insertMethod')) {
            $model_name->insertMethod($data);
        }else{
            $model_name->create($data);

            //add activity
            $ac['activity'] = "Create new ".$mName;
            $ac['actions'] =  "create";
            $ac['module'] = "Admin";
            $ac['user_type'] = "2";
            $ac["extra"] = json_encode(["request" => $data]);
            //addSiteActitvity(auth()->guard('admin')->user(), $ac);
        }



        session()->flash('message', "New $mName Created Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route('admin.ViewItem', $this->pageType);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $id = request()->route()->parameter('id');
        if (!$this->pageType) {
            session()->flash('message', "Does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $pageType  = $this->pageType;
        $mName = ucfirst($pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        //dd($module);
        if (!$module) {
            session()->flash('message', "$mName not found.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));

        $item =  $model_name->where("id", $id)->first();
        if (!$item) {
            session()->flash('message', "Item not exists.$id");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }

        $data["page_title"] = $mName;
        $data["page_sub_title"] = "Edit $mName";
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => "$mName",
                "url" => route('admin.ViewItem', $this->pageType)
            ],
            [
                "title" => "Edit $mName",
                "url" => null
            ]
        ];



        $data["data"] = $item;
        $data["form_url"] = route('admin.UpdateItem', [$this->pageType, $id]);
        $data["column"] = $model_name->column();


        //add activity
        $ac['activity'] = "Visited $mName edit page ";
        $ac['actions'] =  "visit";
        $ac['module'] = $mName;
        $ac['user_type'] = "2";
        $ac["extra"] = json_encode(["id" =>  $id]);
        //addSiteActitvity(auth()->guard('admin')->user(), $ac);


        if (method_exists($model_name, 'editMethod')) {
            $data=  array_merge($data,$model_name->editMethod($id));
          return view($data['view'], $data);
        }else{
            return view('admin.form.index', $data);
        }

    }

    //ajax column update
    public function ajaxUpdate($id, Request $request)
    {
        $id = request()->route()->parameter('id');
        if (!$this->pageType) {
         return response(['success' => false, 'message' => "Does not exists."]);
        }
        $pageType  = $this->pageType;
        $mName = ucfirst($this->pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();

        if (!$module) {
            return response(['success' => false, 'message' => "$mName not found."]);
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));
        $item =  $model_name->where("id", $id)->first();
        $column = request('column');
        $value = request('value');

        //check if module
        if(request("model") && request("model") != null && request("model") != ""){
            $model = request("model");
            $keys = explode('->', $model);
            $item = $item;
            // foreach ($keys as $key) {
            //     $item = $item->$key;
            // }
            $item->{$keys[0]}->{$keys[1]} = $value;
            $item->{$keys[0]}->save();

        }else{
        $item->$column = $value;
        $item->save();
        }


         //add activity
         $ac['activity'] = "Updated $mName ";
         $ac['actions'] =  "update";
         $ac['module'] = $mName;
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" =>  $id, "request" => $request]);
         //addSiteActitvity(auth()->guard('admin')->user(), $ac);


        return response(['success' => true, 'message' => $value]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $id = request()->route()->parameter('id');
        if (!$this->pageType) {
            session()->flash('message', "Does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $pageType  = $this->pageType;
        $mName = ucfirst($this->pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        //dd($module);
        if (!$module) {
            session()->flash('message', "$mName not found.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));

        $item =  $model_name->where("id", $id)->first();
        if (!$item) {
            session()->flash('message', "Item not exists.$id");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }

        $this->validate($request, $model_name->rules($id));

        $data = request()->except('_method', '_token');

        //check for field with image
        foreach ($model_name->column() as $column) {
            
            if ($column->input_type == "7") {
                                 unset($data["existing_" . $column->column_name]);



    if (request()->hasFile($column->column_name)) {
        // 🟢 Case 1: User uploaded new image
        $imagePath = request($column->column_name)->store(strtolower($pageType), 'public');

        if (isset($column->image_pros) && $column->image_pros != null && $column->image_pros != [] && $column->image_pros->resize) {
            if ($column->image_pros->mode == "fit") {
                $image = Image::make(public_path("storage/{$imagePath}"))
                    ->fit($column->image_pros->width, $column->image_pros->height)
                    ->brightness(10)
                    ->sharpen(15);
            } else {
                $image = Image::make(public_path("storage/{$imagePath}"))
                    ->resize(
                        $column->image_pros->width,
                        $column->image_pros->height,
                        function ($constraint) {
                            $constraint->aspectRatio();
                        }
                    )
                    ->brightness(10)
                    ->sharpen(15);
            }
        } else {
            $image = Image::make(public_path("storage/{$imagePath}"))
                ->brightness(10)
                ->sharpen(15);
        }

        $image->save();
        $data[$column->column_name] = $imagePath;
    } else {
        // 🔵 Case 2: No new file uploaded — use existing image
        $data[$column->column_name] = $item->{$column->column_name};
    }
}



            // if ($column->input_type == "7") {
                
                
            //     if (request($column->column_name)) {
                    
                    
            //         $imagePath = request($column->column_name)->store(strtolower($pageType), 'public');
            //         if (isset($column->image_pros) && $column->image_pros != null  && $column->image_pros != [] && $column->image_pros->resize) {
            //             if ($column->image_pros->mode == "fit") {
            //                 //fit
            //                 $image = Image::make(public_path("storage/{$imagePath}"))->fit($column->image_pros->width, $column->image_pros->height)
            //                     ->brightness(10) // increase brightness of image
            //                     ->sharpen(15); // Also sharpen the image
            //             } else {
            //                 //resize
            //                 $image = Image::make(public_path("storage/{$imagePath}"))->resize(
            //                     $column->image_pros->width,
            //                     $column->image_pros->height,
            //                     function ($constraint) {
            //                         $constraint->aspectRatio();
            //                     }
            //                 )->brightness(10) // increase brightness of image
            //                     ->sharpen(15);
            //             }
                        
                        
                        
            //         } else {
            //             $image = Image::make(public_path("storage/{$imagePath}"))
            //                 ->brightness(10) // increase brightness of image
            //                 ->sharpen(15); // Also sharpen the image
            //         }
                    
                    
                    

            //         $image->save();
            //         $data[$column->column_name] = $imagePath;
            //     }
            // }

            // if($column->column_name == "header" ){

            //         dd(request($column->column_name)[0] == null);

            // }


             //check if addMore Column
             elseif(isset($column->addMore)){
                //check if the column is requested
                if(request($column->column_name) && request($column->column_name)[0] != null){
                    //check if its has a value
                if($column->addMore->hasValue){
                    $addMoreColumn =  request($column->column_name);
                    $addMoreValue = request($column->column_name."_value");
                    $addMoreData = array_combine($addMoreColumn, $addMoreValue);



                    if($column->addMore->toJson){
                        foreach ($addMoreData as $key => $value) {
                            if($addMoreData[$key] == "" || $value == null){
                                unset($addMoreData[$key]);
                            }
                        }

                            $addMoreData = json_encode($addMoreData);


                    }

                        $data[$column->column_name] = $addMoreData;


                }else{
                    $addMoreData = request($column->column_name);
                    if($column->addMore->toJson){
                        $addMoreData = json_encode($addMoreData);
                    }
                    $data[$column->column_name] = $addMoreData;
                }
                }else{
                    unset($data[$column->column_name]);
                }

                unset($data[$column->column_name."_value"]);
            }
        }
        if (method_exists($model_name, 'updateMethod')) {
            $model_name->updateMethod($id, $data);
        }else{
            $item->fill($data)->save();
            //add activity
        $ac['activity'] = "Updated $mName ";
        $ac['actions'] =  "update";
        $ac['module'] = $mName;
        $ac['user_type'] = "2";
        $ac["extra"] = json_encode(["id" =>  $id, "request" => $request]);
        //addSiteActitvity(auth()->guard('admin')->user(), $ac);
        }


        session()->flash('message', "$item->name Updated Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');
        ///return redirect()->back();
        return redirect()->route("admin.ViewItem", $pageType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $id = request()->route()->parameter('id');
        if (!$this->pageType) {
            session()->flash('message', "Page Does not exists.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $pageType  = $this->pageType;
        $mName = ucfirst($this->pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        //dd($module);
        if (!$module) {
            session()->flash('message', "$mName not found.");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));

        $item =  $model_name->where("id", $id)->first();
        if (!$item) {
            session()->flash('message', "Item not exists.$id");
            Session::flash('type', 'error');
            Session::flash('title', 'Error');
            return redirect()->route("admin.index");
        }

        $item->delete();

         //add activity
         $ac['activity'] = "Deleted $mName ";
         $ac['actions'] =  "delete";
         $ac['module'] = $mName;
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" =>  $id]);
         //addSiteActitvity(auth()->guard('admin')->user(), $ac);

        session()->flash('message', "$item->name deleted Successfully.");
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return redirect()->route("admin.ViewItem", $pageType);
    }


    public function bulkDestroy(Request $request)
    {
        if (!$this->pageType) {
            return response(['success' => false, 'message' => "Page Does not exists."]);
        }
        $pageType  = $this->pageType;
        $mName = ucfirst($this->pageType);
        $module = Module::where("name", $mName)->where('general_view', "1")->first();
        //dd($module);
        if (!$module) {
            return response(['success' => false, 'message' => "$mName not found."]);
        }
        $model_name = app("App\Models\\" . ucfirst($module->module));

        foreach ($request->data_id as $id) {
        $item =  $model_name->where("id", $id)->first();
        if (!$item) {
            return response(['success' => false, 'message' => "Item not exists."]);
        }

        $item->delete();

         //add activity
         $ac['activity'] = "Deleted $mName ";
         $ac['actions'] =  "delete";
         $ac['module'] = $mName;
         $ac['user_type'] = "2";
         $ac["extra"] = json_encode(["id" =>  $id]);
         //addSiteActitvity(auth()->guard('admin')->user(), $ac);

    }
        return response(['success' => true, 'message' => "$item->name deleted Successfully."]);

    }
}
