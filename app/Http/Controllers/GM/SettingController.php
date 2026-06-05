<?php

namespace App\Http\Controllers\GM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $pagePath = 'GM/SETTINGS';
        $pagePath = explode('/', $pagePath);
        $pageName = 'Pengaturan';

        $user = $request->user()->load('roles');
        $redirectTo = route('gm.settings.index');

        return view('gm.settings.index', compact(
            'pagePath',
            'pageName',
            'user',
            'redirectTo'
        ));
    }
}
