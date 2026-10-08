<?php

namespace App\Http\Controllers;

use App\TravelExpense;
use App\setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TravelExpenseController extends Controller
{
    // Display all expenses
    public function index()
    {
        // Fetch all expenses from the database
        $expenses = TravelExpense::all();

        // Return view with expenses data
        return view('admin.travel_expenses.index', compact('expenses'));
    }

    // Store a new expense
    public function store(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'departure' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'date' => 'required|date',
            'distance' => 'required|numeric',
            'vehicle_type' => 'required|string|max:255',
        ]);

        // If validation fails, return with errors
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $setting=setting::first();
        if($request->vehicle_type=="Petrol"){
            $amount=$setting->petrol_price;
        }else{
            $amount=$setting->diesel_price;
        }
        // Create a new expense
        TravelExpense::create([
            'departure' => $request->departure,
            'destination' => $request->destination,
            'date' => $request->date,
            'amount' =>$amount*$request->distance,
            'vehicle_type'=>$request->vehicle_type,
            'distance'=>$request->distance
        ]);

        // Redirect back with success message
        return redirect()->route('expenses.index')->with('success', 'Expense created successfully.');
    }

    // Update an existing expense
    public function update(Request $request, $id)
    {
        // Find the expense by ID
        $expense = TravelExpense::findOrFail($id);

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'departure' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'date' => 'required|date',
            'vehicle_type' => 'required|string|max:255',
            'distance'     => 'required|numeric'
        ]);

        // If validation fails, return with errors
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $setting=setting::first();
        if($request->vehicle_type=="Petrol"){
            $amount=$setting->petrol_price;
        }else{
            $amount=$setting->diesel_price;
        }
        // Update the expense
        $expense->update([
            'departure' => $request->departure,
            'destination' => $request->destination,
            'date' => $request->date,
           'amount' =>$amount*$request->distance,
            'vehicle_type'=>$request->vehicle_type,
            'distance'=>$request->distance
        ]);

        // Redirect back with success message
        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    // Delete an expense
    public function destroy($id)
    {
        // Find the expense by ID
        $expense = TravelExpense::findOrFail($id);

        // Delete the expense
        $expense->delete();

        // Redirect back with success message
        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }
}
