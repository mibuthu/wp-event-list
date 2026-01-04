<?php // phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
// TODO: Change filename to avoid phpcs warnings
?>
<?php
/**
 * The class to handle the event category functions
 *
 * TODO: Fix phan warnings to remove the suppressed checks
 *
 * @phan-file-suppress PhanPluginNoCommentOnPrivateProperty
 * @phan-file-suppress PhanPluginNoCommentOnPublicMethod
 * @phan-file-suppress PhanPluginNoCommentOnPrivateMethod
 * @phan-file-suppress PhanPluginUnknownPropertyType
 * @phan-file-suppress PhanPluginUnknownMethodParamType
 * @phan-file-suppress PhanPluginUnknownMethodReturnType
 * @phan-file-suppress PhanPossiblyUndeclaredProperty
 * @phan-file-suppress PhanPluginDuplicateConditionalNullCoalescing
 *
 * @package event-list
 */

// TODO: Fix phpcs warnings to remove the disabled checks
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log

if ( ! defined( 'WP_ADMIN' ) ) {
	exit;
}

require_once EL_PATH . 'includes/events_post_type.php';
require_once EL_PATH . 'includes/events.php';

/**
 * This class handles general functions which can be used on different admin pages
 */
class EL_Event_Category_Functions {

	private static $instance;

	/**
	 * The event post type
	 *
	 * @var EL_Events_Post_Type
	 */
	private $events_post_type;

	private $events;


	public static function &get_instance() {
		// Create class instance if required
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		// Return class instance
		return self::$instance;
	}


	private function __construct() {
		$this->events_post_type = &EL_Events_Post_Type::get_instance();
		$this->events           = &EL_Events::get_instance();
	}


	public function get_sync_affected_cats( $direction, $types = array( 'to_add', 'to_del', 'to_mod' ) ) {
		$affected_cats = array();
		if ( 'to_event_cats' === $direction ) {
			$this->register_event_category_taxonomy();
		}
		// prepare category slugs for comparison
		$post_cats       = get_categories(
			array(
				'type'       => 'post',
				'orderby'    => 'parent',
				'hide_empty' => 0,
			)
		);
		$event_cats      = $this->get_event_cats(
			array(
				'taxonomy'   => $this->events_post_type->event_cat_taxonomy,
				'orderby'    => 'parent',
				'hide_empty' => false,
			)
		);
		$post_cat_slugs  = wp_list_pluck( $post_cats, 'slug' );
		$event_cat_slugs = wp_list_pluck( $event_cats, 'slug' );
		if ( 'to_post_cats' === $direction ) {
			$source_cats = $event_cat_slugs;
			$target_cats = $post_cat_slugs;
		} else {
			$source_cats = $post_cat_slugs;
			$target_cats = $event_cat_slugs;
		}
		// to_add:
		if ( in_array( 'to_add', $types, true ) ) {
			$affected_cats['to_add'] = array_diff( $source_cats, $target_cats );
		}
		// to_del:
		if ( in_array( 'to_del', $types, true ) ) {
			$affected_cats['to_del'] = array_diff( $target_cats, $source_cats );
		}
		// to_mod:
		if ( in_array( 'to_mod', $types, true ) ) {
			$cat_intersect = array_intersect( $source_cats, $target_cats );
			// compare intersect elements and determine which categories require an update
			$affected_cats['to_mod'] = array();
			foreach ( $cat_intersect as $cat_slug ) {
				$post_cat  = get_category_by_slug( $cat_slug );
				$event_cat = $this->events->get_cat_by_slug( $cat_slug );
				if ( $post_cat->name !== $event_cat->name ||
					$post_cat->alias_of !== $event_cat->alias_of ||
					$post_cat->description !== $event_cat->description ) {
						$affected_cats['to_mod'][] = $cat_slug;
						continue;
				}
				// parent checking
				$post_cat_parent  = 0 === $post_cat->parent ? null : get_category( $post_cat->parent );
				$event_cat_parent = 0 === $event_cat->parent ? null : $this->events->get_cat_by_id( $event_cat->parent );

				/**
				 * Add to $affected_cats['to_mod'] when:
				 *   * one of the category is root (parent is_null) and the other is not
				 *   * both category parent exists (instanceof WP_Term) and parent slug of post and event category are different
				 */
				if ( ( is_null( $post_cat_parent ) xor is_null( $event_cat_parent ) ) ||
					( $post_cat_parent instanceof WP_Term && $event_cat_parent instanceof WP_Term && ( $post_cat_parent->slug !== $event_cat_parent->slug ) ) ) {
					$affected_cats['to_mod'][] = $cat_slug;
				}
			}
		}
		return $affected_cats;
	}


