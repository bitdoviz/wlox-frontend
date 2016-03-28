<?php
include '../lib/common.php';
 
// first session values
$action = $_REQUEST['action'];
if (!$action) {
	$_SESSION['invoice_id'] = md5(uniqid(mt_rand(),true));
	if ($_REQUEST['currency'] && !empty($CFG->currencies[strtoupper($_REQUEST['currency'])])) {
		$_SESSION['api_currency'] = $CFG->currencies[strtoupper($_REQUEST['currency'])]['id'];
		if ($CFG->currencies[strtoupper($_REQUEST['currency'])]['is_crypto'] == 'Y')
			$_SESSION['c_currency'] = $CFG->currencies[strtoupper($_REQUEST['currency'])]['id'];
		else 
			$_SESSION['currency'] = $CFG->currencies[strtoupper($_REQUEST['currency'])]['id'];
	}
	if ($_REQUEST['amount'])
		$_SESSION['api_amount'] = $_REQUEST['amount'];
	if ($_REQUEST['user_email'])
		$_SESSION['user_email'] = $_REQUEST['user_email'];
}
else {
	if ($_REQUEST['currency'])
		$_SESSION['currency'] = $_REQUEST['currency'];
	if ($_REQUEST['c_currency'])
		$_SESSION['c_currency'] = $_REQUEST['c_currency'];
}

