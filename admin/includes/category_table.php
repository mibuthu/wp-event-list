<?php
if( !defined( 'ABSPATH' ) ) {
	exit;
}

// load the base class (WP_List_Table class isn't automatically available)
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH.'wp-admin/includes/class-wp-list-table.php' );
}
require_once( EL_PATH.'includes/options.php' );
require_once( EL_PATH.'includes/db.php' );
require_once( EL_PATH.'includes/categories.php' );

class EL_Category_Table extends WP_List_Table {
	private $options;
	private $db;
	private $categories;
	private $is_disabled;

	public function __construct($is_disabled) {
		$this->options = &EL_Options::get_instance();
		$this->db = &EL_Db::get_instance();
		$this->categories = &EL_Categories::get_instance();
		$this->is_disabled = $is_disabled;
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
		switch($column_name){
			case 'desc' :
				return '<div>'.$item[$column_name].'</div>';
			case 'slug' :
				return $item[$column_name];
			case 'num_events' :
				return $this->db->count_events( $item['slug'] );
			default :
				echo $column_name;
				return $item[$column_name];
		}
	}

	/** ************************************************************************
	* This is a custom column method and is responsible for what is
	* rendered in any column with a name/slug of 'name'.
	*
	* @see WP_List_Table::::single_row_columns()
	* @param array $item A singular item (one full row's worth of data)
	* @return string Text to be placed inside the column <td> (movie title only)
	***************************************************************************/
	protected function column_name($item) {
		// create prefix with indenting according cat level
		$prefix = str_pad('', 7*$item['level'], '&#8212;', STR_PAD_LEFT).' ';
		$out = '<b>'.$prefix.$item['name'].'</b>';
		if(!$this->is_disabled) {
			// prepare Actions
			$actions = array(
				'edit'      => '<a href="?page='.$_REQUEST['page'].'&amp;id='.$item['slug'].'&amp;action=edit">Edit</a>',
				'delete'    => '<a href="#" onClick="eventlist_deleteCategory(\''.$item['slug'].'\');return false;">Delete</a>'
			);
			//Return the title contents
			$out .= $this->row_actions($actions);
		}
		return $out;
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
		if(!$this->is_disabled) {
			return '<input type="checkbox" name="slug[]" value="'.$item['slug'].'" />';
		}
		else {
			return '';
		}
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
			'cb'         => $this->is_disabled ? '' : '<input type="checkbox" />', //Render a checkbox instead of text
			'name'       => 'Name',
			'desc'       => 'Description',
			'slug'       => 'Slug',
			'num_events' => 'Events'
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
			'name'       => array( 'name', true ),  //true means its already sorted
			'desc'       => array( 'desc', false ),
			'slug'       => array( 'slug', false ),
			'num_events' => array( 'num_events', false )
		);
		// TODO: sorting of tables
		//return $sortable_columns;
		return array();
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
		if(!$this->is_disabled) {
			$actions = array(
				'delete_bulk' => 'Delete'
			);
			return $actions;
		}
		else {
			return array();
		}
	}

	/** ************************************************************************
	* Function to handle the process of the bulk actions.
	*
	* @see $this->prepare_items()
	***************************************************************************/
	private function process_bulk_action() {
		if(!$this->is_disabled) {
			//Detect when a bulk action is being triggered...
			if( 'delete_bulk' === $this->current_action() ) {
				// Show confirmation window before deleting
				echo '<script language="JavaScript">eventlist_deleteCategory ("'.implode( ', ', $_GET['slug'] ).'");</script>';
			}
		}
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
		$per_page = 15;
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		// handle the bulk actions
		$this->process_bulk_action();
		// get the required event data
		$data = $this->categories->get_cat_array();
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
}

