<?php

namespace App\Http\Controllers\Livestream;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Show the livestream report view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('livestream.report');
    }
}
