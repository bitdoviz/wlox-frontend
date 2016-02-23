<?php
include '../lib/common.php';


if (User::$info['locked'] == 'Y' || User::$info['deactivated'] == 'Y')
	Link::redirect('settings.php');
elseif (User::$awaiting_token)
	Link::redirect('verify-token.php');
elseif (!User::isLoggedIn())
	Link::redirect('login.php');


if ((!empty($_REQUEST['order_by'])))
	$_SESSION['tr_order_by'] = preg_replace("/[^a-z]/", "",$_REQUEST['order_by']);
 

$page1 = (!empty($_REQUEST['page'])) ? preg_replace("/[^0-9]/", "",$_REQUEST['page']) : false;

/*

getAffilliateTransactionsTotal(
        $count=false, $page=false, $per_page=false,  $c_currency=false, $currency=false,
        $user=false,  $type=false, $order_by=false,   $order_desc=false,
        $public_api_all=false, $dont_paginate=false, $affiliates=false)
*/

// getAffiliatesTotal30Days($count=false,$paginated=false,$page=0,$results_per_page=30)

API::add('Affiliates','getAffiliatesTotal30Days',array(1,1,$page1,30));
$query = API::send();

$total = getItemsByKey( $query, 'total');

API::add('Affiliates','getAffiliatesTotal30Days',array(0,1,$page1,30));
$query = API::send();

$transactions = current(getItemsByKey(  $query, 'results'));
 
$pagination = Content::pagination('affiliate_info.php',$page1,$total,30,5,false);

$currency_info = ($currency1) ? $CFG->currencies[strtoupper($currency1)] : array();

if ($trans_realized1 > 0)
	Messages::add(str_replace('[transactions]',$trans_realized1,Lang::string('transactions-done-message')));

$page_title = Lang::string('transactions');

if (!$bypass) {
	include 'includes/head.php';
	
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= Lang::string('title_my_subordinates')  ?></h1></div> 
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
<? } ?>
        	<div class="table-style">
        		<input type="hidden" id="refresh_transactions" value="1" />
        		<input type="hidden" id="page" value="<?= $page1 ?>" />
        		<table class="table-list trades" id="transactions_list">
        			<tr id="table_first">
        				<th><?= Lang::string('user') ?></th>

        				<th><?= Lang::string('orders-amount') ?></th>

                        <th><?= Lang::string('default-currency') ?></th>
        				<th><?= Lang::string('default-c-currency') ?></th>

        				<th><?= Lang::string('affiliate-comissions-30-days') ?></th>
        				<th><?= Lang::string('total-commissions') ?></th>
 
        			</tr>

        			<? 
        			if ($transactions) {
						foreach ($transactions as $transaction) {

 
							echo '
					<tr id="user_'.$transaction['user'].'">

						<td>'.$transaction['user'].'</td>
						<td> '.String::currency($transaction['30_day_volume'],true).' '.$CFG->currencies[$transaction['default_c_currency']]['fa_symbol'].'</td>

						<td> '. $CFG->currencies[$transaction['default_currency']]['fa_symbol'].'</td>
						<td> '. $CFG->currencies[$transaction['default_c_currency']]['fa_symbol'].'</td>

						<td> '.String::currency($transaction['affiliates_fee'],true).'</td>
                        <td> '.String::currency($transaction['affiliates_fee1'],true).'</td>

					</tr>';
						}
					}
					echo '<tr id="no_transactions" style="'.(is_array($transactions) ? 'display:none;' : '').'"><td colspan="6">'.Lang::string('transactions-no').'</td></tr>';
        			?>
					

        		</table>
        		<?= $pagination ?>
			</div>
			<div class="clear"></div>
		</div>
<? if (!$bypass) { ?>
		<div class="mar_top5"></div>
	</div>
	<? include 'includes/sidebar_account.php'; ?>
</div>

<? include 'includes/foot.php'; ?>
<? } ?>
