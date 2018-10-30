<?php

namespace App\Services\Email;

/**
 * 
 * @author xjm
 *
 */
use App\Services\Service;
use JsonSerializable;

class Mail   implements JsonSerializable
{
	private $content, $type, $filename, $disposition, $content_id;

	public function setContent($content) {
		$this->content = $content;
	}
	public function getContent() {
		return $this->content;
	}
	public function setType($type) {
		$this->type = $type;
	}
	public function getType() {
		return $this->type;
	}
	public function setFilename($filename) {
		$this->filename = $filename;
	}
	public function getFilename() {
		return $this->filename;
	}
	public function setDisposition($disposition) {
		$this->disposition = $disposition;
	}
	public function getDisposition() {
		return $this->disposition;
	}
	public function setContentID($content_id) {
		$this->content_id = $content_id;
	}
	public function getContentID() {
		return $this->content_id;
	}
	public function jsonSerialize() {
		return array_filter ( [ 
				'content' => $this->getContent (),
				'type' => $this->getType (),
				'filename' => $this->getFilename (),
				'disposition' => $this->getDisposition (),
				'content_id' => $this->getContentID () 
		] );
	}
}


class TemplateContentService  extends Service{
	private $template_vars;
	private $template_invoke_name;
	public function getTemplateVars() {
		return $this->template_vars;
	}
	public function addVars($key, $value = array()) {
		$this->template_vars [$key] = $value;
	}
	public function setTemplateInvokeName($invoke_name) {
		$this->template_invoke_name = $invoke_name;
	}
	public function getTemplateInvokeName() {
		return $this->template_invoke_name;
	}
}
