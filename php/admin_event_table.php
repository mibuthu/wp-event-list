<?php
// load the base class (WP_List_Table class isn't automatically available)
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
require_once( EL_PATH.'php/db.php' );

class Admin_Event_Table extends WP_List_Table {
	public function __construct() {
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
		switch($column_name){
			case 'date' :
				return $this->format_date( $item->start_date, $item->end_date, $item->time );
			case 'details' :
				return $this->truncate( 80, $item->details );
			case 'pub_user' :
				return get_userdata( $item->$column_name )->user_login;
			default :
				return $item->$column_name;
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
			'edit'      => '<a href="?page='.$_REQUEST['page'].'&id='.$item->id.'&action=edit">Edit</a>',
			'duplicate' => '<a href="?page=el_admin_new&id='.$item->id.'&action=copy">Duplicate</a>',
			'delete'    => '<a href="#" onClick="eventlist_deleteEvent('.$item->id.');return false;">Delete</a>'
		);

		//Return the title contents
		return sprintf( '<b>%1$s</b> <span style="color:silver">(id:%2$s)</span>%3$s',
			$item->title,
			$item->id,
			$this->row_actions( $actions )
		);
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
			'cb'       => '<input type="checkbox" />', //Render a checkbox instead of text
			'date'     => 'Date',
			'title'    => 'Event',
			'location' => 'Location',
			'details'  => 'Details',
			'pub_user' => 'Author',
			'pub_date' => 'Published'
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
		// TODO: bulk action must be integrated
		//Detect when a bulk action is being triggered...
		if( 'delete_bulk'===$this->current_action() ) {
			// Show confirmation window before deleting
			echo '<script language="JavaScript">eventlist_deleteEvent ("'.implode( ', ', $_GET['id'] ).'");</script>';
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
	public function prepare_items( $date_range='upcoming' ) {
		$per_page = 20;
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		// handle the bulk actions
		$this->process_bulk_action();
		// get the required event data
		$data = $this->get_events( $date_range );
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

	private function get_events( $date_range ) {
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
		return el_db::get_events( $date_range, $sort_array );
	}

	/** ************************************************************************
	* In this function the start date, the end date and time is formated for
	* the output.
	*
	* @param string $start_date The start date of the event
	* @param string $end_date The end date of the event
	* @param string $time The start time of the event
	***************************************************************************/
	private function format_date( $start_date, $end_date, $start_time ) {
		$start_array = explode("-", $start_date);
		$start_date = mktime(0,0,0,$start_array[1],$start_array[2],$start_array[0]);
		$end_array = explode("-", $end_date);
		$end_date = mktime(0,0,0,$end_array[1],$end_array[2],$end_array[0]);
		$out = '<span style="white-space:nowrap;">';
		// one day event
		if( $start_date == $end_date ) {
			if ($start_array[2] == "00") {
				$start_date = mktime(0,0,0,$start_array[1],15,$start_array[0]);
				$out .= date("F, Y", $start_date);
			}
			else {
				$out .= date("M j, Y", $start_date);
			}
		}
		// multiday event with start and end date in the same year
		elseif( $start_array[0] == $end_array[0] ) {
			$out .= date("M j", $start_date).'-';
			// same start and end month
			if( $start_array[1] == $end_array[1] ) {
				$out .= date("j, Y", $end_date);
			}
			// different start and end month
			else {
				$out .= date("M j, Y", $end_date);
			}
		}
		// multiday event with different start and end year
		else {
			$out .= date("M j, Y", $start_date).'-<br />'.date("M j, Y", $end_date).'&nbsp;';
		}
		$out .= '<br />
					<span class="time">'.$start_time.'</span></span>';
		return $out;
	}

	// function to truncate and shorten html text
	/** ************************************************************************
	* Function to truncate and shorten text
	*
	* @param int $max_length The length to which the text should be shortened
	* @param string $text The text which should be shortened
	***************************************************************************/
	private static function truncate( $max_length, $text ) {
		$printedLength = 0;
		$position = 0;
		$tags = array();
		$out = '';
		while ($printedLength < $max_length && preg_match('{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}', $text, $match, PREG_OFFSET_CAPTURE, $position)) {
			list($tag, $tagPosition) = $match[0];
			// Print text leading up to the tag.
			$str = substr($text, $position, $tagPosition - $position);
			if ($printedLength + strlen($str) > $max_length) {
				$out .= substr($str, 0, $max_length - $printedLength);
				$printedLength = $max_length;
				break;
			}
			$out .= $str;
			$printedLength += strlen($str);
			if ($tag[0] == '&') {
				// Handle the entity.
				$out .= $tag;
				$printedLength++;
			}
			else {
				// Handle the tag.
				$tagName = $match[1][0];
				if ($tag[1] == '/')
				{
					// This is a closing tag.
					$openingTag = array_pop($tags);
					assert($openingTag == $tagName); // check that tags are properly nested.
					$out .= $tag;
				}
				else if ($tag[strlen($tag) - 2] == '/') {
					// Self-closing tag.
					$out .= $tag;
				}
				else {
					// Opening tag.
					$out .= $tag;
					$tags[] = $tagName;
				}
			}
			// Continue after the tag.
			$position = $tagPosition + strlen($tag);
		}
		// Print any remaining text.
		if ($printedLength < $max_length && $position < strlen($text)) {
			$out .= substr($text, $position, $max_length - $printedLength);
		}
		if ($max_length < strlen($text)) {
			$out .= "...";
		}
		// Close any open tags.
		while (!empty($tags)) {
			$out .= "</" . array_pop($tags) . ">";
		}
		return $out;
	}
}

