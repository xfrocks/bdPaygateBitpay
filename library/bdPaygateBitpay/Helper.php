<?php

class bdPaygateBitpay_Helper
{
	public static function getInvoiceUrl($apiKeyId, $price, $currency,
			$posData = false, $notificationUrl = false, $redirectUrl = false,
			$itemDesc = false, $itemCode = false
	) {
		$post = array();
		$post['price'] = $price;
		$post['currency'] = $currency;

		if (!empty($posData)) $post['posData'] = $posData;
		if (!empty($notificationUrl)) $post['notificationURL'] = $notificationUrl;
		if (!empty($redirectUrl)) $post['redirectURL'] = $redirectUrl;
		if (!empty($itemDesc)) $post['itemDesc'] = $itemDesc;
		if (!empty($itemCode)) $post['itemCode'] = $itemCode;

		$post = json_encode($post);

		$response = self::request('https://bitpay.com/api/invoice/', $apiKeyId, $post);

		if (empty($response) OR !is_array($response) OR empty($response['url']))
		{
			return false;
		}

		return $response['url'];
	}

	private static function request($url, $apiKey, $post = false) {
		$curl = curl_init($url);
		$length = 0;
		if ($post)
		{	
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			$length = strlen($post);
		}

		$uname = base64_encode($apiKey);
		$header = array(
			'Content-Type: application/json',
			"Content-Length: $length",
			"Authorization: Basic $uname",
		);

		curl_setopt($curl, CURLOPT_PORT, 443);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC) ;
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);

		$responseString = curl_exec($curl);

		if($responseString == false) {
			$response = curl_error($curl);
		} else {
			$response = json_decode($responseString, true);
		}

		curl_close($curl);

		return $response;
	}
}