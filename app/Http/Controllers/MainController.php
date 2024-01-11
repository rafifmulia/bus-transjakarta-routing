<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MainController extends Controller
{
    public function main(Request $req) {
        return view('pages.maps');
    }
}
