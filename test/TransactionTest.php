<?php
use PayEx\PayEx;

require_once('src/PayEx/PayEx.php');

class TransactionTest extends PHPUnit_Framework_TestCase {
	/* TODO... */
	
	public function testSoapClient() {
		
		$client     = new PayEx;
		/// PayEx->setOption('testMode', true);
		
		$soapClient = $client->getSoapClientByWSDLType( PayEx::ORDER_WSDL_TYPE );
		$functions  = $soapClient->__getFunctions();
	}
	
}