$invoice_id = (!empty($_SESSION['invoice_id'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_SESSION['invoice_id']) : false;
$api_key = (!empty($_REQUEST['api_key'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_REQUEST['api_key']) : false;
$api_amount = (!empty($_SESSION['api_amount'])) ? preg_replace("/[^0-9]/","",$_SESSION['api_amount']) : false;
$api_currency = (!empty($_SESSION['api_currency'])) ? preg_replace("/[^0-9]/","",$_SESSION['api_currency']) : false;
$user_email = (!empty($_SESSION['user_email'])) ? preg_replace("/[^0-9a-zA-Z@\.\!#\$%\&\*+_\~\?\-]/","",$_SESSION['user_email']) : false;
$address = (!empty($_SESSION['deposit_address'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_SESSION['deposit_address']) : false;
$currency = (!empty($_SESSION['currency'])) ? preg_replace("/[^0-9]/","",$_SESSION['currency']) : false;
$c_currency = (!empty($_SESSION['c_currency'])) ? preg_replace("/[^0-9]/","",$_SESSION['c_currency']) : false;
$usd_amount = (is_numeric($api_amount) && !empty($CFG->currencies[$api_currency])) ? $api_amount * $CFG->currencies[$api_currency]['usd_ask'] : false;

$exit = false;
$merchant_info = false;
$allowed_currencies = array();

// check if merchant and referrer are valid
if (!$api_key || strlen($api_key) != 16) {
	$exit = true;
}
else {
	API::add('APIKeys','getMerchantInfo',array($api_key));
	$result = API::send();
	$merchant_info = $result['APIKeys']['getMerchantInfo']['results'][0];
	
	if (!$merchant_info)
		$exit = true;
	/*
	 * DEBUG
	if (!$action && API::getReferrerDomain($merchant_info['merchant_url']) != API::getReferrerDomain($_SERVER['HTTP_REFERER']))
		$exit;
	*/
}

if ($exit)
	Link::redirect($CFG->baseurl);

// set currency within allowed or set to merchant's defaults
$merchant_currencies = explode(',',$merchant_info['merchant_currencies']);
if (is_array($merchant_currencies)) {
	$pref_c_currency = false;
	$pref_currency = false;
	
	foreach ($merchant_currencies as $currency1) {
		$c_info = $CFG->currencies[strtoupper($currency1)];
		if (!$c_info)
			continue;
			
		$allowed_currencies[] = $c_info['id'];
		if (!$c_currency && $c_info['is_crypto'] == 'Y')
			$c_currency = $c_info['id'];
		if (!$currency && $c_info['is_crypto'] != 'Y')
			$currency = $c_info['id'];
		if (!$pref_c_currency && $c_info['is_crypto'] == 'Y')
			$pref_c_currency = $c_info['id'];
		if (!$pref_currency && $c_info['is_crypto'] != 'Y')
			$pref_currency = $c_info['id'];
	}
	
	if (is_array($allowed_currencies) && count($allowed_currencies) > 0) {
		if (!in_array($c_currency,$allowed_currencies))
			$c_currency = ($pref_c_currency) ? $pref_c_currency : $pref_currency;
		if (!in_array($currency,$allowed_currencies))
			$currency = ($pref_currency) ? $pref_currency : $pref_c_currency;
	}
}

// actions switch
if (!$action) {
	API::add('APIKeys','newInvoice',array($invoice_id,$api_key,$user_email,$currency,$api_amount));
	$result = API::send();
}
	
if (!$action || $action == 'switch-crypto') {
	if (empty($_SESSION['deposit_addresses'][$c_currency])) {
		//API::add('BitcoinAddresses', 'getNew',array($c_currency,true,$api_key,$invoice_id));
		//$result = API::send();
		/* DEBUG
		$address = $result['BitcoinAddresses']['getNew']['results'][0];
		*/
		$address = 'LgG6oS1weBWjxafRLBi565P4T9dt4jgeaQ';
		$_SESSION['deposit_address'] = $address;
		$_SESSION['deposit_addresses'][$c_currency] = $address;
	}
	else 
		$_SESSION['deposit_address'] = $_SESSION['deposit_addresses'][$c_currency];
}
else if ($action == 'login') {
	$user1 = (!empty($_REQUEST['login']['user'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['login']['user']) : false;
	$pass1 = (!empty($_REQUEST['login']['pass'])) ? preg_replace($CFG->pass_regex, "",$_REQUEST['login']['pass']) : false;
	
	if (empty($user1)) {
		Errors::add(Lang::string('login-user-empty-error'));
	}
	
	if (empty($pass1)) {
		Errors::add(Lang::string('login-password-empty-error'));
	}
	
	if (!empty($_REQUEST['submitted']) && (empty($_SESSION["merchant_uniq"]) || $_SESSION["merchant_uniq"] != $_REQUEST['uniq']))
		Errors::add('Page expired.');
	
	if (!empty(User::$attempts) && User::$attempts > 3 && !empty($CFG->google_recaptch_api_key) && !empty($CFG->google_recaptch_api_secret)) {
		$captcha = new Form('captcha');
		$captcha->reCaptchaCheck(1);
		if (!empty($captcha->errors) && is_array($captcha->errors)) {
			Errors::add($captcha->errors['recaptcha']);
		}
	}
	
	if (!is_array(Errors::$errors)) {
		$login = User::logIn($user1,$pass1);
		if ($login && empty($login['error'])) {
			if (!empty($login['message']) && $login['message'] == 'awaiting-token') {
				$_SESSION["register_uniq"] = md5(uniqid(mt_rand(),true));
				Link::redirect('verify-token.php?bypass=deposit');
			}
			elseif (!empty($login['message']) && $login['message'] == 'logged-in') {
				$_SESSION["register_uniq"] = md5(uniqid(mt_rand(),true));
				Link::redirect('merchant-deposit.php?action=logged-in');
			}
		}
		elseif (!$login || !empty($login['error'])) {
			Errors::add(Lang::string('login-invalid-login-error'));
		}
	}
}
else if ($action == 'process') {
	if ($_REQUEST['process'] == 'crypto') {
		$address = (!empty($_REQUEST['address'])) ?  preg_replace("/[^\da-z]/i", "",$_REQUEST['address']) : false;
		if (!$c_currency || strlen($api_key) != 16)
			exit;
		
		API::add('Requests','get',array(false,false,false,false,$c_currency,false,false,false,$address,$api_key));
		$query = API::send();
		$deposits = $query['Requests']['get']['results'][0];
		$received = 0;
		
		if ($deposits) {
			foreach ($deposits as $deposit) {
				$received += $deposit['amount'];
			}
		}
		
		if ($received > 0) {
			
		}
	}
}

if ($action == 'logged-in' || User::isLoggedIn()) {
	API::add('User','getAvailable');
	$query = API::send();
	$available = $query['User']['getAvailable']['results'][0];
	
	if (empty($available[$CFG->currencies[$currency]['currency']])) {
		foreach ($allowed_currencies as $currency1) {
			if (!empty($available[$CFG->currencies[$currency1]['currency']]))
				$currency = $currency1;
		}
	}
}

$currency_info = $CFG->currencies[$currency];
$c_currency_info = $CFG->currencies[$c_currency];

$page_title = Lang::string('merchant-title');
$_SESSION["merchant_uniq"] = md5(uniqid(mt_rand(),true));

if (!$action) {
	include 'includes/head.php';
?>
 
<input type="hidden" id="api_key" value="<?= $api_key ?>" />
<input type="hidden" id="usd_amount" value="<?= $usd_amount ?>" />
<input type="hidden" id="invoice_id" value="<?= $invoice_id ?>" />
<div class="fresh_projects login_bg">
	<div class="clearfix mar_top8"></div>
    <div class="container" id="container">
    	<h2 class="merchant_title"><?= $merchant_info['merchant_name'] ?></h2>
    	<div class="clearfix mar_top1"></div>
    	<div class="testimonials-4">
    		<div class="merchant-processing-final">
    			<img src="images/loading.gif" />
				<?= Lang::string('merchant-deposit-refer') ?>
    		</div>
			<div class="one_half" id="merchant-crypto-flow">
				<div class="content">
					<h3 class="section_label">
						<span class="left"><i class="fa fa-btc fa-2x"></i></span>
						<span class="right"><?= Lang::string('deposit-bitcoins') ?></span>
					</h3>
					<div class="clear"></div>
					<div id="merchant-cryptos-deposit" class="buyform">
						<? } if (!$action || $action == 'switch-crypto') { ?>
						<input type="hidden" id="c_curency_abbr" value="<?= $c_currency_info['fa_symbol'] ?>" />
						<div class="spacer"></div>
						<? if ($api_amount) { ?>
						<div class="param">
							<label for="crypto_amount"><?= Lang::string('gateway-amount') ?></label>
							<input type="text" id="crypto_amount" name="crypto_amount" readonly="readonly" value="<?= String::currency($usd_amount/$c_currency_info['usd_ask'],2,8).' '.$c_currency_info['fa_symbol'] ?>" />
							<div class="clear"></div>
						</div>
						<? } ?>
						<div class="param">
							<label for="c_currency_merchant"><?= Lang::string('deposit-c-currency') ?></label>
							<select id="c_currency_merchant" name="c_currency">
							<?
							if ($CFG->currencies) {
								foreach ($CFG->currencies as $key => $currency) {
									if (is_numeric($key) || $currency['is_crypto'] != 'Y' || ($allowed_currencies && !in_array($currency['id'],$allowed_currencies)))
										continue;
									
									echo '<option '.(($currency['id'] == $c_currency) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
								}
							}	
							?>
							</select>
							<div class="clear"></div>
						</div>
						<div class="param">
							<label for="deposit_address"><?= Lang::string('deposit-send-to-address') ?></label>
							<input type="text" id="deposit_address" name="deposit_address" readonly="readonly" value="<?= $address ?>" />
							<div class="clear"></div>
						</div>
						<div class="spacer"></div>
						<div class="calc">
							<img class="qrcode" src="includes/qrcode.php?code=<?= $address ?>" />
							<div id="merchant-crypto-finalized">
								<div class="contain">
									<h3 class="section_label">
										<span class="left"><i class="fa fa-check fa-2x"></i></span>
										<span class="right"><?= Lang::string('merchant-payment-received') ?></span>
										<div class="clear"></div>
									</h3>
									<div class="clear"></div>
									<div class="mar_top1"></div>
									<p><?= Lang::string('merchant-crypto-continue') ?></p>
									<div class="clear"></div>
									<div class="mar_top2"></div>
									<ul class="list_empty">
										<li><a href="#" class="but_user"><?= Lang::string('merchant-finalize') ?></a></li>
										<div class="clear"></div>
									</ul>
									<div class="clear"></div>
								</div>
								<div class="overlay1"></div>
							</div>
						</div>
						<div class="calc" id="merchant-checking-crypto">
							<div class="middle">
								<img src="images/loading.gif" />
								<?= Lang::string('merchant-checking-address') ?>
							</div>
							<div class="clear"></div>
						</div>
						<div class="calc bigger dotted-over">
							<input type="hidden" id="amount_received" value="0" />
							<div class="label"><?= Lang::string('merchant-received') ?></div>
							<div class="value"><span id="amount_received_dummy">0</span> / <?= String::currency($usd_amount/$c_currency_info['usd_ask'],2,8) ?> <?= $c_currency_info['fa_symbol'] ?></div>
							<div class="clear"></div>
						</div>
						<div class="spacer"></div>
						<? } if (!$action) { ?>
					</div>
					<div class="overlay"></div>
				</div>
			</div>
			<div class="one_half last" id="merchant-login-flow">
				<? } if (!$action || $action == 'login' || $action == 'logged-in') { ?>
				<div class="content">
					<? if (!User::isLoggedIn()) { ?>
					<h3 class="section_label">
						<span class="left"><i class="fa fa-user fa-2x"></i></span>
						<span class="right"><?= Lang::string('merchant-pay-with') ?></span>
					</h3>
					<div class="clear"></div>
			    	<? 
			    	if (count(Errors::$errors) > 0) {
						echo '
					<div class="error" id="div4">
						<div class="message-box-wrap">
							'.((User::$timeout > 0) ? str_replace('[timeout]','<span class="time_until"></span><input type="hidden" class="time_until_seconds" value="'.(time() + User::$timeout).'" />',Lang::string('login-timeout')) : Errors::$errors[0]).'
						</div>
					</div>';
					}
			    	?>
			    	<form method="POST" action="merchant-deposit.php" name="login" id="merchant-login">
				    	<div class="loginform">
				    		<a href="forgot.php"><?= Lang::string('forgot-ask') ?></a>
				    		<div class="loginform_inputs">
					    		<div class="input_contain">
					    			<i class="fa fa-user"></i>
					    			<input type="text" class="login" name="login[user]" value="<?= $user1 ?>">
					    		</div>
					    		<div class="separate"></div>
					    		<div class="input_contain last">
					    			<i class="fa fa-lock"></i>
					    			<input type="password" class="login" name="login[pass]" value="<?= $pass1 ?>">
					    		</div>
				    		</div>
				    		<? if (!empty(User::$attempts) && User::$attempts > 2 && !empty($CFG->google_recaptch_api_key) && !empty($CFG->google_recaptch_api_secret)) { ?>
					    	<div style="margin-bottom:10px;">
					    		<div class="g-recaptcha" data-sitekey="<?= $CFG->google_recaptch_api_key ?>"></div>
					    	</div>
					    	<? } ?>
				    		<input type="hidden" name="submitted" value="1" />
				    		<input type="hidden" name="uniq" value="<?= $_SESSION["merchant_uniq"] ?>" />
				    		<input type="submit" name="submit" value="<?= Lang::string('home-login') ?>" class="but_user" />
				    	</div>
			    	</form>
					<? } else { ?>
	            	<h3 class="section_label">
	                    <span class="left"><i class="fa fa-check fa-2x"></i></span>
	                    <span class="right"><?= Lang::string('account-balance') ?></span>
	                </h3>
	                <div class="clear"></div>
	                <div class="balances">
		            	<?
		            	foreach ($available as $currency => $balance) {
							if (count($allowed_currencies) > 0 && !in_array($CFG->currencies[$currency]['id'],$allowed_currencies))
								continue;
							
							$is_crypto = ($CFG->currencies[$currency]['is_crypto'] == 'Y');
						?>
						<div class="one_half">
	                		<div class="label"><?= $currency.' '.Lang::string('account-available') ?>:</div>
	                		<div class="amount"><?= (!$is_crypto ? $CFG->currencies[$currency]['fa_symbol'].' ' : '').String::currency($balance,$is_crypto) ?></div>
	                	</div>
						<?
						} 
		            	?>
		            	<div class="clear"></div>
	            	</div>
	            	<form method="POST" action="merchant-deposit.php" name="user-payment" id="merchant-user-payment" class="buyform">
						<div class="param">
							<label for="user_pay_currency"><?= Lang::string('gateway-currency-pay') ?></label>
							<select id="user_pay_currency" name="user_pay_currency">
							<?
							if ($CFG->currencies) {
								foreach ($CFG->currencies as $key => $currency) {
									if (is_numeric($key) || $currency['is_crypto'] != 'Y' || ($allowed_currencies && !in_array($currency['id'],$allowed_currencies)))
										continue;
									
									echo '<option '.(($currency['id'] == $c_currency) ? 'selected="selected"' : '').' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
								}
							}	
							?>
							</select>
							<div class="clear"></div>
						</div>
						<? if ($api_amount) { ?>
						<div class="param dotted-over">
							<label for="user_pay_amount"><?= Lang::string('gateway-amount-pay') ?></label>
							<input type="text" id="user_pay_amount" name="user_pay_amount" readonly="readonly" value="<?= String::currency($usd_amount/$c_currency_info['usd_ask'],2,8).' '.$c_currency_info['fa_symbol'] ?>" />
							<div class="clear"></div>
						</div>
						<? } else { ?>
						<div class="param">
							<label for="user_pay_amount"><?= Lang::string('gateway-amount') ?></label>
							<input type="text" id="user_pay_amount" name="user_pay_amount" readonly="readonly" value="" />
							<div class="clear"></div>
						</div>
						<? } ?>
						<div class="calc bigger dotted-over" <?= ($usd_amount > 0 && ($usd_amount/$currency_info['usd_ask']) > $available[$currency_info['currency']]) ? '' : 'style="display:none;"' ?>>
							<div class="label price-red"><?= Lang::string('merchant-insufficient-balance') ?></div>
							<div class="clear"></div>
						</div>
						<div class="mar_top2"></div>
						<ul class="list_empty" <?= ($usd_amount > 0 && ($usd_amount/$currency_info['usd_ask']) > $available[$currency_info['currency']]) ? 'style="display:none;"' : '' ?>>
							<li><input type="submit" name="submit" value="<?= Lang::string('merchant-finalize') ?>" class="but_user" /></li>
						</ul>
	            	</form>
	            	<div class="clear"></div>
					<? } ?>
					<div class="overlay"></div>
				</div>
				<? } if (!$action) { ?>
			</div>
			<div class="clear"></div>
		</div>
    </div>
    <div class="bg"></div>
    <div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>
<? } ?>
