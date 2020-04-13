<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OmeyController extends Controller
{
    public function __construct()
    {

    }

    public function index(Request $request)
    {
        try {
            echo "test";
        } catch (\Exception $e) {
            report($e);

        }
    }
}
