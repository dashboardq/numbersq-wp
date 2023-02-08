<?php

defined('ABSPATH') || exit;

// Inspired by:
// https://gist.github.com/paulund/7659452
// https://github.com/Veraxus/wp-list-table-example/blob/master/includes/class-tt-example-list-table.php
class NumbersQ_Numbers_List_Table extends WP_List_Table {
    public function prepare_items() {
        $per_page = 100;

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $data = $this->table_data();

        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $this->items = $data;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ]);
    }

    public function get_columns() {
        $columns = [
            'number' => 'Number',
            'value' => 'Value',
        ];

        return $columns;
    }

    private function table_data() {
        $data = [];

        if(class_exists('WooCommerce')) {
            $data[] = [
                'number' => 'Total WooCommerce Orders',
                'value' => number_format(NumbersQ_Data_Woo_Orders::output('all', '', '', 'value')['value']),
            ];

            $data[] = [
                'number' => 'Total WooCommerce Products',
                'value' => number_format(NumbersQ_Data_Woo_Products::output('all', '', '', 'value')['value']),
            ];

            $data[] = [
                'number' => 'Total WooCommerce Revenue',
                'value' => wc_price(NumbersQ_Data_Woo_Revenue::output('all', '', '', 'value')['value']),
            ];

            $data[] = [
                'number' => 'Total WooCommerce Shipping',
                'value' => wc_price(NumbersQ_Data_Woo_Shipping::output('all', '', '', 'value')['value']),
            ];

            $data[] = [
                'number' => 'Total Repeat Customer Orders',
                'value' => number_format(NumbersQ_Data_Woo_Orders_Repeat::output('all', '', '', 'value')['value']),
            ];

            $data[] = [
                'number' => 'Total WooCommerce International Orders',
                'value' => number_format(NumbersQ_Data_Woo_Orders_International::output('all', '', '', 'value')['value']),
            ];

            $data[] = [
                'number' => 'Total WooCommerce International Revenue',
                'value' => wc_price(NumbersQ_Data_Woo_Revenue_International::output('all', '', '', 'value')['value']),
            ];
        }

        $data[] = [
            'number' => 'Total WordPress Comments',
            'value' => number_format(NumbersQ_Data_WP_Comments::output('all', '', '', 'value')['value']),
        ];

        $data[] = [
            'number' => 'Total WordPress Pages',
            'value' => number_format(NumbersQ_Data_WP_Pages::output('all', '', '', 'value')['value']),
        ];

        $data[] = [
            'number' => 'Total WordPress Posts',
            'value' => number_format(NumbersQ_Data_WP_Posts::output('all', '', '', 'value')['value']),
        ];

        $data[] = [
            'number' => 'Total WordPress Users',
            'value' => number_format(NumbersQ_Data_WP_Users::output('all', '', '', 'value')['value']),
        ];

        $data = apply_filters('numbersq_list', $data);

        return $data;
    }

    public function column_default($item, $column_name) {
        if(isset($item[$column_name])) {
            return $item[$column_name];
        } else {
            // Troubleshoot if column is not available.
            return print_r($item, true);
        }
    }

}

