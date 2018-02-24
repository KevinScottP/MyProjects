<?php
class ModelPaymentConverge extends Model {
	public function getMethod($address, $total) {
		$this->load->language('payment/converge');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('converge_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('converge_total') > 0 && $this->config->get('converge_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('converge_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'converge',
				'title'      => $this->language->get('text_title'),
			    'terms'      => '',
				'sort_order' => $this->config->get('converge_sort_order')
			);
		}

		return $method_data;
	}

	/**
	 * This runs a request to the converge test or live server and returns a responce. User information is
	 * atomaticaly added to the feilds string.
	 *
	 * @param array $feilds - the request array.
	 * @return string|array - error result or a array of the responce (still need to handle converge errors)
     */
	public function sendRequest( $feilds ) {
	    $url = 'https://www.myvirtualmerchant.com/VirtualMerchant/process.do';

	    $settings = array(
	        'ssl_merchant_id'        => $this->config->get('converge_merchant_id'),
	        'ssl_user_id'            => $this->config->get('converge_user_id'),
	        'ssl_pin'                => $this->config->get('converge_pin'),
	        'ssl_card_present'       => 'N',
	        'ssl_show_form'          => 'FALSE',
	        'ssl_result_format'      => 'ASCII',
	    );

	    if ($this->config->get('converge_server') == 'live') {
//      	        $settings['ssl_test_mode'] = 'FALSE';
	    } else {
     	        $settings['ssl_test_mode'] = 'TRUE';
	    }

	    $curl = curl_init($url);

	    curl_setopt($curl, CURLOPT_PORT, 443);
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	    //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); //added
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
	    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
	    curl_setopt($curl, CURLOPT_POST, 1);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
	    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array_merge($settings, $feilds), '', '&'));
//	       $this->log->write('CONVERGE send: ' . print_r(array_merge($settings, $feilds),true));
	    $response = curl_exec($curl);
//	       $this->log->write('CONVERGE responce: ' . print_r($response,true));
	    if (curl_error($curl)) {
	        $this->log->write('CONVERGE CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl));

	        curl_close($curl);

	        return 'CURL ERROR: ' . curl_errno($curl) . '::' . curl_error($curl);
	    } elseif ($response) {
	        curl_close($curl);

	        return $this->formateResponse($response);
	    } else {
			$this->log->write('CONVERGE CURL ERROR: Empty Gateway Response');

			curl_close($curl);

			return 'Empty Gateway Response';
		}
	}

	/**
	 * Puts the responce in a array(field[value], ... ).
	 *
	 * @param string $response - string responce from converge
	 * @return array - responce in the form of a array with every feild as a key and vaule as vaule pairs i.e. array(field[value], ... ).
     */
	protected function formateResponse($response) {
	    $results = explode("\n", $response);
	    $results_array = array();
	    foreach($results as $result) {
	        $temp = explode("=", $result);
	        $results_array[$temp[0]] = $temp[1];
	    }
	    return $results_array;
	}
}
?>