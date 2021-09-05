<?php
class ControllerExtensionModuleBulkProductUpdate extends Controller {
	private $error = array();
	private $updates = [];
	private $total_products = 0;

	public function index() {
		$this->load->language('extension/module/bulk_product_update');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->updates = [];
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate() && $this->updateProducts()) {

			$this->session->data['success'] = sprintf($this->language->get('text_success'), $this->total_products);

			$this->response->redirect($this->url->link('extension/module/bulk_product_update', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['products'])) {
			$data['error_products'] = $this->error['products'];
		} else {
			$data['error_products'] = '';
		}

		if (isset($this->error['common'])) {
			$data['error_common'] = $this->error['common'];
		} else {
			$data['error_common'] = '';
		}

		if (isset($this->error['price_change_by'])) {
			$data['error_price_change_by'] = $this->error['price_change_by'];
		} else {
			$data['error_price_change_by'] = '';
		}

		if (isset($this->error['price_change_mode'])) {
			$data['error_price_change_mode'] = $this->error['price_change_mode'];
		} else {
			$data['error_price_change_mode'] = '';
		}

		if (isset($this->error['price_change_round_off_value'])) {
			$data['error_price_change_round_off_value'] = $this->error['price_change_round_off_value'];
		} else {
			$data['error_price_change_round_off_value'] = '';
		}

		if (isset($this->error['price_change_value'])) {
			$data['error_price_change_value'] = $this->error['price_change_value'];
		} else {
			$data['error_price_change_value'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/bulk_product_update', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/bulk_product_update', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/bulk_product_update', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/bulk_product_update', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['products'])) {
			$data['products'] = $this->request->post['products'];
		} else {
			$data['products'] = [];
		}

		if (isset($this->request->post['price_change_by'])) {
			$data['price_change_by'] = $this->request->post['price_change_by'];
		} else {
			$data['price_change_by'] = null;
		}

		if (isset($this->request->post['price_change_mode'])) {
			$data['price_change_mode'] = $this->request->post['price_change_mode'];
		} else {
			$data['price_change_mode'] = null;
		}

		if (isset($this->request->post['price_change_value'])) {
			$data['price_change_value'] = $this->request->post['price_change_value'];
		} else {
			$data['price_change_value'] = 0;
		}

		if (isset($this->request->post['price_change_round_off_value'])) {
			$data['price_change_round_off_value'] = $this->request->post['price_change_round_off_value'];
		} else {
			$data['price_change_round_off_value'] = 10;
		}
		
		if (isset($this->request->post['price_change_round_off'])) {
			$data['price_change_round_off'] = $this->request->post['price_change_round_off'];
		} else {
			$data['price_change_round_off'] = null;
		}

		// Get Categories
		$this->load->model('catalog/category');

		$results = $this->model_catalog_category->getCategories();

		foreach ($results as $result) {
			$data['categories'][] = array(
				'id' => $result['category_id'],
				'name'        => $result['name'],
			);
		}

		$data['user_token'] = $this->session->data['user_token'];
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/bulk_product_update', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/bulk_product_update')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->post['products'])) {
			$this->error['products'] = $this->language->get('error_products');
		}

		// Price change
		if(isset($this->request->post['price_change']) && $this->request->post['price_change'] == 1){
			$this->updates[] = 'price_change';

			if (!$this->request->post['price_change_by']) {
				$this->error['price_change_by'] = $this->language->get('error_price_change_by');
			}
	
			if (!$this->request->post['price_change_mode']) {
				$this->error['price_change_mode'] = $this->language->get('error_price_change_mode');
			}
	
			if ($this->request->post['price_change_value'] < 1) {
				$this->error['price_change_value'] = $this->language->get('error_price_change_value');
			}

			if ($this->request->post['price_change_round_off'] != 3) {
                if ($this->request->post['price_change_round_off_value'] != 10) {
                    $this->error['price_change_round_off_value'] = $this->language->get('error_price_change_round_off_value');
                }
			}
		}

		if(!$this->updates){
			$this->error['common'] = $this->language->get('error_updates');
		}

		if($this->error){
			$this->error['common'] = $this->language->get('error_common');
		}

		return !$this->error;
	}

	public function filterProducts()
	{
        $json = [];
        $results = [];

        if (isset($this->request->get['filter_category_id'])) {
            $this->load->model('catalog/product');
            
            $category_id = $this->request->get['filter_category_id'];

            $results = $this->model_catalog_product->getProductsByCategoryId($category_id);

            foreach ($results as $result) {
                $json[] = array(
                    'product_id' => $result['product_id'],
                    'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

	private function updateProducts()
	{
		$this->load->model('catalog/product');
		
		$products = $this->request->post['products'];
		
        foreach ($products as $product_id) {
			$product = $this->model_catalog_product->getProduct($product_id);

			if($product){
				$this->total_products++;

				if(in_array('price_change', $this->updates)){
					$this->updatePrice($product);
				}
			}
        }

		return true;
	}

	private function updatePrice($product)
	{		
		$price_change_by = (int) $this->request->post['price_change_by'];
		$price_change_mode = (int) $this->request->post['price_change_mode'];
		$price_change_value = (float) $this->request->post['price_change_value'];
		$price_change_round_off = (int) $this->request->post['price_change_round_off'];
		$price_change_round_off_value = (int) $this->request->post['price_change_round_off_value'];

		$current_price = (float) $product['price'];

		$price = $modified_price = 0;

		// Price change by
		switch ($price_change_by) {
			case 1:
				$modified_price = ($price_change_value / 100) * $current_price;
				break;
			case 2:
				$modified_price = $price_change_value;
				break;
			default:
				break;
		}

		// Price change mode
		switch ($price_change_mode) {
			case 1:
				$price = $current_price + $modified_price;
				break;
			case 2:
				$price = ($current_price > $modified_price) 
					? $current_price - $modified_price
					: 0;
				break;
			case 3:
				$price = $modified_price;
				break;
			default:
			break;
		}

		// Price round off
		if($price_change_round_off){
			switch ($price_change_round_off) {
				case 1:
					$price = ceil($price / $price_change_round_off_value) * $price_change_round_off_value;
					break;
				case 2:
					$price = floor($price / $price_change_round_off_value) * $price_change_round_off_value;
					break;
				default:
					break;
			}

			$price = (float) number_format($price, '4');
		}else{
			$price = round((float) $price, 4);
		}

		$data['product_id'] = $product['product_id'];
		$data['price'] = $price;

		$this->load->model('extension/module/bulk_product_update');

		$this->model_extension_module_bulk_product_update->updatePrice($data);
	}
}
