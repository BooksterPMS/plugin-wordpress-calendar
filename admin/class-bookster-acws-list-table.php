<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Bookster_ACWS_List_Table extends WP_List_Table 
{
  private $post_type;

  public static function define_columns() 
  {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'shortcode' => 'Shortcode',
			'author' => 'Author',
			'date' => 'Date',
		);

		return $columns;
	}

  public function __construct($post_type) 
  {
    $this->post_type = $post_type;

		parent::__construct( array(
			'singular' => 'post',
			'plural' => 'posts',
			'ajax' => false,
		) );
	}

  public function prepare_items()
  {
    $per_page = 1000;

    $columns = $this->get_columns();
    $hidden = array();
    $sortable = $this->get_sortable_columns();
    $this->_column_headers = array($columns, $hidden, $sortable);

    $args = array(
      'post_status' => 'any',
      'post_type' => $this->post_type,
			'posts_per_page' => $per_page,
			'orderby' => 'title',
			'order' => 'ASC',
			'offset' => ( $this->get_pagenum() - 1 ) * $per_page,
		);

    if ( ! empty( $_REQUEST['orderby'] ) ) {
			if ( 'title' == $_REQUEST['orderby'] ) {
				$args['orderby'] = 'title';
			} elseif ( 'author' == $_REQUEST['orderby'] ) {
				$args['orderby'] = 'author';
			} elseif ( 'date' == $_REQUEST['orderby'] ) {
				$args['orderby'] = 'date';
			}
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			if ( 'asc' == strtolower( $_REQUEST['order'] ) ) {
				$args['order'] = 'ASC';
			} elseif ( 'desc' == strtolower( $_REQUEST['order'] ) ) {
				$args['order'] = 'DESC';
			}
		}

    $this->items = get_posts($args);

    $total_items = count(get_posts(array('post_type' => $this->post_type)));
    $total_pages = ceil( $total_items / $per_page );

    $this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page' => $per_page,
		) );
  }

  public function get_columns()
  {
    $columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => 'Title',
			'shortcode' => 'Shortcode',
			'author' => 'Author',
			'date' => 'Date',
		);

		return $columns;
  }

  protected function get_sortable_columns()
  {
		$columns = array(
			'title' => array( 'title', true ),
			'author' => array( 'author', false ),
			'date' => array( 'date', false ),
		);

		return $columns;
	}

	protected function get_bulk_actions()
  {
		$actions = array(
			'delete' => 'Delete',
		);

		return $actions;
	}

	protected function column_default( $item, $column_name )
  {
		return '';
	}

	public function column_cb( $item )
  {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			$this->_args['singular'],
			$item->ID
		);
	}

  public function column_title( $item )
  {
    return '<a class="row-title" href="post.php?action=edit&post='.$item->ID.'">'.$item->post_title.'</a>';
  }

  public function column_shortcode( $item )
  {
    $property_id = get_post_meta($item->ID, '_bookster_property_id', true);

    $shortcode = '[bookster_acw property_id="'.$property_id.'" title="'.$item->post_title.'"]';

    $html = '<span class="shortcode"><input type="text" readonly="readonly" class="large-text code bookster-acw-shortcodes" value="'.esc_attr($shortcode).'" /></span>';

    return $html;
  }

  public function column_author( $item ) 
  {
		$author = get_userdata( $item->post_author );

		if ( false === $author ) {
			return;
		}

		return esc_html( $author->display_name );
	}

  public function column_date( $item )
  {
		$datetime = get_post_datetime( $item );

		if ( false === $datetime ) {
			return '';
		}

		$t_time = sprintf(
			'%1$s at %2$s',
			$datetime->format('Y/m/d'),
			$datetime->format('g:i a')
		);

		return $t_time;
	}
}