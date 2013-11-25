PAYEX PHP CLASS
===============

A php5 module for simple transaction(s) with PayEx.

This module is a rewrite of [cobrax/payex](https://github.com/cobraz/payex) and intended for usage with php5 only and as composer compatible module.


Dependencies
---------------

PHP >= 5.3


Installation
---------------

If you're using Composer to manage libraries, include this package in your composer.json

	"require" : {
	    "furusystems/payex-php" : "0.1.*"
	}


Or just load this library in your PHP project by including PayEx.php

	require_once('../your/project/directory/here/lib/PayEx/PayEx.php');



Usage Example
---------------

// Start transaction
	
	use PayEx\PayEx;
	...
	
	$parameters = array(
		'accountNumber' => '<accountNumber>',
		'encryptionKey' => '<encryptionKey>',
		'purchaseOperation' => PayEx::TRANSACTION_AUTHORIZATION,
		'view'           => 'CREDITCARD',
		'currency'       => 'NOK',
		'vat'            => '0',
		'orderID'        => $orderID,
		'productNumber'  => $productNumber,
		'price'          => '100',
		'description'    => 'blah',
		'returnUrl'       => 'http://example.com/return-url',
		# 'cancelUrl'       => 'http://example.com/cancel-url',
	);
	# PayEx::setDefaultOption('testMode', true);
		
	$client = PayEx::transaction($parameters);
	if ($client->transactionIsOk()) {
		$session->set('payex', $payex);
		$client->transactionRedirect();
	}

	
// Transaction return
    
    use PayEx\PayEx;
	...
	
    $payex = $session->get('payex');

    // 1. Explisit way
    // $orderRef = $_REQUEST['orderRef'];
    // $status   = $payex->transactionComplete($orderRef);

    // 2. Implicit
    $payex->transactionComplete(); // orderRef stored in this payex transaction instance



Licence
-------

The project is released under GNU-license. The class
are provided as is. And the class are in no way an offical
class by PayEx. The initial creators are however a sertified partner with PayEx.