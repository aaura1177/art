<?php

namespace App\Http\Controllers;

use App\PermissionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $records = PermissionManager::orderByDesc('id')->get();
        return view("Permissions.index", compact("records"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("Permissions.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try 
        {
           
            PermissionManager::updateOrCreate(
                ['name' => $request->name],
                ['guard_name' => 'web'],
            );
            

            return redirect()->route('permission-management.create')->with('success', 'Permission created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save data: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PermissionManager $permission)
    {
        return view("Permissions.show", compact('permission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PermissionManager $permission)
    {
        return view("Permissions.edit", compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PermissionManager $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name,' . $permission->id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = [
                'name' => $request->name,
                'guard_name' => 'web',
            ];
            $permission->update($data);
            return redirect()->route('permission-management.index')->with('success', 'Permission updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update data: ' . $e->getMessage());
        }
    }
       

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PermissionManager $permission)
    {
        try {
            // Delete the specified record
            $permission->delete();
            return redirect()->back()->with('success', 'Permission deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete data: ' . $e->getMessage());
        }
    }

}
