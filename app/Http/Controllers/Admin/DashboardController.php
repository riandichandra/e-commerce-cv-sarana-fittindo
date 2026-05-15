<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $pagePath = 'Admin/Dashboard';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Dashboard';
        return view('admin.dashboard', compact('pagePath', 'pageName'));
    }
}
