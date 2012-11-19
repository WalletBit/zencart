<?php
	class walletbit
	{
		var $code, $title, $description, $enabled, $payment;

		function walletbit()
		{
			global $order;
			$this->code = 'walletbit';
			$this->title = MODULE_PAYMENT_WALLETBIT_TEXT_TITLE;
			$this->description = MODULE_PAYMENT_WALLETBIT_TEXT_DESCRIPTION;
			$this->sort_order = MODULE_PAYMENT_WALLETBIT_SORT_ORDER;
			$this->enabled = ((MODULE_PAYMENT_WALLETBIT_STATUS == 'True') ? true : false);

			if ((int)MODULE_PAYMENT_WALLETBIT_ORDER_STATUS_ID > 0)
			{
				$this->order_status = MODULE_PAYMENT_WALLETBIT_ORDER_STATUS_ID;
				$payment = 'walletbit';
			}
			else if ($payment == 'walletbit')
			{
				$payment = '';
			}

			if (is_object($order)) $this->update_status();

			$this->email_footer = MODULE_PAYMENT_WALLETBIT_TEXT_EMAIL_FOOTER;
		}

		function update_status()
		{
			global $db;
			global $order;

			if (($this->enabled == true) && ((int)MODULE_PAYMENT_WALLETBIT_ZONE > 0))
			{
				$check_flag = false;
				$check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_WALLETBIT_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
				
				while (!$check->EOF)
				{
					if ($check->fields['zone_id'] < 1)
					{
						$check_flag = true;
						break;
					}
					else if ($check->fields['zone_id'] == $order->billing['zone_id'])
					{
						$check_flag = true;
						break;
					}

					$check->MoveNext();
				}

				if ($check_flag == false)
				{
					$this->enabled = false;
				}
			}

			if (!MODULE_PAYMENT_WALLETBIT_EMAIL OR !strlen(MODULE_PAYMENT_WALLETBIT_EMAIL))
			{
				print 'No Email';
				$this->enabled = false;
			}

			if (!MODULE_PAYMENT_WALLETBIT_TOKEN OR !strlen(MODULE_PAYMENT_WALLETBIT_TOKEN))
			{
				print 'No Token';
				$this->enabled = false;
			}

			if (!MODULE_PAYMENT_WALLETBIT_SECURITYWORD OR !strlen(MODULE_PAYMENT_WALLETBIT_SECURITYWORD))
			{
				print 'No Security Word';
				$this->enabled = false;
			}
		}

		function selection()
		{
			return array('id' => $this->code, 'module' => $this->title);
		}

		function javascript_validation()
		{
			return false;
		}

		function confirmation()
		{
			return false;
		}

		function process_button()
		{
			return false;
		}

		function pre_confirmation_check()
		{
			return false;
		}

		function before_process()
		{
			return false; 
		}

		// called upon clicking confirm (after before_process and after the order is created)
		function after_process()
		{
			global $insert_id, $order, $db;
					
			// change order status to value selected by merchant
			$db->Execute("update ". TABLE_ORDERS. " set orders_status = " . MODULE_PAYMENT_WALLETBIT_UNPAID_STATUS_ID . " where orders_id = ". $insert_id);

			$url = 'https://walletbit.com/pay?token=' . MODULE_PAYMENT_WALLETBIT_TOKEN . '&item_name=' . $item_name . '&amount=' . $order->info['total'] . '&currency=' . $order->info['currency'] . '&returnurl=' . rawurlencode(zen_href_link('account')) . '&additional=order_id=' . $insert_id . '|physical=' . ($order->content_type == 'physical' ? 'true' : 'false') . '|buyerName=' . $order->customer['firstname'].' '.$order->customer['lastname'];
			
			$_SESSION['cart']->reset(true);
			zen_redirect($url);

			return false;
		}

		function get_error()
		{
			return false;
		}

		function check()
		{
			global $db;
			if (!isset($this->_check))
			{
				$check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_WALLETBIT_STATUS'");
				$this->_check = $check_query->RecordCount();
			}
			
			return $this->_check;
		}

		function install()
		{
			global $db, $messageStack;
			if (defined('MODULE_PAYMENT_WALLETBIT_STATUS'))
			{
				$messageStack->add_session('WalletBit module already installed.', 'error');
				zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=walletbit', 'NONSSL'));
				return 'failed';
			}

			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
			."values ('Enable WalletBit Module', 'MODULE_PAYMENT_WALLETBIT_STATUS', 'True', 'Do you want to accept bitcoin payments via walletbit.com?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now());");

			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
			."values ('WalletBit Email', 'MODULE_PAYMENT_WALLETBIT_EMAIL', '', 'Enter your WalletBit email', '6', '0', now());");

			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
			."values ('WalletBit Token', 'MODULE_PAYMENT_WALLETBIT_TOKEN', '', 'Enter your token from walletbit.com', '6', '0', now());");

			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
			."values ('WalletBit Security Word', 'MODULE_PAYMENT_WALLETBIT_SECURITYWORD', '', 'Enter the security word you generated at walletbit.com', '6', '0', now());");

			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
			."values ('Unpaid Order Status', 'MODULE_PAYMENT_WALLETBIT_UNPAID_STATUS_ID', '" . DEFAULT_ORDERS_STATUS_ID .  "', 'Automatically set the status of unpaid orders to this value.', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
			."values ('Paid Order Status', 'MODULE_PAYMENT_WALLETBIT_PAID_STATUS_ID', '2', 'Automatically set the status of paid orders to this value.', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
				
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) "
			."values ('Payment Zone', 'MODULE_PAYMENT_WALLETBIT_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
			
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
			."values ('Sort order of display.', 'MODULE_PAYMENT_WALLETBIT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '2', now())");
		}

		function remove()
		{
			global $db;
			$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
		}

		function keys()
		{
			return array(
				'MODULE_PAYMENT_WALLETBIT_STATUS', 
				'MODULE_PAYMENT_WALLETBIT_EMAIL',
				'MODULE_PAYMENT_WALLETBIT_TOKEN',
				'MODULE_PAYMENT_WALLETBIT_SECURITYWORD',
				'MODULE_PAYMENT_WALLETBIT_UNPAID_STATUS_ID',
				'MODULE_PAYMENT_WALLETBIT_PAID_STATUS_ID',
				'MODULE_PAYMENT_WALLETBIT_SORT_ORDER',
				'MODULE_PAYMENT_WALLETBIT_ZONE'
			);
		}
	}
?>