<?php
/**
 * Plugin Name: Network List
 * Description: Aggregates a list of all registered sites within a disparate group of multisite installations
 * Version: 0.1a
 * Network: true
 * Author: cgrymala
 * License: GPL2
 */

if ( ! class_exists( 'Network_List' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/classes/class-network-list.php' );
	function inst_network_list_obj() {
		global $network_list_obj;
		$network_list_obj = new Network_List;
	}
	inst_network_list_obj();
}