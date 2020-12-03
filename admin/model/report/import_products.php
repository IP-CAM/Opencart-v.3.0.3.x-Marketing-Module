<?php
class ModelReportImportProducts extends Model
{

	public function getProductIdByModel($model)
	{
		$query = $this->db->query("SELECT product_id, model FROM " . DB_PREFIX . "product WHERE model = '" . $model . "' LIMIT 1");
		if ($query->num_rows) {
			return $query->row['product_id'];
		} else {
			return null;
		}
	}

	public function updateProduct($query)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "product SET " . $query);
	}

	public function escapeString($str)
	{
		return  $this->db->escape($str);
	}

	public function updateProductDescription($query)
	{
		try {
			$this->db->query("UPDATE " . DB_PREFIX . "product_description SET " . $query);
			return true;
		} catch (Exception $e) {
			return  $e->getMessage() . ' <br/>' . $query;
		}
	}

	public function updateSpecials($special_array)
	{
		$products_id = array();
		$updated = array();

		$query_price_update = 'UPDATE ' . DB_PREFIX .  'product_special SET price = (CASE product_id ';
		$query_start_update = ', date_start = (CASE product_id ';
		$query_end_update = ', date_end = (CASE product_id ';

		$query_price_insert = 'INSERT INTO ' . DB_PREFIX .  'product_special (
			product_id,
			price,
			date_start,
			date_end,
			customer_group_id
		) VALUES ( ';

		foreach ($special_array as $key => $value) {
			array_push($products_id, $key);
		}


		try {
			$query = $this->db->query("SELECT product_special_id, product_id FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ")");
			$customer_group_id = 0;
			$customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
			if ($customer_query->num_rows) {
				$customer_group_id = $customer_query->row['customer_group_id'];
			}
			if ($query->num_rows) {
				foreach ($query->rows as $row) {
					array_push($updated, $row["product_id"]);
					$query_price_update .= ' WHEN ' . $row["product_id"] . ' THEN ' . intval($special_array[$row["product_id"]]["price"]);
					$start_date = $this->escapeString($special_array[$row["product_id"]]["start"]);
					$end_date = $this->escapeString($special_array[$row["product_id"]]["end"]);

					if ($start_date != '0000-00-00' && $start_date != '') {
						$query_start_update .= ' WHEN ' . $row["product_id"] . " THEN CAST('" . date('Y-m-d', strtotime($start_date)) . "' AS DATE) ";
					}
					if ($end_date != '0000-00-00'  && $end_date != '') {
						$query_end_update .= ' WHEN ' . $row["product_id"] . " THEN CAST('" . date('Y-m-d', strtotime($end_date)) . "' AS DATE) ";
					}
				}

				$query_price_update .= ' END ) ';
				if (strpos($query_price_update, 'THEN') !== false) {
					$query_price_update .= strpos($query_start_update, 'THEN') ?  $query_start_update . ' END ) ' :  '';
				}

				if (strpos($query_end_update, 'THEN') !== false) {
					$query_price_update .= strpos($query_end_update, 'THEN') ? $query_end_update . ' END ) ' :  ' END ) ';
				}
			}

			$count = false;

			foreach ($special_array as $key => $value) {
				if (!in_array($key, $updated)) {
					$query_price_insert .= $count ? ', (' : '';
					$count = true;
					$query_price_insert .= $key . ', ' . intval($special_array[$key]["price"]);
					$start_date = $this->escapeString($special_array[$key]["start"]);
					$end_date = $this->escapeString($special_array[$key]["end"]);


					$query_price_insert .= $start_date != '0000-00-00' && $start_date != '' ? ", CAST('" .  date('Y-m-d', strtotime($start_date))  . "' AS DATE) " : ', 0000-00-00 ';
					$query_price_insert .= $end_date != '0000-00-00' && $end_date != '' ? ", CAST('" .  date('Y-m-d', strtotime($end_date)) . "' AS DATE) " : ', 0000-00-00 ';

					$query_price_insert .= ', ' . $customer_group_id . ' )';
				}
			}

			if (strpos($query_price_insert, '), (') !== false) {
				$this->db->query($query_price_insert);
			}

			if (strpos($query_price_update, 'THEN') !== false) {
				$this->db->query($query_price_update);
			}

			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function updateRewards($rewards_array)
	{
		$products_id = array();
		$rewards_query = 'INSERT INTO ' . DB_PREFIX .  'product_reward (
			product_id,
			customer_group_id,
			points
		) VALUES ( ';

		$customer_group_id = 0;
		$customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
		if ($customer_query->num_rows) {
			$customer_group_id = $customer_query->row['customer_group_id'];
		}

		$count = false;
		foreach ($rewards_array as $key => $value) {
			if ($value != 0) {
				$rewards_query .= $count ? ', (' : '';
				$rewards_query .= $key . ", " . $customer_group_id  . ", " . $value . " ) ";
				$count = true;
			}
			array_push($products_id, $key);
		}

		try {
			//deleting exist prduct rewards
			$query = $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
			$query = $this->db->query($rewards_query);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function updateCategories($products_to_category)
	{
		$category = array();
		$map_category = array();

		$query_insert = 'INSERT INTO ' . DB_PREFIX .  'product_to_category (
			product_id,
			category_id) VALUES ( ';

		foreach ($products_to_category as $key => $value) {
			$categories = explode(";", $value);
			foreach ($categories as $cat) {
				array_push($category, "'" . $this->db->escape($cat) . "'");
			}
		}

		try {
			$query = $this->db->query("SELECT category_id, name FROM " . DB_PREFIX . "category_description WHERE name IN (" . implode(",", $category) . ")");
			if ($query->num_rows) {
				foreach ($query->rows as $row) {
					$map_category[$row['name']] = $row['category_id'];
				}

				$products_to_delete = array();
				if (count($map_category) > 0) {
					$count = false;
					foreach ($products_to_category as $key => $value) {
						$categories = explode(";", $value);
						foreach ($categories as $cat) {
							$name = $this->db->escape($cat);
							if (isset($map_category[$name])) {
								array_push($products_to_delete, $key);
								$query_insert .= $count == true ? ', (' . $key . ' , ' . $map_category[$name] . ' )' : $key . ' , ' . $map_category[$name] . ' )';
								$count = true;
							}
						}
					}
				}
				if (count($products_to_delete) > 0) {
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id IN (" . implode(",", $products_to_delete) . ")");
					$this->db->query($query_insert);
				}
			}
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	public function getPermission($user_id)
	{
		try {
			$query = $this->db->query(
				"SELECT " . DB_PREFIX . "user_group.name AS name FROM " . DB_PREFIX .
					"user INNER JOIN " . DB_PREFIX . "user_group ON " . DB_PREFIX .
					"user.user_group_id = " . DB_PREFIX . "user_group.user_group_id AND " . DB_PREFIX .
					"user.user_id = " . $user_id
			);
			if ($query->num_rows) {
				$name = strtolower($query->row['name']);
				if (strpos($name, 'admin') !== false) {
					return true;
				}
				return false;
			}
		} catch (Exception $e) {
			return false;
		}
	}

	public function updateSeo($product_to_seo)
	{
		$category = array();
		$map_category = array();
		$seo_list = array();

		$query_update = 'UPDATE ' . DB_PREFIX .  'seo_url SET keyword = (CASE query ';

		foreach ($product_to_seo as $key => $value) {
			if (in_array($this->db->escape($value), $seo_list) == false) {
				$query_update .= " WHEN '" . $this->db->escape($key) . "' THEN '" . $this->db->escape($value) . "'";
				array_push($seo_list, $this->db->escape($value));
			}
		}

		try {
			if (strpos($query_update, 'THEN') !== false) {
				$query_update .= ' END ) ';
				$this->db->query($query_update);
			}
		} catch (Exception $e) {
			return false;
		}
		return true;
	}
}
