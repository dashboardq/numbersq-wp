<?php
/*
Plugin Name: NumbersQ
Description: Provides a connection between WordPress and NumbersQ.com.
Author: Anthony Graddy
Author URI: https://www.dashboardq.com
Plugin URI: https://github.com/dashboardq/numbersq-wp
Version: 1.1.1
*/

defined('ABSPATH') || exit;

if(is_admin()) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    require_once(__DIR__ . '/classes/class-numbersq-numbers-list-table.php');
}

require_once(__DIR__ . '/classes/class-numbersq-column.php');
require_once(__DIR__ . '/classes/class-numbersq-cpt.php');
require_once(__DIR__ . '/classes/class-numbersq-type.php');

require_once(__DIR__ . '/classes/class-numbersq-data-test.php');

require_once(__DIR__ . '/classes/class-numbersq-data-woo-orders-international.php');
require_once(__DIR__ . '/classes/class-numbersq-data-woo-orders-repeat.php');
require_once(__DIR__ . '/classes/class-numbersq-data-woo-orders.php');
require_once(__DIR__ . '/classes/class-numbersq-data-woo-products.php');
require_once(__DIR__ . '/classes/class-numbersq-data-woo-revenue-international.php');
require_once(__DIR__ . '/classes/class-numbersq-data-woo-revenue.php');
require_once(__DIR__ . '/classes/class-numbersq-data-woo-shipping.php');

require_once(__DIR__ . '/classes/class-numbersq-data-wp-comments.php');
require_once(__DIR__ . '/classes/class-numbersq-data-wp-pages.php');
require_once(__DIR__ . '/classes/class-numbersq-data-wp-posts.php');
require_once(__DIR__ . '/classes/class-numbersq-data-wp-users.php');

class NumbersQ {
	public $key = 'numbersq';
	public $key_ = 'numbersq_';
	public $version = '1.1.1';

	public $cpt;
	public $numbersq;

	public $hook_numbers;
	public $hook_settings;

	public function __construct() {
		add_action('init', [$this, 'init']);

		$this->cpt = new NumbersQ_CPT();
		$this->cpt->config('key', $this->key);

		$this->numbersq = $this->cpt->create('NumbersQ Key', 'NumbersQ Keys', 'manage_options', ['show_in_menu' => false]);
		$this->numbersq->add('text', 'Key');

		$this->numbersq->column->add('cb');
        $this->numbersq->column->add('__fn', 'Instructions', function($post_id) {
            $htm = '';
            $htm .= 'Copy this key and add it to the <a href="https://www.numbersq.com/number/add" target="_blank">NumbersQ.com connection form</a>.';
            return $htm;
        });
        $this->numbersq->column->add('__html', 'Key', function($post_id) {
            $key = get_post_meta($post_id, 'key', true);
            $htm = '';
            $htm .= '<strong>';
            $htm .= $key;
            $htm .= '</strong>';
            $htm .= '<br>';
            $htm .= '<button data-numq-copy="' . $key . '">Copy To Clipboard</button>';
            return $htm;
        });
		$this->numbersq->column->add('date', 'Date');
		$this->numbersq->init();
	}

	public function init() {
        add_action('admin_init', [$this, 'adminTemplateRedirect']);
		add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
		add_action('admin_menu', [$this, 'adminMenu']);

        add_action('wp_ajax_' . $this->key_ . 'data', [$this, 'ajaxNumbersQData']);
        add_action('wp_ajax_nopriv_' . $this->key_ . 'data', [$this, 'ajaxNumbersQData']);
		add_action('wp_ajax_' . $this->key_ . 'settings', [$this, 'ajaxSettings']);

        add_filter('pre_get_posts', [$this, 'preGetPosts']);
	}

	public function adminEnqueueScripts($hook) {
		if(in_array($hook, [$this->hook_numbers, $this->hook_settings])) {
			wp_enqueue_script($this->key_ . 'admin_ajax_form', plugins_url('js/admin_ajax_form.js', __FILE__), ['jquery'], $this->version);
			wp_enqueue_style($this->key_ . 'admin_ajax_form', plugins_url('css/admin_ajax_form.css', __FILE__), [], $this->version);
		}
	}

	public function adminMenu() {
		$this->hook_numbers = add_menu_page('NumbersQ', 'NumbersQ', 'manage_options', $this->key_ . 'numbers', [$this, 'adminNumbers'], 'dashicons-admin-post', 30);

        add_submenu_page($this->key_ . 'numbers', 'Numbers', 'Numbers', 'manage_options', $this->key_ . 'numbers');
        $this->hook_numbers = add_submenu_page($this->key_ . 'numbers', 'Settings', 'Settings', 'manage_options', $this->key_ . 'settings', [$this, 'adminSettings']);

        $cpt_link = 'edit.php?post_type=' . $this->key_ . 'key';
        add_submenu_page($this->key_ . 'numbers', 'Keys', 'Keys', 'manage_options', $cpt_link);
	}

