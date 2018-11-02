<?php

namespace App\Services\Email;


class Response {
	private $body;
	
	public function __construct($body = null) {
		$this->body = $body;
	}
	public function body() {
		return $this->body;
	}
}