<?php
include '../lib/common.php';

if (User::isLoggedIn())
	Link::redirect('account.php');
elseif (!User::$awaiting_token)
	Link::redirect('login.php');

$bypass = (!empty($_REQUEST['bypass'])) ? preg_replace("/[^0-9a-zA-Z\-]/","",$_REQUEST['bypass']) : false;
$token1 = (!empty($_REQUEST['token'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['token']) : false;
$dont_ask1 = !empty($_REQUEST['dont_ask']);
$authcode1 = (!empty($_REQUEST['authcode'])) ? urldecode($_REQUEST['authcode']) : false;
$api_key = (!empty($_REQUEST['api_key'])) ? preg_replace("/[^0-9a-zA-Z]/","",$_REQUEST['api_key']) : false;

if (!empty($_REQUEST['step']) && $_REQUEST['step'] == 1) {
	if (!($token1 > 0))
		Errors::add(Lang::string('security-no-token'));
	
	if (!is_array(Errors::$errors)) {
		$verify = User::verifyToken($token1,$dont_ask1);
		if ($verify) {
			if (!empty($_REQUEST['email_auth']))
				Link::redirect('change-password.php?authcode='.urlencode($_REQUEST['authcode']));
			else if ($bypass == 'deposit')
				Link::redirect('merchant-deposit.php?api_key='.$api_key.'&action=logged-in');
			else if ($bypass == 'withdraw')
				Link::redirect('merchant-withdraw.php?api_key='.$api_key.'&action=logged-in');
			else 
				Link::redirect('account.php');
			exit;
		}
	}
}

API::add('Content','getRecord',array('security-token-login'));
$query = API::send();

$content = $query['Content']['getRecord']['results'][0];
$page_title = Lang::string('verify-token');

if (!$bypass) {
	include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="verify-token.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_right">
		<div class="testimonials-4">
			<h2><?= $content['title'] ?></h2>
			<div class="text"><?= $content['content'] ?></div>
			<div class="mar_top2"></div>
			<div class="clear"></div>
			<? } ?>
			<? Errors::display(); ?>
			<form id="enable_tfa" action="verify-token.php" method="POST">
				<input type="hidden" name="step" value="1" />
				<input type="hidden" name="email_auth" value="<?= !empty($_REQUEST['email_auth']) ?>" />
				<input type="hidden" name="authcode" value="<?= urlencode($authcode1) ?>" />
				<input type="hidden" name="bypass" value="<?= $bypass ?>" />
				<div class="buyform">
					<div class="content">
		            	<h3 class="section_label">
		                    <span class="left"><i class="fa fa-check fa-2x"></i></span>
		                    <span class="right"><?= Lang::string('security-enter-token') ?></span>
		                </h3>
		                <div class="one_half">
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="spacer"></div>
							<div class="param">
								<label for="token"><?= Lang::string('security-token') ?></label>
								<input name="token" id="token" type="text" value="<?= $token1 ?>" />
								<div class="clear"></div>
							</div>
							<!-- div class="param lessbottom">
								<input class="checkbox" name="dont_ask" id="dont_ask" type="checkbox" value="1" <?= ($dont_ask1) ? 'checked="checked"' : '' ?> />
								<label for="dont_ask"><?= Lang::string('security-dont-ask') ?></label>
								<div class="clear"></div>
							</div-->
							 <div class="mar_top2"></div>
							 <ul class="list_empty">
								<li><input type="submit" name="submit" value="<?= Lang::string('security-validate') ?>" class="but_user" /></li>
							</ul>
		                </div>
		                <div class="clear"></div>
		            </div>
	            </div>
            </form>
            <? if (!$bypass) { ?>
		</div>
	</div>
	<div class="mar_top8"></div>
</div>
<? include 'includes/foot.php'; 
}?>