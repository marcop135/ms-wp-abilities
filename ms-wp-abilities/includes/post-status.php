<?php
/**
 * Which post statuses need the post type's publish_posts capability.
 *
 * create-post and apply-post-update hand their status to wp_insert_post() and
 * wp_update_post(), which check no capabilities. Their permission callbacks ask
 * only for edit_posts, so without this a contributor could publish. The rule
 * mirrors WP_REST_Posts_Controller::handle_status_param(): publish, future and
 * private need publish_posts; draft and pending do not.
 *
 * Kept free of WordPress calls so it can be unit-tested. See
 * tests/PostStatusTest.php.
 *
 * @package MS_WP_Abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether setting a post to this status needs the post type's publish_posts.
 *
 * @param mixed $status Requested post status.
 * @return bool
 */
function mswpa_status_requires_publish( $status ): bool {
	return in_array( $status, array( 'publish', 'future', 'private' ), true );
}
