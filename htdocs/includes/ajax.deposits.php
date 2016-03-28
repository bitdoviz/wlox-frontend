<?php
chdir('..');

$ajax = true;
include '../lib/common.php';

$c_currency = (!empty($CFG->currencies[strtoupper($_REQUEST['c_currency'])])) ? $_REQUEST['c_currency'] : false;
$invoice_id = (!empty($_REQUEST['invoice_id'])) ?  preg_replace("/[^\0-9a-zA-Z]/", "",$_REQUEST['invoice_id']) : false;

if (!$c_currency || strlen($api_key) != 16)
	exit;

API::add('Requests','get',array(false,false,false,false,$c_currency,false,false,false,$address,$invoice_id));
$query = API::send();
$deposits = $query['Requests']['get']['results'][0];
$received = 0;

if ($deposits) {
	foreach ($deposits as $deposit) {
		$received += $deposit['amount'];
	}
}
echo json_encode(array('received'=>$received,'currency_info'=>$CFG->currencies[$c_currency]));


