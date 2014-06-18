<?php
if(!defined('ABSPATH')) {
	exit;
}

// load the base class (WP_List_Table class isn't automatically available)
if(!class_exists('WP_List_Table')){
	require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}
require_once(EL_PATH.'includes/db.php');
require_once(EL_PATH.'includes/categories.php');
require_once(EL_PATH.'includes/filterbar.php');

class EL_Event_Table extends WP_List_Table {
	private $db;
	private $categories;
	private $filterbar;
	private $args;

	public function __construct() {
		$this->db = &EL_Db::get_instance();
		$this->categories = &EL_Categories::get_instance();
		$this->filterbar = &EL_Filterbar::get_instance();
		$this->set_args();

		global $status, $page;
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'event',     //singular name of the listed records
			'plural'    => 'events',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );
	}

	/** ************************************************************************
	* This method is called when the parent class can't find a method
	* specifically build for a given column.
	*
	* @param array $item A singular item (one full row's worth of data)
	* @param array $column_name The name/slug of the column to be processed
	* @return string Text or HTML to be placed inside the column <td>
	***************************************************************************/
	protected function column_default($item, $column_name) {
		switch($column_name) {
			case 'date' :
				return $this->format_event_date($item->start_date, $item->end_date, $item->time);
			case 'details' :
				return '<div>'.$this->db->truncate(wpautop($item->details), 80).'</div>';
			case 'pub_user' :
				return get_userdata($item->pub_user)->user_login;
			case 'pub_date' :
				return $this->format_pub_date($item->pub_date);
			case 'categories' :
				return esc_html($this->categories->get_category_string($item->$column_name));
			default :
				return esc_html($item->$column_name);
		}
	}

	/** ************************************************************************
	* This is a custom column method and is responsible for what is
	* rendered in any column with a name/slug of 'title'.
	*
	* @see WP_List_Table::::single_row_columns()
	* @param array $item A singular item (one full row's worth of data)
	* @return string Text to be placed inside the column <td> (movie title only)
	***************************************************************************/
	protected function column_title($item) {
		//Prepare Columns
		$actions = array(
			'edit'      => '<a href="?page='.$_REQUEST['page'].'&amp;id='.$item->id.'&amp;action=edit">Edit</a>',
			'duplicate' => '<a href="?page=el_admin_new&amp;id='.$item->id.'&amp;action=copy">Duplicate</a>',
			'delete'    => '<a href="#" onClick="eventlist_deleteEvent('.$item->id.');return false;">Delete</a>');

		//Return the title contents
		return sprintf('<b>%1$s</b> <span style="color:silver">(id:%2$s)</span>%3$s',
			esc_html($item->title),
			$item->id,
			$this->row_actions($actions));
	}

	/** ************************************************************************
	* Required if displaying checkboxes or using bulk actions! The 'cb' column
	* is given special treatment when columns are processed.
	*
	* @see WP_List_Table::::single_row_columns()
	* @param array $item A singular item (one full row's worth of data)
	* @return string Text to be placed inside the column <td> (movie title only)
	***************************************************************************/
	protected function column_cb($item) {
		//Let's simply repurpose the table's singular label ("event")
		//The value of the checkbox should be the record's id
		return '<input type="checkbox" name="id[]" value="'.$item->id.'" />';
	}

	/** ************************************************************************
	* This method dictates the table's columns and titles. This should returns
	* an array where the key is the column slug (and class) and the value is
	* the column's title text.
	*
	* @see WP_List_Table::::single_row_columns()
	* @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	***************************************************************************/
	public function get_columns() {
		return array(
			'cb'          => '<input type="checkbox" />', //Render a checkbox instead of text
			'date'        => __( 'Date' ),
			'title'       => __( 'Event' ),
			'location'    => __( 'Location' ),
			'details'     => __( 'Details' ),
			'categories'  => __( 'Categories' ),
			'pub_user'    => __( 'Author' ),
			'pub_date'    => __( 'Published' )
		);
	}

	/** ************************************************************************
	* If you want one or more columns to be sortable (ASC/DESC toggle), you
	* will need to register it here. This should return an array where the key
	* is the column that needs to be sortable, and the value is db column to
	* sort by.
	*
	* @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	***************************************************************************/
	public function get_sortable_columns() {
		$sortable_columns = array(
			'date'     => array( 'date', true ),  //true means its already sorted
			'title'    => array( 'title', false ),
			'location' => array( 'location', false ),
			'pub_user' => array( 'pub_user', false ),
			'pub_date' => array( 'pub_date', false )
		);
		return $sortable_columns;
	}

	/** ************************************************************************
	* Optional. If you need to include bulk actions in your list table, this is
	* the place to define them. Bulk actions are an associative array in the format
	* 'slug'=>'Visible Title'
	* If this method returns an empty value, no bulk action will be rendered.
	*
	* @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	****************************************************************************/
	public function get_bulk_actions() {
		$actions = array(
			'delete_bulk' => 'Delete'
		);
		return $actions;
	}

	/** ************************************************************************
	* Function to handle the process of the bulk actions.
	*
	* @see $this->prepare_items()
	***************************************************************************/
	private function process_bulk_action() {
		//Detect when a bulk action is being triggered...
		if( 'delete_bulk'===$this->current_action() ) {
			// Show confirmation window before deleting
			echo '<script language="JavaScript">eventlist_deleteEvent ("'.implode( ', ', $_GET['id'] ).'");</script>';
		}
	}

	public function extra_tablenav($which) {
		$out = '';
		// add filter elements
		if('top' === $which) {
			$out = '
				<div class="alignleft actions">';
			$out .= $this->filterbar->show_years('?page=el_admin_main', $this->args, 'dropdown', 'admin', array('show_past'=>true));
			$out .= $this->filterbar->show_cats('?page=el_admin_main', $this->args, 'dropdown', 'admin');
			$out .= '
				<input type="hidden" name="noheader" value="true" />
				<input id="event-query-submit" class="button" type="submit" name ="filter" value="'.__('Filter').'" />
			</div>';
		}
		echo $out;
	}

	/** ************************************************************************
	* In this function the data for the display is prepared.
	*
	* @param string $date_range Date range for displaying the events
	* @uses $this->_column_headers
	* @uses $this->items
	* @uses $this->get_columns()
	* @uses $this->get_sortable_columns()
	* @uses $this->get_pagenum()
	* @uses $this->set_pagination_args()
	***************************************************************************/
	public function prepare_items() {
		$per_page = 20;
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		// handle the bulk actions
		$this->process_bulk_action();
		// get the required event data
		$data = $this->get_events();
		// setup pagination
		$current_page = $this->get_pagenum();
		$total_items = count( $data );
		$data = array_slice( $data, ( ( $current_page-1 )*$per_page ), $per_page );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );
		// setup items which are used by the rest of the class
		$this->items = $data;
	}

	private function set_args() {
		// filters
		$this->args['date_filter'] = 'all';
		$this->args['cat_filter'] = 'all';
		// actual_date
		$this->args['actual_date'] = 'upcoming';
		if(isset($_GET['date']) && (is_numeric($_GET['date']) || 'all' == $_GET['date'] || 'upcoming' == $_GET['date'] || 'past' == $_GET['date'])) {
			$this->args['actual_date'] = $_GET['date'];
		}
		// actual_cat
		$this->args['actual_cat'] = 'all';
		if(isset($_GET['cat'])) {
			$this->args['actual_cat'] = $_GET['cat'];
		}
	}

	private function get_events() {
		// define sort_array
		$order = 'ASC';
		if( isset( $_GET['order'] ) && $_GET['order'] === 'desc' ) {
			$order = 'DESC';
		}
		$orderby = '';
		if( isset( $_GET['orderby'] ) ){
			$orderby = $_GET['orderby'];
		}
		// set standard sort according date ASC, only when date should be sorted desc, DESC should be used
		if( $orderby == 'date' && $order == 'DESC' ) {
			$sort_array = array( 'start_date DESC', 'time DESC', 'end_date DESC');
		}
		else {
			$sort_array = array( 'start_date ASC', 'time ASC', 'end_date ASC');
		}
		// add primary order column to the front of the standard sort array
		switch( $orderby )
		{
			case 'title' :
				array_unshift( $sort_array, 'title '.$order );
				break;
			case 'location' :
				array_unshift( $sort_array, 'location '.$order );
				break;
			case 'pub_user' :
				array_unshift( $sort_array, 'pub_user '.$order );
				break;
			case 'pub_date' :
				array_unshift( $sort_array, 'pub_date '.$order );
				break;
		}
		// get and return events in the correct order
		return $this->db->get_events($this->args['actual_date'], $this->args['actual_cat'], 0, $sort_array);
	}

	/** ************************************************************************
	* In this function the start date, the end date and time is formated for
	* the output.
	*
	* @param string $start_date The start date of the event
	* @param string $end_date The end date of the event
	* @param string $time The start time of the event
	***************************************************************************/
	private function format_event_date( $start_date, $end_date, $start_time ) {
		$out = '<span style="white-space:nowrap;">';
		// start date
		$out .= mysql2date( __( 'Y/m/d' ), $start_date );
		// end date for multiday event
		if( $start_date !== $end_date ) {
			$out .= ' -<br />'.mysql2date( __( 'Y/m/d' ), $end_date );
		}
		// event time
		if( '' !== $start_time ) {
			// set time format if a known format is available, else only show the text
			$date_array = date_parse( $start_time );
			if( empty( $date_array['errors']) && is_numeric( $date_array['hour'] ) && is_numeric( $date_array['minute'] ) ) {
				$start_time = mysql2date( get_option( 'time_format' ), $start_time );
			}
			$out .= '<br />
				<span class="time">'.esc_html($start_time).'</span>';
		}
		$out .= '</span>';
		return $out;
	}

	private function format_pub_date( $pub_date ) {
		// similar output than for post or pages
		$timestamp = strtotime( $pub_date );
		$time_diff = time() - $timestamp;
		if( $time_diff >= 0 && $time_diff < 24*60*60 ) {
			$date = sprintf( __( '%s ago' ), human_time_diff( $timestamp ) );
		}
		else {
			$date = mysql2date( __( 'Y/m/d' ), $pub_date );
		}
		$datetime = mysql2date( __( 'Y/m/d g:i:s A' ), $pub_date );
		return '<abbr title="'.$datetime.'">'.$date.'</abbr>';
	}
}

