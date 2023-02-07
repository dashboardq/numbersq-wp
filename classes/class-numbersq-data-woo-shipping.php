<?php

defined('ABSPATH') || exit;

class NumbersQ_Data_Woo_Shipping {
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
        global $wpdb;

        $value = $wpdb->get_var("
            SELECT SUM(pm.meta_value)
            FROM {$wpdb->prefix}posts as p
            INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing','wc-completed')
            AND pm.meta_key = '_order_shipping'
        ");

        if(!$value) {
            $value = 0;
        }

        $output = [];
        $output['value'] = $value;
        $output['range'] = 'all';
        $output['start'] = '';
        $output['end'] = '';
        return $output;
    }

	public static function filter($start, $end) {
        global $numbersQ, $wpdb;

        $ts_start = $numbersQ->getTimestamp($start);
        $ts_end = $numbersQ->getTimestamp($end);

        // Inspired by: 
        // https://stackoverflow.com/questions/51152861/get-orders-total-purchases-amount-for-the-day-in-woocommerce
        $value = $wpdb->get_var("
            SELECT SUM(pm.meta_value)
            FROM {$wpdb->prefix}posts as p
            INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing','wc-completed')
            AND {$ts_start} <= UNIX_TIMESTAMP(p.post_date)
            AND UNIX_TIMESTAMP(p.post_date) <= {$ts_end}
            AND pm.meta_key = '_order_shipping'
        ");

        if(!$value) {
            $value = 0;
        }

        $output = [];
        $output['value'] = $value;
        $output['range'] = 'custom';
        $output['start'] = $start;
        $output['end'] = $end;
        return $output;
    }
}
