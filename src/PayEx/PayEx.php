<?php

namespace PayEx;

define('PAYEX_PROD_ORDER_WSDL',    'https://external.payex.com/pxorder/pxorder.asmx?wsdl');
define('PAYEX_PROD_CONFINED_WSDL', 'https://confined.payex.com/PxConfined/pxorder.asmx?wsdl');

define('PAYEX_TEST_ORDER_WSDL',    'https://test-external.payex.com/pxorder/pxorder.asmx?wsdl');
define('PAYEX_TEST_CONFINED_WSDL', 'https://test-confined.payex.com/PxConfined/pxorder.asmx?wsdl');

/**
 * PayEx
 */
class PayEx implements \Serializable {
	static $defaultParameters = array();
	static $defaultOptions    = array('testMode' => false);

	public function __construct($parameters = array(), $options = array()) {
		if (!class_exists('SoapClient')) {
			throw new Exception('>> Missing SoapClient << Make sure the php-soap extension is installed!!!');
		}

		$this->parameters = array_merge(self::$defaultParameters, $parameters);
		$this->options    = array_merge(self::$defaultOptions, $options);
		$this->clients    = array();
	}


	public static function setDefaultParameter($name, $value) {
		self::$defaultParameters[$name] = $value;
	}

	public static function getDefaultParameters($name) {
		return !isset(self::$defaultParameters[$key]) ? null : self::$defaultParameters[$key];
	}

	public static function setDefaultOption($name, $value) {
		self::$defaultOptions[$name] = $value;
	}
	
	public static function getDefaultOption($name) {
		return !isset(self::$defaultOptions[$name]) ? null : self::$defaultOptions->options[$name];
	}

	public function setOption($name, $value) {
		$this->options[$name] = $value;
	}
	
	public function getOption($name) {
		return !isset($this->options[$name]) ? null : $this->options[$name];
	}

	public function setParameter($name, $value) {
		$this->parameters[$name] = $value;
	}

	public function getParameter($name) {
		return !isset($this->parameters[$name]) ? null : $this->parameters[$name];
	}

	public function getSoapWSDL($type) {
		if (isset($this->options[$type])) {
			return $this->options[$type];
		}

		$wsdls = array(
			'orderWSDL'    => PAYEX_PROD_ORDER_WSDL,
			'confinedWSDL' => PAYEX_PROD_CONFINED_WSDL
		);

		if ($this->options['testMode']) {
			$wsdls = array(
				'orderWSDL'    => PAYEX_TEST_ORDER_WSDL,
				'confinedWSDL' => PAYEX_TEST_CONFINED_WSDL
			);
		}

		return $wsdls[$type]; 
	}

	public function getSoapClient($wsdl, $options = array('trace' => 1, "exceptions" => 0), $flush = false) {
		if (!isset($this->clients[$type]) || $flush) {
			$this->clients[$type] = new \SoapClient($wsdl, $options);
		}

		return $this->clients[$type];
	}

	public static function transaction($parameters) {
		$payex = new PayEx($parameters);
		$payex->startTwoPhaseTransaction();

		return $payex;
	}

	public function createHash($params) {
		return md5($params . $this->params['encryptionKey']);
	}

	public function startTwoPhaseTransaction($parameters = array()) {
		$parameters = array_merge($this->parameters, $parameters);

		$this->result = $this->initialize();
		$this->status = $this->checkStatus($this->result);
	}

	public function initialize($parameters = array()) {
		$parameters = array_merge(
			array(
				'clientIPAddress'  => $_SERVER['REMOTE_ADDR'],
				'clientIdentifier' => "USERAGENT=" . $_SERVER['HTTP_USER_AGENT']
			), $parameters
		);
		

	}

	public function redirect() {
		// if code & description & errorCode is OK, redirect the user
		$status = $this->status;
		$fn = function ($key) use ($status) {
			return $status[$key] == "OK";
		};

		if ($fn('code') && $fn('errorCode') && $fn('description')) {
			header('Location: '. $status['redirectUrl']);
		}
		else {
			foreach ($status as $error => $value) { 
				echo "$error, $value"."\n"; 
			}
		}
	}


	public function serialize() {
		return serialize(array(
			'parameters' => $parameters,
			'options'    => $options
		));
	}

	public function unserialize($data) {
		$values = unserialize($data);
		foreach ($values as $key => $value) {
			$this->{$key} = $value;
		}
	}
}