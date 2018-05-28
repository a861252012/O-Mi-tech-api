<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Exceptions;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\Curl\Util;

/**
 * Base class for all mail handlers
 *
 * @author Gyula Sallai
 */
class TelegramHandler extends AbstractProcessingHandler
{

    /**
     * @var string Telegram API token
     */
    private $token='476921246:AAF8GZ_70hZQuaaNP0oEog3PHVAvGYQRU3U';
    /**
     * @var int Chat identifier
     */
    private $chatId='-289417825';
    /**
     * Builds the header of the API Call.
     *
     * @param string $content
     *
     * @return array
     */
    protected function buildHeader($content)
    {
        return [
            'Content-Type: application/json',
            'Content-Length: '.strlen($content),
        ];
    }
    /**
     * {@inheritdoc}
     */
    protected function write(array $record=[])
    {
        $content = [
            'chat_id' => $this->chatId,
            'text' => $record['formatted'],
        ];
        $content = json_encode($content);
        //dd($content);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->buildHeader($content));
        curl_setopt($ch, CURLOPT_URL, sprintf('https://api.telegram.org/bot%s/sendMessage', $this->token));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        Util::execute($ch);
    }
}
