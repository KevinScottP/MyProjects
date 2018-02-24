<?php
/**
 * This is the admin class for the Bizuno/PrestaShop interface. It handles installo, uninstall, and hooks
 * @name Bizuno PrestaShop Interface (admin part)
 * @copyright 2014, PhreeSoft, www.PhreeSoft.com
 * @author PhreeSoft, (Kevin)
 * @version 1.0 Last Update: 2014-10-27
 */

// Set the module identity
if (!defined('BIZUNO_APP_TITLE')) define('BIZUNO_APP_TITLE', 'Bizuno'); // choices are Bizuno OR PhreeBooks

// TO STOP FROM VEIWING PAGE
if (!defined('_PS_VERSION_')) die('invalid access!');

/**
 * This is the main entrypoint for the administrator. It handles install, uninstall, and configuration.
 * @author PhreeSoft
 *
 */
class Bizuno extends Module {

	/**
	 * Needed for prestashop to see the module
	 */
	public function __construct() {
		$this->name					  = 'bizuno';
		$this->tab					  = 'export';
		$this->version				  = '1.0';
		$this->author				  = 'PhreeSoft';
		$this->need_instance		  = 1;
		$this->ps_versions_compliancy = array('min'=>'1.5', 'max'=>_PS_VERSION_);
		$this->dependencies			  = array('blockcart');
		$this->bootstrap			  = true;

		parent::__construct ();

		$this->displayName		= $this->l(BIZUNO_APP_TITLE.' Interface');
		$this->description		= $this->l('This is to transfer order and product information back to '.BIZUNO_APP_TITLE.'.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
		// display warnings for required feilds missing
		if (!Configuration::get('BIZ_URL'))  $this->warning = $this->l('No '.BIZUNO_APP_TITLE.' url provided');
		if (!Configuration::get('BIZ_USER')) $this->warning = $this->l('No '.BIZUNO_APP_TITLE.' user provided.');
		if (!Configuration::get('BIZ_PW'))   $this->warning = $this->l('No '.BIZUNO_APP_TITLE.' pw provided.');
	}

	/**
	 *
	 * @return boolean
	 */
	public function install() {
		// check to see if multistore is disabled.
		if (Shop::isFeatureActive ()) return false;
		// install and register hooks
		if (! parent::install () || ! $this->registerHook ( 'displayAdminOrder' )) return false;
		$db = Db::getInstance();
		// install sku field
		if (!$this->dbFieldExists(_DB_PREFIX_."order_bizuno", 'sku')) {
			$db->execute("ALTER TABLE `"._DB_PREFIX_."product` ADD `sku` VARCHAR(24) NOT NULL DEFAULT '' COMMENT 'Bizuno SKU' AFTER `upc`;");
		}
		// install table
		$db->execute("CREATE TABLE `"._DB_PREFIX_."order_bizuno` (
		  `id_order` int(11) NOT NULL,
		  `transaction_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `transaction_mode` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
		  `hint` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `order_exported` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
		  `order_confirmed` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id_order`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		// load defaults
		if (!Configuration::updateValue('BIZ_URL', 'https://www.yourdomain.com') || !Configuration::updateValue ('BIZ_USER', '') || !Configuration::updateValue('BIZ_PW', '') || !Configuration::updateValue('BIZ_PREFIX', 'ps_'))
			return false;
		return true;
	}

	/**
	 *
	 * @return boolean
	 */
	public function uninstall() {
		// run uninstall
		if (!parent::uninstall()) return false;
		// clean up db
		Db::getInstance ()->execute('DELETE FROM `'._DB_PREFIX_.'order_bizuno`');
		// clean up the settings
		if (!Configuration::deleteByName('BIZ_URL') || !Configuration::deleteByName('BIZ_USER') || !Configuration::deleteByName('BIZ_PW') || !Configuration::deleteByName('BIZ_PREFIX'))
			return false;
		return true;
	}

	/**
	 * validates the form data.
	 * @return string
	 */
	public function getContent() {
		$output = null;

		if (Tools::isSubmit('submit'.$this->name )) {
			$biz_user = strval(Tools::getValue('BIZ_USER'));
			if (! $biz_user || empty ( $biz_user )) $output .= $this->displayError($this->l('No '.BIZUNO_APP_TITLE.' user provided.'));
			else Configuration::updateValue('BIZ_USER', $biz_user);

			$biz_pw = strval(Tools::getValue('BIZ_PW'));
			if (!$biz_pw || empty($biz_pw)) $output .= $this->displayError($this->l('No '.BIZUNO_APP_TITLE.' pw provided.'));
			else Configuration::updateValue('BIZ_PW', $biz_pw);

			$biz_url = strval(Tools::getValue('BIZ_URL'));
			if (!isset($biz_url)) $output .= $this->displayError($this->l('Invalid Configuration value'));
			else Configuration::updateValue('BIZ_URL', $biz_url);

			$biz_comp = strval(Tools::getValue('BIZ_COMP'));
			if (!isset($biz_comp)) $output .= $this->displayError($this->l('Invalid Configuration value'));
			else Configuration::updateValue('BIZ_COMP', $biz_comp);

			$biz_prefix = strval(Tools::getValue('BIZ_PREFIX'));
			if (!isset($biz_prefix)) $output .= $this->displayError($this->l('Invalid Configuration value'));
			else Configuration::updateValue('BIZ_PREFIX', $biz_prefix);

			$biz_confirm = strval(Tools::getValue('BIZ_CONFIRM_STATUS'));
			if (!isset($biz_confirm)) $output .= $this->displayError($this->l('Invalid Ship Confirmation Status'));
			else Configuration::updateValue('BIZ_CONFIRM_STATUS', $biz_confirm);

			if (!is_string($output)) $output .= $this->displayConfirmation($this->l('Settings updated'));
		}
		return $output . $this->displayForm();
	}

	/**
	 * Displays the admin page
	 */
	public function displayForm() {
		// Get default language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		// Init Fields form array
		$fields_form[0]['form'] = array (
			'legend' => array (
				'title' => $this->l('Settings'),
			),
			'input' => array (
				array (
					'type' => 'text',
					'label' => $this->l ( BIZUNO_APP_TITLE.' Login Username' ),
					'name' => 'BIZ_USER',
					'size' => 30,
					'required' => true,
				),
				array (
					'type' => 'password',
					'label' => $this->l ( BIZUNO_APP_TITLE.' Login Password' ),
					'name' => 'BIZ_PW',
					'size' => 30,
					'required' => true,
				),
			    array (
			        'type' => 'text',
			        'label' => $this->l ( 'Download PhreeBook Url (Leave blank for bizuno users)' ),
			        'name' => 'BIZ_URL',
			        'size' => 50,
			        'required' => false,
			    ),
			    array (
			        'type' => 'text',
			        'label' => $this->l ( 'Download Company (Leave blank for one company)' ),
			        'name' => 'BIZ_COMP',
			        'size' => 50,
			        'required' => false,
			    ),
				array (
					'type' => 'text',
					'label' => $this->l ( BIZUNO_APP_TITLE.' Order Prefix (Leave blank for no prefix)' ),
					'name' => 'BIZ_PREFIX',
					'size' => 20,
					'required' => false,
				),
				array(
					'type' => 'select',
					'label' => $this->l('Status to set upon confirmed shipments'),
					'name' => 'BIZ_CONFIRM_STATUS',
					'required' => false,
					'options' => array(
						'default' => array('value' => 0, 'label' => $this->l('Select Status')),
						'query' => OrderState::getOrderStates((int)Context::getContext()->language->id),
						'id' => 'id_order_state',
						'name' => 'name',
					),
				),
			),
			'submit' => array (
				'title' => $this->l ( 'Save' ),
				'class' => 'button',
			)
		);

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true; // false -> remove toolbar
		$helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array (
			'save' => array (
				'desc' => $this->l ( 'Save' ),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules')
			),
			'back' => array (
				'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite ( 'AdminModules' ),
				'desc' => $this->l ( 'Back to list' ),
			)
		);

		// Load current value
		$helper->fields_value ['BIZ_USER'] = Configuration::get('BIZ_USER');
		$helper->fields_value ['BIZ_PW'] = Configuration::get('BIZ_PW');
		$helper->fields_value ['BIZ_URL'] = Configuration::get('BIZ_URL');
		$helper->fields_value ['BIZ_COMP'] = Configuration::get('BIZ_COMP');
		$helper->fields_value ['BIZ_PREFIX'] = Configuration::get('BIZ_PREFIX');
		$helper->fields_value ['BIZ_CONFIRM_STATUS'] = Configuration::get('BIZ_CONFIRM_STATUS');

		return $helper->generateForm ( $fields_form );
	}

	/**
	 * This calls the order display class
	 * TODO use ajax to download the order
	 *
	 * @param unknown $params
	 */
	public function hookDisplayAdminOrder($params) {
		$db = Db::getInstance();
		// find order_bizuno from table if does not exist, make it.
		$result = $db->getRow("SELECT * FROM `"._DB_PREFIX_."order_bizuno` WHERE `id_order`={$_GET['id_order']}");
		if ($result == false) {
			$db->execute("INSERT INTO `"._DB_PREFIX_."order_bizuno` set `id_order`={$_GET['id_order']}, `order_exported`='0'");
			$download = 0;
		} else {
			$download = isset($result['order_exported']) ? $result['order_exported'] : 0;
		}
		$this->context->smarty->assign(array(
			'downloaded'=> $download, // download or not
			'order'     => $params['id_order'],
		    'base_dir'  => '',
			'admin_url' => _PS_ROOT_DIR_,
			'ps_version'=> _PS_VERSION_,
			'biz_title' => $this->displayName,
			'biz_id'    => BIZUNO_APP_TITLE,
		));
		return $this->display(__FILE__, 'bizuno.tpl');
	}

	/**
	 * adds the order to the bizuno order table
	 * get other important information and add to table.
	 *
	 * @param unknown $params
	 */
	public function hookActionValidateOrder($params) {
		$order = $params ['order'];
		// $cart = $params['cart'];
		// $customer = $params['customer'];
		// $currency = $params['currency'];
		// $orderStatus = $params['orderStatus'];

		// if($hint !== '') {
		// $first_four = substr($hint, 0, 4);
		// $last_four = substr($hint, strlen($hint)-5, 4);
		// $hint = $first_four . "********" . $last_four;
		// }
		// $db->query("UPDATE `"._DB_PREFIX_."order_bizuno` SET `transaction_id` = '$transaction_id',`transaction_mode` = '$mode',`hint` = '$hint' WHERE `"._DB_PREFIX_."order`.`id_order` =$order_id;");

		$db = Db::getInstance();
		// add order to table;
		// error_log(print_r($params,true));
	}

	private function dbFieldExists($table, $field) {
		$db = Db::getInstance();
		$result = $db->query("SHOW FIELDS FROM `$table`");
		foreach ($result as $row) if ($row['Field'] == $field) return true;
		return false;
	}

}

?>