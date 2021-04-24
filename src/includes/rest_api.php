<?php

require_once(EL_PATH.'includes/events.php');

add_action( 'rest_api_init', 'event_list_rest_api_init', 10, 0 );

function event_list_rest_api_init() {
  $namespace = 'event-list/v1';

  register_rest_route( $namespace, '/events', array(
    'methods' => 'GET',
    'callback' => 'event_list_rest_get_events',
    'args' => array(
      'start_date' => array(
        'required' => false,
        'validate_callback' => function($param, $request, $key) {
          return event_list_is_date( $param );
        }
      ),
      'end_date' => array(
        'required' => false,
        'validate_callback' => function($param, $request, $key) {
          return event_list_is_date( $param );
        }
      )
    )
  ) );
}

function event_list_rest_get_events( WP_REST_Request $request ) {
  $start_date = $request->get_param( 'start_date' );

  if ( null === $start_date ) {
    $start_date = '1970-01-01';
  }

  $end_date = $request->get_param( 'end_date' );
  if ( null === $end_date ) {
    $end_date = '2999-12-31';
  }

  $events = EL_Events::get_instance();
  $options = array(
    'order' => array( 'startdate ASC', 'starttime DESC' ),
    'date_filter' => $start_date . '~' . $end_date,
  );

  $results = $events->get($options);

  $response = array();

  foreach ( $results as $event ) {
    // sanity check we are only returning event list posts
    if ( $event->post->post_type !== 'el_events' ) {
      continue;
    }

    $response[] = array(
      'post' => array(
        'ID' => $event->post->ID,
      ),
      'categories' => $event->categories,
      'title' => $event->title,
      'startdate' => $event->startdate,
      'enddate' => $event->enddate,
      'starttime' => $event->startime,
      'location' => $event->location,
      'excerpt' => $event->excerpt,
      'content' => $event->content
    );
  }

  return $response;
}

function event_list_is_date( $date ) {
  $regex = '#^\d{4}-\d{2}-\d{2}$#';

  if ( ! preg_match( $regex, $date ) ) {
    return false;
  }

  return strtotime ( $date );
}

?>