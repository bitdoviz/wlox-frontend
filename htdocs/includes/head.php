<? $_SESSION["logout_uniq"] = md5(uniqid(mt_rand(),true));?>
<!doctype html>
<!--[if IE 7 ]>    <html lang="<?= $CFG->language ?>" class="isie ie7 oldie no-js"> <![endif]-->
<!--[if IE 8 ]>    <html lang="<?= $CFG->language ?>" class="isie ie8 oldie no-js"> <![endif]-->
<!--[if IE 9 ]>    <html lang="<?= $CFG->language ?>" class="isie ie9 no-js"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="<?= $CFG->language ?>" class="no-js"> <!--<![endif]-->

<head>
	<title><?= $page_title ?></title>
	<base href="<?= $CFG->baseurl ?>" />
	
	<meta charset="utf-8">
	<meta name="keywords" content="" />

	<meta name="description" content="<?= (!empty($meta_desc) ? $meta_desc : false) ?>" />
	<meta name="publisher" content="WLOX White Label Open-Source Exchange" />

    
    <!-- Favicon --> 
	<link rel="shortcut icon" href="images/favicon.ico">
    
    <!-- this styles only adds some repairs on idevices  -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    
    <!-- Google fonts -->
    <link href='https://fonts.googleapis.com/css?family=Titillium+Web:400,300,400italic,600,700|Open+Sans:400,300,600,700&subset=latin,latin-ext,cyrillic' rel='stylesheet' type='text/css'>
    
    <!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
    
    <!-- ######### CSS STYLES ######### -->
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
	<link rel="stylesheet" href="css/style.css?v=20151203" type="text/css" />
    <link rel="stylesheet" href="css/font-awesome/css/font-awesome.min.css">
    
    <!-- responsive devices styles -->
	<link rel="stylesheet" media="screen" href="css/responsive-leyouts.css?v=20160120" type="text/css" />
    
    <? if ($CFG->self == 'security.php') { ?>
    <!-- authy -->
    <link href="https://www.authy.com/form.authy.min.css" media="screen" rel="stylesheet" type="text/css">
    <? } ?>
    
    <? if ($CFG->self == 'contact.php') { ?>
	<script type="text/javascript" src="ckeditor/ckeditor.js"></script>
	<? } ?>
	
	<? if ($CFG->self == 'api-docs.php') { ?>
	<link rel="stylesheet" media="screen" href="css/prism.css" type="text/css" />
	<? } ?>
	
	<? if ($CFG->self == 'contact.php' || $CFG->self == 'login.php' || $CFG->self == 'register.php' || $CFG->self == 'forgot.php') { ?>
	<script src='https://www.google.com/recaptcha/api.js<?= ((!empty($CFG->language) && $CFG->language != 'en') ? '?hl='.($CFG->language == 'zh' ? 'zh-CN' : $CFG->language) : '') ?>'></script>
	<? } ?>
	
	<?= Lang::url($CFG->self,1); ?>
</head>

