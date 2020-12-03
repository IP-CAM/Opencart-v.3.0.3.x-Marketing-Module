<?php
class ModelReportExportProducts extends Model
{
    public function getManufacturerList()
    {

        $manufacturer_list = array();

        if (!$manufacturer_list) {
            $query = $this->db->query("SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer");

            $manufacturer_list = $query->rows;
        }

        return $manufacturer_list;
    }

    public function getSkuList()
    {

        $sku_list = array();

        if (!$sku_list) {
            $query = $this->db->query("SELECT sku FROM " . DB_PREFIX . "product WHERE sku != '' GROUP BY sku");

            $sku_list = $query->rows;
        }

        return $sku_list;
    }

    public function getCategoryList()
    {

        $category_list = array();

        if (!$category_list) {
            $query = $this->db->query("SELECT category_id, name FROM " . DB_PREFIX . "category_description WHERE language_id = 1");
            $category_list = $query->rows;
        }

        return $category_list;
    }

    public function getByManufacturer($manufacturer_id, $checkboxes)
    {
        $data = array();
        $products_id = array();
        $seo = array();

        $manufacturer_id = $this->db->escape($manufacturer_id);
        $manufacturer_name = "";

        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = " . $manufacturer_id . " LIMIT 1");
        if ($query->num_rows) {
            $manufacturer_name =  $query->row['name'];
        }

        $checkboxes = explode(";", $checkboxes);

        $customer_group_id = 0;
        $customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
        if ($customer_query->num_rows) {
            $customer_group_id = $customer_query->row['customer_group_id'];
        }


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE manufacturer_id = " . $manufacturer_id);

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $product_id = $row["product_id"];
                array_push($products_id, $product_id);
                array_push($seo, "'product_id=" . $product_id . "'");
                $data[$product_id]['item'] = $product_id;
                $data[$product_id]['model'] = $row["model"];
                $data[$product_id]['name'] = "";
                $data[$product_id]['manufacturer'] = "";
                $data[$product_id]['prooveedor'] = "";
                $data[$product_id]['stock'] = "";
                $data[$product_id]['price'] = "";
                $data[$product_id]['point'] = "";
                $data[$product_id]['rewards'] = "";
                $data[$product_id]['order'] = "";
                $data[$product_id]['status'] = "";
                $data[$product_id]['category'] = "";
                $data[$product_id]['tags'] = "";
                $data[$product_id]['special'] = "";
                $data[$product_id]['start'] = "";
                $data[$product_id]['end'] = "";
                $data[$product_id]['url'] = "";
                $data[$product_id]['purchased'] = "";


                if (in_array('price', $checkboxes)) {
                    $data[$product_id]['price'] = intval($row["price"]);
                }
                if (in_array('quantity', $checkboxes)) {
                    $data[$product_id]['stock'] = intval($row["quantity"]);
                }
                if (in_array('manufacturer', $checkboxes)) {
                    $data[$product_id]['manufacturer'] = $manufacturer_name;
                }
                if (in_array('points', $checkboxes)) {
                    $data[$product_id]['point'] = intval($row["points"]);
                }
                if (in_array('sku', $checkboxes)) {
                    $data[$product_id]['prooveedor'] = $row["sku"];
                }
                if (in_array('order', $checkboxes)) {
                    $data[$product_id]['order'] = $row["sort_order"];
                }
                if (in_array('status', $checkboxes)) {
                    $data[$product_id]['status'] = $row["status"];
                }
            }
            if (in_array('name', $checkboxes) || in_array('tags', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, name, tag FROM " . DB_PREFIX . "product_description WHERE product_id IN (" . implode(",", $products_id) . ") AND language_id = 1");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['name'] = $this->cleanString($row["name"]);
                        $data[$product_id]['tags'] = $this->cleanString($row["tag"]);
                    }
                }
            }
            if (in_array('special', $checkboxes)) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['special'] = intval($row["price"]);
                        $data[$product_id]['start'] = $row["date_start"];
                        $data[$product_id]['end'] = $row["date_end"];
                    }
                }
            }
            if (in_array('rewards', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['rewards'] = intval($row["points"]);
                    }
                }
            }

            // if (in_array('manufacturer', $checkboxes)) {
            //     $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = " . $manufacturer_id);
            //     if ($query->num_rows) {
            //         foreach ($query->rows as $row) {
            //             $product_id = $row["product_id"];
            //             $data[$product_id]['rewards'] = $row["points"];
            //         }
            //     }
            // }

            if (in_array('category', $checkboxes)) {
                $query = $this->db->query("SELECT " . DB_PREFIX . "product_to_category.product_id AS product_id, " . DB_PREFIX . "category_description.name AS name FROM " . DB_PREFIX . "product_to_category INNER JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "product_to_category.category_id= " . DB_PREFIX . "category_description.category_id AND  " . DB_PREFIX . "product_to_category.product_id IN (" . implode(",", $products_id) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['category'] .= $data[$product_id]['category'] != '' ? ';' . $this->cleanString($row["name"]) : $this->cleanString($row["name"]);
                    }
                }
            }
            if (in_array('url', $checkboxes)) {
                $query = $this->db->query("SELECT query, keyword FROM " . DB_PREFIX . "seo_url WHERE query  IN (" . implode(",", $seo) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = substr($row["query"], strpos($row["query"], '=') + 1);
                        $data[$product_id]['url'] = $row["keyword"];
                    }
                }
            }


            if (in_array('purchased', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, SUM(quantity) AS total FROM " . DB_PREFIX . "order_product WHERE product_id IN (" . implode(",", $products_id) . ") GROUP BY  product_id");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['purchased'] = $row["total"] == null ? 0 : $row["total"];
                    }
                }
            }
        }


        return $data;
    }

    public function getBySku($sku, $checkboxes)
    {
        $data = array();
        $products_id = array();
        $manufacturer = array();
        $seo = array();

        $sku =  $this->db->escape($sku);

        $checkboxes = explode(";", $checkboxes);

        $customer_group_id = 0;
        $customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
        if ($customer_query->num_rows) {
            $customer_group_id = $customer_query->row['customer_group_id'];
        }


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE sku = '" . $sku . "'");

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $product_id = $row["product_id"];
                array_push($products_id, $product_id);
                array_push($seo, "'product_id=" . $product_id . "'");
                array_push($manufacturer, $row["manufacturer_id"]);
                $data[$product_id]['item'] = $product_id;
                $data[$product_id]['model'] = $row["model"];
                $data[$product_id]['name'] = "";
                $data[$product_id]['manufacturer'] = in_array('manufacturer', $checkboxes) ? $row["manufacturer_id"] : "";
                $data[$product_id]['prooveedor'] = "";
                $data[$product_id]['stock'] = "";
                $data[$product_id]['price'] = "";
                $data[$product_id]['point'] = "";
                $data[$product_id]['rewards'] = "";
                $data[$product_id]['order'] = "";
                $data[$product_id]['status'] = "";
                $data[$product_id]['category'] = "";
                $data[$product_id]['tags'] = "";
                $data[$product_id]['special'] = "";
                $data[$product_id]['start'] = "";
                $data[$product_id]['end'] = "";
                $data[$product_id]['url'] = "";
                $data[$product_id]['purchased'] = "";


                if (in_array('price', $checkboxes)) {
                    $data[$product_id]['price'] = intval($row["price"]);
                }
                if (in_array('quantity', $checkboxes)) {
                    $data[$product_id]['stock'] = intval($row["quantity"]);
                }
                if (in_array('points', $checkboxes)) {
                    $data[$product_id]['point'] = intval($row["points"]);
                }
                if (in_array('sku', $checkboxes)) {
                    $data[$product_id]['prooveedor'] = $row["sku"];
                }
                if (in_array('order', $checkboxes)) {
                    $data[$product_id]['order'] = $row["sort_order"];
                }
                if (in_array('status', $checkboxes)) {
                    $data[$product_id]['status'] = $row["status"];
                }
            }

            if (in_array('name', $checkboxes) || in_array('tags', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, name, tag FROM " . DB_PREFIX . "product_description WHERE product_id IN (" . implode(",", $products_id) . ") AND language_id = 1");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['name'] = $this->cleanString($row["name"]);
                        $data[$product_id]['tags'] = $this->cleanString($row["tag"]);
                    }
                }
            }
            if (in_array('special', $checkboxes)) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['special'] = intval($row["price"]);
                        $data[$product_id]['start'] = $row["date_start"];
                        $data[$product_id]['end'] = $row["date_end"];
                    }
                }
            }
            if (in_array('rewards', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['rewards'] = intval($row["points"]);
                    }
                }
            }

            if (in_array('manufacturer', $checkboxes)) {

                $query = $this->db->query("SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id IN (" . implode(",", $manufacturer) . ")");
                $manufacturer_ids = array();
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $val = $row["manufacturer_id"];
                        $manufacturer_ids[$val] =  $row["name"];
                    }
                    foreach ($products_id as $key) {
                        $val = $data[$key]["manufacturer"];
                        if (isset($manufacturer_ids[$val])) {
                            $data[$key]['manufacturer'] = $manufacturer_ids[$val];
                        } else {
                            $data[$key]['manufacturer'] = '';
                        }
                    }
                }
            }

            if (in_array('category', $checkboxes)) {
                $query = $this->db->query("SELECT " . DB_PREFIX . "product_to_category.product_id AS product_id, " . DB_PREFIX . "category_description.name AS name FROM " . DB_PREFIX . "product_to_category INNER JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "product_to_category.category_id= " . DB_PREFIX . "category_description.category_id AND  " . DB_PREFIX . "product_to_category.product_id IN (" . implode(",", $products_id) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['category'] .= $data[$product_id]['category'] != '' ? ';' . $this->cleanString($row["name"]) : $this->cleanString($row["name"]);
                    }
                }
            }
            if (in_array('url', $checkboxes)) {
                $query = $this->db->query("SELECT query, keyword FROM " . DB_PREFIX . "seo_url WHERE query  IN (" . implode(",", $seo) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = substr($row["query"], strpos($row["query"], '=') + 1);
                        $data[$product_id]['url'] = $row["keyword"];
                    }
                }
            }


            if (in_array('purchased', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, SUM(quantity) AS total FROM " . DB_PREFIX . "order_product WHERE product_id IN (" . implode(",", $products_id) . ") GROUP BY  product_id");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['purchased'] = $row["total"] == null ? 0 : $row["total"];
                    }
                }
            }
        }


        return $data;
    }

    public function getByStock($quantity, $checkboxes)
    {
        $data = array();
        $products_id = array();
        $manufacturer = array();
        $seo = array();

        $quantity =  $this->db->escape($quantity);

        $checkboxes = explode(";", $checkboxes);

        $customer_group_id = 0;
        $customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
        if ($customer_query->num_rows) {
            $customer_group_id = $customer_query->row['customer_group_id'];
        }

        $operator = $quantity == 0 ? " = " : " > ";

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE quantity " . $operator  . " 0 ");

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $product_id = $row["product_id"];
                array_push($products_id, $product_id);
                array_push($seo, "'product_id=" . $product_id . "'");
                array_push($manufacturer, $row["manufacturer_id"]);
                $data[$product_id]['item'] = $product_id;
                $data[$product_id]['model'] = $row["model"];
                $data[$product_id]['name'] = "";
                $data[$product_id]['manufacturer'] = in_array('manufacturer', $checkboxes) ? $row["manufacturer_id"] : "";
                $data[$product_id]['prooveedor'] = "";
                $data[$product_id]['stock'] = "";
                $data[$product_id]['price'] = "";
                $data[$product_id]['point'] = "";
                $data[$product_id]['rewards'] = "";
                $data[$product_id]['order'] = "";
                $data[$product_id]['status'] = "";
                $data[$product_id]['category'] = "";
                $data[$product_id]['tags'] = "";
                $data[$product_id]['special'] = "";
                $data[$product_id]['start'] = "";
                $data[$product_id]['end'] = "";
                $data[$product_id]['url'] = "";
                $data[$product_id]['purchased'] = "";


                if (in_array('price', $checkboxes)) {
                    $data[$product_id]['price'] = intval($row["price"]);
                }
                if (in_array('quantity', $checkboxes)) {
                    $data[$product_id]['stock'] = intval($row["quantity"]);
                }
                if (in_array('points', $checkboxes)) {
                    $data[$product_id]['point'] = intval($row["points"]);
                }
                if (in_array('sku', $checkboxes)) {
                    $data[$product_id]['prooveedor'] = $row["sku"];
                }
                if (in_array('order', $checkboxes)) {
                    $data[$product_id]['order'] = $row["sort_order"];
                }
                if (in_array('status', $checkboxes)) {
                    $data[$product_id]['status'] = $row["status"];
                }
            }
            if (in_array('name', $checkboxes) || in_array('tags', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, name, tag FROM " . DB_PREFIX . "product_description WHERE product_id IN (" . implode(",", $products_id) . ") AND language_id = 1");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['name'] = $this->cleanString($row["name"]);
                        $data[$product_id]['tags'] = $this->cleanString($row["tag"]);
                    }
                }
            }
            if (in_array('special', $checkboxes)) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['special'] = intval($row["price"]);
                        $data[$product_id]['start'] = $row["date_start"];
                        $data[$product_id]['end'] = $row["date_end"];
                    }
                }
            }
            if (in_array('rewards', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['rewards'] = intval($row["points"]);
                    }
                }
            }

            if (in_array('manufacturer', $checkboxes)) {

                $query = $this->db->query("SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id IN (" . implode(",", $manufacturer) . ")");
                $manufacturer_ids = array();
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $val = $row["manufacturer_id"];
                        $manufacturer_ids[$val] =  $row["name"];
                    }
                    foreach ($products_id as $key) {
                        $val = $data[$key]["manufacturer"];
                        if (isset($manufacturer_ids[$val])) {
                            $data[$key]['manufacturer'] = $manufacturer_ids[$val];
                        } else {
                            $data[$key]['manufacturer'] = '';
                        }
                    }
                }
            }

            if (in_array('category', $checkboxes)) {
                $query = $this->db->query("SELECT " . DB_PREFIX . "product_to_category.product_id AS product_id, " . DB_PREFIX . "category_description.name AS name FROM " . DB_PREFIX . "product_to_category INNER JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "product_to_category.category_id= " . DB_PREFIX . "category_description.category_id AND  " . DB_PREFIX . "product_to_category.product_id IN (" . implode(",", $products_id) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['category'] .= $data[$product_id]['category'] != '' ? ';' . $this->cleanString($row["name"]) : $this->cleanString($row["name"]);
                    }
                }
            }
            if (in_array('url', $checkboxes)) {
                $query = $this->db->query("SELECT query, keyword FROM " . DB_PREFIX . "seo_url WHERE query  IN (" . implode(",", $seo) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = substr($row["query"], strpos($row["query"], '=') + 1);
                        $data[$product_id]['url'] = $row["keyword"];
                    }
                }
            }


            if (in_array('purchased', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, SUM(quantity) AS total FROM " . DB_PREFIX . "order_product WHERE product_id IN (" . implode(",", $products_id) . ") GROUP BY  product_id");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['purchased'] = $row["total"] == null ? 0 : $row["total"];
                    }
                }
            }
        }


        return $data;
    }

    public function getByStatus($status, $checkboxes)
    {
        $data = array();
        $products_id = array();
        $manufacturer = array();
        $seo = array();

        $checkboxes = explode(";", $checkboxes);

        $customer_group_id = 0;
        $customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
        if ($customer_query->num_rows) {
            $customer_group_id = $customer_query->row['customer_group_id'];
        }


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE status =  " . $status);

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $product_id = $row["product_id"];
                array_push($products_id, $product_id);
                array_push($seo, "'product_id=" . $product_id . "'");
                array_push($manufacturer, $row["manufacturer_id"]);
                $data[$product_id]['item'] = $product_id;
                $data[$product_id]['model'] = $row["model"];
                $data[$product_id]['name'] = "";
                $data[$product_id]['manufacturer'] = in_array('manufacturer', $checkboxes) ? $row["manufacturer_id"] : "";
                $data[$product_id]['prooveedor'] = "";
                $data[$product_id]['stock'] = "";
                $data[$product_id]['price'] = "";
                $data[$product_id]['point'] = "";
                $data[$product_id]['rewards'] = "";
                $data[$product_id]['order'] = "";
                $data[$product_id]['status'] = "";
                $data[$product_id]['category'] = "";
                $data[$product_id]['tags'] = "";
                $data[$product_id]['special'] = "";
                $data[$product_id]['start'] = "";
                $data[$product_id]['end'] = "";
                $data[$product_id]['url'] = "";
                $data[$product_id]['purchased'] = "";


                if (in_array('price', $checkboxes)) {
                    $data[$product_id]['price'] = intval($row["price"]);
                }
                if (in_array('quantity', $checkboxes)) {
                    $data[$product_id]['stock'] = intval($row["quantity"]);
                }
                if (in_array('points', $checkboxes)) {
                    $data[$product_id]['point'] = intval($row["points"]);
                }
                if (in_array('sku', $checkboxes)) {
                    $data[$product_id]['prooveedor'] = $row["sku"];
                }
                if (in_array('order', $checkboxes)) {
                    $data[$product_id]['order'] = $row["sort_order"];
                }
                if (in_array('status', $checkboxes)) {
                    $data[$product_id]['status'] = $row["status"];
                }
            }

            if (in_array('name', $checkboxes) || in_array('tags', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, name, tag FROM " . DB_PREFIX . "product_description WHERE product_id IN (" . implode(",", $products_id) . ") AND language_id = 1");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['name'] = $this->cleanString($row["name"]);
                        $data[$product_id]['tags'] = $this->cleanString($row["tag"]);
                    }
                }
            }
            if (in_array('special', $checkboxes)) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['special'] = intval($row["price"]);
                        $data[$product_id]['start'] = $row["date_start"];
                        $data[$product_id]['end'] = $row["date_end"];
                    }
                }
            }
            if (in_array('rewards', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['rewards'] = intval($row["points"]);
                    }
                }
            }

            if (in_array('manufacturer', $checkboxes)) {

                $query = $this->db->query("SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id IN (" . implode(",", $manufacturer) . ")");
                $manufacturer_ids = array();
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $val = $row["manufacturer_id"];
                        $manufacturer_ids[$val] =  $row["name"];
                    }
                    foreach ($products_id as $key) {
                        $val = $data[$key]["manufacturer"];
                        if (isset($manufacturer_ids[$val])) {
                            $data[$key]['manufacturer'] = $manufacturer_ids[$val];
                        } else {
                            $data[$key]['manufacturer'] = '';
                        }
                    }
                }
            }

            if (in_array('category', $checkboxes)) {
                $query = $this->db->query("SELECT " . DB_PREFIX . "product_to_category.product_id AS product_id, " . DB_PREFIX . "category_description.name AS name FROM " . DB_PREFIX . "product_to_category INNER JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "product_to_category.category_id= " . DB_PREFIX . "category_description.category_id AND  " . DB_PREFIX . "product_to_category.product_id IN (" . implode(",", $products_id) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['category'] .= $data[$product_id]['category'] != '' ? ';' . $this->cleanString($row["name"]) : $this->cleanString($row["name"]);
                    }
                }
            }
            if (in_array('url', $checkboxes)) {
                $query = $this->db->query("SELECT query, keyword FROM " . DB_PREFIX . "seo_url WHERE query  IN (" . implode(",", $seo) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = substr($row["query"], strpos($row["query"], '=') + 1);
                        $data[$product_id]['url'] = $row["keyword"];
                    }
                }
            }


            if (in_array('purchased', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, SUM(quantity) AS total FROM " . DB_PREFIX . "order_product WHERE product_id IN (" . implode(",", $products_id) . ") GROUP BY  product_id");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['purchased'] = $row["total"] == null ? 0 : $row["total"];
                    }
                }
            }
        }


        return $data;
    }

    public function getByManufacturerAndSku($manufacturer_id, $sku, $checkboxes)
    {
        $data = array();
        $products_id = array();
        $seo = array();

        $manufacturer_id = $this->db->escape($manufacturer_id);
        $sku = $this->db->escape($sku);
        $manufacturer_name = "";

        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id = " . $manufacturer_id . " LIMIT 1");
        if ($query->num_rows) {
            $manufacturer_name =  $query->row['name'];
        }

        $checkboxes = explode(";", $checkboxes);

        $customer_group_id = 0;
        $customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
        if ($customer_query->num_rows) {
            $customer_group_id = $customer_query->row['customer_group_id'];
        }


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE manufacturer_id = " . $manufacturer_id . " AND sku = '" . $sku . "'");

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $product_id = $row["product_id"];
                array_push($products_id, $product_id);
                array_push($seo, "'product_id=" . $product_id . "'");
                $data[$product_id]['item'] = $product_id;
                $data[$product_id]['model'] = $row["model"];
                $data[$product_id]['name'] = "";
                $data[$product_id]['manufacturer'] = "";
                $data[$product_id]['prooveedor'] = "";
                $data[$product_id]['stock'] = "";
                $data[$product_id]['price'] = "";
                $data[$product_id]['point'] = "";
                $data[$product_id]['rewards'] = "";
                $data[$product_id]['order'] = "";
                $data[$product_id]['status'] = "";
                $data[$product_id]['category'] = "";
                $data[$product_id]['tags'] = "";
                $data[$product_id]['special'] = "";
                $data[$product_id]['start'] = "";
                $data[$product_id]['end'] = "";
                $data[$product_id]['url'] = "";
                $data[$product_id]['purchased'] = "";


                if (in_array('price', $checkboxes)) {
                    $data[$product_id]['price'] = intval($row["price"]);
                }
                if (in_array('quantity', $checkboxes)) {
                    $data[$product_id]['stock'] = intval($row["quantity"]);
                }
                if (in_array('manufacturer', $checkboxes)) {
                    $data[$product_id]['manufacturer'] = $manufacturer_name;
                }
                if (in_array('points', $checkboxes)) {
                    $data[$product_id]['point'] = intval($row["points"]);
                }
                if (in_array('sku', $checkboxes)) {
                    $data[$product_id]['prooveedor'] = $row["sku"];
                }
                if (in_array('order', $checkboxes)) {
                    $data[$product_id]['order'] = $row["sort_order"];
                }
                if (in_array('status', $checkboxes)) {
                    $data[$product_id]['status'] = $row["status"];
                }
            }

            if (in_array('name', $checkboxes) || in_array('tags', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, name, tag FROM " . DB_PREFIX . "product_description WHERE product_id IN (" . implode(",", $products_id) . ") AND language_id = 1");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['name'] = $this->cleanString($row["name"]);
                        $data[$product_id]['tags'] = $this->cleanString($row["tag"]);
                    }
                }
            }
            if (in_array('special', $checkboxes)) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['special'] = intval($row["price"]);
                        $data[$product_id]['start'] = $row["date_start"];
                        $data[$product_id]['end'] = $row["date_end"];
                    }
                }
            }
            if (in_array('rewards', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['rewards'] = intval($row["points"]);
                    }
                }
            }

            if (in_array('category', $checkboxes)) {
                $query = $this->db->query("SELECT " . DB_PREFIX . "product_to_category.product_id AS product_id, " . DB_PREFIX . "category_description.name AS name FROM " . DB_PREFIX . "product_to_category INNER JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "product_to_category.category_id= " . DB_PREFIX . "category_description.category_id AND  " . DB_PREFIX . "product_to_category.product_id IN (" . implode(",", $products_id) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['category'] .= $data[$product_id]['category'] != '' ? ';' . $this->cleanString($row["name"]) : $this->cleanString($row["name"]);
                    }
                }
            }
            if (in_array('url', $checkboxes)) {
                $query = $this->db->query("SELECT query, keyword FROM " . DB_PREFIX . "seo_url WHERE query  IN (" . implode(",", $seo) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = substr($row["query"], strpos($row["query"], '=') + 1);
                        $data[$product_id]['url'] = $row["keyword"];
                    }
                }
            }


            if (in_array('purchased', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, SUM(quantity) AS total FROM " . DB_PREFIX . "order_product WHERE product_id IN (" . implode(",", $products_id) . ") GROUP BY  product_id");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['purchased'] = $row["total"] == null ? 0 : $row["total"];
                    }
                }
            }
        }


        return $data;
    }

    public function getByName($contain, $name, $checkboxes)
    {
        $data = array();
        $products_id = array();
        $manufacturer = array();
        $seo = array();

        $name =  $this->db->escape($name);

        $query_name = $contain == 1 ? " LIKE '%" . $name . "%'" : " LIKE '" . $name . "%'";

        $checkboxes = explode(";", $checkboxes);

        $customer_group_id = 0;
        $customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
        if ($customer_query->num_rows) {
            $customer_group_id = $customer_query->row['customer_group_id'];
        }


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product INNER JOIN " . DB_PREFIX .
            "product_description ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "product_description.product_id AND " . DB_PREFIX .
            "name " . $query_name  . " AND " . DB_PREFIX . "product_description.language_id = 1");

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $product_id = $row["product_id"];
                array_push($products_id, $product_id);
                array_push($seo, "'product_id=" . $product_id . "'");
                array_push($manufacturer, $row["manufacturer_id"]);
                $data[$product_id]['item'] = $product_id;
                $data[$product_id]['model'] = $row["model"];
                $data[$product_id]['name'] = $row["name"];
                $data[$product_id]['manufacturer'] = in_array('manufacturer', $checkboxes) ? $row["manufacturer_id"] : "";
                $data[$product_id]['prooveedor'] = "";
                $data[$product_id]['stock'] = "";
                $data[$product_id]['price'] = "";
                $data[$product_id]['point'] = "";
                $data[$product_id]['rewards'] = "";
                $data[$product_id]['order'] = "";
                $data[$product_id]['status'] = "";
                $data[$product_id]['category'] = "";
                $data[$product_id]['tags'] = in_array('tags', $checkboxes) ? $row["tag"] : "";
                $data[$product_id]['special'] = "";
                $data[$product_id]['start'] = "";
                $data[$product_id]['end'] = "";
                $data[$product_id]['url'] = "";
                $data[$product_id]['purchased'] = "";


                if (in_array('price', $checkboxes)) {
                    $data[$product_id]['price'] = intval($row["price"]);
                }
                if (in_array('quantity', $checkboxes)) {
                    $data[$product_id]['stock'] = intval($row["quantity"]);
                }
                if (in_array('points', $checkboxes)) {
                    $data[$product_id]['point'] = intval($row["points"]);
                }
                if (in_array('sku', $checkboxes)) {
                    $data[$product_id]['prooveedor'] = $row["sku"];
                }
                if (in_array('order', $checkboxes)) {
                    $data[$product_id]['order'] = $row["sort_order"];
                }
                if (in_array('status', $checkboxes)) {
                    $data[$product_id]['status'] = $row["status"];
                }
            }

            if (in_array('special', $checkboxes)) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['special'] = intval($row["price"]);
                        $data[$product_id]['start'] = $row["date_start"];
                        $data[$product_id]['end'] = $row["date_end"];
                    }
                }
            }
            if (in_array('rewards', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['rewards'] = intval($row["points"]);
                    }
                }
            }

            if (in_array('manufacturer', $checkboxes)) {

                $query = $this->db->query("SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id IN (" . implode(",", $manufacturer) . ")");
                $manufacturer_ids = array();
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $val = $row["manufacturer_id"];
                        $manufacturer_ids[$val] =  $row["name"];
                    }
                    foreach ($products_id as $key) {
                        $val = $data[$key]["manufacturer"];
                        if (isset($manufacturer_ids[$val])) {
                            $data[$key]['manufacturer'] = $manufacturer_ids[$val];
                        } else {
                            $data[$key]['manufacturer'] = '';
                        }
                    }
                }
            }

            if (in_array('category', $checkboxes)) {
                $query = $this->db->query("SELECT " . DB_PREFIX . "product_to_category.product_id AS product_id, " . DB_PREFIX . "category_description.name AS name FROM " . DB_PREFIX . "product_to_category INNER JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "product_to_category.category_id= " . DB_PREFIX . "category_description.category_id AND  " . DB_PREFIX . "product_to_category.product_id IN (" . implode(",", $products_id) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['category'] .= $data[$product_id]['category'] != '' ? ';' . $this->cleanString($row["name"]) : $this->cleanString($row["name"]);
                    }
                }
            }
            if (in_array('url', $checkboxes)) {
                $query = $this->db->query("SELECT query, keyword FROM " . DB_PREFIX . "seo_url WHERE query  IN (" . implode(",", $seo) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = substr($row["query"], strpos($row["query"], '=') + 1);
                        $data[$product_id]['url'] = $row["keyword"];
                    }
                }
            }


            if (in_array('purchased', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, SUM(quantity) AS total FROM " . DB_PREFIX . "order_product WHERE product_id IN (" . implode(",", $products_id) . ") GROUP BY  product_id");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['purchased'] = $row["total"] == null ? 0 : $row["total"];
                    }
                }
            }
        }


        return $data;
    }


    public function getByCategory($category, $checkboxes)
    {
        $data = array();
        $products_id = array();
        $manufacturer = array();
        $seo = array();

        $category =  $this->db->escape($category);

        $checkboxes = explode(";", $checkboxes);

        $customer_group_id = 0;
        $customer_query = $this->db->query("SELECT customer_group_id FROM  " . DB_PREFIX . "customer_group_description WHERE name LIKE '%Default%' LIMIT 1");
        if ($customer_query->num_rows) {
            $customer_group_id = $customer_query->row['customer_group_id'];
        }


        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product INNER JOIN " . DB_PREFIX .
            "product_to_category ON " . DB_PREFIX . "product.product_id = " . DB_PREFIX . "product_to_category.product_id AND " . DB_PREFIX .
            "product_to_category.category_id = " . $category);

        if ($query->num_rows) {
            foreach ($query->rows as $row) {
                $product_id = $row["product_id"];
                array_push($products_id, $product_id);
                array_push($seo, "'product_id=" . $product_id . "'");
                array_push($manufacturer, $row["manufacturer_id"]);
                $data[$product_id]['item'] = $product_id;
                $data[$product_id]['model'] = $row["model"];
                $data[$product_id]['name'] = "";
                $data[$product_id]['manufacturer'] = in_array('manufacturer', $checkboxes) ? $row["manufacturer_id"] : "";
                $data[$product_id]['prooveedor'] = "";
                $data[$product_id]['stock'] = "";
                $data[$product_id]['price'] = "";
                $data[$product_id]['point'] = "";
                $data[$product_id]['rewards'] = "";
                $data[$product_id]['order'] = "";
                $data[$product_id]['status'] = "";
                $data[$product_id]['category'] = "";
                $data[$product_id]['tags'] = in_array('tags', $checkboxes) ? $row["tag"] : "";
                $data[$product_id]['special'] = "";
                $data[$product_id]['start'] = "";
                $data[$product_id]['end'] = "";
                $data[$product_id]['url'] = "";
                $data[$product_id]['purchased'] = "";


                if (in_array('price', $checkboxes)) {
                    $data[$product_id]['price'] = intval($row["price"]);
                }
                if (in_array('quantity', $checkboxes)) {
                    $data[$product_id]['stock'] = intval($row["quantity"]);
                }
                if (in_array('points', $checkboxes)) {
                    $data[$product_id]['point'] = intval($row["points"]);
                }
                if (in_array('sku', $checkboxes)) {
                    $data[$product_id]['prooveedor'] = $row["sku"];
                }
                if (in_array('order', $checkboxes)) {
                    $data[$product_id]['order'] = $row["sort_order"];
                }
                if (in_array('status', $checkboxes)) {
                    $data[$product_id]['status'] = $row["status"];
                }
            }
            if (in_array('name', $checkboxes) || in_array('tags', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, name, tag FROM " . DB_PREFIX . "product_description WHERE product_id IN (" . implode(",", $products_id) . ") AND language_id = 1");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['name'] = $this->cleanString($row["name"]);
                        $data[$product_id]['tags'] = $this->cleanString($row["tag"]);
                    }
                }
            }
            if (in_array('special', $checkboxes)) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['special'] = intval($row["price"]);
                        $data[$product_id]['start'] = $row["date_start"];
                        $data[$product_id]['end'] = $row["date_end"];
                    }
                }
            }
            if (in_array('rewards', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, points FROM " . DB_PREFIX . "product_reward WHERE product_id IN (" . implode(",", $products_id) . ") AND customer_group_id = " . $customer_group_id);
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['rewards'] = intval($row["points"]);
                    }
                }
            }

            if (in_array('manufacturer', $checkboxes)) {

                $query = $this->db->query("SELECT manufacturer_id, name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id IN (" . implode(",", $manufacturer) . ")");
                $manufacturer_ids = array();
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $val = $row["manufacturer_id"];
                        $manufacturer_ids[$val] =  $row["name"];
                    }
                    foreach ($products_id as $key) {
                        $val = $data[$key]["manufacturer"];
                        if (isset($manufacturer_ids[$val])) {
                            $data[$key]['manufacturer'] = $manufacturer_ids[$val];
                        } else {
                            $data[$key]['manufacturer'] = '';
                        }
                    }
                }
            }

            if (in_array('category', $checkboxes)) {
                $query = $this->db->query("SELECT " . DB_PREFIX . "product_to_category.product_id AS product_id, " . DB_PREFIX . "category_description.name AS name FROM " . DB_PREFIX . "product_to_category INNER JOIN " . DB_PREFIX . "category_description ON " . DB_PREFIX . "product_to_category.category_id= " . DB_PREFIX . "category_description.category_id AND  " . DB_PREFIX . "product_to_category.product_id IN (" . implode(",", $products_id) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['category'] .= $data[$product_id]['category'] != '' ? ';' . $this->cleanString($row["name"]) : $this->cleanString($row["name"]);
                    }
                }
            }
            if (in_array('url', $checkboxes)) {
                $query = $this->db->query("SELECT query, keyword FROM " . DB_PREFIX . "seo_url WHERE query  IN (" . implode(",", $seo) . ") ");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = substr($row["query"], strpos($row["query"], '=') + 1);
                        $data[$product_id]['url'] = $row["keyword"];
                    }
                }
            }


            if (in_array('purchased', $checkboxes)) {
                $query = $this->db->query("SELECT product_id, SUM(quantity) AS total FROM " . DB_PREFIX . "order_product WHERE product_id IN (" . implode(",", $products_id) . ") GROUP BY  product_id");
                if ($query->num_rows) {
                    foreach ($query->rows as $row) {
                        $product_id = $row["product_id"];
                        $data[$product_id]['purchased'] = $row["total"] == null ? 0 : $row["total"];
                    }
                }
            }
        }


        return $data;
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
}