	public function sync_categories( $direction, $affected_cats ) {
		$ret = array();
		if ( 'to_event_cats' === $direction ) {
			$this->register_event_category_taxonomy();
		}

		// modify & add categories (same procedure for both types)
		foreach ( array( 'add', 'mod' ) as $type ) {
			if ( isset( $affected_cats[ 'to_' . $type ] ) ) {
				foreach ( $affected_cats[ 'to_' . $type ] as $cat_slug ) {
					$post_cat   = get_category_by_slug( $cat_slug );
					$event_cat  = $this->events->get_cat_by_slug( $cat_slug );
					$source_cat = 'to_event_cats' === $direction ? $post_cat : $event_cat;
					if ( empty( $source_cat ) ) {
						$ret[ $type . '_error' ][] = $cat_slug;
						continue;
					}
					$parent_source_cat = 'to_event_cats' === $direction ? get_category( $source_cat->parent ) : $this->events->get_cat_by_id( $source_cat->parent );
					if ( $parent_source_cat instanceof WP_Term ) {
						$parent_target_cat = 'to_event_cats' === $direction ? $this->events->get_cat_by_slug( $parent_source_cat->slug ) : get_category_by_slug( $parent_source_cat->slug );
					} else {
						$parent_target_cat = 0;
					}
					$parent_id = $parent_target_cat instanceof WP_Term ? $parent_target_cat->term_id : 0;
					$args      = array(
						'name'        => $source_cat->name,
						// availability check required for older WordPress versions
						'alias_of'    => isset( $source_cat->alias_of ) ? $source_cat->alias_of : '',
						'description' => $source_cat->description,
						'parent'      => $parent_id,
						'slug'        => $source_cat->slug,
					);
					// TODO: The following lines must be tested
					if ( 'add' === $type ) {
						$result = 'to_event_cats' === $direction ? $this->events->insert_category( $source_cat->name, $args ) : wp_insert_term( $source_cat->name, $this->events_post_type->post_cat_taxonomy, $args );
					} else {
						$result = 'to_event_cats' === $direction ? $this->events->update_category( $source_cat->slug, $args ) : wp_update_term( $post_cat->term_id, $this->events_post_type->post_cat_taxonomy, $args );
					}
					if ( $result instanceof WP_Error ) {
						$ret[ $type . '_error' ][] = $cat_slug;
					} else {
						$ret[ $type . '_ok' ][] = $cat_slug;
					}
				}
			}
		}

		// delete categories
		if ( isset( $affected_cats['to_del'] ) ) {
			foreach ( $affected_cats['to_del'] as $cat_slug ) {
				$result = 'to_event_cats' === $direction ? $this->events->delete_category( $cat_slug ) : wp_delete_category( get_category_by_slug( $cat_slug )->term_id );
				if ( $result instanceof WP_Error ) {
					$ret['del_error'][] = $cat_slug;
				} else {
					$ret['del_ok'][] = $cat_slug;
				}
			}
		}

		if ( 'to_event_cats' === $direction ) {
			$this->unregister_event_category_taxonomy();
		}
		return $ret;
	}


