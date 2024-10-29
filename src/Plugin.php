<?php
/**
 * @author Tomáš Blatný
 */

namespace Pays\SmsGate;

use DateTime;
use GuzzleHttp\Client;
use Pays\SmsGate\Client as SmsClient;
use WC_Order;

class Plugin
{

	const OPTION_NAME = 'pays-sms-gate';
	const DOMAIN = 'axima-sms-gate';
	const TABLE_MESSAGES = 'messages';

	const STATUS_SENT = 0;
	const STATUS_DELIVERED = 1;
	const STATUS_ERROR = 2;

	const AXIMA_SMS_GATE_CRON = 'axima_sms_gate_cron';

	/** @var Database */
	private $database;


	public function install()
	{
		global $wp_version;

		$checks = array(
			'Your Wordpress version is not compatible with this plugin which requires at least version 3.1. Please update your Wordpress installation.' => version_compare($wp_version, '3.1', '<'),
			'This plugin requires at least PHP version 5.5.0, your version: ' . PHP_VERSION . '. Please ask your hosting company to bring your PHP version up to date.' => version_compare(PHP_VERSION, '5.5.0', '<'),
			'You need WooCommerce plugin installed and activated to run this plugin.' => !in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))),
		);

		foreach ($checks as $message => $disable) {
			if ($disable) {
				deactivate_plugins(basename(__FILE__));
				wp_die($message);
			}
		}

		$this->database->createTable(self::TABLE_MESSAGES, array(
			'id' => 'int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'sms_id' => 'varchar(255) NULL',
			'text' => 'text NOT NULL',
			'number' => 'varchar(255) NOT NULL',
			'status' => 'tinyint(1) unsigned NOT NULL',
			'note' => 'varchar(255) NULL',
			'date_sent' => 'datetime NOT NULL',
			'date_delivered' => 'datetime NULL',
		));

		wp_schedule_event(time(), 'hourly', self::AXIMA_SMS_GATE_CRON);

		$this->updateOptions(array(
			'enabled' => array(
				'wc-processing' => TRUE,
				'wc-completed' => TRUE,
			),
			'texts' => array(
				'wc-processing' => 'Thank you, your order is being processed.',
				'wc-completed' => 'Thank you, your order has been completed.',
			),
		));
	}

	public function deactivate()
	{
		wp_clear_scheduled_hook(self::AXIMA_SMS_GATE_CRON);
	}


	public static function uninstall()
	{
		global $wpdb;
		$database = new Database($wpdb);
		$database->dropTable(self::TABLE_MESSAGES);
		delete_option(self::OPTION_NAME);
	}


	public function init()
	{
		$that = $this;

		if (isset($_GET['page']) && $_GET['page'] === $that::DOMAIN) {
			$pluginUrl = plugins_url('', __DIR__);
			wp_enqueue_style('axima_sms_style_bootstrap', $pluginUrl . '/assets/bootstrap.min.css');
			wp_enqueue_style('axima_sms_style_bootstrap_theme', $pluginUrl . '/assets/bootstrap-theme.min.css');
			wp_enqueue_script('axima_sms_script_bootstrap', $pluginUrl . '/assets/bootstrap.min.js');
		}

		load_plugin_textdomain('axima-sms-gate', FALSE, dirname(plugin_basename(__FILE__)) . '/lang/');

		add_action(self::AXIMA_SMS_GATE_CRON, function () use ($that) {
			$that->checkForNewSms();
		});

		add_action('admin_menu', function () use ($that) {
			add_menu_page(
				__('sms.sluzba.cz', $that::DOMAIN),
				__('sms.sluzba.cz', $that::DOMAIN),
				'manage_options',
				$that::DOMAIN,
				array($that, 'actionSettings')
			);
		});

		add_filter( 'plugin_action_links_' . $that::DOMAIN . '/' . $that::DOMAIN . '.php', function ($links) use ($that) {
			$settings_link = '<a href="admin.php?page=' . $that::DOMAIN . '">' . __('Settings', $that::DOMAIN) . '</a>';
			array_unshift($links, $settings_link);
			return $links;
		});

		add_action('woocommerce_order_status_changed', function ($orderId, $oldStatus, $newStatus) use ($that) {
			$that->onStatusChange(wc_get_order($orderId), $newStatus);
		}, 10, 3);
	}


	public function actionSettings()
	{
		$page = isset($_GET['payspage']) ? $_GET['payspage'] : 'default';
		if (!preg_match('~^[a-zA-Z]+$~', $page)) {
			$page = 'default';
		}
		$method = 'render' . ucfirst($page);
		$data = array();
		if (method_exists($this, $method)) {
			$data = (array) $this->$method();
		}
		$this->render(__DIR__ . '/templates/' . $page . '.php', array(
			'page' => $page,
			'domain' => self::DOMAIN,
			'shortCodes' => $this->getShortCodes(),
		) + $data);
	}


	public function register($file)
	{
		global $wpdb;
		$this->database = new Database($wpdb);

		$that = $this;
		register_activation_hook($file, function () use ($that) {
			$that->install();
		});
		register_deactivation_hook($file, function () use ($that) {
			$that->deactivate();
		});
		register_uninstall_hook($file, array(__CLASS__, 'uninstall'));

		$this->pluginUrl = plugins_url('', $file);
		if (is_admin()) {
			add_action('plugins_loaded', function () use ($that) {
				$that->init();
			});
		}
	}


	public function onStatusChange(WC_Order $order, $newStatus)
	{
		$codes = $this->getShortCodes($order);

		$newStatus = 'wc-' . $newStatus;
		$texts = $this->getOption('texts', array());
		$enabled = $this->getOption('enabled', array());
		if (isset($enabled[$newStatus], $texts[$newStatus]) && $enabled[$newStatus] && trim($texts[$newStatus])) {
			$phone = str_replace(' ', '', $order->billing_phone);
			$text = trim($texts[$newStatus]);
			$text = str_replace(array_map(function ($item) {
				return '[' . $item . ']';
			}, array_keys($codes)), array_values($codes), $text);

			$this->sendSms($phone, $text);
		}
	}


	public function redirect($action = NULL, $other = NULL)
	{
		wp_redirect($this->link($action, $other));
		exit;
	}


	public function link($action = NULL, $other = NULL)
	{
		return '?page=' . self::DOMAIN . ($action ? ('&payspage=' . urlencode($action)) : '') . $other;
	}


	private function renderDefault()
	{
		$error = NULL;
		if (isset($_GET['check'])) {
			$client = $this->getSmsClient();
			$stats = $client->getAccountStatus();
			$date = new DateTime;
			$this->updateOptions(array(
				'credit' => $stats['credit'],
				'credit_last_update' => $date->format('Y-m-d G:i:s'),
			));
			$this->redirect();
		}
		if (isset($_POST['_send'])) {
			$number = $_POST['number'];
			$text = $_POST['text'];
			if (!Validators::validateNumber($number)) {
				$error = 'Invalid phone number';
			} elseif (!trim($text)) {
				$error = 'Please provide SMS text.';
			} else {
				$this->sendSms($number, $text);
				$this->redirect();
			}
		}
		$lastWeek = new DateTime('-1 week');
		$lastWeek = $lastWeek->format('Y-m-d G:i:s');
		return array(
			'text' => $this->getQuery('text'),
			'number' => $this->getQuery('number'),
			'error' => $error,
			'totalSent' => $this->database->selectOne('COUNT(*)', self::TABLE_MESSAGES, 'WHERE `status` IN (%d, %d)', array(self::STATUS_SENT, self::STATUS_DELIVERED)),
			'totalDelivered' => $this->database->selectOne('COUNT(*)', self::TABLE_MESSAGES, 'WHERE `status` = %d', array(self::STATUS_DELIVERED)),
			'lastWeekSent' => $this->database->selectOne('COUNT(*)', self::TABLE_MESSAGES, 'WHERE `status` IN (%d, %d) AND `date_sent` > %s', array(self::STATUS_SENT, self::STATUS_DELIVERED, $lastWeek)),
			'lastWeekDelivered' => $this->database->selectOne('COUNT(*)', self::TABLE_MESSAGES, 'WHERE `status` = %d AND `date_sent` > %s', array(self::STATUS_DELIVERED, $lastWeek)),
			'totalErrors' => $this->database->selectOne('COUNT(*)', self::TABLE_MESSAGES, 'WHERE `status` = %d', array(self::STATUS_ERROR)),
			'lastWeekErrors' => $this->database->selectOne('COUNT(*)', self::TABLE_MESSAGES, 'WHERE `status` = %d AND `date_sent` > %s', array(self::STATUS_ERROR, $lastWeek)),
			'lastCredit' => $this->getOption('credit'),
			'lastCreditUpdate' => $this->getOption('credit_last_update'),
		);
	}


	private function checkForNewSms()
	{
		$client = $this->getSmsClient();
		$reports = $client->getDeliveryReports();
		foreach ($reports as $id => $timestamp) {
			$date = date_create_from_format('YmdGis', $timestamp);
			$this->database->update(self::TABLE_MESSAGES, array('status' => self::STATUS_DELIVERED, 'date_delivered' => $date->format('Y-m-d G:i:s')), '`sms_id` = %s', array($id));
			$client->confirmDeliveryReport($id);
		}
		$date = new DateTime;
		$this->updateOptions(array('lastCheck' => $date->format('Y-m-d G:i:s')));
	}


	private function renderLogs()
	{
		$page = (int) $this->getQuery('list', 1);
		$count = (int) $this->database->selectOne('COUNT(*)', self::TABLE_MESSAGES);
		$perPage = 30;
		$pageCount = (int) ceil($count / $perPage);
		if ($page < 1) {
			$page = 1;
		}
		if ($page > $pageCount) {
			$page = $pageCount;
		}
		$pages = array(1, $pageCount, $page, $page - 1, $page - 2, $page + 1, $page + 2);
		if ($pageCount > 5) {
			for ($i = 1; $i <= $pageCount; $i += ($pageCount / 5)) {
				$pages[] = round($i);
			}
		}
		$pages = array_unique(array_filter($pages, function ($item) use ($pageCount) {
			return ($item >= 1) && ($item <= $pageCount);
		}));
		sort($pages);
		$results = array();
		if ($count) {
			$results = $this->database->select(self::TABLE_MESSAGES, 'ORDER BY `date_sent` DESC LIMIT ' . $perPage . ' OFFSET ' . ($page - 1) * $perPage);
		}
		$lastCheck = $this->getOption('lastCheck') ?: 'Never';

		if (isset($_GET['check'])) {
			$this->checkForNewSms();
			$this->redirect('logs', '&list=' . $page);
		}

		return array(
			'messages' => $results,
			'pages' => $pages,
			'list' => $page,
			'maxPage' => $pageCount,
			'lastCheck' => $lastCheck,
		);
	}


	private function renderSettings()
	{
		if (isset($_POST['_submit'])) {
			$password = trim($this->getPost('password'));
			$enabled = array();
			$enabledData = $this->getPost('enabled', array());
			foreach ($this->getStatuses() as $status => $name) {
				$enabled[$status] = isset($enabledData[$status]) && $enabledData[$status] === 'on';
			}
			$update = array(
				'name' => trim($this->getPost('name')),
				'texts' => $this->getPost('texts'),
				'enabled' => $enabled,
			);
			if ($password) {
				$update['password'] = $password;
			}
			$this->updateOptions($update);
		}

		return array(
			'settings' => $this->getOptions(),
			'statuses' => $this->getStatuses(),
		);
	}


	private function sendSms($number, $text)
	{
		$client = $this->getSmsClient();
		$date = new DateTime;
		$date = $date->format('Y-m-d G:i:s');
		try {
			$result = $client->sendSms($text, $number, TRUE);
			$this->database->insert(self::TABLE_MESSAGES, array(
				'sms_id' => $result['id'],
				'text' => $text,
				'number' => $number,
				'status' => self::STATUS_SENT,
				'date_sent' => $date,
			));
			$this->updateOptions(array(
				'credit' => $result['credit'],
				'credit_last_update' => $date,
			));
		} catch (Exception $exception) {
			$note = '';
			if ($exception instanceof SmsGateException) {
				$note = $exception->getMessage();
			}
			$this->database->insert(self::TABLE_MESSAGES, array(
				'sms_id' => NULL,
				'text' => $text,
				'number' => $number,
				'status' => self::STATUS_ERROR,
				'note' => $note,
				'date_sent' => $date,
			));
		}

	}


	private function render($template, $vars = array())
	{
		call_user_func_array(function () use ($template, $vars) {
			extract($vars);
			include $template;
		}, array());
	}


	private function updateOptions(array $options)
	{
		$current = $this->getOptions();
		foreach ($options as $key => $option) {
			$current[$key] = $option;
		}
		update_option(self::OPTION_NAME, $current);
	}


	/**
	 * @return array
	 */
	private function getOptions()
	{
		return get_option(self::OPTION_NAME);
	}


	private function deleteOptions()
	{
		delete_option(self::OPTION_NAME);
	}


	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	private function getOption($name, $default = NULL)
	{
		$options = $this->getOptions();
		return isset($options[$name]) ? $options[$name] : $default;
	}


	private function getStatuses()
	{
		return wc_get_order_statuses();
	}


	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	private function getPost($name, $default = NULL)
	{
		return isset($_POST[$name]) && $_POST[$name] ? $_POST[$name] : $default;
	}


	private function getQuery($name, $default = NULL)
	{
		return isset($_GET[$name]) && $_GET[$name] ? $_GET[$name] : $default;
	}


	private function getSmsClient()
	{
		return new SmsClient(new Client(), $this->getOption('name'), $this->getOption('password'));
	}


	private function getShortCodes(WC_Order $order = NULL)
	{
		if (!$order) {
			$order = (object) array( // dummy data
				'billing_first_name' => 'John',
				'billing_last_name' => 'Doe',
				'billing_address_1' => 'Narrow Street',
				'billing_address_2' => '145',
				'billing_city' => 'London',
				'billing_postcode' => 'B45 0HY',
				'shipping_first_name' => 'John',
				'shipping_last_name' => 'Doe',
				'shipping_address_1' => 'Wide Street',
				'shipping_address_2' => '285',
				'shipping_city' => 'Birmingham',
				'shipping_postcode' => '0HY B45',
				'order_total' => '1,785.43',
				'order_currency' => '€',
				'payment_method_title' => 'Bank transfer',
			);
		}
		return array(
			'name' => $order->billing_first_name . ' ' . $order->billing_last_name,
			'billingAddress' => $order->billing_address_1 . ' ' . $order->billing_address_2 . ', ' . $order->billing_city . ', ' . $order->billing_postcode,
			'price' => $order->order_total . $order->order_currency,
			'shippingAddress' => $order->shipping_address_1 . ' ' . $order->shipping_address_2 . ', ' . $order->shipping_city . ', ' . $order->shipping_postcode,
			'paymentMethod' => $order->payment_method_title,
		);
	}

}
