<?php
/*
 * Based on MIT licensed https://github.com/agraddy/wp-base-cpt
 */

defined('ABSPATH') || exit;

class NumbersQ_Column {
	public $columns = [];
	public $keys = [];
	public $filter_type = '';
	public $filters = [];
	public $filter_keys = [];
	public $full_key;
	public $sorts = [];
	public $type;

	function __construct($type, $full_key) {
		$this->type = $type;
		$this->full_key = $type->full_key;
		add_action('init', array($this, 'wpInit'));
	}

	function add($key, $title = '', $format = null) {
		$temp = new stdClass();

		if($key == 'cb' && $title == '') {
			$temp->key = $key;
			$temp->title = '<input type="checkbox" />';
		} elseif(is_array($key)) {
			$temp->key = implode('~~', $key);
			$temp->title = $title;
			$temp->format = $format;
		} else {
			$temp->key = $key;
			$temp->title = $title;
			$temp->format = $format;
		}

		array_push($this->columns, $temp);
		$this->keys[$temp->key] = $temp;
	}

	function filter($type, $list, $values = false) {   
		$this->filter_type = $type;
		$this->filters = $list;
		$this->filter_values = $values;

		for($i = 0; $i < count($list); $i++) {
			if(isset($values[$i])) {
				$this->filter_keys['filter_' . $this->parseToKey($list[$i])] = $values[$i];
			} else {
				$this->filter_keys['filter_' . $this->parseToKey($list[$i])] = $list[$i];
			}
		}
	}

	function parseToKey($input) {   
		$output = $input;               
		$output = strtolower($output);  
		$output = str_replace(' ', '_', $output); 
		return $output;
	}

	function sort($input, $fn = null) {   
		$this->sorts[] = [$input, $fn];
	}

        // Remove date filter
        function wpAdminInit() {
                global $typenow;
                if(
                        $typenow == $this->full_key
			&& !$this->type->config['show_filter_date']
                ) {
                        add_filter('months_dropdown_results', '__return_empty_array');
		} else {
                        add_filter('months_dropdown_results', [$this, 'wpMonthsDropdownResults']);
                }

                if($typenow == $this->full_key && count($this->sorts)) {
			add_filter( 'manage_edit-' . $this->full_key . '_sortable_columns', array($this, 'wpSortableColumns'));
		}
        }

	function wpColumns() {
		$columns = [];

		for($i = 0; $i < count($this->columns); $i++) {
			$column = $this->columns[$i];
			$columns[$column->key] = $column->title;
		}

		return $columns;
	}

	function wpColumnContent($column, $post_id) {
		$output;
		if(substr($column, 0, 4) == '__fn' || substr($column, 0, 6) == '__html' || substr($column, 0, 5) == '__htm') {
			if(is_callable($this->keys[$column]->format)) {
				$fn = $this->keys[$column]->format;
				$output = $fn($post_id);
			} else {
				$output = $this->keys[$column]->format;
			}   
		} elseif(strpos($column, 'author__') === 0) {
			// Get author info
			$parts = explode('__', $column);
			$post = get_post($post_id);
			$author = get_user_by('ID', $post->post_author);

			$temp = $parts[1];
			if(isset($author->$temp)) {
				$output = $author->$temp;
			} else {
				$output = get_user_meta($author->ID, $temp, true);
			}
		} elseif(strpos($column, '__') !== false) {
			// Get user info
			$parts = explode('__', $column);
			$user_id = get_post_meta( $post_id, $parts[0], true );
			$user = get_user_by('ID', $user_id);

			$temp = $parts[1];
			if(isset($user->$temp)) {
				$output = $user->$temp;
			} else {
				if(isset($user->ID)) {
					$output = get_user_meta($user->ID, $temp, true);
				} else {
					$output = '';
				}
			}
		} elseif(strpos($column, '==') !== false) {
			// Get post info
			$parts = explode('==', $column);
			$post_id = get_post_meta( $post_id, $parts[0], true );
			$post = get_post($post_id);

			$temp = $parts[1];
			if(isset($post->$temp)) {
				$output = $post->$temp;
			} else {
				if(isset($post->ID)) {
					$output = get_post_meta($post->ID, $temp, true);
				} else {
					$output = '';
				}
			}
		} elseif(strpos($column, '~~') !== false && is_callable($this->keys[$column]->format)) {
			$parts = explode('~~', $column);
			$values = [];

			for($i = 0; $i < count($parts); $i++) {
				$values[] = get_post_meta( $post_id, $parts[$i], true );
			}

			$fn = $this->keys[$column]->format;
			$output = $fn(...$values);
		} elseif(strpos($column, '--') !== false && is_callable($this->keys[$column]->format)) {
			$parts = explode('--', $column);
			$values = [];

			for($i = 0; $i < 1; $i++) {
				$values[] = get_post_meta( $post_id, $parts[$i], true );
			}

			$fn = $this->keys[$column]->format;
			$output = $fn(...$values);
		} else {
			$output = get_post_meta( $post_id, $column, true );
		}

		if(substr($column, 0, 4) == '__fn' && substr($column, 0, 6) == '__html' && substr($column, 0, 5) == '__htm' && strpos($column, '~~') === false && strpos($column, '--') === false && is_callable($this->keys[$column]->format)) { 
			$fn = $this->keys[$column]->format;
			$output = $fn($output);
		}

		echo wp_kses_post($output);
	}

