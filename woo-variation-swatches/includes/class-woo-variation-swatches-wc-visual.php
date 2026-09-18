<?php

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! class_exists( 'Woo_Variation_Swatches_WC_Visual' ) ) {
	class Woo_Variation_Swatches_WC_Visual {

		/**
		 * Color visual type.
		 */
		public string $type_color = 'color';

		/**
		 * Image visual type.
		 */
		public string  $type_image = 'image';

		/**
		 * Empty visual type.
		 */
		public string  $type_none = 'none';

		public string  $attribute_type = 'wc-visual';

		public string $type_color_key           = 'product_attribute_color';
		public string $type_image_key           = 'product_attribute_image';

		public static function instance(): self {

			static $instance = null;

			if ( is_null( $instance ) ) {
				$instance = new self();
			}

			return $instance;
		}

		public function is_enable(): bool {
			return wp_is_block_theme() && class_exists( FeaturesUtil::class ) && FeaturesUtil::feature_is_enabled( 'wc-visual-attribute' );
		}

		public function get_attribute_type(): string {
			return $this->attribute_type;
		}

		/**
		 * Get an empty visual term value.
		 *
		 * @return array{type: string, value: string}
		 *
		 * @since 10.9.0
		 */
		public function get_empty_visual(): array {
			return array(
				'type'  => $this->type_none,
				'value' => '',
			);
		}

		/**
		 * Get the normalized visual value for a term.
		 *
		 * @param int    $term_id Term ID.
		 * @param string $image_size Image size for image visual URLs.
		 * @return array{type: string, value: string}
		 *
		 * @since 10.9.0
		 */
		public function get_term_visual( int $term_id, string $image_size = 'thumbnail' ): array {
			return $this->build_term_visual( $term_id, $image_size );
		}

		/**
		 * Get the normalized visual value for a term.
		 *
		 * @param int    $term_id Term ID.
		 * @param string $image_size Image size for image visual URLs.
		 * @return array{type: string, value: string}
		 *
		 * @since 10.9.0
		 */
		public function get_swatches_term_visual( int $term_id, string $image_size = 'thumbnail' ): array {
			return $this->build_swatches_term_visual( $term_id, $image_size );
		}

		/**
		 * Get normalized visual values for the given terms.
		 *
		 * @param array  $term_ids Term IDs.
		 * @param string $image_size Image size for image visual URLs.
		 * @return array<int, array{type: string, value: string}> Map of term ID to visual values.
		 *
		 * @since 10.9.0
		 */
		public function get_term_visuals( array $term_ids, string $image_size = 'thumbnail' ): array {
			$visuals  = array();
			$term_ids = $this->prime_term_visual_caches( $term_ids );

			foreach ( $term_ids as $term_id ) {
				$visuals[ $term_id ] = $this->build_term_visual( $term_id, $image_size );
			}

			return $visuals;
		}

		/**
		 * Get normalized visual values for the given terms.
		 *
		 * @param array  $term_ids Term IDs.
		 * @param string $image_size Image size for image visual URLs.
		 * @return array<int, array{type: string, value: string}> Map of term ID to visual values.
		 *
		 * @since 10.9.0
		 */
		public function get_swatches_term_visuals( array $term_ids, string $image_size = 'thumbnail' ): array {
			$visuals  = array();
			// $term_ids = $this->prime_swatches_term_visual_caches( $term_ids );

			foreach ( $term_ids as $term_id ) {
				$visuals[ $term_id ] = $this->build_swatches_term_visual( $term_id, $image_size );
			}

			return $visuals;
		}

		/**
		 * Prime caches needed to build visual values for terms.
		 *
		 * @param array $term_ids Term IDs.
		 * @return array<int> Normalized term IDs.
		 *
		 * @since 10.9.0
		 */
		public function prime_term_visual_caches( array $term_ids ): array {
			$term_ids = array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );

			if ( empty( $term_ids ) ) {
				return array();
			}

			update_meta_cache( 'term', $term_ids );

			$image_ids = array();
			foreach ( $term_ids as $term_id ) {
				$image_id = absint( get_term_meta( $term_id, 'image', true ) );

				if ( $image_id ) {
					$image_ids[] = $image_id;
				}
			}

			$image_ids = array_values( array_unique( $image_ids ) );
			if ( ! empty( $image_ids ) ) {
				_prime_post_caches( $image_ids, false, true );
			}

			return $term_ids;
		}

		/**
		 * Prime caches needed to build visual values for terms.
		 *
		 * @param array $term_ids Term IDs.
		 * @return array<int> Normalized term IDs.
		 *
		 * @since 10.9.0
		 */
		public function prime_swatches_term_visual_caches( array $term_ids ): array {
			$term_ids = array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );

			if ( empty( $term_ids ) ) {
				return array();
			}

			update_meta_cache( 'term', $term_ids );

			$image_ids = array();
			foreach ( $term_ids as $term_id ) {
				$image_id = absint( get_term_meta( $term_id, $this->type_image_key, true ) );

				if ( $image_id ) {
					$image_ids[] = $image_id;
				}
			}

			$image_ids = array_values( array_unique( $image_ids ) );
			if ( ! empty( $image_ids ) ) {
				_prime_post_caches( $image_ids, false, true );
			}

			return $term_ids;
		}

		/**
		 * Build a normalized visual value for a term.
		 *
		 * @param int    $term_id Term ID.
		 * @param string $image_size Image size for image visual URLs.
		 * @return array{type: string, value: string}
		 */
		public function build_term_visual( int $term_id, string $image_size ): array {
			$image_id = absint( get_term_meta( $term_id, 'image', true ) );

			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				// $image_url = wp_get_attachment_image_url( $image_id, $image_size );
				$image_src = wp_get_attachment_image_src( $image_id, $image_size );

				if ( isset( $image_src[0] ) ) {
					return array(
						'type'  => $this->type_image,
						'value' => $image_src[0],
						'attrs'=>array(
							'width'=>$image_src[1],
							'height'=>$image_src[2],
						)
					);
				}
			}

			$color = sanitize_hex_color( get_term_meta( $term_id, 'color', true ) );

			if ( $color ) {
				return array(
					'type'  => $this->type_color,
					'value' => $color,
				);
			}

			return $this->get_empty_visual();
		}

		/**
		 * Build a normalized visual value for a term.
		 *
		 * @param int    $term_id Term ID.
		 * @param string $image_size Image size for image visual URLs.
		 * @return array{type: string, value: string}
		 */
		public function build_swatches_term_visual( int $term_id, string $image_size ): array {

			$image_id = absint( get_term_meta( $term_id, $this->type_image_key, true ) );

			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				 $image_url = wp_get_attachment_image_url( $image_id, $image_size );
				return array(
					'type'  => $this->type_image,
					'value' => $image_url
				);

			}

			$color = sanitize_hex_color( get_term_meta( $term_id, $this->type_color_key, true ) );

			if ( $color ) {
				return array(
					'type'  => $this->type_color,
					'value' => $color,
				);
			}

			return $this->get_empty_visual();
		}

		/**
		 * Check whether a taxonomy is a wc-visual product attribute taxonomy.
		 *
		 * @param string $taxonomy Taxonomy name.
		 * @return bool
		 *
		 * @since 10.9.0
		 */
		public function is_swatches_attribute_taxonomy( string $taxonomy ): bool {
			static $visual_attribute_taxonomies = array();
			static $cache_prefix                = '';

			$current_cache_prefix = \WC_Cache_Helper::get_cache_prefix( 'woocommerce-attributes' );
			if ( $cache_prefix !== $current_cache_prefix ) {
				$cache_prefix                = $current_cache_prefix;
				$visual_attribute_taxonomies = array();

				foreach ( wc_get_attribute_taxonomies() as $attribute ) {

					// From Woo Variation Swatches.
					$combined_visual = array( 'color', 'image' );

					$is_visual_attribute = in_array( $attribute->attribute_type, $combined_visual, true );

					if ( $is_visual_attribute ) {
						$visual_attribute_taxonomies[ wc_attribute_taxonomy_name( $attribute->attribute_name ) ] = true;
					}
				}
			}

			return isset( $visual_attribute_taxonomies[ $taxonomy ] );
		}

		/**
		 * Build an inline swatch style from a normalized visual value.
		 *
		 * @param array{type?: string, value?: string} $visual Normalized visual value.
		 * @return string
		 *
		 * @since 10.9.0
		 */
		public function get_swatch_style( array $visual ): string {
			$type  = isset( $visual['type'] ) ? (string) $visual['type'] : $this->type_none;
			$value = isset( $visual['value'] ) ? (string) $visual['value'] : '';

			if ( $this->type_image === $type ) {
				$image = esc_url_raw( $value );

				if ( $image ) {
					return sprintf( "background-image:url('%s')", str_replace( "'", '%27', $image ) );
				}
			}

			if (  $this->type_color === $type ) {
				$color = sanitize_hex_color( $value );

				if ( $color ) {
					return sprintf( 'background-color:%s', $color );
				}
			}

			return '';
		}

		public function is_wc_visual_attribute( $attribute ): bool {
			if ( ! is_object( $attribute ) ) {
				return false;
			}

			return $this->get_attribute_type() === $attribute->attribute_type;
		}
	}
}
