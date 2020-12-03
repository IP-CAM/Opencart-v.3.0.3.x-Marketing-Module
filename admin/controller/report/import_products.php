<?php
class ControllerReportImportProducts extends Controller
{
	private $error = array();
	public function import()
	{

		$this->load->language('report/import_products');
		$this->load->model('report/import_products');

		$json = array();
		$warning_msg = '';
		if (isset($this->session->data['user_id']) && !empty($this->session->data['user_token'])) {
			if (!$this->user->hasPermission('modify', 'report/import_products')) {
				$json['error'] = $this->language->get('error_permission');
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));
			}

			if (isset($this->request->files['import']['tmp_name']) && is_uploaded_file($this->request->files['import']['tmp_name'])) {
				$filename = tempnam(DIR_UPLOAD, 'bac');

				move_uploaded_file($this->request->files['import']['tmp_name'], $filename);
			} elseif (isset($this->request->get['import'])) {
				$filename = DIR_UPLOAD . basename(html_entity_decode($this->request->get['import'], ENT_QUOTES, 'UTF-8'));
			} else {
				$filename = '';
			}

			$name = isset($this->request->post['name']);
			$tags = isset($this->request->post['tags']);

			$quantity = isset($this->request->post['quantity']);
			$order = isset($this->request->post['order']);
			$price = isset($this->request->post['price']);
			$delivery = isset($this->request->post['delivery']);
			$status = isset($this->request->post['status']);
			$points = isset($this->request->post['points']);
			$sku = isset($this->request->post['sku']);

			$manufacturer = isset($this->request->post['manufacturer']);

			$category = isset($this->request->post['category']);

			$special = isset($this->request->post['special']);

			$url = isset($this->request->post['url']);

			$rewards = isset($this->request->post['rewards']);

			$related = isset($this->request->post['related']);

			if (!is_file($filename)) {
				$json['error'] = $this->language->get('error_file');
			}

			if (isset($this->request->get['position'])) {
				$position = $this->request->get['position'];
			} else {
				$position = 0;
			}


