<?php

$link_type = empty($_GET["type"]) ? 'transactions_detail' : $_GET["type"];

$allowed = array('overview','transactions_detail','my_subordinates');

if(!in_array($link_type, $allowed))
    die('invalid method.');

$template = "affiliate_info__{$link_type}.php";

require_once($template);

