<?php
include '../lib/common.php';

$currencies = Settings::sessionCurrency();
$currency1 = $currencies['currency'];
$c_currency1 = $currencies['c_currency'];

if ($_REQUEST['fee_currency'])
	$currency1 = preg_replace("/[^0-9]/", "",$_REQUEST['fee_currency']);

API::add('Content','getRecord',array('fee-schedule'));
API::add('FeeSchedule','get',array($currency1));
API::add('Currencies','getMain');
$query = API::send();

$content = $query['Content']['getRecord']['results'][0];
$main = $query['Currencies']['getMain']['results'][0];
$main_usd_ask = $CFG->currencies[$main['fiat']]['usd_ask'];
$this_usd_ask = $CFG->currencies[$currency1]['usd_ask'];
$currency_info = $CFG->currencies[$currency1];
$fee_schedule = $query['FeeSchedule']['get']['results'][0];

$page_title = $content['title'];

include 'includes/head.php';
?>
<div class="page_title">
	<div class="container">
		<div class="title"><h1><?= $page_title ?></h1></div>
        <div class="pagenation">&nbsp;<a href="index.php"><?= Lang::string('fee-schedule') ?></a> <i>/</i> <a href="fee-schedule.php"><?= Lang::string('fee-schedule') ?></a></div>
	</div>
</div>
<div class="container">
	<div class="content_right">
    	<div class="text1"><?= $content['content'] ?></div>
    	<div class="clearfix mar_top2"></div>
    	<div class="table-style">
    		<table class="table-list trades">
				<tr>
					<th><?= Lang::string('fee-schedule-fee1') ?></th>
					<th><?= Lang::string('fee-schedule-fee') ?></th>
					<th>
						<?= Lang::string('fee-schedule-volume') ?>
						<span class="graph_options" style="display: inline-block; padding: 0px; margin-left: 5px; top: 0px; position: relative;">
							<span style="margin:0;float:none;display:inline;">
								<select id="fee_currency">
								<? 
								if ($CFG->currencies) {
									foreach ($CFG->currencies as $key => $currency) {
										if (is_numeric($key) || $currency['id'] == $c_currency1)
											continue;
										
										echo '<option '.($currency['id'] == $currency1 ? 'selected="selected"' : '' ).' value="'.$currency['id'].'">'.$currency['currency'].'</option>';
									}
								}
								?>
								</select>
							</span>
						</span>
					</th>
					<!--th><?= Lang::string('fee-schedule-flc') ?></th -->
				</tr>
				<? 
				if ($fee_schedule) {
					$last_fee1 = false;
					$last_btc = false;
					foreach ($fee_schedule as $fee) {
						$symbol = ($fee['to_usd'] > 0) ? '<' : '>';
						$from = ($fee['to_usd'] > 0) ? String::currency(($fee['to_usd'] * $main_usd_ask)/$this_usd_ask,0) : String::currency(($fee['from_usd'] * $main_usd_ask)/$this_usd_ask,0);
				?>
				<tr>
					<?= ($fee['fee1'] != $last_fee1) ? '<td>'.$fee['fee1'].'%</td>' : '<td class="inactive"></td>' ?>
					<td><?= $fee['fee'] ?>%</td>
					<td><?= $symbol.' '.$currency_info['fa_symbol'].$from ?></td>
					<? ($fee['global_btc'] != $last_btc) ? '<td>'.number_format($fee['global_btc'],1).' BTC</td>' : '<td class="inactive"></td>' ?>
				</tr>
				<?
						$last_fee1 = $fee['fee1'];
						$last_btc = $fee['global_btc'];
					}
				}
				?>
			</table>
    	</div>
    </div>
    <? include 'includes/sidebar_topics.php'; ?>
	<div class="clearfix mar_top8"></div>
</div>
<? include 'includes/foot.php'; ?>
