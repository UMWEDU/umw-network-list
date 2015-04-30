<?php
/**
 * Set up the Network_List class
 * @version 0.1a
 */

class Network_List {
	var $version = '0.1a';
	var $networks = array();
	var $sites = array();
	var $settings_page = 'network-list';
	var $opt_name = '_network_list_networks';
	
	/**
	 * Construct the Network_List object
	 */
	function __construct() {
		add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}
	
	/**
	 * Handle adding the options page to the admin menu
	 */
	function admin_menu() {
		add_submenu_page( 'settings.php', __( 'Network List' ), __( 'Network List' ), 'manage_sites', $this->settings_page, array( $this, 'options_page' ) );
	}
	
	/**
	 * Whitelist the plugin settings & set up the option fields
	 */
	function admin_init() {
		register_setting( $this->settings_page, $this->opt_name, array( $this, 'sanitize_settings' ) );
		add_settings_section( $this->settings_page, __( 'Network List Settings' ), array( $this, 'settings_section' ), $this->settings_page );
		add_settings_field( $this->opt_name, __( 'Network list:' ), array( $this, 'settings_field' ), $this->settings_page, $this->settings_page, array( 'label_for' => $this->opt_name ) );
	}
	
	/**
	 * Retrieve the options for this plugin
	 */
	function _get_options() {
		$this->networks = get_option( $this->opt_name, array() );
	}
	
	/**
	 * Save any options that have been modified
	 * Only used in network admin and multinetwork settings. The Settings API handles saving options
	 * 		in the regular site admin area.
	 * @return array the output from each save action
	 */
	protected function _set_options( $opt ) {
		if( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $this->settings_page . '-options' ) )
			/*wp_die( 'The nonce could not be verified' );*/
			return false;
		if( ! is_network_admin() )
			return false;
		
		$opt = $this->sanitize_settings( $opt );
		
		return $this->is_multinetwork() ? update_mnetwork_option( $this->opt_name, $opt ) : update_site_option( $this->opt_name, $opt );
	}
	
	/**
	 * Output the settings page for this plugin
	 */
	function options_page() {
		if( ( is_network_admin() && ! current_user_can( 'manage_network_options' ) ) )
			return $this->_no_permissions();
		
		if( is_network_admin() && isset( $_REQUEST['action'] ) && $this->settings_page == $_REQUEST['page'] ) {
			$msg = $this->_set_options( $_REQUEST[$this->opt_name] );
		}
		
		$this->_get_options();
?>
<div class="wrap">
	<h2><?php _e( 'Network List Settings' ) ?></h2>
<?php
		if( isset( $msg ) ) {
			$this->options_updated_message( $msg );
		}
?>
    <form method="post" action="<?php echo ( is_network_admin() ) ? '' : 'options.php'; ?>">
<?php
		settings_fields( $this->settings_page );
		do_settings_sections( $this->settings_page );
?>
		<p><input type="submit" value="<?php _e( 'Save' ) ?>" class="button-primary"/></p>
    </form>
</div>
<?php
	}
	
	/**
	 * Output anything that needs to go in the settings section
	 */
	function settings_section() {
		return;
	}
	
	/**
	 * Output the settings field
	 */
	function settings_field( $args=array() ) {
		$list = implode( "\n", $this->networks );
?>
<p><textarea name="<?php echo $args['label_for'] ?>" id="<?php echo $args['label_for'] ?>" cols="15" rows="5" class="large-text widefat"><?php echo $list ?></textarea></p>
<p><em>Please enter each Network URL on a new line.</em></p>
<?php
	}
	
	/**
	 * Sanitize and escape the settings input
	 */
	function sanitize_settings( $input ) {
		$output = array();
		if ( ! is_array( $input ) ) {
			$input = str_replace( "\r", '', $input );
			$input = explode( "\n", $input );
		}
		foreach ( $input as $u ) {
			$u = trim( $u );
			if ( esc_url( $u ) ) 
				$output[] = esc_url( $u );
		}
		
		return $output;
	}

	/**
	 * Output a message indicating whether or not the options were updated
	 */
	protected function options_updated_message( $msg ) {
?>
	<div class="settings-updated is-dismissible">
<?php
			printf( __( '<p>The %s options were %supdated%s.</p>' ), $this->settings_titles[$k], ( true === $msg ? '' : '<strong>not</strong> ' ), ( true === $msg ? ' successfully' : '' ) );
?>
	</div>
<?php
	}
		
	/**
	 * Output the appropriate error message about the user not having the right permissions
	 */
	protected function _no_permissions() {
?>
<div class="wrap">
	<h2><?php _e( 'Post Content Shortcodes', $this->text_domain ) ?></h2>
	<p><?php _e( 'You do not have the appropriate permissions to update these options. Please work with an administrator of the site to update the options. Thank you.', $this->text_domain ) ?></p>
</div>
<?php
	}
	
	/**
	 * Determine whether this is a multinetwork install or not
	 * Will only return true if the is_multinetwork() & the add_mnetwork_option() functions exist
	 * @return bool whether this is a multi-network install capable of handling multi-network options
	 */
	function is_multinetwork() {
		return function_exists( 'is_multinetwork' ) && function_exists( 'add_mnetwork_option' ) && is_multinetwork();
	}
	
	/**
	 * Determine whether this plugin is network active in a multisite install
	 * @uses is_plugin_active_for_network()
	 * @uses is_multisite()
	 * @return bool whether this is a multisite install with the plugin activated network-wide
	 */
	function is_plugin_active_for_network() {
		return function_exists( 'is_plugin_active_for_network' ) && is_multisite() && is_plugin_active_for_network( $this->plugin_dir_name );
	}
		
}