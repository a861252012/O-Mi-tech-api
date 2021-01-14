<?php


namespace App\Components;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

class AthenaFormatter extends BaseJsonFormatter
{
    public function format(array $record)
    {
        $newRecord = [
            'dt'   => date('Y-m-d'),
            'ts'   => time(),
            'type' => 'req',
            'msg'  => $record['message'],
        ];

        // Contextual Information
        if (!empty($record['context'])) {
            $newRecord = array_merge($newRecord, $record['context']);
        }

        // 轉換成 JSON 並換行
        $json = $this->toJson($newRecord) . ($this->appendNewline ? "\n" : '');

        return $json;
    }
}
