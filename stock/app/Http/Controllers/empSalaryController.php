<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\employeeSalary;
use App\certificate;
use App\user;
use \auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class empSalaryController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth');
    }

    
	
	public function generateSalarySlips($month,$year){
		$posts = employeeSalary::where('MONTH(date)',$month)->where('YEAR(date)',$year)->get();
		echo $month;die;
	}

}
