<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = 'MARKETING/SETTINGS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pengaturan';

        $user = $request->user()->load('roles');
        $redirectTo = route('marketing.settings.index');

        return view('marketing.settings.index', compact(
            'pagePath',
            'pageName',
            'user',
            'redirectTo'
        ));
    }
}
