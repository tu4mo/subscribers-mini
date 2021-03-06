<?php

/**
 * Plugin Name: Subscribers Mini
 * Plugin URI: http://github.com/tu4mo/subscribers-mini
 * Description: A minimal e-mail subscribe form with CSV export
 * Version: 1.0.1
 * Author: tu4mo
 * Author URI: tu4mo.com
 * License: ISC
 */

defined( 'ABSPATH' ) or die();

class SubscribersMini {

	public function __construct() {
		// Add actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_subsmin_subscribe', array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_nopriv_subsmin_subscribe', array( $this, 'subscribe' ) );
		add_action( 'admin_post_subsmin_export', array( $this, 'subsmin_export' ) );

		// Add filters
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
		add_filter( 'manage_edit-subsmin_subscription_columns', array( $this, 'subscription_columns' ) );

		// Add shortcode
		add_shortcode( 'subsmin', array( $this, 'shortcode' ) );
	}

	/**
	 * Register custom post type for subscriptions
	 */
	public function init() {
		$labels = array(
			'name'               => _x( 'Subscribers', 'subscribers_mini' ),
			'singular_name'      => _x( 'Subscriber', 'subscribers_mini' ),
			'add_new'            => _x( 'Add New', 'subscribers_mini' ),
			'add_new_item'       => _x( 'Add New Subscriber', 'subscribers_mini' ),
			'edit_item'          => _x( 'Edit Subscriber', 'subscribers_mini' ),
			'new_item'           => _x( 'New Subscriber', 'subscribers_mini' ),
			'view_item'          => _x( 'View Subscriber', 'subscribers_mini' ),
			'search_items'       => _x( 'Search Subscribers', 'subscribers_mini' ),
			'not_found'          => _x( 'No subscribers found', 'subscribers_mini' ),
			'not_found_in_trash' => _x( 'No subscribers found in Trash', 'subscribers_mini' ),
			'parent_item_colon'  => _x( 'Parent Subscriber:', 'subscribers_mini' ),
			'menu_name'          => _x( 'Subscribers', 'subscribers_mini' ),
		);

		$args = array(
			'labels'          => $labels,
			'description'     => 'Subscribers',
			'supports'        => array( 'title' ),
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 25,
			'menu_icon'       => 'dashicons-email-alt',
			'query_var'       => true,
			'can_export'      => true,
			'rewrite'         => false,
			'capability_type' => 'post'
		);

		register_post_type( 'subsmin_subscription', $args );
	}

	/**
	 * Add "Export As CSV" -link
	 */
	public function admin_menu() {
		add_submenu_page(
			'edit.php?post_type=subsmin_subscription',
			'Export',
			'Export As CSV',
			'manage_options',
			'admin-post.php?action=subsmin_export'
		);
	}

	/**
	 * Handle subscription AJAX
	 */
	public function subscribe() {
		global $wpdb;

		$email = wp_strip_all_tags( $_POST['email'] );

		if ( ! get_page_by_title( $email, 'OBJECT', 'subsmin_subscription' ) ) {
			$post = array(
				'post_title'   => $email,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'subsmin_subscription',
				'post_author'  => 1
			);

			wp_insert_post( $post );
		}

		wp_die();
	}

	/**
	 * Export
	 */
	public function subsmin_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		header( 'Content-Type: application/csv' );
		header( 'Content-Disposition: attachment; filename=subscribers.csv' );
		header( 'Pragma: no-cache' );

		$subscribers = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => 'subsmin_subscription'
			)
		);

		foreach ( $subscribers as $subscriber ) {
			echo $subscriber->post_title . "\n";
		}
	}

	/**
	 * Change placeholder text for Title field
	 */
	public function enter_title_here( $input ) {
		global $post_type;

		if ( 'subsmin_subscription' == $post_type ) {
			return __( 'Enter email here');
		}

		return $input;
	}

	/**
	 * Change post columns
	 */
	public function subscription_columns( $columns ) {
		$columns = array(
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'Email' ),
			'date'  => __( 'Date' )
		);

		return $columns;
	}

	/**
	 * Create shortcode
	 */
	public function shortcode( $atts ) {
		wp_enqueue_script( 'subsmin', plugins_url( 'subscribers-mini.js', __FILE__ ), array( 'jquery' ), null, true );
		wp_localize_script( 'subsmin', 'subsmin', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

		$a = shortcode_atts(
			array(
				'placeholder'        => 'Email address',
				'button_label'       => 'Subscribe',
				'completion_message' => 'Thank you for subscribing.'
			),
			$atts
		);

		ob_start();

		?>
			<form id="subsminform" class="subsmin-form">
				<input type="email" placeholder="<?php echo esc_attr( $a['placeholder'] ); ?>" id="subsminemail" class="subsmin-email" required>
				<input type="submit" value="<?php echo esc_attr( $a['button_label'] ); ?>" id="subsminsubmit" class="subsmin-submit">
			</form>
			<p class="subsmin-completion-message" style="display: none;">
				<?php echo esc_html( $a['completion_message'] ); ?>
			</p>
		<?php

		return ob_get_clean();
	}
}

$subscribersMini = new SubscribersMini();
