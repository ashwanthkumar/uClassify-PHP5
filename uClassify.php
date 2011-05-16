<?php

/**
 *	PHP Library for accessing the XML API of uClassify
 *
 *	@author Ashwanth Kumar
 *	@version 0.1
 **/

class uClassify {
	private $read_key;
	private $write_key;
	private $xmlRequest;
	private $uclassify;
	
	private $texts = array();
	private $textIds = array();
	
	public function __construct() {
	}
	
	public function setReadApiKey($ReadKey) {
		if(empty($ReadKey)) {
			throw new uClassifyException("Read API Key in empty");
		} else {
			$this->read_key = $ReadKey;
		}
	}
	
	public function setWriteApiKey($WriteKey) {
		if(empty($WriteKey)) {
			throw new uClassifyException("Write API Key is empty");
		} else {
			$this->write_key = $WriteKey;
		}
	}
	
	public function classifyS($texts = array(), $classifierName = null, $username = null) {
		if(count($texts) < 1) throw new uClassifyException("What should be classified? No text seems to be specified!");
		if(empty($classifierName)) throw new uClassifyException("How should the text be classified? No ClassiferName seems to be specified!");

		$this->buildXMLRequest();

		foreach($texts as $text) {
			$this->texts[] = base64_encode($text);
			// Setting the Ids for the text
			$this->textIds[] = 'Text' . (rand(0,getrandmax()) * time());
		}

		$texts = $this->xmlRequest->createElement('texts');
		$readCalls = $this->xmlRequest->createElement('readCalls');
		
		if(empty($this->read_key) || !isset($this->read_key)) throw new uClassifyException("Read API Key is not specified.");
		$readCalls->setAttribute('readApiKey' , $this->read_key);
		
		$this->uclassify->appendChild($texts);
		$this->uclassify->appendChild($readCalls);
		
		$_counter = 0;
		foreach($this->texts as $textBase64) {
			// Creating the textBase64 element tags 
			$textb = $this->xmlRequest->createElement('textBase64',$textBase64);
			$texts->appendChild($textb);
			$textb->setAttribute('id', $this->textIds[$_counter]);

			// Creating the classify tags for the same textBase64 elements
			$classify = $this->xmlRequest->createElement('classify');
			$readCalls->appendChild($classify);
			$classify->setAttribute('id','Classify' . rand(0, getrandmax()) . time());
			$classify->setAttribute('classifierName',$classifierName);
			$classify->setAttribute('textId',$this->textIds[$_counter]);
			if(!empty($username)) $classify->setAttribute('username',$username);
			$_counter++;
		}

		$xr = $this->xmlRequest->saveXML();
		echo $xr;
		
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Error Found!");
		} else {
			print_r($resp);
		}
	}

	/**
	 *	Perform the classification on a single text call. 
	 *
	 *	@param $text			Text that needs to be classified
	 *	@param $classifierName	Name of the classifier to be used
	 *	@param $username		Username under which the classifier is published if it does not belong to the current user
	 **/
	public function classify($text, $classifierName = null, $username = null) {
		if(empty($text)) throw new uClassifyException("What should be classified? No text seems to be specified!");
		if(empty($classifierName)) throw new uClassifyException("How should the text be classified? No ClassiferName seems to be specified!");
		
		$this->buildXMLRequest();
		
		$this->texts[] = base64_encode($text);
		
		// Setting the Ids for the text
		$this->textIds[] = 'Text' . (rand(0,getrandmax()) * time());

		$texts = $this->xmlRequest->createElement('texts');
		$readCalls = $this->xmlRequest->createElement('readCalls');
		
		if(empty($this->read_key) || !isset($this->read_key)) throw new uClassifyException("Read API Key is not specified.");
		$readCalls->setAttribute('readApiKey' , $this->read_key);
		
		$this->uclassify->appendChild($texts);
		$this->uclassify->appendChild($readCalls);
		
		$_counter = 0;
		foreach($this->texts as $textBase64) {
			// Creating the textBase64 element tags 
			$textb = $this->xmlRequest->createElement('textBase64',$textBase64);
			$texts->appendChild($textb);
			$textb->setAttribute('id', $this->textIds[$_counter]);

			// Creating the classify tags for the same textBase64 elements
			$classify = $this->xmlRequest->createElement('classify');
			$readCalls->appendChild($classify);
			$classify->setAttribute('id','Classify' . rand(0, getrandmax()) . time());
			$classify->setAttribute('classifierName',$classifierName);
			$classify->setAttribute('textId',$this->textIds[$_counter]);
			if(!empty($username)) $classify->setAttribute('username',$username);
			$_counter++;
		}

		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Error Found!");
		} else {
			$this->parseReadCallResponse($resp);
		}
	}
	
	private function buildXMLRequest() {
		$this->xmlRequest = new DOMDocument('1.0', 'utf-8');
		$this->uclassify = $this->xmlRequest->createElementNS('http://api.uclassify.com/1/RequestSchema','uclassify');
		$this->xmlRequest->appendChild($this->uclassify);
		$this->uclassify->setAttribute('version','1.01');
				
		$this->texts = array();
		$this->textIds = array();
		
		// Setting the option just in case if we're echo'ng the text
		$this->xmlRequest->formatOutput = true;
	}
	
	private function postRequest($xmlRequest) {
		$params = array('http' => array(
								   'method' => 'POST',
								   'header' => 'Content-Type: text/xml; charset=utf-8',
								   'content' => $xmlRequest)
						);
		$ctx = stream_context_create($params);
		$fp = @fopen('http://api.uclassify.com', 'rb', false, $ctx);
		if (!$fp)
			return false;
		$xmlResponse = @stream_get_contents($fp);
		@fclose($fp);
		return $xmlResponse;
	}
	
	public function parseReadCallResponse($xmlResponse) {
		$xmlResp = new SimpleXMLElement($xmlResponse);
		
		if($xmlResp->status['success'] == "false") {
			throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
		}
		
		$formatted = array();
		$_counter = 0;
		foreach($xmlResp->readCalls->classify as $readCalls) {
			$ClassificationClass = array();
			foreach($readCalls->classification->{'class'} as $classes) {
				$ClassificationClass[] = array('class' => $classes['className'], 'p' => $classes['p']);
				// print_r($classes);
			}
			$formatted[] = array('id' => $readCalls['id'], 'classification' => $ClassificationClass, 'text' => base64_decode($this->texts[$_counter])); 
			$_counter++;
		}
		
		return $formatted;
	}

}

class uClassifyException extends Exception {
}
