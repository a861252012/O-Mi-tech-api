<?php


namespace App\Services\Logging;

use App\Components\AthenaFormatter;

class LogService
{
    /**
     * Customize the given logger instance.
     *
     * @param \Illuminate\Log\Logger $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new AthenaFormatter());
        }
    }
}
