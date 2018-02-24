<?php
class ControllerPaymentConverge extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/converge');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('converge', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_test'] = $this->language->get('text_test');
		$data['text_live'] = $this->language->get('text_live');
		$data['text_authorization'] = $this->language->get('text_authorization');
		$data['text_capture'] = $this->language->get('text_capture');

		$data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
		$data['entry_user_id'] = $this->language->get('entry_user_id');
		$data['entry_pin'] = $this->language->get('entry_pin');
		$data['entry_server'] = $this->language->get('entry_server');
		$data['entry_mode'] = $this->language->get('entry_mode');
		$data['entry_method'] = $this->language->get('entry_method');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['help_total'] = $this->language->get('help_total');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant_id'])) {
			$data['error_merchant_id'] = $this->error['merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['user_id'])) {
			$data['error_user_id'] = $this->error['user_id'];
		} else {
			$data['error_user_id'] = '';
		}

		if (isset($this->error['pin'])) {
			$data['error_pin'] = $this->error['pin'];
		} else {
			$data['error_pin'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/converge', 'token=' . $this->session->data['token'], 'SSL'),
		);

		$data['action'] = $this->url->link('payment/converge', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['converge_merchant_id'])) {
			$data['converge_merchant_id'] = $this->request->post['converge_merchant_id'];
		} else {
			$data['converge_merchant_id'] = $this->config->get('converge_merchant_id');
		}

		if (isset($this->request->post['converge_user_id'])) {
			$data['converge_user_id'] = $this->request->post['converge_user_id'];
		} else {
			$data['converge_user_id'] = $this->config->get('converge_user_id');
		}

		if (isset($this->request->post['converge_pin'])) {
			$data['converge_pin'] = $this->request->post['converge_pin'];
		} else {
			$data['converge_pin'] = $this->config->get('converge_pin');
		}

		if (isset($this->request->post['converge_server'])) {
			$data['converge_server'] = $this->request->post['converge_server'];
		} else {
			$data['converge_server'] = $this->config->get('converge_server');
		}

		if (isset($this->request->post['converge_method'])) {
			$data['converge_method'] = $this->request->post['converge_method'];
		} else {
			$data['converge_method'] = $this->config->get('converge_method');
		}

		if (isset($this->request->post['converge_total'])) {
			$data['converge_total'] = $this->request->post['converge_total'];
		} else {
			$data['converge_total'] = $this->config->get('converge_total');
		}

		if (isset($this->request->post['converge_order_status_id'])) {
			$data['converge_order_status_id'] = $this->request->post['converge_order_status_id'];
		} else {
			$data['converge_order_status_id'] = $this->config->get('converge_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['converge_geo_zone_id'])) {
			$data['converge_geo_zone_id'] = $this->request->post['converge_geo_zone_id'];
		} else {
			$data['converge_geo_zone_id'] = $this->config->get('converge_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['converge_status'])) {
			$data['converge_status'] = $this->request->post['converge_status'];
		} else {
			$data['converge_status'] = $this->config->get('converge_status');
		}

		if (isset($this->request->post['converge_sort_order'])) {
			$data['converge_sort_order'] = $this->request->post['converge_sort_order'];
		} else {
			$data['converge_sort_order'] = $this->config->get('converge_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/converge.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/converge')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['converge_merchant_id']) {
			$this->error['merchant_id'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['converge_user_id']) {
			$this->error['user_id'] = $this->language->get('error_user_id');
		}

		if (!$this->request->post['converge_pin']) {
			$this->error['pin'] = $this->language->get('error_pin');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
?>