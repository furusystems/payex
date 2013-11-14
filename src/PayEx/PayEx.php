<?php

namespace PayEx;

/**
 * PayEx
 */
class PayEx implements \Serializable {
	
	//--------------------------------------------------------------------------
	//
	//  Constants & static class variables
	//
	//-------------------------------------------------------------------------	
	
	const PROD_ORDER_WSDL    = 'https://external.payex.com/pxorder/pxorder.asmx?wsdl';
	const PROD_CONFINED_WSDL = 'https://confined.payex.com/PxConfined/pxorder.asmx?wsdl';
	
	const TEST_ORDER_WSDL    = 'https://test-external.payex.com/pxorder/pxorder.asmx?wsdl';
	const TEST_CONFINED_WSDL = 'https://test-confined.payex.com/PxConfined/pxorder.asmx?wsdl';
	
	const ORDER_WSDL_TYPE    = 'orderWSDL';
	const CONFINED_WSDL_TYPE = 'confinedWSDL';
			
	const TRANSACTION_SALE          = 'SALE';
	const TRANSACTION_AUTHORIZATION = 'AUTHORIZATION';
	
	// @see http://www.payexpim.com/technical-reference/pxorder/initialize8/
	static $defaultParameters = array(
		
		// Authorization or SALE
		'purchaseOption' => 'SALE', 
		
		// Default payment method. CREDITCARD|DIRECTDEBIT|PAYPAL...
		'view' => 'CREDITCARD', 
		
		// 
		'currency' => 'NOK',        
		
		// if the vat is supposed to be 25%, its defined as 25%
		'vat'      => '0',           
		
		// Supported languages: nb-NO, da-DK, en-US, sv-SE, es-ES, de-DE, fi-FI, fr-FR, pl-PL, cs-CZ, hu-HU 
		'clientLanguage' => 'nb-NO' 
	);
	
	static $defaultOptions = array(
		'testMode' => false,
		'throwError' => false
	);
	
	static $defaultClients = array();

	
	//--------------------------------------------------------------------------
	//
	//  Constructor
	//
	//-------------------------------------------------------------------------	
	
	public function __construct($parameters = array(), $options = array()) {
		if (!class_exists('SoapClient')) {
			throw new Exception('>> Missing SoapClient << Make sure the php-soap extension is installed!!!');
		}
		
		$this->initialized = true;
		$this->parameters = array_merge(self::$defaultParameters, $parameters);
		$this->options    = array_merge(self::$defaultOptions, $options);
		$this->clients    = array();
	}

	
	//--------------------------------------------------------------------------
	//
	//  Parameters and options methods
	//
	//-------------------------------------------------------------------------	
	
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
	
	
	//--------------------------------------------------------------------------
	//
	//  Soap Service methods
	//
	//-------------------------------------------------------------------------	
	
	public static function transaction($parameters) {
		$payex = new PayEx($parameters);
		$payex->transactionInitialize($parameters);

		return $payex;
	}

	public function transactionInitialize($parameters = array()) {
		$parameters = array_merge(
			array(
				'clientIPAddress'  => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
				'clientIdentifier' => isset($_SERVER['HTTP_USER_AGENT']) ? "USERAGENT=" . $_SERVER['HTTP_USER_AGENT'] : '',
			), $parameters
		);

		if (isset($parameters['additionalValues']) && is_array($parameters['additionalValues'])) {
			$values = $parameters['additionalValues'];
			$args = array();
			$keys = array( 
				'INVOICE_INVOICETEXT', 'INVOICE_MEDIADISTRIBUTION', 
				'INVOICE_CUSTOMERID', 'INVOICE_DUEDATE', 
				'INVOICE_COUNTRY', 'INVOICE_CUSTOMERTYPE', 
				'INVOICE_SOCIALSECURITYNUMBER', 'INVOICE_ORGANIZATIONNUMBER', 
				'INVOICE_FIRSTNAME', 'INVOICE_FAMILYNAME',
				'INVOICE_PHONENUMBER', 'INVOICE_EMAIL', 
				'INVOICE_INVOICENUMBER', 'PAYMENTMENU', 
				'DOCUMENTID', 'PREAUTHORIZATION', 
				'TRANSACTIONTYPE', 'MSISDN', 
				'CHANNELID', 'DURATION'
			);
			foreach ($values as $key => $value) {
				if (!in_array($key, $keys)) {
					continue;
				}
				$args[] = $key . '=' . $value;
			}
			$parameters['additionalValues'] = join('&', $args);
		}
		
		$keys = array(
			'accountNumber' => true,
			'purchaseOperation' => true,
			'price' => true,
			'priceArgList' => false,
			'currency' => true,
			'vat' => true,
			'orderID' => true,
			'productNumber' => true,
			'description' => true,
			'clientIPAddress' => true,
			'clientIdentifier' => false,
			'additionalValues' => false,
			'externalID' => false,
			'returnUrl' => true,
			'view' => true,
			'agreementRef' => false,
			'cancelUrl' => false,
			'clientLanguage' => false
		);
		
		$values = array();
		$args   = array();
		foreach ($keys as $key => $required) {
			$set   = isset($parameters[$key]);
			$value = $set ? $parameters[$key] : '';
			if ($required && !$set) {
				throw new \Exception("Missing required parameter: ${key}");
			}
			else if ($required && $set && empty($value) && ($key != 'vat' || ($key == 'vat' && $value == ''))) {
				throw new \Exception("Empty required parameter: ${key}");
			}
			
			$values[$key] = $value;
		}
		$values['price'] = number_format($values['price'], 2, "", "");
		$values['hash'] = $this->createHash($values);
		$soapClient = $this->getSoapClient( self::ORDER_WSDL_TYPE );
		
		try {
			$respons = $soapClient->Initialize8($values);
		} catch (\SoapFault $ex) {
			throw new \Exception("SoapFault: " . $ex->faulString);
		}
		
		$result = $respons->Initialize8Result;		
		$xml = new \SimpleXMLElement($result);
		
		$this->result = $result;
		$this->status = array(
			'code' => strtoupper($xml->status->code),
			'errorCode' => strtoupper($xml->status->errorCode),
			'description' => strtoupper($xml->status->description),
			'redirectUrl' => (string) $xml->redirectUrl,
			'orderRef' => strtoupper($xml->orderRef)
		);
		
		return $this->status;
	}
	
		
	public function transactionIsOk() {
		return $this->status['code'] == "OK";
	}
	