			$total_line = '';
			$seo_permission = $this->model_report_import_products->getPermission($this->session->data['user_id']);
			if (!$json) {
				// We set $i so we can batch execute the queries rather than do them all at once.
				$i = 0;
				$start = false;
				$partes_ruta = pathinfo($filename);

				$handle = fopen($filename, 'r');


				fseek($handle, $position, SEEK_SET);

				$price_col_query = 'price = (CASE product_id ';
				$quan_col_query = 'quantity = (CASE product_id ';
				$points_col_query = 'points = (CASE product_id ';
				$status_col_query = 'status = (CASE product_id ';
				$order_col_query = 'sort_order = (CASE product_id ';
				$delivery_col_query = 'CASE product_id ';
				$sku_col_query = 'sku = (CASE product_id ';
				$manufacturer_col_query = 'manufacturer_id = (CASE product_id ';

				$special_array = array();
				$rewards_array = array();
				$products_to_category = array();
				$seo = array();


				$name_col_query = 'name = (CASE product_id ';


				$product_ids = ' WHERE product_id IN';

				// positions
				$name_pos = 0;
				$tags_pos = 0;

				$quantity_pos = 0;
				$order_pos = 0;
				$price_pos = 0;
				$delivery_pos = 0;
				$status_pos = 0;
				$points_pos = 0;
				$url_pos = 0;
				$rewards_pos = 0;
				$related_pos = 0;
				$sku_pos = 0;
				$model_pos = 0;

				$special_pos = 0;
				$special_start_pos = 0;
				$special_end_pos = 0;

				$categoria_pos = 0;

				while (!feof($handle) && ($i < 200)) {
					$position = ftell($handle);

					$line = fgetcsv($handle, 10000, ";");
					if ($line != null) {

						if ($i == 0) {
							$name_pos = array_search('Name', $line);
							$tags_pos = array_search('Tags', $line);

							$quantity_pos = array_search('Quantity', $line);
							$order_pos = array_search('Order', $line);
							$price_pos = array_search('Price', $line);
							$delivery_pos = array_search('Delivery', $line);
							$status_pos = array_search('Status', $line);
							$points_pos = array_search('Points', $line);
							$sku_pos = array_search('Sku', $line);

							$categoria_pos = array_search('Categories', $line);

							$special_pos = array_search('Special Price', $line);
							$special_start_pos = array_search('Special Start Date', $line);
							$special_end_pos = array_search('Special End Date', $line);

							$url_pos = array_search('Seo', $line);

							$rewards_pos = array_search('Rewards', $line);

							$related_pos = array_search('Related', $line);

							$model_pos = array_search('Model', $line);
						} else {
							try {
								$product_id = $this->model_report_import_products->getProductIdByModel($line[$model_pos]);

								if ($product_id != null) {
									if ($price && $price_pos > 0) {
										if (intval($line[$price_pos]) > 0) {
											$price_col_query .=  ' WHEN ' . $product_id .  ' THEN ' . intval($line[$price_pos]);
										} else {
											$warning_msg .=  $line[$model_pos] . ', ';
										}
									}

									if ($quantity && $quantity_pos > 0) {
										$quan_col_query .=  ' WHEN ' . $product_id .  ' THEN ' . intval($line[$quantity_pos]);
									}

									if ($status && $status_pos > 0) {
										$status_col_query .=  ' WHEN ' . $product_id .  ' THEN ' . intval($line[$status_pos]);
									}

									if ($points && $points_pos > 0) {
										$points_col_query .=  ' WHEN ' . $product_id .  ' THEN ' . intval($line[$points_pos]);
									}

									if ($sku && $sku_pos > 0) {
										if ($line[$sku_pos] != '') {
											$sku_col_query .=  ' WHEN ' . $product_id .  " THEN '" . $this->model_report_import_products->escapeString($line[$sku_pos]) . "'";
										}
									}

									if ($order && $order_pos > 0) {
										$order_col_query .=  ' WHEN ' . $product_id .  ' THEN ' . intval($line[$order_pos]);
									}

									if ($name && $name_pos > 0) {
										if ($line[$name_pos] != '') {
											$name_col_query .=  " WHEN " . $product_id .  " THEN '" . $this->model_report_import_products->escapeString($this->cleanString($line[$name_pos])) . "'";
										} else {
											$warning_msg .= 'Incorrect name for: ' .  $line[$model_pos] . ', ';
										}
									}

									if ($special && $special_pos > 0) {
										$special_array[$product_id] = array();
										$special_array[$product_id]['price'] = intval($line[$special_pos]);
										$special_array[$product_id]['start'] = $special_start_pos > 0 ? $line[$special_start_pos] : '0000-00-00';
										$special_array[$product_id]['end'] = $special_end_pos > 0 ? $line[$special_end_pos] : '0000-00-00';
									}

									if ($rewards && $rewards_pos > 0) {
										$rewards_array[$product_id] =  intval($line[$rewards_pos]);
									}

									if ($category && $categoria_pos > 0) {
										if ($line[$categoria_pos] != '') {
											$products_to_category[$product_id] =  $line[$categoria_pos];
										} else {
											$warning_msg .= 'Incorrect category for: ' .  $line[$model_pos] . ' -> ' . $line[$categoria_pos] . ',<br/>';
										}
									}

									if ($url && $url_pos > 0) {
										if ($line[$url_pos] != '' && $this->validateAlphaNumericUnderscore($line[$url_pos])) {
											$key = "product_id=" . $product_id;
											$seo[$key] =  $this->validateSeo($line[$url_pos]);
										} else {
											$warning_msg .= 'Incorrect seo for: ' .  $line[$model_pos] . ' -> ' . $line[$url_pos] . ',<br/>';
										}
									}

									$product_ids .= strpos($product_ids, '(') == false ? ' ( ' . $product_id : ', ' . $product_id;
								}
							} catch (Exception $e) {
								$json['error'] = 'Unexpected error, try again later.';
							}
						}
					}


					$i++;
				}


				$price_col_query .= ' END )';
				$quan_col_query .= ' END )';
				$points_col_query .= ' END )';
				$status_col_query .= ' END )';
				$order_col_query .= ' END )';
				$delivery_col_query .= ' END )';
				$sku_col_query .= ' END )';
				$manufacturer_col_query .= ' END )';

				$name_col_query .= ' END )';

				$full_product_query = '';

				if (strpos($price_col_query, 'THEN') !== false) {
					$full_product_query .=  $price_col_query;
				}

				if (strpos($quan_col_query, 'THEN') !== false) {
					$full_product_query .= $full_product_query != '' ? ', ' . $quan_col_query : $quan_col_query;
				}

				if (strpos($points_col_query, 'THEN') !== false) {
					$full_product_query .= $full_product_query != '' ? ', ' . $points_col_query : $points_col_query;
				}

				if (strpos($status_col_query, 'THEN') !== false) {
					$full_product_query .= $full_product_query != '' ? ', ' . $status_col_query : $status_col_query;
				}

				if (strpos($sku_col_query, 'THEN') !== false) {
					$full_product_query .= $full_product_query != '' ? ', ' . $sku_col_query : $sku_col_query;
				}

				if (strpos($order_col_query, 'THEN') !== false) {
					$full_product_query .= $full_product_query != '' ? ', ' . $order_col_query : $order_col_query;
				}

				if (strpos($manufacturer_col_query, 'THEN') !== false) {
					$full_product_query .= $full_product_query != '' ? ', ' . $manufacturer_col_query : $manufacturer_col_query;
				}

				$full_product_query .= $product_ids . ' )';


				$full_description_query = '';

				if (strpos($name_col_query, 'THEN') !== false) {
					$full_description_query .= $full_description_query != '' ? ', ' . $name_col_query : $name_col_query;
				}

				$full_description_query .= $product_ids . ' )';

				try {
					if (strpos($full_product_query, 'THEN') !== false) {
						$this->model_report_import_products->updateProduct($full_product_query);
					}

					if (strpos($full_description_query, 'THEN') !== false) {
						$result = $this->model_report_import_products->updateProductDescription($full_description_query);
						if ($result != true) {
							$json['error'] = 'Unexpected error, try again later. ';
						}
					}

					if ($special && $special_pos > 0 && count($special_array) > 0) {
						$result = $this->model_report_import_products->updateSpecials($special_array);
						if ($result != true) {
							$json['error'] = 'Unexpected error, try again later. ';
						}
					}

					if ($rewards && $rewards_pos > 0 && count($rewards_array) > 0) {
						$result = $this->model_report_import_products->updateRewards($rewards_array);
						if ($result != true) {
							$json['error'] = 'Unexpected error, try again later. ';
						}
					}

					if ($products_to_category && $categoria_pos > 0 && count($products_to_category) > 0) {
						$result = $this->model_report_import_products->updateCategories($products_to_category);
						if ($result != true) {
							$json['error'] = 'Unexpected error, try again later. ';
						}
					}

					if ($url && $url_pos > 0  && count($seo) > 0) {
						$result = $this->model_report_import_products->updateSeo($seo);
						if ($result !== true) {
							$json['error'] = 'Unexpected error, try again later. ';
						}
					} else if ($url && $seo_permission !== true) {
						$warning_msg .= "You dont have permission to update Seo. <br/>" . $seo_permission . " " . $this->session->data['user_id'];
					}

					$json['success'] = $this->language->get('text_success');
				} catch (Exception $e) {
					$json['error'] = 'Unexpected error, try again later. ' . $e->getMessage();
				}


				$position = ftell($handle);

				$size = filesize($filename);

				$json['total'] = round(($position / $size) * 100);

				if ($position && !feof($handle)) {

					fclose($handle);
				} else {
					fclose($handle);

					unlink($filename);

					$this->cache->delete('*');
				}
			}
			if ($warning_msg != '') {
				$json['warning'] = 'Warning: Please review these products, they were not updated. ' . $warning_msg;
			}
			$json['lines'] = $total_line;
		} else {
			$json['error'] = $this->language->get('error_permission');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function index()
	{
		$this->load->language('report/import_products');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];

			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('report/import_products', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['export'] = $this->url->link('report/import_products/export', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('report/import_products');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('report/import_products', $data));
	}

	function cleanString($text)
	{
		$search = explode(
			",",
			"ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,ø,Ø,Å,Á,À,Â,Ä,È,É,Ê,Ë,Í,Î,Ï,Ì,Ò,Ó,Ô,Ö,Ú,Ù,Û,Ü,Ÿ,Ç,Æ,Œ"
		);
		$replace = explode(
			",",
			"c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,o,O,A,A,A,A,A,E,E,E,E,I,I,I,I,O,O,O,O,U,U,U,U,Y,C,AE,OE"
		);
		return str_replace($search, $replace, $text);
	}

	function validateSeo($text)
	{
		$text = strtolower($text);
		$text = $this->cleanString($text);
		return $text;
		//	return preg_replace('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/i', "", $text);
	}

	function validateAlphaNumericUnderscore($string)
	{
		if (false === strpos($string, '--')) {
			return 0 < preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/i', $string);
		}
		return false;
	}


	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'report/import_products')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
