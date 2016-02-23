<?php

/*
30-day-income
number-of-users
“current cut %” (el porcentaje del fee que gana el Affiliate)
decimal cuando creo el textbox.eso tiene que venir dle man.
*/


@session_start();
include '../lib/common.php';

if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');

if ((!empty($_REQUEST['c_currency']) && array_key_exists(strtoupper($_REQUEST['c_currency']),$CFG->currencies)))
	$_SESSION['tr_c_currency'] = preg_replace("/[^0-9]/", "",$_REQUEST['c_currency']);
else if (empty($_SESSION['tr_c_currency']) || $_REQUEST['c_currency'] == 'All')
	$_SESSION['tr_c_currency'] = false;

if ((!empty($_REQUEST['currency']) && array_key_exists(strtoupper($_REQUEST['currency']),$CFG->currencies)))
	$_SESSION['tr_currency'] = preg_replace("/[^0-9]/", "",$_REQUEST['currency']);
else if (empty($_SESSION['tr_currency']) || $_REQUEST['currency'] == 'All')
	$_SESSION['tr_currency'] = false;

if ((!empty($_REQUEST['order_by'])))
	$_SESSION['tr_order_by'] = preg_replace("/[^a-z]/", "",$_REQUEST['order_by']);
else if (empty($_SESSION['tr_order_by']))
	$_SESSION['tr_order_by'] = false;

$currency1 = $_SESSION['tr_currency'];
$c_currency1 = $_SESSION['tr_c_currency'];
$order_by1 = $_SESSION['tr_order_by'];
$start_date1 = false;
$type1 = (!empty($_REQUEST['type'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['type']) : false;
$page1 = (!empty($_REQUEST['page'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['page']) : false;
$trans_realized1 = (!empty($_REQUEST['transactions'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['transactions']) : false;
$bypass = !empty($_REQUEST['bypass']);

 

$user = false;

API::add('Affiliates','getOverview',array());
$query = API::send();

print_r($query);

/*
Array
(
    [session] => 12
    [Affiliates] => Array
        (
            [getOverview] => Array
                (
                    [results] => Array
                        (
                            [0] => Array
                                (
                                    [income_30_day] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [user] => 55388194
                                                    [default_currency] => 27
                                                    [default_c_currency] => 28
                                                    [30_day_volume] => 1287134.1975551692
                                                    [income] => 0
                                                    [affiliates_fee] => 0.00000000
                                                    [affiliates_fee1] => 0.00000000
                                                )
                                        )
                                    [number_of_subordinates] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [total] => 3
                                                )
                                        )
                                    [current_cut] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [NOW()] => 2016-02-22 20:34:32
                                                )
                                        )
                                )
                        )
                )
        )
    [nonce_updated] => 
)
*/

$info = array(
    'total'                  => $query['Affiliates']['getOverview']['results']['0']['income_30_day']['0']['30_day_volume'],
    'income'                 => $query['Affiliates']['getOverview']['results']['0']['income_30_day']['0']['income'],
    'number-of-subordinates' => $query['Affiliates']['getOverview']['results']['0']['number_of_subordinates']['0']['total'],
    'current-cut'            => current($query['Affiliates']['getOverview']['results']['0']['current_cut']['0']),
);
 
$page_title = Lang::string('transactions');

include 'includes/head.php';
	
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= Lang::string('overview') ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('home') ?></a> <i>/</i> <a href="account.php"><?= Lang::string('account') ?></a> <i>/</i> <a href="transactions.php"><?= $page_title ?></a></div>
	</div>
</div>
<div class="container">

	<div class="content_right">
		<? Messages::display(); ?>

            <ul id="tabnav">
                <li class="tab1"><a href="affiliate_info__overview.php"><?= Lang::string('overview') ?></a></li>
                <li class="tab2"><a href="affiliate_info__transactions_detail.php"><?= Lang::string('transactions-detail') ?></a></li>
                <li class="tab3"><a href="affiliate_info__my_subordinates.php"><?= Lang::string('my-subordinates') ?></a></li>
            </ul>

			 
		<div class="clear"></div>
		<div id="filters_area">
        	<div class="table-style">
        		<input type="hidden" id="refresh_transactions" value="1" />
        		<input type="hidden" id="page" value="<?= $page1 ?>" />
        		<table class="table-list trades" id="transactions_list">
        			 
        			<? foreach ($info as $k=>$v) { ?>
                            <tr>
                                <td><?= Lang::string($k) ?></td><td><?= $v ?></td>
                            </tr>
					<? } ?>
        		</table>
			</div>
			<div class="clear"></div>
		</div>
		<div class="mar_top5"></div>
	</div>
	<? include 'includes/sidebar_account.php'; ?>
</div>

<? include 'includes/foot.php'; ?>
