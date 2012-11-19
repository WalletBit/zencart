<?php
	require 'includes/application_top.php';

	global $db;

	$str =
	$_POST["merchant"].":".
	$_POST["customer_email"].":".
	$_POST["amount"].":".
	$_POST["batchnumber"].":".
	$_POST["txid"].":".
	$_POST["address"].":".
	MODULE_PAYMENT_WALLETBIT_SECURITYWORD;

	$hash = strtoupper(hash('sha256', $str));

	// proccessing payment only if hash is valid
	if ($_POST["merchant"] == MODULE_PAYMENT_WALLETBIT_EMAIL && $_POST["encrypted"] == $hash && $_POST["status"] == 1)
	{
		@$db->Execute("update " . TABLE_ORDERS . " set orders_status = " . MODULE_PAYMENT_WALLETBIT_PAID_STATUS_ID . " where orders_id = " . intval($_POST['order_id']));			

		print '1';
	}
?>