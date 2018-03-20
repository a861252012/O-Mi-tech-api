<?php
/**
 *  移动端专用StartSession中间件，会依次从GET POST参数、header、cookie中读取session id
 */

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;


class MobileSession extends StartSession
{
    public function getSession(Request $request)
    {
        return tap($this->manager->driver(), function ($session) use ($request) {
            $session->setId(
                $request->get($session->getName()) ?:
                    $request->headers->get($session->getName()) ?:
                        $request->cookies->get($session->getName()));
        });
    }

}
