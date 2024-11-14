<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebsitesController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'url' => $request->url
        ]);
    }
}
