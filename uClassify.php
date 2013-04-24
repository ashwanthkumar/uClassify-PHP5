<?php

if(!class_exists('SimpleXMLElement')) throw new uClassifyException("uClassify PHP SDK, requires SimpleXMLElement class to work properly.");

/**
 *	Copyright 2011 Ashwanth Kumar <ashwanthkumar@googlemail.com>

 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 *	PHP Library for accessing the XML API of uClassify
 *
 *	@author Ashwanth Kumar
 *	@version 0.1
 *
 *	
 **/

class uClassify {
	// Read API Key
	private $read_key;
	// Write API Key
	private $write_key;
	
	// XML Request that will be sent to the server
	private $xmlRequest;
	// XML Object that is the root element in the constructed element
	private $uclassify;
	
	// Contains the array of texts that is used for classification or training
	private $texts = array();
	// Randomly generated Text Ids, used while sending the array of texts to the server
	private $textIds = array();
	
	/**
	 *	Set the Read API Key used for making the API calls
	 *
	 *	@param $ReadKey		Read API Key
	 **/
	public function setReadApiKey($ReadKey) {
		if(empty($ReadKey)) {
			throw new uClassifyException("Read API Key in empty");
		} else {
			$this->read_key = $ReadKey;
		}
	}

	/**
	 *	Set the Write Key used for the API calls
	 *
	 *	@param $WriteKey 	Write API Key
	 **/
	public function setWriteApiKey($WriteKey) {
		if(empty($WriteKey)) {
			throw new uClassifyException("Write API Key is empty");
		} else {
			$this->write_key = $WriteKey;
		}
	}
	
	/**
	 *	Batch operation of Classify method 
	 *
	 *	@param $texts	Acutal Array of texts that needs to be classified
	 *	@param $classifierName	Name of the classifier against which the array of texts needs to be classified
	 *	@param $username	Name of the user, under whom the classifier exist. Use this option if you need to access other's published classifiers
	 **/
	public function classifyMany($texts = array(), $classifierName = null, $username = null) {
		if(count($texts) < 1) throw new uClassifyException("What should be classified? No text seems to be specified!");
		if(empty($classifierName)) throw new uClassifyException("How should the text be classified? No ClassiferName seems to be specified!");

		$this->buildXMLRequest();

		$_id = 0;
		foreach($texts as $text) {
			$this->texts[] = base64_encode($text);
			// Setting the Ids for the text
			$this->textIds[] = 'Text' . ($_id++);
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
		
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			return $this->parseClassifyResponse($resp);
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
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			return $this->parseClassifyResponse($resp);
		}
	}