	function wpInit() {
		add_filter('manage_edit-' . $this->full_key . '_columns', array($this, 'wpColumns')) ;
		add_action('manage_' . $this->full_key . '_posts_custom_column', array($this, 'wpColumnContent'), 10, 2 );

		// Remove hover edits
		// From: https://wordpress.stackexchange.com/a/14982
		add_filter('post_row_actions', array($this, 'wpPostRowActions'), 10, 2);
		add_filter('page_row_actions', array($this, 'wpPostRowActions'), 10, 2);

		// Remove date filters (and other filters) as needed
		add_action('admin_init', array($this, 'wpAdminInit'));

		// Redirect after save
		//add_filter('redirect_post_location', array($this, 'wpRedirectSave'));

		// Remove filter views like All(1) and Published(1)
		if(!$this->type->config['show_filters']) {
			add_filter( 'views_edit-' . $this->full_key, '__return_null' );
		} elseif(count($this->filters)) {
			add_filter( 'views_edit-' . $this->full_key, array($this, 'wpViewsEdit'));
			add_filter('parse_query', [$this, 'wpParseQuery']);
		}
	}

        function wpMonthsDropdownResults($months) {
		//echo '<pre>';
		//print_r($months);
		//echo '</pre>';
		//die;
		return $months;
	}

        function wpParseQuery($query) {
		 if(is_admin() && $query->query['post_type'] == $this->full_key && isset($_GET[$this->filter_type])) {

			 $key = sanitize_text_field($_GET[$this->filter_type]);
			 if(isset($this->filter_keys[$key]) && (in_array($this->filter_keys[$key], $this->filters) || in_array($this->filter_keys[$key], $this->filter_values))) {
				 $query->query_vars['meta_key'] = $this->filter_type;
				 $query->query_vars['meta_value'] = $this->filter_keys[$key];
				 //echo '<pre>';
				 //print_r($query);die;
			 }
		 }

		return $query;
	}

        // Remove hover edit
        function wpPostRowActions($actions, $post) {
                if(
                        $post->post_type == $this->full_key
                ) {
                        unset($actions['edit']);
                        unset($actions['trash']);
                        $actions['inline hide-if-no-js'] = '';
                } elseif(
                        $post->post_type == $this->full_key
                ) {
                        unset($actions['edit']);
                        unset($actions['trash']);
                        unset($actions['inline hide-if-no-js']);
                }
                return $actions;
        }

	function wpSortableColumns() {
		$output = [];
		for($i = 0; $i < count($this->sorts); $i++) {
			$output[$this->sorts[$i][0]] = $this->sorts[$i][0];
		}

		return $output;
	}

        function wpViewsEdit($views) {
		//echo '<pre>';print_r($views);die;
		$views = array();

		// Get all count first
		$key = 'filter_all'; 
		remove_filter('parse_query', [$this, 'wpParseQuery']);
		$posts = get_posts([
			'post_type' => $this->full_key,
			'numberposts' => -1
		]);
		$count = count($posts);
		add_filter('parse_query', [$this, 'wpParseQuery']);

		$url = admin_url('edit.php?&post_type=' . $this->full_key);
		$views[$key] = '';
		$views[$key] .= '<a href="' . $url . '"';
		if(!isset($_GET[$this->filter_type])) {
			$views[$key] .= ' class="current"'; 
		}
		$views[$key] .= '>'; 
		$views[$key] .= __('All', $this->full_key); 
		$views[$key] .= ' <span class="count">(' . $count . ')</span>'; 
		$views[$key] .= '</a>'; 

		for($i = 0; $i < count($this->filters); $i++) {
			$name = $this->filters[$i];
			$key = 'filter_' . $this->parseToKey($name);
			$url = admin_url('edit.php?post_status=publish&post_type=' . $this->full_key . '&' . $this->filter_type . '='. $key);

			if(isset($this->filter_values[$i])) {
				$value = $this->filter_values[$i];
			} else {
				$value = $name;
			}

			remove_filter('parse_query', [$this, 'wpParseQuery']);
			$posts = get_posts([
				'post_type' => $this->full_key,
				'numberposts' => -1,
				'meta_key' => $this->filter_type,
				'meta_value' => $value
			]);
			$count = count($posts);
			add_filter('parse_query', [$this, 'wpParseQuery']);

			if($count) {
				$views[$key] = '';
				$views[$key] .= '<a href="' . $url . '"';
				if(isset($_GET[$this->filter_type]) && $_GET[$this->filter_type] == $key) {
					$views[$key] .= ' class="current"'; 
				}
				$views[$key] .= '>'; 
				$views[$key] .= __($name, $this->full_key); 
				$views[$key] .= ' <span class="count">(' . $count . ')</span>'; 
				$views[$key] .= '</a>'; 
			}
		}

		return $views;
	}

        // Inspired by: https://gist.github.com/davejamesmiller/1966595
	/*
        function wpRedirectSave($location) {
                global $post;
                if($post->post_type == $this->full_key) {
                        $url = 'edit.php?post_type=' . $this->full_key;
                        $location = get_admin_url(null, $url);
                        return $location;
                } else {
                        return $location;
                }
        }
	 */
}

