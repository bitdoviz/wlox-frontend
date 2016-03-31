<?php
class Currencies {
	public static function getMain() {
		global $CFG;
	
		$main_crypto = 0;
		$main_fiat = 0;
	
		foreach ($CFG->currencies as $currency_id => $currency) {
			if (!is_numeric($currency_id) || $currency['is_main'] != 'Y')
				continue;
				
			if ($currency['is_crypto'] == 'Y')
				$main_crypto = $currency_id;
			else
				$main_fiat = $currency_id;
		}
	
		if (!$main_crypto)
			$main_crypto = $CFG->currencies['BTC']['id'];
		if (!$main_fiat)
			$main_fiat = $CFG->currencies['USD']['id'];
	
		$return = array('crypto'=>$main_crypto,'fiat'=>$main_fiat);	
		return $return;
	}
	
	public static function getCryptos() {
		global $CFG;
	
		$cryptos = array();
		foreach ($CFG->currencies as $currency_id => $currency) {
			if (!is_numeric($currency_id) || $currency['is_crypto'] != 'Y')
				continue;
	
			$cryptos[] = $currency_id;
		}
		return $cryptos;
	}
	
	public static function getNotConvertible() {
		global $CFG;
	
		$not = array();
		foreach ($CFG->currencies as $currency_id => $currency) {
			if (!is_numeric($currency_id) || $currency['not_convertible'] != 'Y')
				continue;
	
			$not[] = $currency_id;
		}
		return $not;
	}
	
	public static function convertTo($amount,$from_currency,$to_currency,$fee_type=false) {
		global $CFG;
		
		if (!($amount > 0))
			return $amount;

		if (!empty($CFG->currencies[$from_currency]))
			$from_info = $CFG->currencies[$from_currency];
		else
			return $amount;
	
		if (!empty($CFG->currencies[$to_currency]))
			$to_info = $CFG->currencies[$to_currency];
		else
			return $amount;
	
		if ($from_info['currency'] == $to_info['currency'])
			return $amount;
		
		$markup = 0;
		if ($fee_type == 'up')
			$markup = 1;
		else if ($fee_type == 'down')
			$markup = -1;
	
		$conversion = ($from_info['currency'] == 'USD') ? $from_info['usd_ask'] : $from_info['usd_ask'] / $to_info['usd_ask'];
		return round((($amount * $conversion) + (($amount * $conversion) * $CFG->currency_conversion_fee * $markup)),($to_info['is_crypto'] == 'Y' ? 8 : 2),PHP_ROUND_HALF_UP);
	}
}