<?php

class bdPaygateBitpay_XenForo_Model_Option extends XFCP_bdPaygateBitpay_XenForo_Model_Option
{
	// this property must be static because XenForo_ControllerAdmin_UserUpgrade::actionIndex
	// for no apparent reason use XenForo_Model::create to create the optionModel
	// (instead of using XenForo_Controller::getModelFromCache)
	private static $_bdPaygateBitpay_hijackOptions = false;
	
	public function getOptionsByIds(array $optionIds, array $fetchOptions = array())
	{
		if (self::$_bdPaygateBitpay_hijackOptions === true)
		{
			$optionIds[] = 'bdPaygateBitpay_apiKeyId';
			$optionIds[] = 'bdPaygateBitpay_exchangeRates';
		}
		
		$options = parent::getOptionsByIds($optionIds, $fetchOptions);
		
		self::$_bdPaygateBitpay_hijackOptions = false;

		return $options;
	}
	
	public function bdPaygateBitpay_hijackOptions()
	{
		self::$_bdPaygateBitpay_hijackOptions = true;
	}
}