<body>
<input type="hidden" id="javascript_date_format" value="<?= Lang::string('javascript-date-format') ?>" />
<input type="hidden" id="javascript_mon_0" value="<?= Lang::string('jan') ?>" />
<input type="hidden" id="javascript_mon_1" value="<?= Lang::string('feb') ?>" />
<input type="hidden" id="javascript_mon_2" value="<?= Lang::string('mar') ?>" />
<input type="hidden" id="javascript_mon_3" value="<?= Lang::string('apr') ?>" />
<input type="hidden" id="javascript_mon_4" value="<?= Lang::string('may') ?>" />
<input type="hidden" id="javascript_mon_5" value="<?= Lang::string('jun') ?>" />
<input type="hidden" id="javascript_mon_6" value="<?= Lang::string('jul') ?>" />
<input type="hidden" id="javascript_mon_7" value="<?= Lang::string('aug') ?>" />
<input type="hidden" id="javascript_mon_8" value="<?= Lang::string('sep') ?>" />
<input type="hidden" id="javascript_mon_9" value="<?= Lang::string('oct') ?>" />
<input type="hidden" id="javascript_mon_10" value="<?= Lang::string('nov') ?>" />
<input type="hidden" id="javascript_mon_11" value="<?= Lang::string('dec') ?>" />
<input type="hidden" id="gmt_offset" value="<?= $CFG->timezone_offset ?>" />
<input type="hidden" id="is_logged_in" value="<?= User::isLoggedIn() ?>" />
<input type="hidden" id="cfg_orders_edit" value="<?= Lang::string('orders-edit') ?>" />
<input type="hidden" id="cfg_orders_delete" value="<?= Lang::string('orders-delete') ?>" />
<input type="hidden" id="cfg_user_id" value="<?= (User::isLoggedIn()) ? User::$info['user'] : '0' ?>" />
<input type="hidden" id="buy_errors_no_compatible" value="<?= Lang::string('buy-errors-no-compatible') ?>" />
<input type="hidden" id="orders_converted_from" value="<?= Lang::string('orders-converted-from') ?>" />
<input type="hidden" id="your_order" value="<?= Lang::string('home-your-order') ?>" />
<input type="hidden" id="order-cancel-all-conf" value="<?= Lang::string('order-cancel-all-conf') ?>" />
<input type="hidden" id="orders-click-price-buy" value="<?= Lang::string('orders-click-price-buy') ?>" />
<input type="hidden" id="orders-click-price-sell" value="<?= Lang::string('orders-click-price-sell') ?>" />
<input type="hidden" id="orders-click-amount-buy" value="<?= Lang::string('orders-click-amount-buy') ?>" />
<input type="hidden" id="orders-click-amount-sell" value="<?= Lang::string('orders-click-amount-sell') ?>" />
<input type="hidden" id="this_currency_id" value="<?= (!empty($currency_info)) ? $currency_info['id'] : 0 ?>" />
<input type="hidden" id="chat_handle" value="<?= (User::isLoggedIn()) ? User::$info['chat_handle'] : 'not-logged-in' ?>" />
<input type="hidden" id="chat_baseurl" value="<?= ($CFG->chat_baseurl) ? $CFG->chat_baseurl : $CFG->baseurl ?>" />
<input type="hidden" id="cfg_thousands_separator" value="<?= (!empty($CFG->thousands_separator)) ? $CFG->thousands_separator : ',' ?>" />
<input type="hidden" id="cfg_decimal_separator" value="<?= (!empty($CFG->decimal_separator)) ? $CFG->decimal_separator : '.' ?>" />
<input type="hidden" id="cfg_time_24h" value="<?= (!empty($CFG->time_24h)) ? $CFG->time_24h : 'N' ?>" />
<?= Lang::url(false,false,1); ?>
<?= Lang::jsCurrencies(false,false,1); ?>

<div class="site_wrapper">
   
