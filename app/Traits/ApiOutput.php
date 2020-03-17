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

	protected function setStatus($status, $msg = '')
	{
		$this->_status = $status;
		$this->_msg = $msg;
	}

	protected function setData($name, $data)
	{
        $this->_data[$name] = $data;
	}

	protected function setRootData($name, $data)
    {
        $this->response[$name] = $data;
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