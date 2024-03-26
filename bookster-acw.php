<?php 

/*
Plugin Name: Bookster Availability Calendar
Description: Add Bookster Availability Calendar Widgets for your Bookster properties to your posts and pages
Version: 1.0
Author: Bookster
Author URI: https://www.booksterhq.com/
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Bookster_ACW
{
  /**
	 * Static property to hold our singleton instance
	 *
	 */
	static $instance = false;

  const post_type = 'bookster_ac_widgets';
  const version = '1.0';

  private function __construct() {
    # on activation
    register_activation_hook( __FILE__, array($this,'activate'));

    # on deactivation
    register_deactivation_hook(__FILE__, array($this,'deactivate'));
      
    # uninstall
    register_uninstall_hook(__FILE__, array($this,'uninstall'));

    add_action( 'init', array( $this, 'init' ), 10 );

    add_action( 'plugins_loaded', array($this,'pluginLoaded'), 10);

    add_action( 'edit_form_top', array($this, 'editFormTop'));

    add_action( 'edit_form_after_title', array($this,'editFormAfterTitle'));

    if(is_admin()) {
      $this->adminInit();
    }
  }

  /**
   * Run init action
   */
  public function init()
  {
    $this->registerPostType();
  }

  /**
   * Init admin menus, interfaces, templates, and hooks
   */
  public function adminInit()
  {
    add_action('admin_menu', array($this, 'adminMenu'), 9, 0);
    add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
  }

  /**
   * Register shortcodes
   */
  public function pluginLoaded()
  {
    add_shortcode('bookster_acw', array($this,'runShortcode'));

    add_action( 'save_post_'.self::post_type, array($this,'saveACW'), 10);
  }

  /**
   * Run plugin activation tasks
   */
  public function activate()
  {
    $this->registerPostType();
    flush_rewrite_rules();
  }

  /**
   * Run plugin deactivation tasks
   */
  public function deactivate()
  {
    unregister_post_type(self::post_type);
    flush_rewrite_rules();
  }

  /**
   * Run plugin uninstall tasks
   */
  public function uninstall()
  {
    $posts = get_posts(
      array(
        'numberposts' => -1,
        'post_type' => self::post_type,
        'post_status' => 'any',
      )
    );
  
    foreach ( $posts as $post ) {
      wp_delete_post( $post->ID, true );
    }
  }

  /**
   * Callback function for the widget shortcode.
   */
  public function runShortcode($atts, $content = null, $code)
  {
    if ( is_feed() ) {
      return '[bookster_acw]';
    }
  
    $output = '';

    if ( 'bookster_acw' === $code ) {
      $atts = shortcode_atts(
        array(
          'property_id' => '',
          'title' => '',
        ),
        $atts, 'bookster_acw'
      );
  
      $id = trim( $atts['property_id'] );
  
      if($id != '') {
        $output = "<script>(function(w,d,a){var b=(w.bookster=w.bookster||[]);b.push({calendar:a});var h=d.getElementsByTagName('head')[0];var j=d.createElement('script');j.type='text/javascript';j.async=true;j.src='https://cdn.booksterhq.com/widgets/v1/calendar.js';h.appendChild(j)})(window,document,{id:'bookster-calendar-widget-".$id."',property:".$id.",syndicate:67,theme:{}})</script>";
        $output .= '<div id="bookster-calendar-widget-'.$id.'" style="height:430px;">';
      }
    }
  
    return $output;
  }

  /**
   * Register custom post type
   */
  private function registerPostType()
  {
    register_post_type( self::post_type, array(
			'labels' => array(
				'name' => 'Bookster Availability Calendars',
				'singular_name' => 'Bookster Availability Calendar',
        'add_new' => 'Add New',
  	    'add_new_item' => 'Add New Bookster Availability Calendar',
  	    'edit_item' => 'Edit Bookster Availability Calendar',
  	    'new_item' => 'New Bookster Availability Calendar',
  	    'view_item' => 'View Bookster Availability Calendars',
  	    'search_items' => 'Search Bookster Availability Calendars',
  	    'not_found' =>  'No Bookster Availability Calendars found',
  	    'not_found_in_trash' => 'No Bookster Availability Calendars found in Trash',
  	    'parent_item_colon' => ''
			),
			'rewrite' => false,
			'query_var' => false,
			'public' => false,
      'publicly_queryable' => true,
      'show_in_nav_menus' => false,
      'show_ui' => true,
      'show_in_menu' => false,
      'show_in_rest' => false,
      'exclude_from_search' => true,
      'register_meta_box_cb' => array($this, "metaBox"),
      'supports' => array('title'),
			'capability_type' => 'page',
			'capabilities' => array(
				'edit_post' => 'publish_pages',
				'read_post' => 'edit_posts',
				'delete_post' => 'publish_pages',
				'edit_posts' => 'publish_pages',
				'edit_others_posts' => 'publish_pages',
				'publish_posts' => 'publish_pages',
				'read_private_posts' => 'publish_pages',
			),
		));
  }

  /**
   * Add metaboxes to custom post type
   */
  public function metaBox()
  {
    add_meta_box('bookster-property-id', 'Bookster Property ID:', array($this, "metaBoxCallback"));
  }

  /**
   * Display meta box on post edit page
   */
  public function metaBoxCallback($post)
  {
    wp_nonce_field( 'bookster_acw_nonce', 'bookster_acw_nonce' );

    $value = get_post_meta($post->ID, '_bookster_property_id', true);

    echo '<input id="bookster_property_id" name="bookster_property_id" value="'.esc_attr( $value ).'"';
  }

  /**
   * Handle saving of custom post and meta boxes
   */
  public function saveACW($post_id)
  {
    // make sure we aren't using autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return $post_id;

    // do our nonce check
    if ( ! isset( $_POST['bookster_acw_nonce'] ) || ! wp_verify_nonce( $_POST['bookster_acw_nonce'], 'bookster_acw_nonce' ) )
      return $post_id;
      
    if ( !current_user_can( 'edit_posts', $post_id ) ) {
      return $post_id;
    }

    //Sanitize user input
    $property_id = sanitize_text_field( $_POST['bookster_property_id'] );

    // Update the meta field in the database.
    update_post_meta( $post_id, '_bookster_property_id', $property_id );
  }

  /**
   * Setup admin menu items
   */
  public function adminMenu()
  {
    add_menu_page('Bookster ACW', 'Bookster AC', 'edit_posts', 'bookster-acw', array($this, 'adminList'), 'dashicons-calendar');
    $edit = add_submenu_page('bookster-acw', 'Edit Bookster Availability Calendar', 'List All', 'edit_posts', 'bookster-acw', array($this, 'adminList'));
    add_action( 'load-' . $edit, array($this, 'adminEdit'), 10, 0 );
    $addNew = add_submenu_page('bookster-acw', 'Add New Bookster Availability Calendar', 'Add New', 'edit_posts', 'bookster-acw-new', array($this, 'adminNew'));
    add_action( 'load-' . $addNew, array($this, 'adminNew'), 10, 0 );
  }

  /**
   * Enqueue admin scripts and styles
   */
  public function adminEnqueueScripts($hook)
  {
    $screen = get_current_screen();
    if ( false === strpos( $hook, 'bookster-acw' ) && $screen->post_type != self::post_type) {
      return;
    }

    wp_enqueue_style('bookster-acw-admin-css', plugin_dir_url( __FILE__ ) . 'admin/css/bookster-acw-admin.css', array(), self::version, 'all');

    wp_enqueue_script('bookster-acw-admin-js', plugin_dir_url( __FILE__ ) . 'admin/js/bookster-acw-admin.js', array(), self::version);
  }


  /**
   * Handle admin actions
   */
  public function adminEdit()
  {
    $action = $this->get_current_action();

    if( 'delete' == $action ) {
      $posts = empty( $_POST['post_ID'] )
			? (array) $_REQUEST['post']
			: (array) $_POST['post_ID'];

		  $deleted = 0;

      foreach ( $posts as $post ) {
        $post = get_post($post);
        if ( empty( $post ) ) {
          continue;
        }

        if ( ! wp_delete_post( $post->ID, true ) ) {
          wp_die("Error in deleting.");
        }
  
        $deleted = true;
      }
  
      $query = array();
  
      if ( ! empty( $deleted ) ) {
        $query['message'] = 'deleted';
      }
  
      $redirect_to = add_query_arg( $query, menu_page_url( 'bookster-acw', false ) );
  
      wp_safe_redirect( $redirect_to );
      exit();
    } 
  }

  /**
   * Admin page for plugin with list of created Advanced Calendar Widgets
   */
  public function adminList()
  {
    if ( ! class_exists( 'Bookster_ACWS_List_Table' ) ) {
			require_once plugin_dir_path( __FILE__ ). 'admin/class-bookster-acws-list-table.php';
		}

    $list_table = new Bookster_ACWS_List_Table(self::post_type);
	  $list_table->prepare_items();

    ?>
    <div class="wrap" id="bookster-acws-list-table">
      <h1 class="wp-heading-inline">Bookster Availability Calendars</h1>
      <a class="page-title-action" href="post-new.php?post_type=<?php echo self::post_type ?>">Add New</a>

      <hr class="wp-header-end">
     <?php $this->showHelpText(); ?>

      <form method="get" action="">
      <input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
      <?php $list_table->display(); ?>
    </form>
    </div>

    <?php
  }

  /**
   * Add help text to ACW posts
   */
  public function editFormTop(WP_Post $post)
  {
    if($post->post_type !== self::post_type)
      return;
    $this->showHelpText();
  }

  /**
   * Add output of shortcode to saved ACW posts
   */
  public function editFormAfterTitle(WP_Post $post)
  {
    if($post->post_type !== self::post_type)
      return;

    $html = '<div class="inside">This is for your reference only. It will not appear on your website.</div>';

    $screen = get_current_screen();
    
    if($screen->action == '') {
      $property_id = get_post_meta($post->ID, '_bookster_property_id', true);
      $shortcode = '[bookster_acw property_id="'.$property_id.'" title="'.$post->post_title.'"]';

      $html .= '<div class="inside shortcode-container"><p class="description">';
      $html .= '<label for="bookster-acw-shortcode">Copy this shortcode and paste it into your post, page, or text widget content:</label>';
      $html .= '<span class="shortcode wp-ui-highlight">';
      $html .= '<input type="text" id="bookster-acw-shortcode" readonly="readonly" class="large-text code" value="'.esc_attr($shortcode).'" />';
      $html .= '</span></p>';
      $html .= '<p class="description">You can learn how to use WordPress shortcodes <a href="https://wordpress.com/support/wordpress-editor/blocks/shortcode-block/">here</a>.</p>';
      $html .= '</div>';
    }

    echo $html;
  }

  /**
   * Redirect to Add New Post Form for plugin custom post type
   */
  public function adminNew()
  {
    wp_safe_redirect('post-new.php?post_type='.self::post_type);
    exit();
  }

  private function get_current_action() {
    if ( isset( $_REQUEST['action'] ) and -1 != $_REQUEST['action'] ) {
      return $_REQUEST['action'];
    }
  
    if ( isset( $_REQUEST['action2'] ) and -1 != $_REQUEST['action2'] ) {
      return $_REQUEST['action2'];
    }
  
    return false;
  }

  /**
   * Get help text displayed in ACW posts and list
   */
  private function showHelpText()
  { ?>
      <div class="notice notice-info">
        <p><strong>Important:</strong> You need a <a href="https://www.booksterhq.com/">Bookster</a> subscription and have a Bookster Property ID to add to your calendar widget for it to work.</p>
        <p>Property IDs can be found in the Bookster dashboard. Go to view a specific property and its Property ID will be the number displayed in the top right hand corner of the Listing Strength box.</p>
        <p>Add and Edit Bookster Availability Calendar widgets and add them to your posts and pages by using the shortcode. You can learn how to use WordPress shortcodes <a href="https://wordpress.com/support/wordpress-editor/blocks/shortcode-block/">here</a>.</p> 
        <p>You can copy the shortcode by clicking on it from the list below or when editing a calendar widget.</p>
      </div>
    <?php
  }

  /**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return Bookster_ACW
	 */

	public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}
}

// Instantiate our class
$Bookster_ACW = Bookster_ACW::getInstance();