<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notification;
use App\supplier;
use App\User;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class notificationsController extends Controller
{
    public function index(Request $request){
    $notifications = new Notification;
        if ($request->has('from') && $request->from != '') {
        $notifications->whereDate('created_at', '>=', $request->from);
    }

    if ($request->has('to') && $request->to != '') {
        $notifications->whereDate('created_at', '<=', $request->to);
    }
    $notifications = DB::select("
    SELECT n.*
    FROM notifications n
    INNER JOIN (
        SELECT notification, MAX(id) as max_id
        FROM notifications
        GROUP BY notification
    ) grouped
    ON n.id = grouped.max_id
    ORDER BY n.id DESC
    LIMIT 500
");

    	
       
		return view('notifications/index', ['notifications'=>$notifications]);
    }
    public function getNotifications(Request $request)
{
    // Get pagination and search parameters from DataTables
    $draw = $request->input('draw');
    $start = $request->input('start');
    $length = $request->input('length');
    $searchValue = $request->input('search.value');

    // Get the total number of records
    $totalRecords = Notification::count();

    // Apply filtering based on search input if available
    $query = Notification::with('user.supplier');
    if ($searchValue) {
        $query->where('notification', 'like', '%' . $searchValue . '%');
    }

    // Get filtered count
    $filteredRecords = $query->count();

    // Apply pagination and fetch results
    $notifications = $query->skip($start)->take($length)->get();

    // Prepare the response to match DataTables format
    return response()->json([
        'draw' => intval($draw),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $notifications->map(function ($notification) {
            return [
                'notification' => $notification->notification,
                'supplier' => $notification->user->supplier->c_name ?? '',
                'id' => $notification->id,
            ];
        })
    ]);
}
    public function create()
    {
        $suppliers = supplier::get();
        return view('notifications/create', ['suppliers'=>$suppliers]);
    }

    public function store(Request $request)
    {
        
        //echo $s->id;die;
        $supplier = supplier::where('id',$request['supplier'])->first();
        $user = User::where('supplier_id',$supplier->id)->first();
        if(isset($user->id)){
            $q = Notification::create([
                'user_id'=>$user->id,
                'notification'=>$request['notification']
            ]);
        
            return redirect('/notifications/index')->with('success', 'Notification added successfully.');
        }else{
            return redirect('/notifications/index')->with('danger', 'Notification cannot be added as User for supplier has not been created.');
        }
    }

    
	
	public function delete(Request $request, $id)
    {
        $notification = Notification::where('id', $id)->first();
        if ($notification) {
			if ($notification->delete()) {
				return redirect('/notifications/index')->with('success', 'Notification deleted successfully.');
			} else {
				return redirect('/notifications/index')->with('danger', 'Notification was not found.');
			}
        }
    }

}