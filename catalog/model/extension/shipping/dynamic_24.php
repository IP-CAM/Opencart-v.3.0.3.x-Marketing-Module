<?php
class ModelExtensionShippingDynamic24 extends Model
{
    function getQuote($address)
    {
        $this->load->language('extension/shipping/dynamic_24');

        $query =  $this->db->query("SELECT * FROM " . DB_PREFIX . "shippping_zones WHERE zone_id = '" .  (int)$address['zone_id'] .
            "' AND delivery_time = 24 AND status = 1");

        $final_cost = 0;
        $description = '';

        if ($query->num_rows && $this->cart->getSubTotal() > $this->config->get('shipping_dynamic_24_cost')) {
            $status = true;
            try {

                $datos = $query->row;
                $costs = array(
                    1 => $datos['method_one'],
                    2 => $datos['method_two'],
                    3 => $datos['method_three'],
                    4 => $datos['method_four']
                );

                $method = 1;

                $products = $this->cart->getProducts();
                foreach ($products as $product) {
                    foreach ($products as $product_detail) {
                        $this->load->model('catalog/product');
                        $product_info = $this->model_catalog_product->getProduct($product_detail['product_id']);
                        // if ($product_info['delivery'] == 0) {
                        //     $delivery = 0;
                        // }
                        if ($product_info['upc'] > $method) {
                            if ($product_detail['quantity'] > $product_info['ean'] && $product_info['upc'] < 4) {
                                $method = $product_info['upc'] + 1;
                            } else {
                                $method = $product_info['upc'];
                            }
                        }
                    }
                }

                $method = $method > 4 ? 4 : $method;
                $final_cost = $this->cart->getSubTotal() > $this->config->get('shipping_dynamic_24_free_cost') ? 0 : $costs[$method];
                $description = $this->language->get('text_description');
            } catch (Exception $e) {
                $description = $this->language->get('text_check_cost');
            }
        } elseif ($this->config->get('shipping_dynamic_24_status') && $this->cart->getSubTotal() > $this->config->get('shipping_dynamic_24_cost')) {
            $status = true;
            $description = $this->language->get('text_check_cost');
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $quote_data = array();

            $quote_data['dynamic_24'] = array(
                'code'         => 'dynamic_24.dynamic_24',
                'title'        => $description,
                'cost'         => $final_cost,
                'tax_class_id' => $this->config->get('shipping_dynamic_24_tax_class_id'),
                'text'         => $this->currency->format($this->tax->calculate($final_cost, $this->config->get('shipping_dynamic_24_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
            );

            $method_data = array(
                'code'       => 'dynamic_24',
                'title'      => $this->language->get('text_title'),
                'quote'      => $quote_data,
                'sort_order' => $this->config->get('shipping_dynamic_24_sort_order'),
                'error'      => false
            );
        }

        return $method_data;
    }
}
