<?php
use PayEx\PayEx;

// TODO: Test with Cancel
// TODO: Test with Complete
// TODO: Test with Callback
require_once('src/PayEx/PayEx.php');

class TransactionTest extends PHPUnit_Framework_TestCase {
	
	const ACCOUNT_NUMBER    = '<accountNumber,0>';
	const ENCRYPTION_KEY    = '<encryptionKey>';
	const CLIENT_IP_ADDRESS = '127.0.0.0';
	
	/* TODO... */
	
	public function testSoapClient() {
		$client = new PayEx;
		$client->setOption('testMode', true);
		
		$soapClient = $client->getSoapClient( PayEx::ORDER_WSDL_TYPE );
		return $this->assertInstanceOf('\SoapClient', $soapClient);
	}
	
	public function testInitialize8() {
		$client = new PayEx;
		$client->setOption('testMode', true);
		
		$soapClient = $client->getSoapClient( PayEx::ORDER_WSDL_TYPE );
		$functions  = $soapClient->__getFunctions();
		
		return $this->assertContains('Initialize8Response Initialize8(Initialize8 $parameters)', $functions);
	}
	
	public function testTransaction() {
		
		$orderID = time();
		$productNumber = sha1($orderID);
		$parameters = array(
			'accountNumber' => self::ACCOUNT_NUMBER,
			'encryptionKey' => self::ENCRYPTION_KEY,
			'purchaseOperation' => PayEx::TRANSACTION_AUTHORIZATION,
			'view'           => 'CREDITCARD',
			'currency'       => 'NOK',
			'vat'            => '0',
			'orderID'        => $orderID,
			'productNumber'  => $productNumber,
			'price'          => '100',
			'description'    => 'blah',
			'clientIPAddress' => self::CLIENT_IP_ADDRESS,
			'returnUrl'       => 'http://127.0.0.1/return-url',
		);
		PayEx::setDefaultOption('testMode', true);
		
		$client = PayEx::transaction($parameters);
		$this->assertTrue($client->isOK());
	}
	
}
