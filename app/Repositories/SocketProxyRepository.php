<?php
/**
 * Socket 代理線路 資源庫
 * @author Weine
 * @date 2020-03-10
 */

namespace App\Repositories;


use App\Entities\Socket;

class SocketProxyRepository
{
    protected $socket;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    public function getAll()
    {
        return $this->socket->all();
    }
}