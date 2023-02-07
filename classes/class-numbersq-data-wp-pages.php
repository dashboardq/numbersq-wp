<?php

defined('ABSPATH') || exit;

class NumbersQ_Data_WP_Pages {
	public static function output($range, $start, $end) {
        if($range == 'all') {
            return self::all();
        } else {
            return self::filter($start, $end);
        }
	}

	public static function all() {
        $query = new WP_Query([
            'numberposts' => 10,
            'post_type' => 'page',
            'fields' => 'ids',
            'post_status' => 'publish',
        ]);

        $output = [];
        $output['value'] = $query->found_posts;
        $output['range'] = 'all';
        $output['start'] = '';
        $output['end'] = '';
        return $output;
    }

	public static function filter($start, $end) {
        $query = new WP_Query([
            'numberposts' => 10,
            'post_type' => 'page',
            'fields' => 'ids',
            'post_status' => 'publish',
            'date_query' => [
                [
                    'column' => 'post_date_gmt',
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ]
            ] 
        ]);

        $output = [];
        $output['value'] = $query->found_posts;
        $output['range'] = 'custom';
        $output['start'] = $start;
        $output['end'] = $end;
        return $output;
    }
}
