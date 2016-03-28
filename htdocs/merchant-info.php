<?php
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');


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
		<div class="testimonials-4">
			<? Errors::display(); ?>
			<? Messages::display(); ?>
			<?= (!empty($notice)) ? '<div class="notice"><div class="message-box-wrap">'.$notice.'</div></div>' : '' ?>
			<h3 class="section_label">
				<span class="left">
				<span class="right">Personal Info</span>
			</h3>
			<div class="clear"></div>
			<form class="form form1" method="POST" action="merchant-info.php" name="settings">
				<ul>
					<li>
						<label class="checkbox_label" for="register_terms"></label>
						<input id="register_terms" class="checkbox" type="checkbox" value="Y" name="register[terms]">
					</li>
					<li>
						<label for="settings_pass">Change Password </label>
						<input id="settings_pass" type="password" value="" name="settings[pass]">
					</li>
				</ul>
			</form>
		</div>
	</div>
</div>
<? include 'includes/foot.php'; ?>