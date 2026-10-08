<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\category;
use App\subCategory;
use \auth;

class categoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
    }

    public function create()
    {
        return view('category/create');
    }

    public function store(Request $request)
    {
    
		$request->validate
        ([
            // 'name'=>$request['name1']
            'name' => 'unique:product_category',
        ]);

        if (!isset($errors))
        {
		  category::create
            ([
                'name'=>$request['name']
            ]);
        };

		// return redirect('/category');
        if (!isset($errors)) 
        {
            return redirect('/category')->with('success', 'Category was added successfully.');
        }

        else
        {
            return redirect('/category')->with('danger', 'Error occurred while adding category.');
        }
         
    }

    public function index()
    {
        $category=category::orderBy('name', 'ASC')->get();
        return view('category/index',['category'=>$category] );
    }

    public function view($id)
    {

        $category = category::find($id);
        if ($category) {
            return view('category/view', ['category'=>$category]);
        } else {
            return redirect('/category')->with('danger', 'Category was not found.');
        }
    }

    public function update(Request $request, $id)
    {   
        $category = category::find($id);
        if ($category) {
            $category->name = $request['category_name'];

            if ($category->save()) {
                return redirect('category')->with('success', 'Category was updated successfully.');
            } else {
                return redirect('category')->with('danger', 'Error occurred while saving category.');
            }
        } else {
            return redirect('/category')->with('danger', 'Category was not found.');
        }
    }

    public function delete(Request $request, $id)
    {
        $category = category::where('id', $id)->first();

        if($category->product->count() == 0)
        {
            $category->delete();
            return redirect('/category')->with('success', 'Category deleted successfully.'); 
        }
        else {
                return redirect('/category')->with('danger', 'Category was not deleted.');
            }
    }

    public function subCategoryCreate()
    {
        return view('category/subCategory/create');
    }

    public function subCategoryStore(Request $request)
    {
    
        $request->validate
        ([
            // 'name'=>$request['name1']
            'name' => 'unique:product_subcategory',
        ]);

        if (!isset($errors))
        {
          subCategory::create
            ([
                'name'=>$request['name']
            ]);
        };

        // return redirect('/category');
        if (!isset($errors)) 
        {
            return redirect('/category/subCategory')->with('success', 'Sub-Category was added successfully.');
        }

        else
        {
            return redirect('/category/subCategory')->with('danger', 'Error occurred while adding Sub-Category.');
        }
         
    }

    public function subCategoryIndex()
    {
        $subCategory = subCategory::orderBy('name', 'ASC')->get();
        return view('category/subCategory/index', ['subCategory' => $subCategory] );
    }

    public function subCategoryView($id)
    {
        $subCategory = subCategory::find($id);
        if($subCategory){
            return view('category/subCategory/view',  ['subCategory' => $subCategory] );
        } else{
            return redirect('/category/subCategory/view')->with('danger', 'Sub-Category was not found.');
        }
    }

    public function subCategoryUpdate(Request $request, $id)
    {
        $subCategory = subCategory::find($id);
        if($subCategory){
            $subCategory->name = $request['name'];

            if ($subCategory->save()) {
                return redirect('category/subCategory')->with('success', 'Sub-Category was updated successfully.');
            } else {
                return redirect('category/subCategory')->with('danger', 'Error occurred while saving Sub-Category.');
            }
        } else {
            return redirect('/category/subCategory')->with('danger', 'Sub-Category was not found.');
        }
    }

    public function subCategoryDelete(Request $require, $id)
    {
        $subCategory = subCategory::where('id', $id)->first();
        if($subCategory->product->count() == 0){
            $subCategory->delete();
            return redirect('/category/subCategory')->with('success', 'Sub-Category deleted successfully.');
        }
        else {
                return redirect('/category/subCategory')->with('danger', 'Sub-Category was not deleted.');
            }
    }
}