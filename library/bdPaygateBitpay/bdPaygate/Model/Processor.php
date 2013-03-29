<?php

class bdPaygateBitpay_bdPaygate_Model_Processor extends XFCP_bdPaygateBitpay_bdPaygate_Model_Processor
{
	public function getProcessorNames()
	{
		$names = parent::getProcessorNames();
		
		$names['bitpay'] = 'bdPaygateBitpay_Processor';
		
		return $names;
	}
}