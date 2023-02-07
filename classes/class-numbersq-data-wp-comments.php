<?php

defined('ABSPATH') || exit;

class NumbersQ_Data_WP_Comments {
	public static function output($range, $start, $end) {
        if($range == 'all') {
            return self::all();
        } else {
            return self::filter($start, $end);
        }
	}

	public static function all() {
        $value = get_comments([
            'count' => true,
        ]);

        $output = [];
        $output['value'] = $value;
        $output['range'] = 'all';
        $output['start'] = '';
        $output['end'] = '';
        return $output;
    }

	public static function filter($start, $end) {
        $value = get_comments([
            'count' => true,
            'date_query' => [
                [
                    'column' => 'comment_date_gmt',
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ]
            ] 
        ]);

        $output = [];
        $output['value'] = $value;
        $output['range'] = 'custom';
        $output['start'] = $start;
        $output['end'] = $end;
        return $output;
    }
}
