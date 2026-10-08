<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\serviceCategories;
use App\ServiceProduct;


class ServiceProductController extends Controller
{
    public function index()
    {
        $products = ServiceProduct::orderBy('created_at', 'desc')->get();

        return view('ServiceProduct/index', compact('products'));
    }
    public function create()
    {
        $serviceCategories = serviceCategories::orderBy('created_at', 'desc')->get();

        return view('ServiceProduct.create', compact('serviceCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|exists:service_categories,id',
            'imgURL' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'gstslab' => 'nullable|in:0,5,12,18,28',
            'quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        if ($request->hasFile('imgURL')) {
            $imageName = time() . '.' . $request->imgURL->extension();
            $request->imgURL->move(public_path('images'), $imageName);
            $validated['imgURL'] = $imageName;  // Store the image file name
        }

        ServiceProduct::create([
            'service_category_id' => $validated['category'],
            'imageURL' => $validated['imgURL'] ?? null,
            'name' => $validated['name'],
            'gstslab' => $validated['gstslab'],
            'quantity' => $validated['quantity'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect('/service/product/view')->with('success', 'Product added successfully!');
    }


    public function view($id)
    {
        $product = ServiceProduct::find($id);
        $serviceCategories = serviceCategories::all();
        return view('ServiceProduct.edit', compact('product', 'serviceCategories'));
    }



    public function update(Request $request, $id)
    {
        $request->validate([
            'category' => 'required|exists:service_categories,id',
            'imgURL' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'name' => 'required|string|max:255',
            'gstslab' => 'nullable|in:0,5,12,18,28',
            'quantity' => 'nullable|integer|min:1',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $product = ServiceProduct::findOrFail($id);

        $updatedData = $request->only(['category', 'name', 'gstslab', 'quantity', 'remarks']);

        if ($request->hasFile('imgURL')) {
            if ($product->imageURL && file_exists(public_path('images/' . $product->imageURL))) {
                unlink(public_path('images/' . $product->imageURL));
            }

            $imageName = time() . '.' . $request->imgURL->extension();
            $request->imgURL->move(public_path('images'), $imageName);
            $updatedData['imgURL'] = $imageName;
        }

        $product->update($updatedData);

        return redirect('/service/product/view')->with('success', 'Product updated successfully!');
    }

    public function delete($id)
    {
        $product = ServiceProduct::findOrFail($id);

        if ($product->imageURL && file_exists(public_path('images/' . $product->imageURL))) {
            unlink(public_path('images/' . $product->imageURL));
        }

        $product->delete();

        return redirect('/service/product/view')->with('success', 'Product deleted successfully!');
    }



    public function serviceCategories(){
        return view('ServiceProduct/create_serviceCategories');
    }

    public function stroeServiceCategories(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name',
        ]);

        serviceCategories::create([
            'name' => $request->name,
        ]);

        return redirect('/service/categories')->with('success', 'Service Category added successfully.');
    }


    public function serviceCategoriesindex(){
        $serviceCategories = serviceCategories::all();
        return view('ServiceProduct/indexcategories', compact('serviceCategories'));
    }


    public function serviceCategoriesView($id){
    $serviceCategory = serviceCategories::find($id);
    return view('ServiceProduct/editcategories', compact('serviceCategory'));
    }
    public function serviceCategoriesUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:service_categories,name,' . $id,
        ]);

        $serviceCategory = serviceCategories::find($id);

        if (!$serviceCategory) {
            return redirect('/service/categoriesindex')->with('error', 'Service Category not found');
        }

        $serviceCategory->name = $request->input('name');
        $serviceCategory->save();

        return redirect('/service/categoriesindex')->with('success', 'Service Category updated successfully');
    }

    public function serviceCategoriesDelete($id)
    {
        $serviceCategory = serviceCategories::find($id);

        if (!$serviceCategory) {
            return redirect('/service/categoriesindex')->with('danger', 'Service Category not found.');
        }

        if ($serviceCategory->products->count() > 0) {
            return redirect('/service/categoriesindex')->with('danger', 'Category cannot be deleted because it has products.');
        }

        $serviceCategory->delete();

        return redirect('/service/categoriesindex')->with('success', 'Service Category deleted successfully.');
    }

}
