<?php

namespace App\Http\Controllers;

use App\Employeedetail;
use App\EmployeeExpense;
use Illuminate\Http\Request;
use App\Imports\EmployeeTravelImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;

class EmployeeExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Fetch all employee details
        $employees = Employeedetail::all();

    // Start a query for employee expenses
    $query = EmployeeExpense::with('employeedetail');

    // Apply month filter if provided
    if ($request->filled('month')) {
        $query->where('month', 'LIKE', $request->month . '%');  // Match the selected month
    }

    // Apply employee filter if provided
    if ($request->filled('employeedetail_id')) {
        $query->where('employeedetail_id', $request->employeedetail_id);  // Filter by selected employee
    }

    // Get the filtered results
    $expenses = $query->get();

    // Return the view with the employees and filtered expenses
  
        
        // Return the view with the expenses and employees data
        return view('admin.employee_expenses.index', compact('employees', 'expenses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the form input
        $request->validate([
            'employeedetail_id' => 'required|exists:employeedetails,id',
            'days_present' => 'required|integer|min:1',
            'month' => 'required|date_format:Y-m',
        ]);

        // Fetch the selected employee details
        $employee = Employeedetail::findOrFail($request->employeedetail_id);
        
        // Calculate the total kilometers based on the distance from the office and days present
        $total_kms = 2 * $employee->distance_from_office * $request->days_present;

        // Create a new employee expense record
        EmployeeExpense::create([
            'employeedetail_id' => $request->employeedetail_id,
            'days_present' => $request->days_present,
            'distance_from_office' => $employee->distance_from_office,
            'total_kms' => $total_kms,
            'month' => $request->month,
        ]);

        // Redirect back with a success message
        return redirect()->route('employee_expenses.index')->with('success', 'Employee travel record added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Fetch the employee expense record
        $expense = EmployeeExpense::findOrFail($id);

        // Fetch all employees to display in the dropdown
        $employees = Employeedetail::all();

        // Return the edit view
        return view('employee_expenses.edit', compact('expense', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the form input
        $request->validate([
            'employeedetail_id' => 'required|exists:employeedetails,id',
            'days_present' => 'required|integer|min:1',
            'month' => 'required|date_format:Y-m',
        ]);

        // Fetch the employee expense record
        $expense = EmployeeExpense::findOrFail($id);

        // Fetch the selected employee details
        $employee = Employeedetail::findOrFail($request->employeedetail_id);

        // Calculate the total kilometers based on the distance from the office and days present
        $total_kms = 2 * $employee->distance_from_office * $request->days_present;

        // Update the employee expense record
        $expense->update([
            'employeedetail_id' => $request->employeedetail_id,
            'days_present' => $request->days_present,
            'distance_from_office' => $employee->distance_from_office,
            'total_kms' => $total_kms,
            'month' => $request->month,
        ]);

        // Redirect back with a success message
        return redirect()->route('employee_expenses.index')->with('success', 'Employee travel record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Fetch the employee expense record
        $expense = EmployeeExpense::findOrFail($id);

        // Delete the expense record
        $expense->delete();

        // Redirect back with a success message
        return redirect()->route('employee_expenses.index')->with('success', 'Employee travel record deleted successfully.');
    }
    public function import(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        // Import the file
        try {
            // Attempt to import the file
            Excel::import(new EmployeeTravelImport, $request->file('file'));
        } catch (ValidationException $e) {
            // If there is a validation error (new employees not in the system), catch it
            return redirect()->back()->withErrors($e->errors());
        }

        // Redirect back with a success message if no errors
        return redirect()->route('employee_expenses.index')->with('success', 'Employee travel records imported successfully.');
    }
}
