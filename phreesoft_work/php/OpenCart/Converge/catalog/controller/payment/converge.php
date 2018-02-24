<?php
class ControllerPaymentConverge extends Controller {
	public function index() {
		$this->load->language('payment/converge');

		$data['text_credit_card'] = $this->language->get('text_credit_card');
		$data['text_wait'] = $this->language->get('text_wait');

		$data['entry_cc_owner'] = $this->language->get('entry_cc_owner');
		$data['entry_frist'] = $this->language->get('entry_frist');
		$data['entry_last'] = $this->language->get('entry_last');
		$data['entry_cc_number'] = $this->language->get('entry_cc_number');
		$data['entry_cc_expire_date'] = $this->language->get('entry_cc_expire_date');
		$data['entry_cc_cvv2'] = $this->language->get('entry_cc_cvv2');

		$data['button_confirm'] = $this->language->get('button_confirm');

		if ($this->customer->isLogged()) {
		    $this->load->model('account/address');
		    $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
		    $data['cc_frist'] = preg_replace('/ /', '', $payment_address['firstname']);
		    $data['cc_last'] = preg_replace('/ /', '', $payment_address['lastname']);
		} elseif (isset($this->session->data['guest'])) {
		    $data['cc_frist'] = preg_replace('/ /', '', $this->session->data['guest']['firstname']);
		    $data['cc_last'] = preg_replace('/ /', '', $this->session->data['guest']['lastname']);
		}

			$data['months'] = array();

		for ($i = 1; $i <= 12; $i++) {
			$data['months'][] = array(
				'text'  => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['year_expire'] = array();

		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$data['year_expire'][] = array(
				'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
				'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
			);
		}

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/converge.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/converge.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/converge.tpl', $data);
		}

	}

	/**
	 * @todo test this function
	 * @todo figure out trial
	 * @todo transaction needed to occur.
	 * Sends up to 3 transactions to converge(trial, normal profile, payment).
     */
	public function send() {
	    $json = array();

	    $this->load->model('checkout/order');
	    $this->load->model('payment/converge');
	    $this->load->model('checkout/recurring');

	    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

	    $country_info = $this->db->query("SELECT `iso_code_3` FROM `" . DB_PREFIX . "country` WHERE `name` = '".$order_info['payment_country']."' AND `status` = '1' LIMIT 1")->row;
	    $country_info = $country_info['iso_code_3'];

        $order_total = '';
        $result = $this->paymentSend($order_info, $country_info, $order_total);
	    if(is_string($result)) {
	       $json['error'] = $result;
	       $this->response->addHeader('Content-Type: application/json');
	       return $this->response->setOutput(json_encode($json));
	    }

	    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'));

	    $json['success'] = $this->url->link('checkout/success');

	    $this->response->addHeader('Content-Type: application/json');
	    $this->response->setOutput(json_encode($json));
	}

    /**
     *
     */
 	public function paymentSend($order_info, $country_info, $order_total = '') {
	    $results_array = array();

	    if($order_total == '') $order_total = $order_info['total'];

	    $fields = array(
	        'ssl_transaction_type'   => ($this->config->get('converge_method') == 'cap') ? 'CCSALE' : 'CCAUTHONLY',
	        'ssl_card_number'        => preg_replace('/ /', '', $this->request->post['cc_number']),
	        'ssl_exp_date'           => $this->request->post['cc_expire_date_month'] . substr($this->request->post['cc_expire_date_year'], -2), // requires 2 digit year
	        'ssl_amount'             => $this->currency->format($order_total, $order_info['currency_code'], 1.00, false),
	        'ssl_cvv2cvc2'           => isset($this->request->post['cc_cvv2']) ? substr("0000".$this->request->post['cc_cvv2'], substr(preg_replace('/[^0-9]/', '', $this->request->post['cc_number']),0,2)=='37' ? -4 : -3) : '',
	        'ssl_invoice_number'     => $this->session->data['order_id'],
	        'ssl_cvv2cvc2_indicator' => isset($this->request->post['cc_cvv2']) ? '1' : '9', // if cvv2 exists, present else not present
	        'ssl_description'        => html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'),
	        'ssl_company'            => html_entity_decode($order_info['payment_company'], ENT_QUOTES, 'UTF-8'),
	        'ssl_first_name'         => html_entity_decode(substr($this->request->post['cc_frist'], 0, 15), ENT_QUOTES, 'UTF-8'), // recommended for hand-keyed transactions, bizuno uses company
	        'ssl_last_name'          => html_entity_decode(substr($this->request->post['cc_last'], 0, 14), ENT_QUOTES, 'UTF-8'), // recommended for hand-keyed transactions, bizuno uses company
	        'ssl_address2'           => html_entity_decode(substr($order_info['payment_address_2'],0,30), ENT_QUOTES, 'UTF-8'),
	        'ssl_avs_address'        => html_entity_decode(substr($order_info['payment_address_1'],0,30), ENT_QUOTES, 'UTF-8'),
	        'ssl_city'               => html_entity_decode($order_info['payment_city'], ENT_QUOTES, 'UTF-8'),
	        'ssl_state'              => html_entity_decode($order_info['payment_zone'], ENT_QUOTES, 'UTF-8'),
	        'ssl_country'            => $country_info,
	        'ssl_avs_zip'            => html_entity_decode(substr($order_info['payment_postcode'],0,9), ENT_QUOTES, 'UTF-8'),
	        'ssl_phone'              => substr(preg_replace('/[^0-9]/', '', $order_info['telephone']), 0, 14),
	        'ssl_email'              => $order_info['email'],
	    );

	    $results_array = $this->model_payment_converge->sendRequest($fields);

	    if(array_key_exists('ssl_result', $results_array)) {
	        if($results_array['ssl_result'] == 0) { //approved

	            $message = '';

	            if(array_key_exists('ssl_approval_code',$results_array)) {
	                $message .= 'Approval Code: ' . $results_array['ssl_approval_code'] . "\n";
	            }

	            if(array_key_exists('ssl_avs_response',$results_array)) {
	                $message .= 'AVS Response: ' . $results_array['ssl_avs_response'] . "\n";
	            }

	            if(array_key_exists('ssl_txn_id',$results_array)) {
	                $message .= 'Transaction ID: ' . $results_array['ssl_txn_id'] . "\n";
	            }

	            if(array_key_exists('ssl_result_message',$results_array)) {
	                $message .= 'Results message: ' . $results_array['ssl_result_message'] . "\n";
	            }

	            if(array_key_exists('ssl_cvv2_response',$results_array)) {
	                $message .= 'CVV Results: ' . $results_array['ssl_cvv2_response'] . "\n";
	            }

	            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('converge_order_status_id'), $message, false);

	            return true;

	        } else { //handels Converge declined

	            $this->log->write("Order (".$order_info['order_id'].") Declined: ".$results_array['ssl_result_message']);

	            return "Declined: ".$results_array['ssl_result_message'];
	        }
	    } else if(array_key_exists('errorCode', $results_array)) { //handels Converge errors

	        $this->log->write("CONVERGE RESPONSE ERROR: ".$results_array['errorCode'].":".$results_array['errorName']."::".$results_array['errorMessage']);

	        return "CONVERGE RESPONSE ERROR: ".$results_array['errorCode'].":".$results_array['errorName']."::".$results_array['errorMessage'];

	    } else {

	        $this->log->write("CONVERGE RESPONSE ERROR: UNKNOW");

	        return "CONVERGE RESPONSE ERROR: UNKNOW";

	    }
	}
}
?>