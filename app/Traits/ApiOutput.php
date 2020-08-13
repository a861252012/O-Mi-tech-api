<?php
/**
 * api 輸出共用庫
 * @author Weine
 * @date 2019/11/12
 */

namespace App\Traits;


trait ApiOutput
{
    private $response = ['status' => 999, 'msg' => ''];

    private $_status;

    private $_data = [];

    private $_msg;

    private $_msgParam;

    protected function setStatus($status, $msg = '', $msgParam = null)
    {
        $this->_status = $status;

        if (empty($msgParam)) {
            $this->_msg = __($msg);
        } else {
            $this->_msg = __($msg, $msgParam);
        }
    }

    protected function setData($name, $data)
    {
        $this->_data[$name] = $data;
    }

    protected function setRootData($name, $data)
    {
        $this->response[$name] = $data;
    }

    protected function setMsgParam($params = null)
    {
        $this->_msgParam = $params;
    }

    protected function jsonOutput()
    {
        $this->response['status'] = $this->_status;
        $this->response['msg'] = $this->_msg;

        if(!empty($this->_data)) {
            $this->response['data'] = $this->_data ?? null;
        }

        return response()->json($this->response);
    }

}