<!-- HEADER -->
<header id="header">

	<!-- Top header bar -->
	<div id="topHeader">
    
	<div class="wrapper">
         
        <div class="top_contact_info">
        
        <div class="container">
        
            <ul class="tci_list_left">
                <li><a href="help.php"><?= Lang::string('help') ?></a></li>
                <li>|</li>
                <li><a href="<?= Lang::url('contact.php') ?>"><?= Lang::string('contact') ?></a></li>
                <li>|</li>
                <li><a target="_blank" href="https://github.com/mbassan/wlox/"><i class="fa fa-github"></i> GitHub</a></li>
            </ul>
        
            <ul class="tci_list">
                <? if (!User::isLoggedIn()) { ?>
                <li><a href="login.php"><i class="fa fa-key"></i> <?= Lang::string('home-login') ?></a></li>
                <li>|</li>
                <li><a href="<?= Lang::url('register.php') ?>"><i class="fa fa-user"></i> <?= Lang::string('home-register') ?></a></li>
                <? } else { ?>
                <li><a href="account.php"><i class="fa fa-user"></i> <?= User::$info['user'] ?></a> | <a href="logout.php?log_out=1&uniq=<?= $_SESSION["logout_uniq"] ?>"><i class="fa fa-unlock"></i> <?= Lang::string('log-out') ?></a></li>
                <? } ?>
                <li class="empty margin-left">
                	<label for="language_selector"><img src="images/<?= $CFG->language ?>.png" /></label>
                	<select id="language_selector" class="lang">
                		<option value="en" <?= ($CFG->language == 'en') ? 'selected="selected"' : '' ?>>English</option>
                		<option value="es" <?= ($CFG->language == 'es') ? 'selected="selected"' : '' ?>>Español</option>
                		<option value="ru" <?= ($CFG->language == 'ru') ? 'selected="selected"' : '' ?>>Pусский</option>
                		<option value="zh" <?= ($CFG->language == 'zh') ? 'selected="selected"' : '' ?>>中文</option>
                	</select>
                </li>
            </ul>
            
        </div>
        
    </div><!-- end top contact info -->
            
 	</div>
    
	</div>
	
    
	<div id="trueHeader">
    
	<div class="wrapper">
    
     <div class="container">
    
		<!-- Logo -->
		<div class="one_fourth"><a href="index.php" id="logo"></a></div>
		
        <!-- Menu -->
        <div class="three_fourth last">
           
           <nav id="access" class="access" role="navigation">
           
            <div id="menu" class="menu">
                
                <ul id="tiny">
                    <li><a href="<?= Lang::url('index.php') ?>" <?= ($CFG->self == 'index.php' || !$CFG->self) ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('home') ?></div><div class="bg"></div></a></li>
                    <li><a href="<?= Lang::url('order-book.php') ?>" <?= ($CFG->self == 'order-book.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('order-book') ?></div><div class="bg"></div></a></li>
                    <? if (!User::isLoggedIn()) { ?>
                    <li><a href="<?= Lang::url('our-security.php') ?>" <?= ($CFG->self == 'our-security.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('our-security') ?></div><div class="bg"></div></a></li>
                    <li><a href="<?= Lang::url('buy-and-sell-bitcoin.php') ?>" <?= ($CFG->self == 'buy-and-sell-bitcoin.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('how-to-register') ?></div><div class="bg"></div></a></li>
                    <li><a href="<?= Lang::url('fee-schedule.php') ?>" <?= ($CFG->self == 'fee-schedule.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('fee-schedule') ?></div><div class="bg"></div></a></li>
                    <li><a href="<?= Lang::url('about.php') ?>" <?= ($CFG->self == 'about.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('about') ?></div><div class="bg"></div></a></li>
                    <? } else { ?>
                    <li><a href="account.php" <?= ($CFG->self == 'account.php' || $CFG->self == 'open-orders.php' || $CFG->self == 'transactions.php' || $CFG->self == 'security.php' || $CFG->self == 'settings.php' || $CFG->self == 'bank-accounts.php' || $CFG->self == 'bitcoin-addresses.php' || $CFG->self == 'history.php' || $CFG->self == 'api-access.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('account') ?> <i class="fa fa-angle-down"></i></div><div class="bg"></div></a>
                        <ul>
                        	<li><a href="open-orders.php"><?= Lang::string('open-orders') ?></a></li>
                            <li><a href="transactions.php"><?= Lang::string('transactions') ?></a></li>
                            <? if (User::$info['shares_enabled']) {?>
							<li><a href="shares.php"><?= Lang::string('shares') ?></a></li>
							<? } ?>
                            <li><a href="security.php"><?= Lang::string('security') ?></a></li>
                            <li><a href="settings.php"><?= Lang::string('settings') ?></a></li>
                            <li><a href="bank-accounts.php"><?= Lang::string('bank-accounts') ?></a></li>
							<li><a href="bitcoin-addresses.php"><?= Lang::string('bitcoin-addresses') ?></a></li>
							<li><a href="history.php"><?= Lang::string('history') ?></a></li>
							<li><a href="api-access.php"><?= Lang::string('api-access') ?></a></li>
                            <li><a href="logout.php?log_out=1"><?= Lang::string('log-out') ?></a></li>
                        </ul>
                    </li>
                    <li><a href="buy-sell.php" <?= ($CFG->self == 'buy-sell.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('buy-sell') ?></div><div class="bg"></div></a></li>
                    <li><a href="deposit.php" <?= ($CFG->self == 'deposit.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('deposit') ?></div><div class="bg"></div></a></li>
                    <li><a href="withdraw.php" <?= ($CFG->self == 'withdraw.php') ? 'class="active"' : '' ?>><div class="contain"><?= Lang::string('withdraw') ?></div><div class="bg"></div></a></li>
	                <? } ?>
                </ul>
            </div>
            
        </nav><!-- end nav menu -->
      
        </div>
        
        
		</div>
		
	</div>
    <div class="bg"></div>
	</div>
    
</header><!-- end header -->
