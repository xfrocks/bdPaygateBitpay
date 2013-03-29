<?php

class bdPaygateBitpay_Processor extends bdPaygate_Processor_Abstract
{
	public function isAvailable()
	{
		$options = XenForo_Application::getOptions();

		$apiKeyId = $options->get('bdPaygateBitpay_apiKeyId');
		if (empty($apiKeyId))
		{
			// no API Key ID
			return false;
		}

		if ($this->_sandboxMode())
		{
			// BitPay doesn't support Sandbox environment
			// so let's disable itself if the system is
			// expecting sandboxed pay gates
			return false;
		}

		return true;
	}

	public function getSupportedCurrencies()
	{
		return array(
			bdPaygate_Processor_Abstract::CURRENCY_USD,
			bdPaygate_Processor_Abstract::CURRENCY_CAD,
			bdPaygate_Processor_Abstract::CURRENCY_AUD,
			bdPaygate_Processor_Abstract::CURRENCY_GBP,
			bdPaygate_Processor_Abstract::CURRENCY_EUR,
		);
	}
	
	public function isRecurringSupported()
	{
		return false;
	}
	
	public function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId)
	{
		$amount = false;
		$currency = false;

		return $this->validateCallback2($request, $transactionId, $paymentStatus, $transactionDetails, $itemId, $amount, $currency);
	}

	public function validateCallback2(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId, &$amount, &$currency)
	{
		$rawData = file_get_contents("php://input");
		if (empty($rawData))
		{
			$this->_setError("Unable to receive data from BitPay server");
			return false;
		}
		$data = @json_decode($rawData, true);
		if (empty($data))
		{
			$this->_setError("Invalid data received from BitPay server");
			return false;
		}
		
		$input = new XenForo_Input($data);
		$filtered = $input->filter(array(
			'id' => XenForo_Input::STRING,
		));
		$invoice = bdPaygateBitpay_Helper::getInvoice(
			XenForo_Application::getOptions()->get('bdPaygateBitpay_apiKeyId'),
			$filtered['id']
		);
		if (empty($invoice))
		{
			$this->_setError("Unable to query invoice data from BitPay server");
			return false;
		}
		
		$transactionId = (!empty($invoice['id']) ? ('bitpay_' . md5($invoice['id'])) : '');
		$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_OTHER;
		$transactionDetails = array_merge(array('rawData' => $data), $invoice);
		$itemId = !empty($invoice['posData']['item_id']) ? $invoice['posData']['item_id'] : '';
		$amount = $invoice['price'];
		$currency = $invoice['currency'];
		$processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
		
		$log = $processorModel->getLogByTransactionId($transactionId);
		if (!empty($log) AND $log['log_type'] == bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED)
		{
			// perform additional check for log_type because BitPay may send us 2 notifications
			// one for confirmed status and another for complete status
			$this->_setError("Transaction {$transactionId} has already been processed");
			return false;
		}
		
		switch ($invoice['status'])
		{
			case 'new':
			case 'paid':
				$this->_setError('Waiting for confirmed/complete status...');
				return false;
			case 'confirmed':
			case 'complete':
				$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED;
				break;
			case 'expired':
			case 'invalid':
				$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED;
				break;
		}
		
		return true;
	}
	
	public function generateFormData($amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array())
	{
		$this->_assertAmount($amount);
		$this->_assertCurrency($currency);
		$this->_assertItem($itemName, $itemId);
		$this->_assertRecurring($recurringInterval, $recurringUnit);

		$formAction = XenForo_Link::buildPublicLink('canonical:misc/bitpay');
		$callToAction = new XenForo_Phrase('bdpaygatebitpay_call_to_action');
		$returnUrl = $this->_generateReturnUrl($extraData);
		$callbackUrl = $this->_generateCallbackUrl($extraData);
		$_xfToken = XenForo_Visitor::getInstance()->get('csrf_token_page');

		// callback URL must be HTTPS so we do a quick check and automatically switch 
		// http to https here...
		// TODO: let admin specify callback URL from AdminCP?
		if (preg_match('/^http:\/\//', $callbackUrl))
		{
			$callbackUrl = 'https://' . utf8_substr($callbackUrl, utf8_strlen('http://'));
		}
		
		$form = <<<EOF
<form action="{$formAction}" method="POST">
	<input type="hidden" name="amount" value="{$amount}" />
	<input type="hidden" name="currency" value="{$currency}" />
	<input type="hidden" name="itemName" value="{$itemName}" />
	<input type="hidden" name="itemId" value="{$itemId}" />
	<input type="hidden" name="returnUrl" value="{$returnUrl}" />
	<input type="hidden" name="callbackUrl" value="{$callbackUrl}" />
	<input type="hidden" name="_xfToken" value="{$_xfToken}" />
	
	<input type="submit" value="{$callToAction}" class="button" />
</form>
EOF;
		
		return $form;
	}
}