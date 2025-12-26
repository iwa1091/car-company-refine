<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * トップページを表示する
     */
    public function index()
    {
        return view('home');
    }
}