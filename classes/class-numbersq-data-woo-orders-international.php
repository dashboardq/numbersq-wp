<?php

defined('ABSPATH') || exit;

class NumbersQ_Data_Woo_Orders_International {
	public static function output($range, $start, $end) {
        global $numbersQ;

        if(!class_exists('WooCommerce')) {
            return $numbersQ->errorResponse();
        } elseif($range == 'all') {
            return self::all();
        } else {
            return self::filter($start, $end);
        }
	}

	public static function all() {
        $country_code = WC()->countries->get_base_country();

        // Uses total from the paginate resonse to get the actual number.
        $items = wc_get_orders([
            'limit' => 10,
            'return' => 'ids',
            'paginate' => true,
            'meta_key'     => '_billing_country',
            'meta_compare' => '!=',
            'meta_value' => $country_code,
        ]);

        $output = [];
        $output['value'] = $items->total;
        $output['range'] = 'all';
        $output['start'] = '';
        $output['end'] = '';
        return $output;
    }

	public static function filter($start, $end) {
        global $numbersQ;

        $country_code = WC()->countries->get_base_country();

        $ts_start = $numbersQ->getTimestamp($start);
        $ts_end = $numbersQ->getTimestamp($end);

        // Uses total from the paginate resonse to get the actual number.
        $items = wc_get_orders([
            'limit' => 10,
            'return' => 'ids',
            'date_created' => $ts_start . '...' . $ts_end,
            'paginate' => true,
            'meta_key'     => '_billing_country',
            'meta_compare' => '!=',
            'meta_value' => $country_code,
        ]);

        $output = [];
        $output['value'] = $items->total;
        $output['range'] = 'custom';
        $output['start'] = $start;
        $output['end'] = $end;
        return $output;
    }
}
