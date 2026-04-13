<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'help-guide');

        if (! in_array($tab, ['setup-guide', 'help-guide', 'faq'])) {
            $tab = 'setup-guide';
        }

        return view('help.index', compact('tab'));
    }
}