	public function transactionComplete($orderRef = null) {
		$not_set = empty($orderRef);
		if ($orderRef && 
				(!property_exists($this, 'status') || !isset($this->status['orderRef'])) ) {
			throw new Exception("No orderRef and no orderRef found at this point the instance status.");
		}
		else if ($not_set) {
			$orderRef = $this->status['orderRef'];
		}
		
		$values = array( 
			'accountNumber' => $this->getParameter('accountNumber'),
			'orderRef' => stripcslashes( $orderRef )
		);
		$values['hash'] = $this->createHash($values);
		$soapClient = $this->getSoapClient( self::ORDER_WSDL_TYPE );
		
		try {
			$response = $soapClient->Complete($values);
		} catch (\SoapFault $ex) {
			throw new Exception("SoapFault: " . $ex->faultString);
		}
		
		if ($response instanceof \SoapFault) {
			throw new \Exception("SoapFault: " . $ex->getMessage());
		}

		$result = $response->CompleteResult;
		$this->result = $result;
		
		$xml = new \SimpleXMLElement($result);
		$this->status = array(
			'code' => strtoupper($xml->status->code),
			'errorCode' => strtoupper($xml->status->errorCode),
			'description' => strtoupper($xml->status->description),
			'transactionStatus' => strtoupper($xml->transactionStatus),
			'orderRef' => $orderRef
		);
		
		foreach (array('transactionErrorCode', 'transactionErrorDescription', 'transactionThirdPartyError') as $key) {
			$this->status[$key] = property_exists($xml, $key) ? (string) $xml->{$key} : "";
		}
		
		return $this->status;
	}
	
	public function transactionRedirect() {
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
	
	public function getSoapWSDL($type) {
		if (isset($this->options[$type])) {
			return $this->options[$type];
		}

		$wsdls = array(
			'orderWSDL'    => self::PROD_ORDER_WSDL,
			'confinedWSDL' => self::PROD_CONFINED_WSDL
		);

		if ($this->options['testMode']) {
			$wsdls = array(
				'orderWSDL'    => self::TEST_ORDER_WSDL,
				'confinedWSDL' => self::TEST_CONFINED_WSDL
			);
		}

		return $wsdls[$type]; 
	}
	
	public function getSoapClient($wsdl, $options = array('trace' => 1, "exceptions" => 0), $flush = false) {
		if (in_array($wsdl, array(self::ORDER_WSDL_TYPE, self::CONFINED_WSDL_TYPE))) {
			$wsdl = $this->getSoapWSDL($wsdl);
		}
		
		if (!isset($this->clients[$wsdl]) || $flush) {
			$this->clients[$wsdl] = new \SoapClient($wsdl, $options);
		}

		return $this->clients[$wsdl];
	}	
	
	public function createHash($values) {
		$str = trim(implode("", $values));
		return md5( $str . $this->parameters['encryptionKey']);
	}

	
	//--------------------------------------------------------------------------
	//
	//  Serializable implementation
	//
	//-------------------------------------------------------------------------	
	
	/**
	 * Serialize
	 * 
	 * @return string
	 */
	public function serialize() {
		if (!property_exists($this, 'initialized')) {
			return null;
		}
		
		return serialize(array(
			// 'defaultParameters' => self::$defaultParameters,
			// 'defaultOptions' => self::$defaultOptions,
			'parameters' => $this->parameters,
			'options'    => $this->options,
			'status'     => property_exists($this, 'status') ? $this->status : null
		));
	}
	
	/**
	 * Unserialize
	 * 
	 * @param mixed $data
	 */
	public function unserialize($data) {		
		$values = unserialize($data);
		
		// self::$defaultParameters = $values['defaultParameters'];
		// self::$defaultOptions    = $values['defaultOptions'];
		foreach (array('parameters', 'options', 'status') as $key) {
			$this->{$key} = $values[$key];
		}
		$this->initialized = true;
	}
	
}