	/**
	 *	Perform the training on a single class
	 *
	 *	@param $text			Array() of Texts that needs to be used for training
	 *	@param $className		Name of the Class that needs to be trained
	 *	@param $classifierName	Name of the classifier to be used
	 **/
	public function train($texts = array(), $className, $classifierName = null) {
		if(count($texts) < 1) throw new uClassifyException("You need to provide atleast a single text for training. ");
		if(empty($className)) throw new uClassifyException("Name of the class to be trained is not specified. ");
		if(empty($classifierName)) throw new uClassifyException("Name of the Classifier under which the $className exist is not specified. ");
		
		$this->buildXMLRequest();
		
		$_id = 0;
		foreach($texts as $text) {
			$this->texts[] = base64_encode($text);
		
			// Setting the Ids for the text
			$this->textIds[] = 'TrainText' . ($_id++);
		}

		$texts = $this->xmlRequest->createElement('texts');
		$writeCalls = $this->xmlRequest->createElement('writeCalls');
		
		if(empty($this->write_key) || !isset($this->write_key)) throw new uClassifyException("Write API Key is not specified.");
		$writeCalls->setAttribute('writeApiKey' , $this->write_key);
		$writeCalls->setAttribute('classifierName' , $classifierName);
		
		$this->uclassify->appendChild($texts);
		$this->uclassify->appendChild($writeCalls);
		
		$_counter = 0;
		foreach($this->texts as $textBase64) {
			// Creating the textBase64 element tags 
			$textb = $this->xmlRequest->createElement('textBase64',$textBase64);
			$texts->appendChild($textb);
			$textb->setAttribute('id', $this->textIds[$_counter]);

			// Creating the train tags for the same textBase64 elements
			$train = $this->xmlRequest->createElement('train');
			$writeCalls->appendChild($train);
			$train->setAttribute('id','Train' . rand(0, getrandmax()) . time());
			$train->setAttribute('className',$className);
			$train->setAttribute('textId',$this->textIds[$_counter]);
			$_counter++;
		}

		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			$xmlResp = new SimpleXMLElement($resp);
			
			if($xmlResp->status['success'] == "false") {
				throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
			} else {
				return true;
			}
		}
	}

	/**
	 *	Perform the Un-training on a single class
	 *
	 *	@param $text			Array() of Texts that needs to be used for training
	 *	@param $className		Name of the Class that needs to be trained
	 *	@param $classifierName	Name of the classifier to be used
	 **/
	public function untrain($texts = array(), $className, $classifierName = null) {
		if(count($texts) < 1) throw new uClassifyException("You need to provide atleast a single text for un-training. ");
		if(empty($className)) throw new uClassifyException("Name of the class to be un-trained is not specified. ");
		if(empty($classifierName)) throw new uClassifyException("Name of the Classifier under which the $className exist is not specified. ");
		
		$this->buildXMLRequest();
		
		$_id = 0;
		foreach($texts as $text) {
			$this->texts[] = base64_encode($text);
		
			// Setting the Ids for the text
			$this->textIds[] = 'UnTrainText' . ($_id++);
		}

		$texts = $this->xmlRequest->createElement('texts');
		$writeCalls = $this->xmlRequest->createElement('writeCalls');
		
		if(empty($this->write_key) || !isset($this->write_key)) throw new uClassifyException("Write API Key is not specified.");
		$writeCalls->setAttribute('writeApiKey' , $this->write_key);
		$writeCalls->setAttribute('classifierName' , $classifierName);
		
		$this->uclassify->appendChild($texts);
		$this->uclassify->appendChild($writeCalls);
		
		$_counter = 0;
		foreach($this->texts as $textBase64) {
			// Creating the textBase64 element tags 
			$textb = $this->xmlRequest->createElement('textBase64',$textBase64);
			$texts->appendChild($textb);
			$textb->setAttribute('id', $this->textIds[$_counter]);

			// Creating the untrain tags for the same textBase64 elements
			$untrain = $this->xmlRequest->createElement('untrain');
			$writeCalls->appendChild($untrain);
			$untrain->setAttribute('id','UnTrain' . rand(0, getrandmax()) . time());
			$untrain->setAttribute('className',$className);
			$untrain->setAttribute('textId',$this->textIds[$_counter]);
			$_counter++;
		}

		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			$xmlResp = new SimpleXMLElement($resp);
			
			if($xmlResp->status['success'] == "false") {
				throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
			} else {
				return true;
			}
		}
	}

	/**
	 *	Get the information about a Classifier
	 *
	 *	@param $classifierName Name of the classifier to be used
	 **/
	public function getInformation($classifierName) {
		if(empty($classifierName)) throw new uClassifyException('Classifier name supplied to getInformation() can not be empty');
		
		$this->buildXMLRequest();

		$readCalls = $this->xmlRequest->createElement('readCalls');
		
		if(empty($this->read_key) || !isset($this->read_key)) throw new uClassifyException("Read API Key is not specified.");
		$readCalls->setAttribute('readApiKey' , $this->read_key);
		
		$this->uclassify->appendChild($readCalls);
		
		$getInformation = $this->xmlRequest->createElement('getInformation');
		$readCalls->appendChild($getInformation);
		
		$getInformation->setAttribute('id','GetInformation'.time());
		
		
		$getInformation->setAttribute('classifierName', $classifierName);
		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			return $this->parseGetInformationResponse($resp);
		}
	}
	
	/**
	 *	Get the information about array of classifiers
	 *
	 *	@param $classifiers Array() of classifiers
	 **/
	public function getInformationMany($classifers = array()) {
		if(count($classifers) < 1) throw new uClassifyException('Atleast one classifier must be passed to the getInformation call');
		
		$this->buildXMLRequest();

		$readCalls = $this->xmlRequest->createElement('readCalls');
		
		if(empty($this->read_key) || !isset($this->read_key)) throw new uClassifyException("Read API Key is not specified.");
		$readCalls->setAttribute('readApiKey' , $this->read_key);
		
		$this->uclassify->appendChild($readCalls);
		
		foreach($classifers as $classifierName) {
			$getInformation = $this->xmlRequest->createElement('getInformation');
			$readCalls->appendChild($getInformation);
			
			// $$$ is used to separate ClassifierName and the randomw number generated, so that we would be able to extract the name of the classifier from the response XML and is used in response array 
			$getInformation->setAttribute('id',$classifierName."_".(rand(0, getrandmax()) * time()));
			
			$getInformation->setAttribute('classifierName', $classifierName);
		}
		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			return $this->parseGetInformationResponse($resp);
		}
	}
	
	/**
	 *	Creates a new classifier in the user's account
	 *
	 *	@param $classifierName Name of the Classifier to create
	 **/
	public function create($classifierName) {
		if(empty($classifierName)) throw new uClassifyException('Name of the classifier that you would want to create can not be empty.');
		
		$this->buildXMLRequest();

		$writeCalls = $this->xmlRequest->createElement('writeCalls');
		
		if(empty($this->write_key) || !isset($this->write_key)) throw new uClassifyException("Read API Key is not specified.");
		$writeCalls->setAttribute('writeApiKey' , $this->write_key);
		$writeCalls->setAttribute('classifierName' , $classifierName);
		
		$create = $this->xmlRequest->createElement('create');
		$create->setAttribute('id', 'Create'.time().'_'.$classifierName);
		$writeCalls->appendChild($create);
		
		$this->uclassify->appendChild($writeCalls);
		
		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			$xmlResp = new SimpleXMLElement($resp);
			
			if($xmlResp->status['success'] == "false") {
				throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
			} else {
				return true;
			}
		}
	}
	
	/**
	 *	Removes a existing classifier in the user's account
	 *
	 *	@param $classifierName Name of the Classifier to create
	 **/
	public function remove($classifierName) {
		if(empty($classifierName)) throw new uClassifyException('Name of the classifier that you would want to delete can not be empty.');
		
		$this->buildXMLRequest();

		$writeCalls = $this->xmlRequest->createElement('writeCalls');
		
		if(empty($this->write_key) || !isset($this->write_key)) throw new uClassifyException("Read API Key is not specified.");
		$writeCalls->setAttribute('writeApiKey' , $this->write_key);
		$writeCalls->setAttribute('classifierName' , $classifierName);
		
		$create = $this->xmlRequest->createElement('remove');
		$create->setAttribute('id', 'Remove'.time().'_'.$classifierName);
		$writeCalls->appendChild($create);
		
		$this->uclassify->appendChild($writeCalls);
		
		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			$xmlResp = new SimpleXMLElement($resp);
			
			if($xmlResp->status['success'] == "false") {
				throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
			} else {
				return true;
			}
		}
	}
	
	/**
	 *	Adds a class to an existing classifier in the user's account
	 *
	 *	@param $className Name of the Class to create
	 *	@param $classifierName Name of the Classifier to create
	 **/
	public function addClass($className, $classifierName) {
		if(empty($classifierName)) throw new uClassifyException('Name of the classifier can not be empty in addClass().');
		if(empty($className)) throw new uClassifyException('Name of the class that you would want to create can not be empty.');
		
		$this->buildXMLRequest();

		$writeCalls = $this->xmlRequest->createElement('writeCalls');
		
		if(empty($this->write_key) || !isset($this->write_key)) throw new uClassifyException("Read API Key is not specified.");
		$writeCalls->setAttribute('writeApiKey' , $this->write_key);
		$writeCalls->setAttribute('classifierName' , $classifierName);
		
		$addClass = $this->xmlRequest->createElement('addClass');
		$addClass->setAttribute('id', 'AddClass'.time().'_'.$classifierName);
		$addClass->setAttribute('className', $className);
		$writeCalls->appendChild($addClass);
		
		$this->uclassify->appendChild($writeCalls);
		
		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			$xmlResp = new SimpleXMLElement($resp);
			
			if($xmlResp->status['success'] == "false") {
				throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
			} else {
				return true;
			}
		}
	}
	
	/**
	 *	Removes a class from an existing classifier in the user's account
	 *
	 *	@param $className Name of the Class to delete
	 *	@param $classifierName Name of the Classifier to use
	 **/
	public function removeClass($className, $classifierName) {
		if(empty($className)) throw new uClassifyException('Name of the class that you would want to delete can not be empty.');
		if(empty($classifierName)) throw new uClassifyException('Name of the classifier can not be empty in removeClass().');
		
		$this->buildXMLRequest();

		$writeCalls = $this->xmlRequest->createElement('writeCalls');
		
		if(empty($this->write_key) || !isset($this->write_key)) throw new uClassifyException("Read API Key is not specified.");
		$writeCalls->setAttribute('writeApiKey' , $this->write_key);
		$writeCalls->setAttribute('classifierName' , $classifierName);
		
		$removeClass = $this->xmlRequest->createElement('removeClass');
		$removeClass->setAttribute('id', 'removeClass'.time().'_'.$classifierName);
		$removeClass->setAttribute('className', $className);
		$writeCalls->appendChild($removeClass);
		
		$this->uclassify->appendChild($writeCalls);
		
		$xr = $this->xmlRequest->saveXML();
		$resp = $this->postRequest($xr);
		
		if(!$resp) {
			throw new uClassifyException("Invalid data sent by the server!");
		} else {
			$xmlResp = new SimpleXMLElement($resp);
			
			if($xmlResp->status['success'] == "false") {
				throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
			} else {
				return true;
			}
		}
	}
	
	/**
	 *	Building the request XML object
	 **/
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
	
	/**
	 *	Uses file stream context as a default method to access the uClassify API server. If your server configuration does not allow fopen() calls, the class will automatically use CURL; but It must be installed on the server
	 *
	 *	@param $xmlRequest	The actual XML string that needs to be sent
	 **/
	protected function postRequest($xmlRequest) {
		// Disabling the fopen based request, somehow that does not seem to pass the headers right.
		// TODO: Always using cURL for now, need to refactor code here
		if(ini_get('allow_url_fopen') && false) {
			// fopen methods will work
			return $this->postFileStreamRequest($xmlRequest);
		} else {
			// use CURL here
			return $this->postCURLRequest($xmlRequest);
		}
	}
	
	/**
	 *	POST the request using cURL, if the ini settings are not enabled
	 *
	 *	@param $xmlRequest XML String that needs to be sent as the request
	 **/
	protected function postCURLRequest($xmlRequest) {
		if(!function_exists('curl_init')) throw new uClassifyException('cURL is not installed on your server, and allow_url_fopen is also not enabled on your php.ini settings. ');
		
		$headers = array('Content-Type: text/xml');

		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, 'http://api.uclassify.com/');

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); // no echo, just return result
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlRequest);

		$xmlResponse = curl_exec($curl);
		
		return $xmlResponse;
	}
	
	/**
	 *	POST the request as fileStreamRequest
	 *
	 *	@param $xmlRequest XML String that needs to be sent as the request
	 **/
	protected function postFileStreamRequest($xmlRequest) {
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
	
	/**
	 *	Parses the response from the <classify> calls
	 *
	 *	@param $xmlResponse	XML String returned as the response from the server
	 **/
	protected function parseClassifyResponse($xmlResponse) {
		$xmlResp = new SimpleXMLElement($xmlResponse);
		
		if($xmlResp->status['success'] == "false") {
			throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
		}
		
		$responeArray = array();
		$_counter = 0;
		foreach($xmlResp->readCalls->classify as $readCalls) {
			$ClassificationClass = array();
			foreach($readCalls->classification->{'class'} as $classes) {
				$ClassificationClass[] = array('class' => (string)$classes['className'], 'p' => (string)$classes['p']);
			}
			// Since the classification Ids are randomly generated during the call, we also add the text to the response array, as it would be easy for the user to see the response as the text given as input -- Any brigther ideas?
			$responeArray[] = array('id' => (string)$readCalls['id'], 'classification' => $ClassificationClass, 'text' => base64_decode($this->texts[$_counter])); 
			$_counter++;
		}
		
		return $responeArray;
	}
	
	/**
	 *	Parse the XML Response from the getInformation API call
	 *
	 *	@param $xmlResponse	XML String returned as the response from the server
	 **/
	protected function parseGetInformationResponse($xmlResponse) {
		$xmlResp = new SimpleXMLElement($xmlResponse);
		
		if($xmlResp->status['success'] == "false") {
			throw new uClassifyException("Call Request failed! Status code: " . $xmlResp->status['statusCode'] . "<br />Reason - " . $xmlResp->status);
		}
		
		$responeArray = array();
		foreach($xmlResp->readCalls->getInformation as $getInfo) {
			$classInformation = array();
			foreach($getInfo->classes->{'classInformation'} as $getClassInfo) {
				$classInformation[] = array('className' => (string)$getClassInfo['className'], 'uniqueFeatures' => (string)$getClassInfo->uniqueFeatures, 'totalCount' => (string)$getClassInfo->totalCount);
			}
			
			$responeArray[] = array('classifier' => (string)preg_replace('/\_([0-9]+)/','',$getInfo['id']), 'meta' => $classInformation);
		}
		
		return $responeArray;
	}
}

/**
 *	uClassify Execption class. Helps differentiate from PHP errors and uClassify Errors
 **/
class uClassifyException extends Exception {
	// Nothing interesting here actually
}
