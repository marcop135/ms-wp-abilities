<?php
/**
 * Tests for the publish_posts check on create-post and apply-post-update.
 *
 * @package MS_WP_Abilities
 */

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers mswpa_status_requires_publish() and its use by the status-setting abilities.
 */
class PostStatusTest extends TestCase {

	/**
	 * Statuses that need publish_posts, as in WP_REST_Posts_Controller::handle_status_param().
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function publishingStatuses(): array {
		return array(
			'publish' => array( 'publish' ),
			'future'  => array( 'future' ),
			'private' => array( 'private' ),
		);
	}

	/**
	 * Statuses any user who can edit posts may set.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function nonPublishingStatuses(): array {
		return array(
			'draft'   => array( 'draft' ),
			'pending' => array( 'pending' ),
			'null'    => array( null ),
			'empty'   => array( '' ),
		);
	}

	/**
	 * Publishing statuses need the capability.
	 *
	 * @param string $status Status.
	 * @return void
	 */
	#[DataProvider( 'publishingStatuses' )]
	public function testPublishingStatusesRequirePublish( string $status ): void {
		$this->assertTrue( mswpa_status_requires_publish( $status ) );
	}

	/**
	 * Draft and pending do not.
	 *
	 * @param mixed $status Status.
	 * @return void
	 */
	#[DataProvider( 'nonPublishingStatuses' )]
	public function testNonPublishingStatusesDoNot( $status ): void {
		$this->assertFalse( mswpa_status_requires_publish( $status ) );
	}

	/**
	 * Matching is exact, so a variant spelling cannot slip past the check.
	 *
	 * @return void
	 */
	public function testMatchingIsStrict(): void {
		$this->assertFalse( mswpa_status_requires_publish( 'Publish' ) );
		$this->assertFalse( mswpa_status_requires_publish( array( 'publish' ) ) );
	}

	/**
	 * Every ability that writes a post status checks the capability first.
	 *
	 * wp_insert_post() and wp_update_post() check no capabilities, so an ability
	 * that passes a status through without this check lets edit_posts publish.
	 *
	 * @return void
	 */
	public function testStatusSettingAbilitiesCheckPublish(): void {
		$source = (string) file_get_contents( __DIR__ . '/../ms-wp-abilities/ms-wp-abilities.php' );
		$chunks = explode( 'wp_register_ability(', $source );
		array_shift( $chunks );

		$setters = array();
		foreach ( $chunks as $chunk ) {
			if ( ! preg_match( "/^\s*'(miriamschwab\/[a-z0-9-]+)'/", $chunk, $name ) ) {
				continue;
			}
			if ( ! preg_match( '/\'post_status\'\s*=>\s*\$status\b|\$args\[\'post_status\'\]\s*=/', $chunk ) ) {
				continue;
			}
			$setters[] = $name[1];
			$this->assertStringContainsString(
				'mswpa_status_requires_publish(',
				$chunk,
				"{$name[1]} sets a post status without checking publish_posts."
			);
		}

		sort( $setters );
		$this->assertSame( array( 'miriamschwab/apply-post-update', 'miriamschwab/create-post' ), $setters );
	}
}
