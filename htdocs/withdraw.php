<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$currencies = Settings::sessionCurrency();
API::add('Wallets','getWallet',array($currencies['c_currency']));
$query = API::send();

$wallet = $query['Wallets']['getWallet']['results'][0];
$c_currency_info = $CFG->currencies[$currencies['c_currency']];
$page1 = (!empty($_REQUEST['page'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['page']) : false;
$btc_address1 = (!empty($_REQUEST['btc_address'])) ?  preg_replace("/[^\da-z]/i", "",$_REQUEST['btc_address']) : false;
$btc_amount1 = (!empty($_REQUEST['btc_amount'])) ? String::currencyInput($_REQUEST['btc_amount']) : 0;
$btc_total1 = ($btc_amount1 > 0) ? $btc_amount1 - $wallet['bitcoin_sending_fee'] : 0;
$account1 = (!empty($_REQUEST['account'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['account']) : false;
$fiat_amount1 = (!empty($_REQUEST['fiat_amount'])) ? String::currencyInput($_REQUEST['fiat_amount']) : 0;
$fiat_total1 = ($fiat_amount1 > 0) ? $fiat_amount1 - $CFG->fiat_withdraw_fee : 0;
$token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
$authcode1 = (!empty($_REQUEST['authcode'])) ? $_REQUEST['authcode'] : false;
$request_2fa = false;
$no_token = false;

$ask_confirm = false;
$passed_uniq = false;
$confirmed = (!empty($_REQUEST['confirmed'])) ? $_REQUEST['confirmed'] : false;;
$gateway_type1 = false;
$gateway_currency1 = $currencies['currency'];
$gateway_amount1 = false;
$card_type1 = false;
$card_name1 = false;
$card_number1 = false;
$card_expiration_month1 = false;
$card_expiration_year1 = false;
$gateway_user1 = false;
$gateway_pass1 = false;
$gateway_bank_account1 = false;
$gateway_bank_iban1 = false;
$gateway_bank_swift1 = false;
$gateway_bank_name1 = false;
$gateway_bank_city1 = false;
$gateway_bank_country1 = false;

if ((!empty($_REQUEST['bitcoins']) || !empty($_REQUEST['fiat'])) && !$token1) {
	if (!empty($_REQUEST['request_2fa'])) {
		if (!($token1 > 0)) {
			$no_token = true;
			$request_2fa = true;
			Errors::add(Lang::string('security-no-token'));
		}
	}

	if ((User::$info['verified_authy'] == 'Y'|| User::$info['verified_google'] == 'Y') && ((User::$info['confirm_withdrawal_2fa_btc'] == 'Y' && $_REQUEST['bitcoins']) || (User::$info['confirm_withdrawal_2fa_bank'] == 'Y' && $_REQUEST['fiat']))) {
		if (!empty($_REQUEST['send_sms']) || User::$info['using_sms'] == 'Y') {
			if (User::sendSMS()) {
				$sent_sms = true;
				Messages::add(Lang::string('withdraw-sms-sent'));
			}
		}
		$request_2fa = true;
	}
}

if ($authcode1) {
	API::add('Requests','emailValidate',array(urlencode($authcode1)));
	$query = API::send();

	if ($query['Requests']['emailValidate']['results'][0]) {
		Link::redirect('withdraw.php?message=withdraw-2fa-success');
	}
	else {
		Errors::add(Lang::string('settings-request-expired'));
	}
}

API::add('Content','getRecord',array('deposit-no-bank'));
API::add('User','getAvailable');
API::add('Requests','get',array(1,false,false,1));
API::add('Requests','get',array(false,$page1,15,1));
API::add('Gateways','get');
API::add('Gateways','getCards');
API::add('User','getCountries');
/*
API::add('BankAccounts','get');
if ($account1 > 0)
	API::add('BankAccounts','getRecord',array($account1));
if ($btc_address1)
	API::add('BitcoinAddresses','validateAddress',array($currencies['c_currency'],$btc_address1));
	*/
$query = API::send();

//$bank_instructions = $query['Content']['getRecord']['results'][0];
//$bank_accounts = $query['BankAccounts']['get']['results'][0];
$user_available = $query['User']['getAvailable']['results'][0];
$total = $query['Requests']['get']['results'][0];
$requests = $query['Requests']['get']['results'][1];
$gateways = $query['Gateways']['get']['results'][0];
$gateway_cards = $query['Gateways']['getCards']['results'][0];
$countries = $query['User']['getCountries']['results'][0];
$pagination = $pagination = Content::pagination('deposit.php',$page1,$total,15,5,false);

$gateway_types = array();
if ($gateways) {
	foreach ($gateways as $gateway) {
		$gateway_types[$gateway['gateway_type']] = array('key'=>$gateway['type_key'],'name'=>$gateway['type_name']);
	}
}

if ($account1 > 0) {
	$bank_account = $query['BankAccounts']['getRecord']['results'][0];
}
elseif ($bank_accounts) {
	$key = key($bank_accounts);
	$bank_account = $bank_accounts[$key];	
}

if ($bank_account) {
	$currency_info = $CFG->currencies[$bank_account['currency']];
	$currency1 = $currency_info['currency'];
}

$pagination = Content::pagination('withdraw.php',$page1,$total,15,5,false);

if ($CFG->withdrawals_status == 'suspended')
	Errors::add(Lang::string('withdrawal-suspended'));

if (!empty($_REQUEST['bitcoins'])) {
	if (($btc_amount1 - $wallet['bitcoin_sending_fee']) < 0.00000001)
		Errors::add(Lang::string('withdraw-amount-zero'));
	if ($btc_amount1 > $user_available[$c_currency_info['currency']])
		Errors::add(str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('withdraw-too-much')));
	if (!$query['BitcoinAddresses']['validateAddress']['results'][0])
		Errors::add(str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('withdraw-address-invalid')));
	
	if (!is_array(Errors::$errors)) {
		if (User::$info['confirm_withdrawal_email_btc'] == 'Y' && !$request_2fa && !$token1) {
			API::add('Requests','insert',array($c_currency_info['id'],$btc_amount1,$btc_address1));
			$query = API::send();
			Link::redirect('withdraw.php?notice=email');
		}
		elseif (!$request_2fa) {
			API::token($token1);
			API::add('Requests','insert',array($c_currency_info['id'],$btc_amount1,$btc_address1));
			$query = API::send();
			
			if ($query['error'] == 'security-com-error')
				Errors::add(Lang::string('security-com-error'));
			
			if ($query['error'] == 'authy-errors')
				Errors::merge($query['authy_errors']);
			
			if ($query['error'] == 'security-incorrect-token')
				Errors::add(Lang::string('security-incorrect-token'));
			
			if (!is_array(Errors::$errors)) {
				if ($query['Requests']['insert']['results'][0]) {
					if ($token1 > 0)
						Link::redirect('withdraw.php?message=withdraw-2fa-success');
					else
						Link::redirect('withdraw.php?message=withdraw-success');
				}	
			}
			elseif (!$no_token) {
				$request_2fa = true;
			}
		}
	}
	elseif (!$no_token) {
		$request_2fa = false;
	}
}
elseif (!empty($_REQUEST['fiat'])) {
	if (empty($_SESSION["deposit_uniq"]) || empty($_REQUEST['uniq']) || !in_array($_REQUEST['uniq'],$_SESSION["deposit_uniq"])) {
		Errors::add('Page expired.');
	}
	else
		$passed_uniq = true;
	
	$gateway_type1 = preg_replace("/[^a-z_]/","",$_REQUEST['gateway_type']);
	$gateway_currency1 = preg_replace("/[^0-9]/","",$_REQUEST['gateway_currency']);
	$gateway_amount1 = String::currencyInput($_REQUEST['gateway_amount']);
	$card_type1 = preg_replace("/[^0-9]/","",$_REQUEST['card_type']);
	$card_name1 = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u", "",$_REQUEST['card_name']);
	$card_number1 = preg_replace("/[^0-9]/", "",$_REQUEST['card_number']);
	$card_expiration_month1 = preg_replace("/[^0-9]/","",$_REQUEST['card_expiration_month']);
	$card_expiration_year1 = preg_replace("/[^0-9]/","",$_REQUEST['card_expiration_year']);
	$card_cvv1 = preg_replace("/[^0-9]/","",$_REQUEST['card_cvv']);
	$card_email1 = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u","",$_REQUEST['card_email']);
	$card_phone1 = preg_replace("/[^0-9\-]/","",$_REQUEST['card_phone']);
	$card_address11 = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u","",$_REQUEST['card_address1']);
	$card_address21 = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u","",$_REQUEST['card_address2']);
	$card_city1 = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u","",$_REQUEST['card_city']);
	$card_state1 = preg_replace("/[^\pL a-zA-Z0-9@\s\._-]/u","",$_REQUEST['card_state']);
	$card_country1 = preg_replace("/[^0-9]/","",$_REQUEST['card_country']);
	$card_zip1 = preg_replace("/[^0-9]/","",$_REQUEST['card_zip']);
	$gateway_id1 = preg_replace("/[^0-9]/","",$_REQUEST['gateway_id']);
	$gateway_user1 = preg_replace($CFG->pass_regex,"",$_REQUEST['gateway_user']);
	$gateway_pass1 = preg_replace($CFG->pass_regex,"",$_REQUEST['gateway_pass']);
	$gateway_bank_account1 = preg_replace("/[^0-9]/","",$_REQUEST['gateway_bank_account']);
	$gateway_bank_iban1 = preg_replace("/[^0-9]/","",$_REQUEST['gateway_bank_iban']);
	$gateway_bank_swift1 = preg_replace("/[^0-9]/","",$_REQUEST['gateway_bank_swift']);
	$gateway_bank_name1 = preg_replace("/[^\pL a-zA-Z0-9@\s\._- ]/u","",$_REQUEST['gateway_bank_name']);
	$gateway_bank_city1 = preg_replace("/[^\pL a-zA-Z0-9@\s\._- ]/u","",$_REQUEST['gateway_bank_city']);
	$gateway_bank_country1 = preg_replace("/[^0-9]/","",$_REQUEST['gateway_bank_country']);
	
	$info = array();
	$info['gateway_type'] = $gateway_type1;
	$info['gateway_currency'] = $gateway_currency1;
	$info['gateway_amount'] = $gateway_amount1;
	$info['card_type'] = $card_type1;
	$info['card_name'] = $card_name1;
	$info['card_number'] = $card_number1;
	$info['card_expiration_month'] = $card_expiration_month1;
	$info['card_expiration_year'] = $card_expiration_year1;
	$info['card_cvv'] = $card_cvv1;
	$info['card_email'] = $card_email1;
	$info['card_phone'] = $card_phone1;
	$info['card_address1'] = $card_address11;
	$info['card_address2'] = $card_address21;
	$info['card_city'] = $card_city1;
	$info['card_state'] = $card_state1;
	$info['card_country'] = $card_country1;
	$info['card_zip'] = $card_zip1;
	$info['gateway_id'] = $gateway_id1;
	$info['gateway_user'] = $gateway_user1;
	$info['gateway_pass'] = $gateway_pass1;
	$info['gateway_bank_account'] = $gateway_bank_account1;
	$info['gateway_bank_iban'] = $gateway_bank_iban1;
	$info['gateway_bank_swift'] = $gateway_bank_swift1;
	$info['gateway_bank_name'] = $gateway_bank_name1;
	$info['gateway_bank_city'] = $gateway_bank_city1;
	$info['gateway_bank_country'] = $gateway_bank_country1;
	
	if ($passed_uniq) {
		if (!$confirmed) {
			API::add('Gateways','withdrawPreconditions',array($info));
			$query = API::send();
			$errors1 = $query['Gateways']['withdrawPreconditions']['results'][0];
			if (!empty($errors1['error']))
				Errors::add($errors1['error']['message']);
			else if (!empty($errors1['offsite']))
				Link::redirect($errors1['offsite'],$errors1['offsite_vars']);
			else
				$ask_confirm = true;
		}
		else {
			if (User::$info['confirm_withdrawal_email_bank'] == 'Y' && !$request_2fa && !$token1) {
				API::add('Gateways','processWithdraw',array($info));
				$query = API::send();
				Link::redirect('withdraw.php?notice=email');
			}
			elseif (!$request_2fa) {
				API::token($token1);
				API::add('Gateways','processWithdraw',array($info));
				$query = API::send();
				$operations = $query['Gateways']['processWithdraw']['results'][0];
					
				if ($query['error'] == 'security-com-error')
					Errors::add(Lang::string('security-com-error'));
				
				if ($query['error'] == 'authy-errors')
					Errors::merge($query['authy_errors']);
					
				if ($query['error'] == 'security-incorrect-token')
					Errors::add(Lang::string('security-incorrect-token'));
				
				if (!is_array(Errors::$errors)) {
					if (!empty($operations['error'])) {
						Errors::add($operations['error']['message']);
					}
					else if ($operations['new_order'] > 0) {
						$_SESSION["deposit_uniq"] = md5(uniqid(mt_rand(),true));
					
						if ($token1 > 0)
							Link::redirect('withdraw.php?message=withdraw-2fa-success');
						else
							Link::redirect('withdraw.php?message=withdraw-success');
					}
				}
				elseif (!$no_token) {
					$request_2fa = true;
				}
			}
		}
	}
}

if (!empty($_REQUEST['message'])) {
	if ($_REQUEST['message'] == 'withdraw-2fa-success')
		Messages::add(Lang::string('withdraw-2fa-success'));
	elseif ($_REQUEST['message'] == 'withdraw-success')
		Messages::add(Lang::string('withdraw-success'));
}

if (!empty($_REQUEST['notice']) && $_REQUEST['notice'] == 'email')
	$notice = Lang::string('withdraw-email-notice');

$page_title = Lang::string('withdraw');

if (empty($_REQUEST['bypass'])) {
	$_SESSION["withdraw_uniq"][time()] = md5(uniqid(mt_rand(),true));
	include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="withdraw.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_right">
		<? Errors::display(); ?>
		<? Messages::display(); ?>
		<?= (!empty($notice)) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
		<div class="testimonials-4">
			<? if (!$request_2fa) { ?>
			<div class="one_half">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-btc fa-2x"></i></span>
						<span class="right"><?= Lang::string('withdraw-bitcoins') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="withdraw.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<div class="calc dotted">
								<div class="label"><?= str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('sell-btc-available')) ?></div>
								<div class="value"><?= String::currency($user_available[$c_currency_info['currency']],true) ?> <?= $c_currency_info['currency'] ?></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="param">
								<label for="c_currency"><?= Lang::string('withdraw-withdraw') ?></label>
								<select id="c_currency" name="currency">
								<?
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $key => $currency) {
										if (is_numeric($key) || $currency['is_crypto'] != 'Y')
											continue;
										
										echo '<option '.(($currency['id'] == $currencies['c_currency']) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="btc_address"><?= Lang::string('withdraw-send-to-address') ?></label>
								<input type="text" id="btc_address" name="btc_address" value="<?= $btc_address1 ?>" />
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="btc_amount"><?= Lang::string('withdraw-send-amount') ?></label>
								<input type="text" id="btc_amount" name="btc_amount" value="<?= String::currency($btc_amount1,true) ?>" />
								<div class="qualify"><?= $c_currency_info['currency'] ?></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="calc">
								<div class="label"><?= Lang::string('withdraw-network-fee') ?> <a title="<?= Lang::string('withdraw-network-fee-explain') ?>" href="javascript:return false;"><i class="fa fa-question-circle"></i></a></div>
								<div class="value"><span id="withdraw_btc_network_fee"><?= String::currencyOutput($wallet['bitcoin_sending_fee']) ?></span> <?= $c_currency_info['currency'] ?></div>
								<div class="clear"></div>
							</div>
							<div class="calc bigger">
								<div class="label">
									<span id="withdraw_btc_total_label"><?= str_replace('[c_currency]',$c_currency_info['currency'],Lang::string('withdraw-btc-total')) ?></span>
								</div>
								<div class="value"><span id="withdraw_btc_total"><?= String::currency($btc_total1,true) ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<input type="hidden" name="bitcoins" value="1" />
							<input type="submit" name="submit" value="<?= Lang::string('withdraw-send-bitcoins') ?>" class="but_user" />
						</div>
					</form>
					<div class="clear"></div>
				</div>
			</div>
			<div class="one_half last">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-money fa-2x"></i></span>
						<span class="right"><?= Lang::string('withdraw-fiat') ?></span>
					</h3>
					<div class="clear"></div>
					<form id="buy_form" action="withdraw.php" method="POST">
						<div class="buyform">
							<div class="spacer"></div>
							<div class="param">
								<label for="gateway_type"><?= Lang::string('gateway-type-cashout') ?></label>
								<select id="gateway_type" name="gateway_type">
								<?
								$i = 1;
								if ($gateway_types) {
									foreach ($gateway_types as $type) {
										echo '<option '.(($gateway_type1 == $type['key'] || (!$gateway_type1 && $i == 1)) ? 'selected="selected"' : '').' value="'.$type['key'].'">'.(($type['key'] == 'credit_card') ? Lang::string('gateway-cashout-card') : $type['name']).'</option>';
										++$i;
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="gateway_currency"><?= Lang::string('gateway-currency-cashout') ?></label>
								<select id="gateway_currency" name="gateway_currency">
								<?
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $key => $currency) {
										if (is_numeric($key) || $currency['is_crypto'] == 'Y')
											continue;
											
										echo '<option '.(($currency['id'] == $gateway_currency1) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="gateway_amount"><?= Lang::string('gateway-amount-cashout') ?></label>
								<input name="gateway_amount" id="gateway_amount" type="text" value="<?= String::currencyOutput($gateway_amount1) ?>" />
								<div class="qualify"><span class="currency_abbr"><?= $currency_info['currency'] ?></span></div>
								<div class="clear"></div>
							</div>
							<div class="credit_card_format hide_section" <?= (!$gateway_type1 || $gateway_type1 == 'credit_card') ? '' : 'style="display:none;"' ?>>
								<div class="param">
									<label for="card_type"><?= Lang::string('gateway-card-type') ?></label>
									<select id="card_type" name="card_type">
									<?
									if ($gateway_cards) {
										$i = 1;
										foreach ($gateway_cards as $card) {
											if ($i == 1) {
												$i++;
												continue;
											}
											
											echo '<option '.(($card['id'] == $card_type1) ? 'selected="selected"' : '').' value="'.$card['id'].'">'.$card['name'].'</option>';
											$i++;
										}
									}	
									?>
									</select>
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_name"><?= Lang::string('gateway-card-name') ?></label>
									<input name="card_name" id="card_name" type="text" value="<?= $card_name1 ?>" />
									<div class="qualify"><i class="fa fa-user"></i></div>
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_number"><?= Lang::string('gateway-card-number') ?></label>
									<input name="card_number" id="card_number" type="text" value="<?= $card_number1 ?>" />
									<div class="qualify"><i class="fa fa-credit-card"></i></div>
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_email"><?= Lang::string('gateway-card-email') ?></label>
									<input name="card_email" id="card_email" type="text" value="<?= $card_email1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_phone"><?= Lang::string('gateway-card-phone') ?></label>
									<input name="card_phone" id="card_phone" type="text" value="<?= $card_phone1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_address1"><?= Lang::string('gateway-card-address1') ?></label>
									<input name="card_address1" id="card_address1" type="text" value="<?= $card_address11 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_address2"><?= Lang::string('gateway-card-address2') ?></label>
									<input name="card_address2" id="card_address2" type="text" value="<?= $card_address21 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_city"><?= Lang::string('gateway-card-city') ?></label>
									<input name="card_city" id="card_city" type="text" value="<?= $card_city1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_state"><?= Lang::string('gateway-card-state') ?></label>
									<input name="card_state" id="card_state" type="text" value="<?= $card_state1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_country"><?= Lang::string('gateway-card-country') ?></label>
									<select id="card_country" name="card_country">
									<?
									if ($countries) {
										foreach ($countries as $country) {
											echo '<option '.(($country['id'] == $card_country1) ? 'selected="selected"' : '').' value="'.$country['id'].'">'.$country['name'].'</option>';
										}
									}	
									?>
									</select>
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_zip"><?= Lang::string('gateway-card-zip') ?></label>
									<input name="card_zip" id="card_zip" type="text" value="<?= $card_zip1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_expiration"><?= Lang::string('gateway-card-expiration') ?></label>
									<div class="param_two" id="card_expiration">
										<select id="card_expiration_month" name="card_expiration_month">
										<? 
										for ($i = 1;$i <= 12;$i++) {
											$month_num = str_pad($i,2,'0',STR_PAD_LEFT);
											echo '<option '.(($i == $card_expiration_month1) ? 'selected="selected"' : '').' value="'.$i.'">'.$month_num.' - '.date("F",mktime(0,0,0,$i,1,1)).'</option>';
										}
										?>
										</select>
									</div>
									<div class="param_two">
										<select id="card_expiration_year" name="card_expiration_year">
										<? 
										foreach (range(date('Y'),(date('Y') + 20)) as $year) {
											echo '<option '.(($year == $card_expiration_year1) ? 'selected="selected"' : '').' value="'.$year.'">'.$year.'</option>';
										}
										?>
										</select>
									</div>
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="card_cvv"><?= Lang::string('gateway-card-cvv') ?></label>
									<div class="param_three">
										<input name="card_cvv" id="card_cvv" type="text" value="<?= $card_cvv1 ?>" />
									</div>
									<div class="param_three2">
										<img class="cvv" src="images/cvn.png" />
									</div>
									<div class="clear"></div>
								</div>
							</div>
							<div class="gateway_format hide_section" <?= ($gateway_type1 != 'gateway') ? 'style="display:none;"' : '' ?>>
								<div class="param">
									<label for="gateway_id"><?= Lang::string('gateway-gateway-name') ?></label>
									<select id="gateway_id" name="gateway_id">
									<?
									if ($gateways) {
										foreach ($gateways as $gateway) {
											if ($gateway['type_key'] != 'gateway')
												continue;
											
											echo '<option '.(($gateway['id'] == $gateway_id1) ? 'selected="selected"' : '').' value="'.$gateway['id'].'">'.$gateway['name'].'</option>';
										}
									}	
									?>
									</select>
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="gateway_user"><?= Lang::string('gateway-user') ?></label>
									<input name="gateway_user" id="gateway_user" type="text" value="<?= $gateway_user1 ?>" />
									<div class="qualify"><i class="fa fa-user"></i></div>
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="gateway_pass"><?= Lang::string('gateway-pass') ?></label>
									<input name="gateway_pass" id="gateway_pass" type="password" value="<?= $gateway_pass1 ?>" />
									<div class="qualify"><i class="fa fa-lock"></i></div>
									<div class="clear"></div>
								</div>
							</div>
							<div class="bank_account_format hide_section" <?= ($gateway_type1 != 'bank_accoutn') ? 'style="display:none;"' : '' ?>>
								<div class="param">
									<label for="gateway_bank_account"><?= Lang::string('gateway-bank-account') ?></label>
									<input name="gateway_bank_account" id="gateway_bank_account" type="text" value="<?= $gateway_bank_account1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="gateway_bank_iban"><?= Lang::string('gateway-bank-iban') ?></label>
									<input name="gateway_bank_iban" id="gateway_bank_iban" type="text" value="<?= $gateway_bank_iban1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="gateway_bank_swift"><?= Lang::string('gateway-bank-swift') ?></label>
									<input name="gateway_bank_swift" id="gateway_bank_swift" type="text" value="<?= $gateway_bank_swift1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="gateway_bank_name"><?= Lang::string('gateway-bank-name') ?></label>
									<input name="gateway_bank_name" id="gateway_bank_name" type="text" value="<?= $gateway_bank_name1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="gateway_bank_city"><?= Lang::string('gateway-bank-city') ?></label>
									<input name="gateway_bank_city" id="gateway_bank_city" type="text" value="<?= $gateway_bank_city1 ?>" />
									<div class="clear"></div>
								</div>
								<div class="param">
									<label for="gateway_bank_country"><?= Lang::string('gateway-country') ?></label>
									<select id="gateway_bank_country" name="gateway_bank_country">
									<?
									if ($countries) {
										foreach ($countries as $country) {
											echo '<option '.(($country['id'] == $gateway_bank_country1) ? 'selected="selected"' : '').' value="'.$country['id'].'">'.$country['name'].'</option>';
										}
									}	
									?>
									</select>
									<div class="clear"></div>
								</div>
							</div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<input type="submit" name="submit" value="<?= Lang::string('withdraw-withdraw') ?>" class="but_user" />
						</div>
					</form>
				</div>
			</div>
			<? } else { ?>
			<div class="content">
				<h3 class="section_label">
					<span class="left"><i class="fa fa-mobile fa-2x"></i></span>
					<span class="right"><?= Lang::string('security-enter-token') ?></span>
				</h3>
				<form id="enable_tfa" action="withdraw.php" method="POST">
					<input type="hidden" name="request_2fa" value="1" />
					<input type="hidden" name="account" value="<?= $account1 ?>" />
					<input type="hidden" name="fiat_amount" value="<?= String::currencyOutput($fiat_amount1) ?>" />
					<input type="hidden" name="btc_address" value="<?= $btc_address1 ?>" />
					<input type="hidden" name="btc_amount" value="<?= String::currencyOutput($btc_amount1) ?>" />
					<input type="hidden" name="bitcoins" value="<?= ($_REQUEST['bitcoins']) ? '1' : '' ?>" />
					<input type="hidden" name="fiat" value="<?= ($_REQUEST['fiat']) ? '1' : '' ?>" />
					<input type="hidden" name="uniq" value="<?= $_SESSION["withdraw_uniq"] ?>" />
					<div class="buyform">
						<div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="token"><?= Lang::string('security-token') ?></label>
								<input name="token" id="token" type="text" value="<?= $token1 ?>" />
								<div class="clear"></div>
							</div>
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-validate') ?>" class="but_user" /></li>
								<? if (User::$info['using_sms'] == 'Y') { ?>
								<li><input type="submit" name="sms" value="<?= Lang::string('security-resend-sms') ?>" class="but_user" /></li>
								<? } ?>
							</ul>
						</div>
					</div>
				</form>
                <div class="clear"></div>
			</div>
			<? } ?>
		</div>
		<div class="mar_top3"></div>
		<div class="clear"></div>
		<h3><?= Lang::string('withdrawal-recent') ?></h3>
		<div id="filters_area">
<? } ?>
        	<div class="table-style">
        		<table class="table-list trades" id="bids_list">
        			<tr>
        				<th>ID</th>
        				<th><?= Lang::string('deposit-date') ?></th>
        				<th><?= Lang::string('deposit-description') ?></th>
        				<th><?= Lang::string('deposit-amount') ?></th>
        				<th><?= Lang::string('withdraw-net-amount') ?></th>
        				<th><?= Lang::string('deposit-status') ?></th>
        			</tr>
        			<? 
        			if ($requests) {
						foreach ($requests as $request) {
							echo '
					<tr>
						<td>'.$request['id'].'</td>
						<td><input type="hidden" class="localdate" value="'.(strtotime($request['date'])/* + $CFG->timezone_offset*/).'" /></td>
						<td>'.$request['description'].'</td>
						<td>'.(($CFG->currencies[$request['currency']]['is_crypto'] == 'Y') ? String::currency($request['amount'],true).' '.$request['fa_symbol'] : $request['fa_symbol'].String::currency($request['amount'])).'</td>
    					<td>'.(($CFG->currencies[$request['currency']]['is_crypto'] == 'Y') ? String::currency((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee'])),true).' '.$request['fa_symbol'] : $request['fa_symbol'].String::currency((($request['net_amount'] > 0) ? $request['net_amount'] : ($request['amount'] - $request['fee'])))).'</td>
						<td>'.$request['status'].'</td>
					</tr>';
						}
					}
					else {
						echo '<tr><td colspan="6">'.Lang::string('withdraw-no').'</td></tr>';
					}
        			?>
        		</table>
			</div>
			<?= $pagination ?>
<? if (empty($_REQUEST['bypass'])) { ?>
		</div>
		<div class="mar_top5"></div>
	</div>
	<? include 'includes/sidebar_account.php'; ?>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>