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

use Illuminate\Support\Facades\Route;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Base class for all mail handlers
 *
 * @author Gyula Sallai
 */
class TelegramHandler extends AbstractProcessingHandler
{

    /**
     * @var string Telegram API token
     * https://api.telegram.org/bot632188770:AAFLLENMyaxIszdhm9Tx8Sy7MmE0DeRlINE/sendMessage?chat_id=247946941&text=Hello%20World
     */
    private $token = '632188770:AAFLLENMyaxIszdhm9Tx8Sy7MmE0DeRlINE';
    /**
     * @var int Chat identifier
     */
    private $chatId = '-247946941';

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
            'Content-Length: ' . strlen($content),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record = [])
    {
        try {
            $tmp = tmpfile();
            fwrite($tmp, $record['formatted']);
            fseek($tmp, 0);
            $meta = stream_get_meta_data($tmp);
            $routename = 'none';
            if (is_object(Route::getCurrentRoute())) {
                $routename = str_replace('/', '.', Route::getCurrentRoute()->uri ?? '');
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => sprintf('https://api.telegram.org/bot%s/sendDocument?chat_id=%s', $this->token, $this->chatId),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'document' => curl_file_create($meta['uri'], 'plain/text', date('ymd:') . \Request::getHost() . '-' . $routename),
                    'caption' => $record['context']['exception']->getMessage()
                ],
                CURLOPT_HTTPHEADER => [
                    'Content-Type: multipart/form-data'
                ],
            ));
            $result = curl_exec($curl);
            curl_close($result);
        } catch (\Exception $e) {
            return;
        }

    }
}
