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
		throw new XenForo_Exception('to be implemented');
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