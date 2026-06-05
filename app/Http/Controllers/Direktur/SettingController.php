<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = 'DIREKTUR/SETTINGS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pengaturan';

        $user = $request->user()->load('roles');
        $redirectTo = route('direktur.settings.index');

        return view('direktur.settings.index', compact(
            'pagePath',
            'pageName',
            'user',
            'redirectTo'
        ));
    }
}
