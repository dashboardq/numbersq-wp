<?php

defined('ABSPATH') || exit;

class NumbersQ_Data_Woo_Orders_Repeat {
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
            SELECT COUNT(id)
            FROM {$wpdb->prefix}posts as p
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing','wc-completed')
            AND p.ID IN (
                SELECT CAST(GROUP_CONCAT(post_id) AS UNSIGNED) 
                FROM {$wpdb->prefix}postmeta pm
                WHERE meta_key = '_billing_email' 
                GROUP BY meta_value 
                HAVING COUNT(post_id) > 1
            )
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
        $value = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(id)
            FROM {$wpdb->prefix}posts as p
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing','wc-completed')
            AND %s <= UNIX_TIMESTAMP(p.post_date_gmt)
            AND UNIX_TIMESTAMP(p.post_date_gmt) <= %s
            AND p.ID IN (
                SELECT CAST(GROUP_CONCAT(post_id) AS UNSIGNED) 
                FROM {$wpdb->prefix}postmeta pm
                WHERE meta_key = '_billing_email' 
                GROUP BY meta_value 
                HAVING COUNT(post_id) > 1
            )
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
