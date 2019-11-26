<?php
/**
 * api 輸出共用庫
 * @author Weine
 * @date 2019/11/12
 */

namespace App\Traits;


trait ApiOutput
{
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
		$this->_data = [$name => $data];
	}

	protected function jsonOutput()
	{
		$response = [
			'status' => $this->_status,
			'msg' => $this->_msg,
		];

		$response['data'] = $this->_data ?? null;

		return response()->json($response);
	}

}