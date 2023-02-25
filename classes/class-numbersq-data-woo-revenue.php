<?php

defined('ABSPATH') || exit;

class NumbersQ_Data_Woo_Revenue {
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

        // SUM does not handle this data well.
        // https://stackoverflow.com/questions/3907021/using-sum-on-float-data
        $value = $wpdb->get_var("
            SELECT ROUND(SUM(pm.meta_value), 2)
            FROM {$wpdb->prefix}posts as p
            INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing','wc-completed')
            AND pm.meta_key = '_order_total'
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
        // SUM does not handle this data well.
        // https://stackoverflow.com/questions/3907021/using-sum-on-float-data
        $value = $wpdb->get_var($wpdb->prepare("
            SELECT ROUND(SUM(pm.meta_value), 2)
            FROM {$wpdb->prefix}posts as p
            INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing','wc-completed')
            AND %s <= UNIX_TIMESTAMP(p.post_date_gmt)
            AND UNIX_TIMESTAMP(p.post_date_gmt) <= %s
            AND pm.meta_key = '_order_total'
        ", $ts_start, $ts_end));

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
