<?php
/*
 * Based on MIT licensed https://github.com/agraddy/wp-base-cpt
 */

defined('ABSPATH') || exit;

class NumbersQ_CPT {
	public $config = [];
	public $fields = [];

	function __construct() {
	}

	function config($key, $value) {
		$this->config[$key] = $value;
	}

	function create($singular, $plural, $cap = 'manage_options', $args = []) {
		if(!isset($this->config['key'])) {
			die('In NumbersQ_CPT, the key needs to be set in config with $cpt->config(\'key\', \'unique_key\').');
		}
		return new NumbersQ_Type($this->config['key'], $singular, $plural, $cap, $args);
	}
}


