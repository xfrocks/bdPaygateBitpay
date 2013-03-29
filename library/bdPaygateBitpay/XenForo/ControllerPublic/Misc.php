<?php

class bdPaygateBitpay_XenForo_ControllerPublic_Misc extends XFCP_bdPaygateBitpay_XenForo_ControllerPublic_Misc
{
	public function actionBitpay()
	{
		$input = $this->_input->filter(array(
			'amount' => XenForo_Input::STRING,
			'currency' => XenForo_Input::STRING,
			'itemName' => XenForo_Input::STRING,
			'itemId' => XenForo_Input::STRING,
			'returnUrl' => XenForo_Input::STRING,
			'callbackUrl' => XenForo_Input::STRING,
		));

		$apiKeyId = XenForo_Application::getOptions()->get('bdPaygateBitpay_apiKeyId');
		if (empty($apiKeyId))
		{
			throw new XenForo_Exception('API Key ID has not been configured');
		}

		$invoiceUrl = bdPaygateBitpay_Helper::getInvoiceUrl(
			$apiKeyId, $input['amount'], $input['currency'],
			json_encode(array('item_id' => $input['itemId'])), $input['callbackUrl'], $input['returnUrl'],
			$input['itemName']
		);
		if (empty($invoiceUrl))
		{
			throw new XenForo_Exception(new XenForo_Phrase('bdpaygatebitpay_unable_to_prepare_invoice'), true);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			$invoiceUrl
		);
	}
}