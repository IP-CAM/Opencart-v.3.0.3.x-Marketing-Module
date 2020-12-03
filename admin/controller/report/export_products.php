<?php
class ControllerReportExportProducts extends Controller
{
    private $error = array();
    public function export()
    {
        $this->load->language('report/export_products');
        $this->load->model('report/export_products');

        $json = array();
        $json['success'] = $this->language->get('text_success');

        $data = array();

        if (isset($this->request->post['main_filter'])) {
            $main_filter = $this->request->post['main_filter'];
            $filter_value = $this->request->post['filter_value'];
            $sub_filter_value = $this->request->post['sub_filter_value'];
            $checkboxes = $this->request->post['checkboxes'];
            switch ($main_filter) {
                case 'by_manufacturer':
                    $data = $this->model_report_export_products->getByManufacturer($filter_value, $checkboxes);
                    break;

                case 'by_sku':
                    $data = $this->model_report_export_products->getBySku($filter_value, $checkboxes);
                    break;
                case 'by_sku_by_manufacturer':
                    $data = $this->model_report_export_products->getByManufacturerAndSku($sub_filter_value, $filter_value, $checkboxes);
                    break;
                case 'by_category':
                    $data = $this->model_report_export_products->getByCategory($filter_value, $checkboxes);
                    break;
                case 'by_name':
                    if ($filter_value == 'Contiene..') {
                        $data = $this->model_report_export_products->getByName(1, $sub_filter_value, $checkboxes);
                    } else {
                        $data = $this->model_report_export_products->getByName(0, $sub_filter_value, $checkboxes);
                    }
                    break;
                case 'by_stock':
                    if ($sub_filter_value == 'in_stock') {
                        $data = $this->model_report_export_products->getByStock(1, $checkboxes);
                    } else if ($sub_filter_value == 'out_of_stock') {
                        $data = $this->model_report_export_products->getByStock(0, $checkboxes);
                    } else if ($sub_filter_value == 'status_enable') {
                        $data = $this->model_report_export_products->getByStatus(1, $checkboxes);
                    } else {
                        $data = $this->model_report_export_products->getByStatus(0, $checkboxes);
                    }
                    break;
                default:
                    $json['success'] = "nopeeee";
                    # code...
                    break;
            }
            // $table = "<table>";
            // $header = $this->getHeaders();
            // $data_table = "";
            // foreach ($data as $key => $value) {
            //     $data_table .= "<tr>";
            //     foreach ($value as $second_key) {
            //         $data_table .= "<td>"  . $second_key . "</td>";
            //     }
            //     $data_table .= "</tr>";
            // }

            // $table .= $header . $data_table . "</table>";
        }

        $title_array = $this->getHeaders();

        $fp = fopen('php://output', 'w');
        fputcsv($fp, $title_array, ";");
        if (!empty($data)) :

            foreach ($data as $values) :
                fputcsv($fp, $values, ";");
            endforeach;

        endif;
        fclose($fp);

        exit();
    }

    public function index()
    {
        $this->load->language('report/export_products');

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
            'href' => $this->url->link('report/export_products', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['user_token'] = $this->session->data['user_token'];

        $data['export'] = $this->url->link('report/export_products/export', 'user_token=' . $this->session->data['user_token'], true);

        $this->load->model('report/export_products');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data['manufacturer_list'] = $this->model_report_export_products->getManufacturerList();

        $data['sku_list'] = $this->model_report_export_products->getSkuList();

        $data['category_list'] = $this->model_report_export_products->getCategoryList();

        $this->response->setOutput($this->load->view('report/export_products', $data));
    }

    function getHeaders()
    {
        $titles = 'Item,Model,Name,Manufacturer,Sku,';
        $titles .= 'Quantity,Price,Points,Rewards,Order,';
        $titles .= 'Status,Category,Tags,Special Price,Special Start Date,Special End Date,';
        $titles .= 'Seo,Purchased';

        $title_array = explode(",", $titles);

        return $title_array;

        // $header_table = "<tr>";

        // foreach ($title_array as $key) {
        //     $header_table .= '<th>' . $key . '</th>';
        // }

        // $header_table .= "</tr>";

        // return $header_table;
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

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'report/export_products')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
