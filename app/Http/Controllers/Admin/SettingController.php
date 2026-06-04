<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = 'ADMIN/SETTINGS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pengaturan';

        $user = $request->user()->load('roles');

        return view('admin.settings.index', compact(
            'pagePath',
            'pageName',
            'user'
        ));
    }
}