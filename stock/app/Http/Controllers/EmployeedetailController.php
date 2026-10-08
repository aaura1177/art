<?php
namespace App\Http\Controllers;
use App\Employeedetail;
use Illuminate\Http\Request;

class EmployeedetailController extends Controller
{
    public function index()
    {
        $employeedetails = Employeedetail::all();
        return view('admin.employeedetails.index', compact('employeedetails'));
    }

    public function create()
    {
        return view('admin.employeedetails.create');
    }

    public function store(Request $request)
    {
        Employeedetail::create($request->all());
        return redirect()->route('employeedetails.index')->with('success', 'Employeedetail added successfully.');
    }

    public function edit(Employeedetail $employeedetail)
    {
        return view('employeedetails.edit', compact('employeedetail'));
    }

    public function update(Request $request, Employeedetail $employeedetail)
    {
        $employeedetail->update($request->all());
        return redirect()->route('employeedetails.index')->with('success', 'Employeedetail updated successfully.');
    }

    public function destroy(Employeedetail $employeedetail)
    {
        $employeedetail->delete();
        return redirect()->route('employeedetails.index')->with('success', 'Employeedetail deleted successfully.');
    }
}
