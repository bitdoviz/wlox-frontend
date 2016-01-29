<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

$page1 = (!empty($_REQUEST['page'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['page']) : false;
$currencies = Settings::sessionCurrency();

API::add('BitcoinAddresses','get',array(false,$currencies['c_currency'],false,1,1));
API::add('Requests','get',array(1));
API::add('Requests','get',array(false,$page1,15));
API::add('Content','getRecord',array('deposit-bank-instructions'));
API::add('Content','getRecord',array('deposit-no-bank'));
API::add('Gateways','get');
API::add('Gateways','getCards');
API::add('User','getCountries');
$query = API::send();

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

if ($_REQUEST['gateway_type']) {
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
	
	if ($passed_uniq) {
		if (!$confirmed) {
			API::add('Gateways','depositPreconditions',array($gateway_type1,$gateway_currency1,$gateway_amount1,$gateway_id1,$card_type1,$card_name1,$card_number1,$card_expiration_month1,$card_expiration_year1,$card_cvv1,$card_email1,$card_phone1,$card_address11,$card_address21,$card_city1,$card_state1,$card_country1,$card_zip1,$gateway_user1,$gateway_pass1,$gateway_bank_account1,$gateway_bank_iban1,$gateway_bank_swift1,$gateway_bank_name1,$gateway_bank_city1,$gateway_bank_country1));
			$query = API::send();
			$errors1 = $query['Gateways']['depositPreconditions']['results'][0];
			if (!empty($errors1['error']))
				Errors::add($errors1['error']['message']);
			else
				$ask_confirm = true;
		}
		else {
			API::add('Gateways','processDeposit',array($gateway_type1,$gateway_currency1,$gateway_amount1,$gateway_id1,$card_type1,$card_name1,$card_number1,$card_expiration_month1,$card_expiration_year1,$card_cvv1,$card_email1,$card_phone1,$card_address11,$card_address21,$card_city1,$card_state1,$card_country1,$card_zip1,$gateway_user1,$gateway_pass1,$gateway_bank_account1,$gateway_bank_iban1,$gateway_bank_swift1,$gateway_bank_name1,$gateway_bank_city1,$gateway_bank_country1));
			$query = API::send();
			$operations = $query['Gateways']['processDeposit']['results'][0];
			
			if (!empty($operations['error'])) {
				Errors::add($operations['error']['message']);
			}
			else if ($operations['new_order'] > 0) {
				$_SESSION["deposit_uniq"][time()] = md5(uniqid(mt_rand(),true));
				if (count($_SESSION["deposit_uniq"]) > 3) {
					unset($_SESSION["deposit_uniq"][min(array_keys($_SESSION["deposit_uniq"]))]);
				}
			
				Link::redirect('deposit',array('transactions'=>$operations['transactions'],'new_order'=>1));
				exit;
			}
		}
	}
}

$currency_info = $CFG->currencies[$gateway_currency1];
$page_title = Lang::string('deposit');
setlocale(LC_TIME,$CFG->language.'_'.strtoupper($CFG->language));

if (empty($_REQUEST['bypass'])) {
	$_SESSION["deposit_uniq"][time()] = md5(uniqid(mt_rand(),true));
	if (count($_SESSION["deposit_uniq"]) > 3) {
		unset($_SESSION["deposit_uniq"][min(array_keys($_SESSION["deposit_uniq"]))]);
	}
	
	include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="deposit.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_right">
		<? Errors::display(); ?>
		<div class="testimonials-4">
			<div class="one_half">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-btc fa-2x"></i></span>
						<span class="right"><?= Lang::string('deposit-bitcoins') ?></span>
					</h3>
					<div class="clear"></div>
					<div class="buyform">
						<div class="spacer"></div>
						<div class="param">
							<label for="c_currency"><?= Lang::string('deposit-c-currency') ?></label>
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
							<label for="deposit_address"><?= Lang::string('deposit-send-to-address') ?></label>
							<input type="text" id="deposit_address" name="deposit_address" value="<?= $bitcoin_addresses[0]['address'] ?>" />
							<div class="clear"></div>
						</div>
						<div class="spacer"></div>
						<div class="calc">
							<img class="qrcode" src="includes/qrcode.php?code=<?= $bitcoin_addresses[0]['address'] ?>" />
						</div>
						<div class="spacer"></div>
						<div class="calc">
							<a class="item_label" href="bitcoin-addresses.php"><i class="fa fa-cog"></i> <?= Lang::string('deposit-manage-addresses') ?></a>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
			<div class="one_half last">
				<form id="deposit_form" action="deposit.php" method="POST">
					<div class="content">
						<? if (!$ask_confirm) { ?>
						<h3 class="section_label">
							<span class="left"><i class="fa fa-money fa-2x"></i></span>
							<span class="right"><?= Lang::string('deposit-fiat-instructions') ?></span>
						</h3>
						<div class="clear"></div>
						<div class="buyform">
							<div class="spacer"></div>
							<div class="param">
								<label for="gateway_type"><?= Lang::string('gateway-type') ?></label>
								<select id="gateway_type" name="gateway_type">
								<?
								$i = 1;
								if ($gateway_types) {
									foreach ($gateway_types as $type) {
										echo '<option '.(($gateway_type1 == $type['key'] || (!$gateway_type1 && $i == 1)) ? 'selected="selected"' : '').' value="'.$type['key'].'">'.$type['name'].'</option>';
										++$i;
									}
								}	
								?>
								</select>
								<div class="clear"></div>
							</div>
							<div class="param">
								<label for="gateway_currency"><?= Lang::string('gateway-currency') ?></label>
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
								<label for="gateway_amount"><?= Lang::string('gateway-amount') ?></label>
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
										foreach ($gateway_cards as $card) {
											echo '<option '.(($card['id'] == $card_type1) ? 'selected="selected"' : '').' value="'.$card['id'].'">'.$card['name'].'</option>';
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
							<input type="hidden" name="uniq" value="<?= end($_SESSION["deposit_uniq"]) ?>" />
							<input type="submit" name="submit" value="<?= Lang::string('deposit') ?>" class="but_user" />
						</div>
						<? } else { ?>
						<h3 class="section_label">
							<span class="left"><i class="fa fa-exclamation fa-2x"></i></span>
							<span class="right"><?= Lang::string('confirm-transaction') ?></span>
							<div class="clear"></div>
						</h3>
						<div class="clear"></div>
						<div class="balances" style="margin-left:0;">
							<input type="hidden" name="confirmed" value="1" />
							<input type="hidden" id="cancel" name="cancel" value="" />
							<input type="hidden" name="gateway_type" value="<?= $gateway_type1 ?>" />
							<input type="hidden" name="gateway_currency" value="<?= $gateway_currency1 ?>" />
							<input type="hidden" name="gateway_amount" value="<?= String::currencyOutput($gateway_amount1) ?>" />
							<input type="hidden" name="card_type" value="<?= $card_type1 ?>" />
							<input type="hidden" name="card_name" value="<?= $card_name1 ?>" />
							<input type="hidden" name="card_number" value="<?= $card_number1 ?>" />
							<input type="hidden" name="card_expiration_month" value="<?= $card_expiration_month1 ?>" />
							<input type="hidden" name="card_expiration_year" value="<?= $card_expiration_year1 ?>" />
							<input type="hidden" name="card_cvv" value="<?= $card_cvv1 ?>" />
							<input type="hidden" name="card_email" value="<?= $card_email1 ?>" />
							<input type="hidden" name="card_phone" value="<?= $card_phone1 ?>" />
							<input type="hidden" name="card_address1" value="<?= $card_address11 ?>" />
							<input type="hidden" name="card_address2" value="<?= $card_address21 ?>" />
							<input type="hidden" name="card_city" value="<?= $card_city1 ?>" />
							<input type="hidden" name="card_state" value="<?= $card_state1 ?>" />
							<input type="hidden" name="card_country" value="<?= $card_country1 ?>" />
							<input type="hidden" name="card_zip" value="<?= $card_zip1 ?>" />
							<input type="hidden" name="gateway_id" value="<?= $gateway_id1 ?>" />
							<input type="hidden" name="gateway_user" value="<?= $gateway_user1 ?>" />
							<input type="hidden" name="gateway_pass" value="<?= $gateway_pass1 ?>" />
							<input type="hidden" name="gateway_bank_account" value="<?= $gateway_bank_account1 ?>" />
							<input type="hidden" name="gateway_bank_iban" value="<?= $gateway_bank_iban1 ?>" />
							<input type="hidden" name="gateway_bank_swift" value="<?= $gateway_bank_swift1 ?>" />
							<input type="hidden" name="gateway_bank_name" value="<?= $gateway_bank_name1 ?>" />
							<input type="hidden" name="gateway_bank_city" value="<?= $gateway_bank_city1 ?>" />
							<input type="hidden" name="gateway_bank_country" value="<?= $gateway_bank_country1 ?>" />
							<input type="hidden" name="uniq" value="<?= end($_SESSION["deposit_uniq"]) ?>" />
						<? if ($gateway_type1 == 'credit_card') { ?>
							<div class="label"><?= Lang::string('gateway-type') ?></div>
							<div class="amount"><?= $gateway_cards[$card_type1]['name'] ?></div>
							<div class="label"><?= Lang::string('gateway-amount') ?></div>
							<div class="amount"><?= String::currency($gateway_amount1,2,8) ?> <?= $currency_info['currency'] ?></div>
							<div class="label"><?= Lang::string('gateway-card-name') ?></div>
							<div class="amount"><?= $card_name1 ?></div>
							<div class="label"><?= Lang::string('gateway-card-number') ?></div>
							<div class="amount"><?= $card_number1 ?></div>
							<div class="label"><?= Lang::string('gateway-card-expiration') ?></div>
							<div class="amount"><?= $card_expiration_month1.'/'.$card_expiration_year1 ?></div>
						<? } else if ($gateway_type1 == 'gateway') { ?>
							<div class="label"><?= Lang::string('gateway-type') ?></div>
							<div class="amount"><?= $gateways[$gateway_id1]['name'] ?></div>
							<div class="label"><?= Lang::string('gateway-name') ?></div>
							<div class="amount"><?= $gateway_user1 ?></div>
						<? } ?>
							<div class="mar_top2"></div>
							<ul class="list_empty">
								<li style="margin-bottom:0;"><input type="submit" name="submit" value="<?= Lang::string('confirm-deposit') ?>" class="but_user" /></li>
								<li style="margin-bottom:0;"><input id="cancel_transaction" type="submit" name="dont" value="<?= Lang::string('confirm-back') ?>" class="but_user grey" /></li>
							</ul>
							<div class="clear"></div>
						</div>
						<? } ?>
					</div>
				</form>
			</div>
		</div>
		<div class="mar_top3"></div>
		<div class="clear"></div>
		<h3><?= Lang::string('deposit-recent') ?></h3>
		<div id="filters_area">
<? } ?>
        	<div class="table-style">
        		<table class="table-list trades" id="bids_list">
        			<tr>
        				<th>ID</th>
        				<th><?= Lang::string('deposit-date') ?></th>
        				<th><?= Lang::string('deposit-description') ?></th>
        				<th><?= Lang::string('deposit-amount') ?></th>
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
						<td>'.$request['status'].'</td>
					</tr>';
						}
					}
					else {
						echo '<tr><td colspan="5">'.Lang::string('deposit-no').'</td></tr>';
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