	/**
	 * Function to switch the event taxonomy (categories) from post to event or from event to post.
	 *
	 * Before the switch is done, the id of all categories are replace. The category comparison is done by the category slug.
	 * If a target category with the same slug is not available, the category will be removed from the event.
	 *
	 * @param string $direction  Defines the direction of the translation.
	 *                           Possible values are 'to_event_cats' and 'to_post_cats'.
	 */
	public function switch_event_taxonomy( $direction ) {
		global $wpdb;
		// get events
		$events = $this->events->get( array( 'status' => null ) );
		// preparations
		if ( 'to_event_cats' === $direction ) {
			$this->register_event_category_taxonomy();
			$source_taxonomy = $this->events_post_type->post_cat_taxonomy;
			$target_taxonomy = $this->events_post_type->event_cat_taxonomy;
			$use_post_cats   = '';
		} elseif ( 'to_post_cats' === $direction ) {
			$source_taxonomy = $this->events_post_type->event_cat_taxonomy;
			$target_taxonomy = $this->events_post_type->post_cat_taxonomy;
			$use_post_cats   = '1';
		} else {
			return new WP_Error( 'Wrong direction specified for translate_events_cats!' );
		}
		// Iterate over all events
		foreach ( $events as $event ) {
			// Iterate over all categories of the event
			foreach ( $event->categories as $source_cat ) {
				// Check if the source category slug is available in the target categories
				$target_cat = get_term_by( 'slug', $source_cat->slug, $target_taxonomy );
				if ( $target_cat instanceof WP_Term ) {
					// target category is available -> set new cat-id in db
					$result = $wpdb->update(
						$wpdb->term_relationships,
						array( 'term_taxonomy_id' => $target_cat->term_id ),
						array(
							'object_id'        => $event->post->ID,
							'term_taxonomy_id' => $source_cat->term_id,
						),
						array( '%d' ),
						array( '%d', '%d' )
					);
				} else {
					// target category is not available -> remove category from event
					wp_remove_object_terms( $event->post->ID, $source_cat->term_id, $source_taxonomy );
					error_log( 'Category "' . $source_cat->slug . '" removed from event "' . $event->post->post_name . '"' );
				}
			}
		}
		// Switch taxonomy -> change option value
		require_once EL_PATH . 'includes/options.php';
		EL_Options::get_instance()->set( 'el_use_post_cats', $use_post_cats );
	}


	/**
	 * Delete all event categories from the database.
	 *
	 * This function can be also called if the event taxonomy is not registered, because the terms are
	 * getting identified via a database request directly.
	 */
	public function delete_all_event_cats() {
		// get terms
		$terms = $this->get_event_cats_from_db();
		// delete terms
		foreach ( $terms as $term ) {
			wp_delete_term( $term->term_id, $this->events_post_type->event_cat_taxonomy );
		}
	}


	public function update_cat_count() {
		$event_cats    = $this->get_event_cats(
			array(
				'taxonomy'   => $this->events_post_type->taxonomy,
				'orderby'    => 'parent',
				'hide_empty' => false,
			)
		);
		$event_cat_ids = wp_list_pluck( $event_cats, 'term_id' );
		wp_update_term_count_now( $event_cat_ids, $this->events_post_type->taxonomy );
	}


	private function register_event_category_taxonomy() {
		$this->events_post_type->taxonomy = $this->events_post_type->event_cat_taxonomy;
		$this->events_post_type->register_event_category_taxonomy();
	}


	private function unregister_event_category_taxonomy() {
		$this->events_post_type->taxonomy = $this->events_post_type->post_cat_taxonomy;
		// @phan-suppress-next-line PhanUndeclaredFunction  The WordPress function unregister_taxonomy() exists
		unregister_taxonomy( $this->events_post_type->event_cat_taxonomy );
	}


	private function get_event_cats( $options ) {
		// fix for different get_terms function parameters in older WordPress versions
		if ( version_compare( get_bloginfo( 'version' ), '4.5' ) < 0 ) {
			// TODO: Remove support for older WordPress version
			// phpcs:ignore WordPress.WP.DeprecatedParameters.Get_termsParam2Found -- Workaround for old wordpress version
			return get_terms( $options['taxonomy'], $options );
		} else {
			return get_terms( $options );
		}
	}


	private function get_event_cats_from_db( $cat_slug = null ) {
		global $wpdb;
		$slug_text = empty( $cat_slug ) ? '' : ' AND slug = "' . $cat_slug . '"';
		$query     = 'SELECT *
			FROM ' . $wpdb->terms . ' AS t
			INNER JOIN ' . $wpdb->term_taxonomy . ' AS tt
			ON t.term_id = tt.term_id
			WHERE tt.taxonomy = "' . $this->events_post_type->event_cat_taxonomy . '"' . $slug_text . '
			ORDER BY parent';
		if ( empty( $cat_slug ) ) {
			return $wpdb->get_results( $query );
		} else {
			return $wpdb->get_row( $query );
		}
	}

}