	public function adminNumbers() {
        global $wp_locale;

		$action = $this->key_ . 'settings';
		$title = 'NumbersQ';

        $table = new NumbersQ_Numbers_List_Table();
        $table->prepare_items();

		include 'views/admin_numbers.php';
	}

	public function adminSettings() {
        global $wp_locale;

		$action = $this->key_ . 'settings';
		$title = 'NumbersQ Settings';

        $timezone_string = get_option($this->key_ . 'timezone_string', 'use_wordpress');

		include 'views/admin_settings.php';
	}

    public function adminTemplateRedirect() {
        global $pagenow;

        // User is accessing the new page so create a key and redirect them to the main CPT list page.
        if($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == $this->key_ . 'key') {
            // Create a new key.
            $post_id = wp_insert_post([
                'post_type' => $this->key_ . 'key',
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            ]);

            $url = admin_url('admin-ajax.php');
            $parts = parse_url($url);
            $scheme = $parts['scheme'];
            $host = $parts['host'];
            $path = $parts['path'];

            $bytes = random_bytes(10);
            $hex = bin2hex($bytes);

            $key = '' ;
            $key .= 'key_';
            $key .= $post_id;
            $key .= '_';
            $key .= $this->version;
            $key .= '_';
            $key .= $scheme;
            $key .= '_';
            $key .= $host;
            $key .= '_';
            // Replace slashes with dots to keep the characters in the key limited.
            // (mainly an aesthetic decision)
            // Strip off the first slash and the php extension.
            $key .= str_replace('/', '.', substr($path, 1, -4)); 
            $key .= '_';
            $key .= $hex;
            $key .= '_end';
            update_post_meta($post_id, 'key', $key);

            // Redirect back to the main list page.
            wp_redirect(admin_url('/edit.php?post_type=' . $this->key_ . 'key'));
            exit;
        }

        if($pagenow == 'post.php' && isset($_GET['post']) && is_numeric($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'edit') {
            $post_type = get_post_type(sanitize_text_field($_GET['post']));

            // If the user is trying to edit a key, just redirect the user.
            // Redirect back to the main list page.
            if($post_type == $this->key_ . 'key') {
                wp_redirect(admin_url('/edit.php?post_type=' . $this->key_ . 'key'));
                exit;
            }
        }
    }

	public function ajaxNumbersQData() {
        $key = '';
        $type = '';
        $range = '';
        $post_id = 0;
        $pass = false;

        $types = [
            'test',
            'woo_orders_international',
            'woo_orders_repeat',
            'woo_orders',
            'woo_products',
            'woo_revenue_international',
            'woo_revenue',
            'woo_shipping',
            'wp_comments',
            'wp_pages',
            'wp_posts',
            'wp_users',
        ];
        $types = apply_filters('numbersq_types', $types, sanitize_text_field($_GET['type']));

        $funcs = [
            'test' => ['NumbersQ_Data_Test', 'output'],
            'woo_orders_international' => ['NumbersQ_Data_Woo_Orders_International', 'output'],
            'woo_orders_repeat' => ['NumbersQ_Data_Woo_Orders_Repeat', 'output'],
            'woo_orders' => ['NumbersQ_Data_Woo_Orders', 'output'],
            'woo_products' => ['NumbersQ_Data_Woo_Products', 'output'],
            'woo_revenue_international' => ['NumbersQ_Data_Woo_Revenue_International', 'output'],
            'woo_revenue' => ['NumbersQ_Data_Woo_Revenue', 'output'],
            'woo_shipping' => ['NumbersQ_Data_Woo_Shipping', 'output'],
            'wp_comments' => ['NumbersQ_Data_WP_Comments', 'output'],
            'wp_pages' => ['NumbersQ_Data_WP_Pages', 'output'],
            'wp_posts' => ['NumbersQ_Data_WP_Posts', 'output'],
            'wp_users' => ['NumbersQ_Data_WP_Users', 'output'],
        ];
        $funcs = apply_filters('numbersq_funcs', $funcs);

        $start = sanitize_text_field($_GET['start'] ?? '');
        $end = sanitize_text_field($_GET['end'] ?? '');
        $range = sanitize_text_field($_GET['range'] ?? '');

        $validated = true;
        // The start and stop need to be in UTC time with a format of:
        // Y-m-d H:i:s
        if($range != 'all') {
            $range = 'custom';
            if(
                !preg_match('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/', $start)
                || !preg_match('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/', $end)
            ) {
                $validated = false;
            }
        }

        if(
            isset($_GET['key'])
            && isset($_GET['type'])
            && in_array($_GET['type'], $types)
            && $validated
        ) {
            $key = sanitize_text_field($_GET['key']);
            $type = sanitize_text_field($_GET['type']);

            $parts = explode('_', $key);
            if(isset($parts[1]) && is_numeric($parts[1])) {
                $post_id = $parts[1];

                $real_key = get_post_meta($post_id, 'key', true);
                $post_type = get_post_type($post_id);
                if($post_type == $this->key_ . 'key' && $key == $real_key) {
                    $pass = true;
                }
            }
        }

        if($pass) {
            $output = call_user_func($funcs[$type], $range, $start, $end);
            $output['type'] = $type;
            if(!isset($output['value'])) {
                $output['value'] = -1;
            }
            echo json_encode($output);
            exit;
        } else {
            $output = [];
            $output['value'] = -1;
            $output['type'] = 'invalid';
            $output['range'] = 'invalid';
            $output['start'] = 'invalid';
            $output['end'] = 'invalid';
            echo json_encode($output);
            exit;
        }
    }

    public function ajaxSettings() {
        $pass = true;
        $output = [];
        $output['status'] = 'error';
        if(wp_verify_nonce($_POST['_wpnonce'], $this->key_ . 'settings')) {
            if(!isset($_POST['timezone_string'])) {
                $output['messages'] = ['Please choose a valid Timezone setting.'];
                $pass = false;
            }

            if($pass) {
                $timezone_string = sanitize_text_field($_POST['timezone_string']);

                update_option($this->key_ . 'timezone_string', $timezone_string, false);

                $output['status'] = 'success';  
                $output['messages'] = ['Values have been updated.'];
            } else {                        
                $output['status'] = 'error';    
                $output['messages'] = ['There was a problem processing the request. Please reload the page and try again.'];
            }  
        } else {
            $output['status'] = 'error';    
            $output['messages'] = ['There was a problem processing the request. Please reload the page and try again.'];
        }
        echo json_encode($output);
        exit;
    }

    public function errorResponse() {
        $output = [];
        $output['value'] = -1;
        return $output;
    }

    public function getTimestamp($input) {
        $utc = new DateTimeZone('UTC');
        $dt = new DateTime($input, $utc);
        $timestamp = $dt->getTimestamp();
        return $timestamp;
    }

    public function preGetPosts($query) {
        if (is_admin() && !isset($_GET['orderby'])) {
            // Get the post type from the query
            $post_type = $query->query['post_type'];
            if(in_array($post_type, [$this->key_ . 'key'])) {
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
            }
        }
    }

    public function range($range) {
        $output = [];
        $output['start'] = 'none';
        $output['end'] = 'none';

        $start_of_week = 'Sunday';

        if($start_of_week == 'Sunday') {
            $end_of_week = 'Saturday';
        } else {
            $end_of_week = 'Sunday';
        }

        $start_of_week = apply_filters('numbersq_start_of_week', $start_of_week);
        $end_of_week = apply_filters('numbersq_end_of_week', $end_of_week);

        if($range == 'last_year') {
            $dt = new DateTime();
            $dt->modify('-1 year');
            $start = $dt->format('Y') . '-01-01';
            $end = $dt->format('Y') . '-12-31';
        } elseif($range == 'last_month') {
            $dt = new DateTime();
            $dt->modify('-1 month');
            $start = $dt->format('Y-m') . '-01';
            $end = $dt->format('Y-m-t');
        } elseif($range == 'last_week') {
            $dt = new DateTime();
            $dt->modify('-1 week');
            if($dt->format('l') == $start_of_week) {
                $start = $dt->format('Y-m-d');
            } else {
                $dt->modify('previous ' . $start_of_week);
                $start = $dt->format('Y-m-d');
            }
            $dt->modify('next ' . $end_of_week);
            $end = $dt->format('Y-m-d');
        } elseif($range == 'yesterday') {
            $dt = new DateTime();
            $dt->modify('-1 day');
            $start = $dt->format('Y-m-d');
            $end = $dt->format('Y-m-d');
        } elseif($range == 'current_year') {
            $dt = new DateTime();
            $start = $dt->format('Y') . '-01-01';
            $end = $dt->format('Y') . '-12-31';
        } elseif($range == 'current_month') {
            $dt = new DateTime();
            $start = $dt->format('Y-m') . '-01';
            $end = $dt->format('Y-m-t');
        } elseif($range == 'current_week') {
            $dt = new DateTime();
            if($dt->format('l') == $start_of_week) {
                $start = $dt->format('Y-m-d');
            } else {
                $dt->modify('previous ' . $start_of_week);
                $start = $dt->format('Y-m-d');
            }
            $dt->modify('next ' . $end_of_week);
            $end = $dt->format('Y-m-d');
        } elseif($range == 'today') {
            $dt = new DateTime();
            $start = $dt->format('Y-m-d');
            $end = $dt->format('Y-m-d');
        }

        return compact('start', 'end');

    }
}

$numbersQ = new NumbersQ();
