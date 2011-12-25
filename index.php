<?php
/*
Plugin Name: WP Last Activity
Plugin URI: http://yoda.neun12.de 
Description: Show last activity of any users on the user screen
Version: 1.0
Author: Ralf Albert, Eric Teubert
Author URI: neun12@googlemail.com, ericteubert@googlemail.com 
License: GPL
*/

/*
 * Based on a idea from Eric Teubert
 * see http://www.satoripress.com/2011/12/wordpress/plugin-development/find-users-by-last-login-activity-225/
 */

if( ! function_exists( 'add_action' ) )
	die( __( 'Cheatin&#8217; uh?' ) );

if( ! class_exists( 'WP_Last_Activity' ) ){
	add_action( 'plugins_loaded', array( 'WP_Last_Activity', 'start_plugin' ) );
	
	class WP_Last_Activity
	{
		/**
		 * 
		 * Textdomain
		 * @var const string WP_LAST_ACTIVITY
		 */
		const WP_LAST_ACTIVITY = 'wp_last_activity';
		
		/**
		 * 
		 * Plugin-instance
		 * @var object $plugin_self
		 */
		private static $plugin_self = NULL;
		
		/**
		 * 
		 * Constructor
		 * Add all needed filter & actions
		 * 
		 * @param none
		 * @return void
		 * @since 1.0
		 */
		public function __construct(){
			// prepare plugin on activation
			register_activation_hook( __FILE__, array( &$this, 'add_last_activity_for_all_users' ) );
			
			//TODO: deactivation-hook and uninstall-hook
			// register_deactivation_hook( $file, $function );
			// register_uninstall_hook( $file, $callback );
			
			// filter & actions for output on users screen
			add_filter( 'manage_users_columns', array( &$this, 'add_user_last_activity_column' ) );			
			add_action( 'manage_users_custom_column',  array( &$this, 'show_user_last_activity_column' ), 10, 3);
			
			// make cols sortable
			add_filter( 'manage_users_sortable_columns', array( &$this, 'add_user_last_activity_sortable_column' ) );
			add_filter( 'pre_user_query', array( &$this, 'order_user_last_activity_sortable_column' ) );
			
			// filter & hooks for logging
			add_action( 'wp_login' , array( &$this, 'add_login_time' ), 10, 1 );
			add_action( 'auth_cookie_valid', array( &$this, 'stay_logged_in_users' ), 10, 1 );

		}

		/**
		 * 
		 * Plugin start
		 * Create an instance of the plugin
		 * 
		 * @param none
		 * @return object $plugin_self
		 * @since 1.0
		 */
		public static function start_plugin(){
			if( NULL == self::$plugin_self )
				self::$plugin_self = new self;
			
			return self::$plugin_self;
		} 
		
		/**
		 * 
		 * Add column to users screen
		 * 
		 * @param array $columns The original array with columns
		 * @return array $columns The modified array with the new column
		 * @since 1.0
		 */
		public function add_user_last_activity_column( $columns ){
			$columns['last_activity'] = __( 'Last activity', self::WP_LAST_ACTIVITY );
		    
		    return $columns;
		}

		/**
		 * 
		 * Display the column-data
		 * 
		 * @param string $value
		 * @param string $column_name
		 * @param int $user_id
		 * @return string $days_inactive The number of days a user is inactive
		 * @since 1.0
		 */
		public function show_user_last_activity_column( $value, $column_name, $user_id ){
			if ( 'last_activity' == $column_name ){
				
				$last_log = get_user_meta( $user_id, 'last_activity' );
				
				if( ! empty( $last_log ) )
					$days =	floor( ( time() - strtotime( $last_log[0] ) ) / 86400 ); // 86400 = 60 secs * 60 mins * 24 hours = 1 day
				else
					$days = -1;
		
				switch( $days ){
					case -1:
						$days_inactive = __( 'never', self::WP_LAST_ACTIVITY );
						break;
					
					case 0:
						$days_inactive = __( 'today', self::WP_LAST_ACTIVITY );
						break;
						
					case 1:
						$days_inactive = __( 'yesterday', self::WP_LAST_ACTIVITY );
						break;
						
					default:
						$days_inactive = sprintf( __( '%d days ago', self::WP_LAST_ACTIVITY ), $days );
				}

				return $days_inactive;
			}
		}

		/**
		 * 
		 * Register the sortable column
		 * 
		 * @param array $columns
		 * @return array $columns
		 * @since 1.0
		 */
		public function add_user_last_activity_sortable_column( $columns ) {
			$columns['last_activity'] = 'last_activity';
		
			return $columns;
		}
		
		/**
		 * 
		 * Modify the query to sort users by last activity
		 * 
		 * @param object $query
		 * @return object $query
		 * @since 1.0
		 */
		public function order_user_last_activity_sortable_column( $query ) {

			if( 'last_activity' != $query->query_vars['orderby'] )
				return $query;
			
			global $wpdb;
			
			$order = $query->query_vars['order'];
			
		   	$query->query_fields 	= "SQL_CALC_FOUND_ROWS {$wpdb->users}.ID, {$wpdb->usermeta}.meta_key AS mk, {$wpdb->usermeta}.meta_value AS mv";
			$query->query_from 		= "FROM {$wpdb->users} INNER JOIN {$wpdb->usermeta} ON ({$wpdb->users}.ID = {$wpdb->usermeta}.user_id)";
			$query->query_where 	= "WHERE 1=1 AND ({$wpdb->usermeta}.meta_key = 'last_activity' )";
			$query->query_orderby 	= "ORDER BY mv {$order}";
		
			return $query;
		}
		
		/**
		 * 
		 * Logging the date & time a user log in
		 * 
		 * @param string $user_login
		 * @return void
		 * @since 1.0
		 */
		public function add_login_time( $user_login ) {
			$user = get_user_by( 'login', $user_login );
			update_user_meta( $user->ID, 'last_activity', current_time( 'mysql' ) );
		}

		/**
		 * 
		 * Handles users who log in with cookie (stay logged in users)
		 * 
		 * @param array $user Array with userdata
		 * @return void
		 * @uses add_login_time()
		 * @since 1.0
		 */
		public function stay_logged_in_users( $user ){
			//TODO: add session-value to avoid logging on each request
			$this->add_login_time( $user['username'] );
		}

		/**
		 * 
		 * Add the current date & time to all users when the plugin is activated
		 * 
		 * @param none
		 * @return void
		 * @since 1.0
		 */
		public function add_last_activity_for_all_users() {
			global $wpdb;
			
			$sql = $wpdb->prepare( "
				SELECT
					u.ID
				FROM
					$wpdb->users AS u
					LEFT JOIN $wpdb->usermeta m ON u.ID = m.user_id AND m.meta_key = 'last_activity'
				WHERE
					m.meta_value IS NULL" );
			$userids = $wpdb->get_col( $sql );
			
			if ( $userids ) {
				foreach ( $userids as $userid ) {
					update_user_meta( $userid, 'last_activity', current_time( 'mysql' ) );
				}
			}
		}

	} // .class WP_Last_activity
} // .if-class-exists