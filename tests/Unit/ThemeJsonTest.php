<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for theme.json that run without WordPress.
 */
final class ThemeJsonTest extends TestCase {

	public function test_theme_json_is_valid_and_has_a_version(): void {
		$path = dirname( __DIR__, 2 ) . '/theme.json';
		$data = json_decode( (string) file_get_contents( $path ), true );

		$this->assertSame( JSON_ERROR_NONE, json_last_error() );
		$this->assertSame( 3, $data['version'] );
	}

	public function test_spacing_presets_are_defined_explicitly(): void {
		$path = dirname( __DIR__, 2 ) . '/theme.json';
		$data = json_decode( (string) file_get_contents( $path ), true );

		$slugs = array_column( $data['settings']['spacing']['spacingSizes'], 'slug' );

		// Every spacing preset used in templates/patterns must be defined here.
		foreach ( [ '40', '50', '60', '70' ] as $slug ) {
			$this->assertContains( $slug, $slugs, "spacing preset {$slug} is used but not defined" );
		}
	}

	public function test_links_keep_their_underline_on_hover(): void {
		$path = dirname( __DIR__, 2 ) . '/theme.json';
		$data = json_decode( (string) file_get_contents( $path ), true );

		$this->assertArrayNotHasKey(
			':hover',
			$data['styles']['elements']['link'] ?? [],
			'Link hover must not remove the underline (WCAG 1.4.1).'
		);
	}
}
