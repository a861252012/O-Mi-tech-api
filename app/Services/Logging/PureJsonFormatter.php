<?php

namespace App\Services\Logging;

use Monolog\Formatter\LineFormatter;

class PureJsonFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                "%context%\n",
            ));
        }
    }
}
