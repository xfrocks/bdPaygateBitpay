<?php

class bdPaygateBitpay_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygateBitpay_XenForo_ControllerAdmin_UserUpgrade
{
	public function actionIndex()
	{
		$optionModel = $this->getModelFromCache('XenForo_Model_Option');
		$optionModel->bdPaygateBitpay_hijackOptions();
		
		return parent::actionIndex();
	}
}