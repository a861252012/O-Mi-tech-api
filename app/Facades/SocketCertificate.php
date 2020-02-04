<?php


namespace App\Facades;


use App\Services\SocketCertificateService;
use Illuminate\Support\Facades\Facade;

class SocketCertificate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SocketCertificateService::class;
    }
}