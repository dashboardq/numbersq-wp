<?php

defined('ABSPATH') || exit;

class NumbersQ_Data_WP_Users {
	public static function output($range, $start, $end) {
        if($range == 'all') {
            return self::all();
        } else {
            return self::filter($start, $end);
        }
	}

	public static function all() {
        $query = new WP_User_Query([
            'number' => 10,
            'fields' => 'ID',
        ]);

        $output = [];
        $output['value'] = $query->get_total();
        $output['range'] = 'all';
        $output['start'] = '';
        $output['end'] = '';
        return $output;
    }

	public static function filter($start, $end) {
        // WP_User_Query uses UTC.
        $query = new WP_User_Query([
            'number' => 10,
            'fields' => 'ID',
            'date_query' => [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ]
            ] 
        ]);

        $output = [];
        $output['value'] = $query->get_total();
        $output['range'] = 'custom';
        $output['start'] = $start;
        $output['end'] = $end;
        return $output;
    }
}
