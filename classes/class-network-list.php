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
	var $site_transient_name = '_msnl-site-list';
	var $transient_timeout = DAY_IN_SECONDS;
	
	/**
	 * Construct the Network_List object
	 */
	function __construct() {
		add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		
		$this->transient_timeout = apply_filters( 'msnl-transient-timeout', DAY_IN_SECONDS );
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
		add_settings_section( 'multisite-network-list-site-list', __( 'Current Site List' ), array( $this, 'site_list_section' ), $this->settings_page );
		add_settings_field( $this->opt_name, __( 'Network list:' ), array( $this, 'settings_field' ), $this->settings_page, $this->settings_page, array( 'label_for' => $this->opt_name ) );
	}
	
	/**
	 * Retrieve the options for this plugin
	 */
	function _get_options() {
		if ( $this->is_multinetwork() && function_exists( 'get_mnetwork_option' ) ) {
			$this->networks = get_mnetwork_option( $this->opt_name, array() );
			return $this->networks;
		}
		$this->networks = get_site_option( $this->opt_name, array() );
		return $this->networks;
	}
	
	/**
	 * Save any options that have been modified
	 * Only used in network admin and multinetwork settings. The Settings API handles saving options
	 * 		in the regular site admin area.
	 * @return array the output from each save action
	 */
	protected function _set_options( $opt ) {
		if( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $this->settings_page . '-options' ) )
			wp_die( 'The nonce could not be verified' );
			/*return false;*/
		if( ! is_network_admin() )
			wp_die( 'This is not the network admin area' );
			/*return false;*/
		
		$opt = $this->sanitize_settings( $opt );
		
		$this->invalidate_cache();
		
		return $this->is_multinetwork() && function_exists( 'update_mnetwork_option' ) ? update_mnetwork_option( $this->opt_name, $opt ) : update_site_option( $this->opt_name, $opt );
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
	<div class="notice updated settings-updated is-dismissible">
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
	<h2><?php _e( 'Network List', $this->text_domain ) ?></h2>
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
		
	/**
	 * Retrieve the full list of sites within the specified networks
	 */
	function get_site_list() {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$sites = get_site_transient( $this->site_transient_name );
			if ( false !== $sites )
				return $this->sites = $sites;
		}
		
		$this->_get_options();
		if ( empty( $this->networks ) )
			return array();
		
		$sites = array();
		foreach ( $this->networks as $n ) {
			$tmp = $this->retrieve_site_list_for_network( $n );
			if ( is_array( $tmp ) && ! empty( $tmp ) )
				$sites[$n] = $tmp;
			else
				$sites[$n] = array();
		}
		
		set_site_transient( $this->site_transient_name, $sites, $this->transient_timeout );
		
		return $sites;
	}
	
	/**
	 * Retrieve and format a list of sites in a specific network
	 */
	function retrieve_site_list_for_network( $network ) {
		$network = esc_url( $network );
		if ( empty( $network ) )
			return false;
		
		$feeds = $this->_get_feed_types();
		if ( ! is_array( $feeds ) || empty( $feeds ) )
			return false;
		
		foreach ( array_keys( $feeds ) as $f ) {
			$url = trailingslashit( $network ) . $f;
			
			$result = wp_remote_get( $url );
			
			if ( ! is_wp_error( $result ) && 200 === absint( wp_remote_retrieve_response_code( $result ) ) ) {
				$type = $feeds[$f]['type'];
				$body = wp_remote_retrieve_body( $result );
				
				return call_user_func( apply_filters( 'multisite-network-list-parse-callback', array( $this, "_parse_feed_{$type}" ), $type ), $body, $feeds[$f] );
			}
		}
	}
	
	/**
	 * Set up an array of the feeds that should be checked
	 * The feed list is formatted as such:
	 * 		Feed file name =>
	 * 			'type' => the type of file being retrieved, 
	 * 			'format' => whether the internal items in the array are arrays or objects
	 * 			'xpath' => if this is an XML feed, provide the XPath to the individual sites
	 * 			'parameters' => 
	 * 				'id' => what the key name is for the element that holds the blog ID
	 * 				'domain' => the key name or relative xpath for the element that holds the domain
	 * 				'path' => the key name or relative xpath for the element that holds the path
	 * 				'public' => the key name or relative xpath for the element that holds the public value
	 */
	function _get_feed_types() {
		$types = array(
			'feed/site-feed.json' => array(
				'type'       => 'json', 
				'format'     => 'array', 
				'xpath'      => false, 
				'namespace'  => false, 
				'parameters' => array(
					'id'     => 'blog_id', 
					'domain' => 'domain', 
					'path'   => 'path', 
					'public' => 'public', 
				),
			), 
			'site.xml'       => array(
				'type'       => 'xml', 
				'format'     => false, 
				'xpath'      => 'url', 
				'namespace'  => 'http://www.sitemaps.org/schemas/sitemap/0.9', 
				'parameters' => array(
					'id'     => false, 
					'domain' => 'loc', 
					'path'   => false, 
					'public' => false, 
				), 
			)
		);
		
		return apply_filters( 'multisite-network-list-feed-types', $types );
	}
	
	/**
	 * Parse a JSON feed of sites
	 */
	function _parse_feed_json( $body, $feed ) {
		$body = json_decode( $body, true );
		
		$sites = array();
		
		foreach ( $body as $site ) {
			/**
			 * If the elements are objects instead of arrays, attempt to 
			 * 		type-cast them to arrays
			 */
			if ( 'object' == $feed['format'] ) {
				$site = (array)$site;
			}
			
			if ( 'array' == $feed['format'] || 'object' == $feed['format'] ) {
				$tmp = array();
				if ( false !== $feed['parameters']['id'] ) {
					$k = $feed['parameters']['id'];
					$tmp['id'] = $site[$k];
				}
				if ( false !== $feed['parameters']['domain'] && false !== $feed['parameters']['path'] ) {
					$d = $feed['parameters']['domain'];
					$p = $feed['parameters']['path'];
					
					if ( ! stristr( $site[$d], '://' ) ) {
						$site[$d] = '//' . $site[$d];
					}
					
					$tmp['url'] = esc_url( $site[$d] . $site[$p] );
				} else if ( false === $feed['parameters']['path'] ) {
					if ( ! stristr( $site[$d], '://' ) ) {
						$site[$d] = '//' . $site[$d];
					}
					
					$tmp['url'] = esc_url( $site[$d] );
				}
				if ( false !== $feed['parameters']['public'] ) {
					$p = $feed['parameters']['public'];
					$tmp['public'] = $site[$p];
				}
				$sites[] = $tmp;
			}
		}
		
		return $sites;
	}
	
	/**
	 * Parse an XML feed of sites
	 */
	function _parse_feed_xml( $body, $feed ) {
		$sites = array();
		
		$xml = new SimpleXMLElement( $body );
		
		$children = $xml->children();
		if ( array_key_exists( $feed['xpath'], $children ) ) {
			foreach ( $children as $child ) {
				$tmp = array();
				
				if ( false !== $feed['parameters']['id'] && property_exists( $child, $feed['parameters']['id'] ) ) {
					$tmp['id'] = absint( (string)$child->{$feed['parameters']['id']} );
				}
				if ( false !== $feed['parameters']['domain'] && false !== $feed['parameters']['path'] ) {
					if ( property_exists( $child, $feed['parameters']['domain'] ) && property_exists( $child, $feed['parameters']['path'] ) ) {
						$d = (string)$child->{$feed['parameters']['domain']};
						$p = (string)$child->{$feed['parameters']['path']};
						
						$url = $d;
						if ( ! stristr( $d, '://' ) ) {
							$url = '//' . $d;
						}
						$tmp['url'] = esc_url( $url . $p );
					}
				} else if ( false === $feed['parameters']['path'] ) {
					if ( property_exists( $child, $feed['parameters']['domain'] ) ) {
						$url = (string)$child->{$feed['parameters']['domain']};
						if ( ! stristr( $url, '://' ) ) {
							$url = '//' . $url;
						}
						
						$tmp['url'] = esc_url( $url );
					}
				}
				if ( false !== $feed['parameters']['public'] && property_exists( $child, $feed['parameters']['public'] ) ) {
					$tmp['public'] = absint( (string)$child->{$feed['parameters']['public']} );
				} else {
					$tmp['public'] = 1;
				}
				
				$sites[] = $tmp;
			}
		}
		
		return $sites;
		
		if ( false !== $feed['namespace'] ) {
			$xml->registerXPathNamespace( 'msnl', $feed['namespace'] );
		}
		
		print( '<pre><code>' );
		var_dump( $feed );
		print( '</code></pre>' );
		
		$sitelist = $xml->xpath( $feed['xpath'] );
		
		while ( list( , $node ) = each( $sitelist ) ) {
			$tmp = array();
			if ( false !== $feed['parameters']['id'] ) {
				$i = $feed['parameters']['id'];
				$tmp['id'] = $node->$i[0];
			}
			if ( false !== $feed['parameters']['domain'] && false !== $feed['parameters']['path'] ) {
				$d = $feed['parameters']['domain'];
				$p = $feed['parameters']['path'];
				
				$url = $node->$d[0];
				if ( ! stristr( $node->$d[0], '://' ) ) {
					$url = '//' . $node->$d[0];
				}
				$tmp['url'] = esc_url( trailingslashit( $url ) . $node->$p[0] );
			} else if ( false === $feed['parameters']['path'] ) {
				$d = $feed['parameters']['domain'];
				$url = $node->$d[0];
				if ( ! stristr( $node->$d[0], '://' ) ) {
					$url = '//' . $node->$d[0];
				}
				$tmp['url'] = esc_url( $url );
			}
			if ( false !== $feed['parameters']['public'] ) {
				$p = $feed['parameters']['public'];
				$tmp['public'] = $node->$p[0];
			} else {
				$tmp['public'] = 1;
			}
			
			$sites[] = $tmp;
		}
		
		return $sites;
	}
	
	/**
	 * Force the deletion of the cached site list
	 */
	function invalidate_cache() {
		if ( $this->is_multinetwork() && function_exists( 'delete_mnetwork_transient' ) ) {
			delete_mnetwork_transient( $this->site_transient_name );
		}
		delete_site_transient( $this->site_transient_name );
	}
	
	/**
	 * Output the list of sites on the admin settings page
	 */
	function site_list_section() {
		$sites = $this->get_site_list();
		
		if ( ! is_array( $sites ) || empty( $sites ) ) {
			_e( '<p>The list of sites is currently empty.</p>' );
			return;
		}
?>
<ol>
<?php
		foreach ( $sites as $n => $list ) {
			$out = '';
			if ( is_array( $list ) && ! empty( $list ) ) {
				$out = '<ol>';
				foreach ( $list as $s ) {
					if ( '//' == substr( $s['url'], 0, 2 ) )
						$s['url'] = 'http:' . $s['url'];
					$out .= sprintf( '<li>%s</li>', esc_url( $s['url'] ) );
				}
				$out .= '</ol>';
			}
			printf( '<li>%1$s%2$s</li>', esc_url( $n ), $out );
		}
?>
</ol>
<?php
	}
}