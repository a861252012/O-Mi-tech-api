<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OPcacheController extends Controller
{
    public function status()
    {
        return opcache_get_status(false);
    }

    public function config()
    {
        return opcache_get_configuration();
    }

    public function flush()
    {
        return opcache_reset();
    }
}
