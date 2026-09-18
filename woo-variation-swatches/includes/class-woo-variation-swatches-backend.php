<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woo_Variation_Swatches_Backend' ) ) {
	class Woo_Variation_Swatches_Backend {

		public string $type_color_key           = 'product_attribute_color';
		public string $type_image_key           = 'product_attribute_image';
		public string $type_dual_color_key      = 'is_dual_color';
		public string $type_secondary_color_key = 'secondary_color';

		protected static $instance = null;

		protected $admin_menu;

		protected function __construct() {
			$this->includes();
			$this->hooks();
			$this->init();
			do_action( 'woo_variation_swatches_backend_loaded', $this );
		}

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		protected function includes() {
			require_once __DIR__ . '/class-woo-variation-swatches-term-meta.php';
			require_once __DIR__ . '/class-woo-variation-swatches-export-import.php';

			require_once __DIR__ . '/getwooplugins/class-getwooplugins-plugin-deactivate-feedback.php';
			require_once __DIR__ . '/getwooplugins/class-getwooplugins-admin-menus.php';

			require_once __DIR__ . '/class-woo-variation-swatches-deactivate-feedback.php';
			require_once __DIR__ . '/class-woo-variation-swatches-product-edit-panel.php';

			require_once __DIR__ . '/class-woo-variation-swatches-wc-api-response.php';

			require_once __DIR__ . '/class-woo-variation-swatches-color-api.php';
		}

		protected function hooks() {
			add_filter( 'getwooplugins_get_settings_pages', array( $this, 'init_settings' ) );
			add_filter( 'product_attributes_type_selector', array( $this, 'attribute_types' ) );

			add_action( 'admin_init', array( $this, 'add_attribute_meta' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'woocommerce_product_option_terms', array( $this, 'product_option_terms' ), 10, 3 );

			add_filter( 'plugin_action_links_' . plugin_basename( WOO_VARIATION_SWATCHES_PLUGIN_FILE ), array(
				$this,
				'plugin_action_links',
			) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'activate_redirect' ) );


			add_filter( 'woocommerce_json_search_found_product_attribute_terms', array( $this, 'add_visual_data_to_attribute_terms' ), 10, 2 );

			// add_action('woocommerce_attribute_updated', array( $this, 'clear_attribute_transient_on_update' ), 10, 2 );
			// add_action('woocommerce_attribute_deleted', array( $this, 'clear_attribute_transient_on_delete' ), 10, 3 );
		}

		public function activate_redirect() {
			if ( wc_string_to_bool( get_option( 'woo_variation_swatches_do_activate_redirect', 'no' ) ) && ! woo_variation_swatches()->is_pro() ) {
				delete_option( 'woo_variation_swatches_do_activate_redirect' );

				wp_redirect( $this->get_admin_menu()->get_settings_link( 'woo_variation_swatches', 'tutorial' ) );
				exit;
			}
		}

		public function clear_attribute_transient_on_update( $attribute_id, $data ) {
			// Clear transient by taxonomy_id
			$transient_key = woo_variation_swatches()->get_cache()->get_cache_key( sprintf( 'woo_variation_swatches_cache_attribute_taxonomy_id__%s', $attribute_id ) );
			delete_transient( $transient_key );

			// Clear transient by taxonomy_name with pa_ prefixed.
			$attribute_name = wc_attribute_taxonomy_name( $data['attribute_name'] );
			$transient_key  = woo_variation_swatches()->get_cache()->get_cache_key( sprintf( 'woo_variation_swatches_cache_attribute_taxonomy__%s', $attribute_name ) );

			delete_transient( $transient_key );
		}

		public function clear_attribute_transient_on_delete( $attribute_id, $name, $taxonomy ) {
			// Clear transient by taxonomy_id
			$transient_key = woo_variation_swatches()->get_cache()->get_cache_key( sprintf( 'woo_variation_swatches_cache_attribute_taxonomy_id__%s', $attribute_id ) );
			delete_transient( $transient_key );

			// Clear transient by taxonomy_name with pa_ prefixed.
			$transient_key = woo_variation_swatches()->get_cache()->get_cache_key( sprintf( 'woo_variation_swatches_cache_attribute_taxonomy__%s', $taxonomy ) );
			delete_transient( $transient_key );
		}

		protected function init() {
			$this->get_admin_menu();
			$this->get_deactivate_feedback();
			$this->get_export_import();
			$this->get_edit_panel();
			$this->wc_api_response();
			$this->color_api();
		}

		// Start

		public function color_api() {
			return Woo_Variation_Swatches_Color_API::instance();
		}

		public function wc_api_response() {
			return Woo_Variation_Swatches_WC_API_Response::instance();
		}

		public function get_edit_panel() {
			return Woo_Variation_Swatches_Product_Edit_Panel::instance();
		}

		public function get_admin_menu() {
			return GetWooPlugins_Admin_Menus::instance();
		}

		public function get_deactivate_feedback() {
			return Woo_Variation_Swatches_Deactivate_Feedback::instance();
		}

		public function get_export_import() {
			return Woo_Variation_Swatches_Export_Import::instance();
		}

		public function plugin_row_meta( $links, $file ) {
			if ( plugin_basename( WOO_VARIATION_SWATCHES_PLUGIN_FILE ) !== $file ) {
				return $links;
			}

			$row_meta = apply_filters( 'woo_variation_swatches_plugin_row_meta', array(
				'docs'    => '<a target="_blank" href="' . esc_url( 'https://getwooplugins.com/documentation/woocommerce-variation-swatches/' ) . '" aria-label="' . esc_attr__( 'View documentation', 'woo-variation-swatches' ) . '">' . esc_html__( 'Documentation', 'woo-variation-swatches' ) . '</a>',
				'videos'  => '<a target="_blank" href="' . esc_url( 'https://www.youtube.com/channel/UC6F21JXiLUPO7sm-AYlA3Ig/videos' ) . '" aria-label="' . esc_attr__( 'Video Tutorials', 'woo-variation-swatches' ) . '">' . esc_html__( 'Video Tutorials', 'woo-variation-swatches' ) . '</a>',
				'support' => '<a target="_blank" href="' . esc_url( 'https://getwooplugins.com/tickets/' ) . '" aria-label="' . esc_attr__( 'Help & Support', 'woo-variation-swatches' ) . '">' . esc_html__( 'Help & Support', 'woo-variation-swatches' ) . '</a>',
			) );

			return array_merge( $links, $row_meta );
		}

		public function plugin_action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . esc_url( $this->get_admin_menu()->get_settings_link( 'woo_variation_swatches' ) ) . '" aria-label="' . esc_attr__( 'View Swatches settings', 'woo-variation-swatches' ) . '">' . esc_html__( 'Settings', 'woo-variation-swatches' ) . '</a>',
			);


			$pro_links = array(
				'gwp-go-pro-action-link' => '<a target="_blank" href="' . esc_url( $this->get_pro_link() ) . '" aria-label="' . esc_attr__( 'Go Pro', 'woo-variation-swatches' ) . '">' . esc_html__( 'Go Pro', 'woo-variation-swatches' ) . '</a>',
			);

			if ( woo_variation_swatches()->is_pro() ) {
				$pro_links = array();
			}

			return array_merge( $action_links, $links, $pro_links );
		}

		// Visual
		public function add_visual_data_to_attribute_terms( $terms, $taxonomy ) {

			$is_swatches_attribute_taxonomy = woo_variation_swatches()->get_wc_visual()->is_swatches_attribute_taxonomy( $taxonomy );

			if ( is_wp_error( $terms ) || empty( $terms ) || ! $is_swatches_attribute_taxonomy ) {
				return $terms;
			}

			// Because `$terms` might be filtered by a plugin, make sure we only operate on valid terms.
			$valid_terms = array_filter(
				$terms,
				static function ( $term ) {
					return is_object( $term ) && isset( $term->term_id );
				}
			);

			$term_ids = array_map( 'intval', wp_list_pluck( $valid_terms, 'term_id' ) );
			$visuals  = woo_variation_swatches()->get_wc_visual()->get_swatches_term_visuals( $term_ids );

			foreach ( $valid_terms as $term ) {
				/**
				 * Term object with dynamic visual property.
				 *
				 * @var \stdClass $term
				 */
				$term->visual = $visuals[ $term->term_id ] ?? woo_variation_swatches()->get_wc_visual()->get_empty_visual();
			}

			return $terms;
		}

		// @see: file woocommerce/includes/admin/meta-boxes/views/html-product-attribute-inner.php
		// @see: file woocommerce/includes/admin/meta-boxes/views/html-product-attribute.php
		public function product_option_terms( $attribute_taxonomy, $i, $attribute ): void {
			// @see: wc_get_attribute_types
			$wc_default_attribute_types = array( 'select', 'wc-visual' );
			$is_in_swatches_type        = array_key_exists( $attribute_taxonomy->attribute_type, $this->attribute_types() );
			$is_in_native_type          = in_array( $attribute_taxonomy->attribute_type, $wc_default_attribute_types, true );


			if ( ! $is_in_native_type && $is_in_swatches_type ) {
				$is_visual_attribute = in_array( $attribute_taxonomy->attribute_type, array( 'color', 'image' ) );

				$attribute_orderby = ! empty( $attribute_taxonomy->attribute_orderby ) ? $attribute_taxonomy->attribute_orderby : 'name';
				/**
				 * Filter the length (number of terms) rendered in the list.
				 *
				 * @param int $term_limit The maximum number of terms to display in the list.
				 *
				 * @since 8.8.0
				 */
				$term_limit = absint( apply_filters( 'woocommerce_admin_terms_metabox_datalimit', 50 ) );
				?>

				<select multiple="multiple"
						data-minimum_input_length="0"
						data-limit="<?php
						echo esc_attr( $term_limit ); ?>" data-return_id="id"
						data-placeholder="<?php
						esc_attr_e( 'Select values', 'woo-variation-swatches' ); ?>"
						data-orderby="<?php
						echo esc_attr( $attribute_orderby ); ?>"
						class="multiselect attribute_values wc-taxonomy-term-search"
						name="attribute_values[<?php
						echo esc_attr( $i ); ?>][]"
						data-taxonomy="<?php
						echo esc_attr( $attribute->get_taxonomy() ); ?>"
						data-is-visual-attribute="<?php
				        echo esc_attr( wc_bool_to_string( $is_visual_attribute ) ); ?>">
					<?php
					$selected_terms = $attribute->get_terms();
					$term_visuals   = array();

					if ( $selected_terms && $is_visual_attribute ) {
						$term_ids = wp_list_pluck( $selected_terms, 'term_id' );

						$term_visuals = woo_variation_swatches()->get_wc_visual()->get_swatches_term_visuals( $term_ids );
					}

					if ( $selected_terms ) {
						foreach ( $selected_terms as $selected_term ) {
							$option_attributes = array(
								'value'    => $selected_term->term_id,
								'selected' => 'selected',
							);

							if ( $is_visual_attribute ) {
								$term_visual_data = $term_visuals[ $selected_term->term_id ] ?? woo_variation_swatches()->get_wc_visual()->get_empty_visual();

								$option_attributes['data-visual'] = wp_json_encode( $term_visual_data );
							}

							/**
							 * Filter the selected attribute term name.
							 *
							 * @param string $name Name of selected term.
							 * @param array  $term The selected term object.
							 *
							 * @since 3.4.0
							 */
							echo '<option ' . wc_implode_html_attributes( $option_attributes ) . '>' . esc_html( apply_filters( 'woocommerce_product_attribute_term_name', $selected_term->name, $selected_term ) ) . '</option>';
						}
					}
					?>
				</select>
				<button class="button plus select_all_attributes"><?php
					esc_html_e( 'Select all', 'woo-variation-swatches' ); ?></button>
				<button class="button minus select_no_attributes"><?php
					esc_html_e( 'Select none', 'woo-variation-swatches' ); ?></button>
				<button class="button fr plus add_new_attribute"><?php
					// @TODO: Create Dialog for Color, Image on Create value button click.
					esc_html_e( 'Create value', 'woo-variation-swatches' ); ?></button>

				<?php
			}
		}

		public function admin_scripts(): void {
			$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'woo-variation-swatches-admin', woo_variation_swatches()->assets_url( "/css/admin{$suffix}.css" ), array(), woo_variation_swatches()->version() );

			$prefix_wc_handle     = version_compare( WC()->version, '10.3', '>=' ) ? 'wc-' : '';
			$serializejson_handle = sprintf( '%sserializejson', $prefix_wc_handle );

			if ( 'product' === $screen_id ) {
				wp_deregister_script( $serializejson_handle );
				wp_register_script( $serializejson_handle, woo_variation_swatches()->assets_url( "/js/jquery.serializejson{$suffix}.js" ), array( 'jquery' ), '3.2.1', true );
			}


			wp_enqueue_script( 'wp-color-picker-alpha', woo_variation_swatches()->assets_url( "/js/wp-color-picker-alpha{$suffix}.js" ), array(
				'jquery',
				'wp-color-picker',
			), woo_variation_swatches()->version(), true );

			wp_enqueue_script( 'gwp-form-field-dependency', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/getwooplugins/js/getwooplugins-form-field-dependency.js', array( 'jquery' ), filemtime( untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/getwooplugins/js/getwooplugins-form-field-dependency.js' ), true );

			wp_enqueue_script( 'woo-variation-swatches-admin', woo_variation_swatches()->assets_url( "/js/admin{$suffix}.js" ), array(
				'jquery',
				'wp-color-picker-alpha',
				'wc-enhanced-select',
				'wp-api-fetch',
				'wp-url',
				$serializejson_handle,
			), woo_variation_swatches()->version(), true );


			wp_localize_script( 'woo-variation-swatches-admin', 'woo_variation_swatches_admin', array(
				'media_title'          => esc_html__( 'Choose an Image', 'woo-variation-swatches' ),
				'dialog_title'         => esc_html__( 'Add Attribute', 'woo-variation-swatches' ),
				'dialog_save'          => esc_html__( 'Add', 'woo-variation-swatches' ),
				'dialog_cancel'        => esc_html__( 'Cancel', 'woo-variation-swatches' ),
				'button_title'         => esc_html__( 'Use Image', 'woo-variation-swatches' ),
				'add_media'            => esc_html__( 'Add Media', 'woo-variation-swatches' ),
				'ajaxurl'              => esc_url( admin_url( 'admin-ajax.php', 'relative' ) ),
				'is_color_api_enabled' => sanitize_text_field( woo_variation_swatches()->get_option( 'enable_color_api', 'no' ) ),
				'wc_ajax_url'          => WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'settings_url'         => esc_url( $this->get_admin_menu()->get_settings_link( 'woo_variation_swatches' ) ),
				'settings_title'       => esc_html__( 'Variation Swatches Settings', 'woo-variation-swatches' ),
				'_wpnonce'             => wp_create_nonce( 'woo_variation_swatches' ),
				'reset_notice'         => esc_html__( 'Are you sure you want to reset it to default setting?', 'woo-variation-swatches' ),
				'nav_warning'          => esc_html__( 'Please save changed first.', 'woo-variation-swatches' ),
			) );
		}

		public function attribute_types(): array {
			$attribute_types = array(
				'select' => esc_html__( 'Select', 'woo-variation-swatches' ),
				'color'  => esc_html__( 'Color', 'woo-variation-swatches' ),
				'image'  => esc_html__( 'Image', 'woo-variation-swatches' ),
				'button' => esc_html__( 'Button', 'woo-variation-swatches' ),
				'radio'  => esc_html__( 'Radio', 'woo-variation-swatches' ),
			);

			$allow_visual_attribute_type = woo_variation_swatches()->get_wc_visual()->is_enable();
			$wc_visual_attribute_type    = woo_variation_swatches()->get_wc_visual()->get_attribute_type();

			// If the store already has some visual attributes, let's allow them even
			// if the current theme is not a block theme.
			if ( ! $allow_visual_attribute_type ) {
				foreach ( wc_get_attribute_taxonomies() as $attribute_taxonomy ) {
					if ( isset( $attribute_taxonomy->attribute_type ) && $wc_visual_attribute_type === $attribute_taxonomy->attribute_type ) {
						$allow_visual_attribute_type = true;
						break;
					}
				}
			}

			if ( $allow_visual_attribute_type ) {
				$attribute_types[ $wc_visual_attribute_type ] = esc_html__( 'Color / image', 'woo-variation-swatches' );
			}

			return $attribute_types;
		}

		public function extended_attribute_types(): array {
			$attribute_types = $this->attribute_types();

			// $attribute_types[ 'custom' ] = esc_html__( 'Custom', 'woo-variation-swatches' );
			$attribute_types['mixed'] = esc_html__( 'Mixed', 'woo-variation-swatches' );

			return $attribute_types;
		}

		public function filtered_attribute_types() {
			$attribute_types = $this->attribute_types();
			unset( $attribute_types['select'], $attribute_types['radio'] );

			return $attribute_types;
		}

		public function load_settings() {
			include_once dirname( __FILE__ ) . '/class-woo-variation-swatches-settings.php';

			return new Woo_Variation_Swatches_Settings();
		}

		public function init_settings( $settings ) {
			$settings[] = $this->load_settings();

			return $settings;
		}

		public function add_attribute_meta() {
			$fields               = $this->attribute_meta_fields();
			$attribute_taxonomies = wc_get_attribute_taxonomies();

			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $taxonomy ) {
					$attribute_name = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
					$attribute_type = $taxonomy->attribute_type;
					if ( array_key_exists( $attribute_type, $fields ) ) {
						new Woo_Variation_Swatches_Term_Meta( $attribute_name, 'product', $fields[ $attribute_type ] );
					}
				}
			}
		}

		public function attribute_meta_fields() {
			$fields = array();

			$fields['color'] = array(
				array(
					'label' => esc_html__( 'Color', 'woo-variation-swatches' ), // <label>
					'desc'  => esc_html__( 'Choose a color', 'woo-variation-swatches' ), // description
					'id'    => $this->type_color_key, // name of field
					'type'  => 'color',
				),
			);

			$fields['image'] = array(
				array(
					'label' => esc_html__( 'Image', 'woo-variation-swatches' ), // <label>
					'desc'  => esc_html__( 'Choose an Image', 'woo-variation-swatches' ), // description
					'id'    => $this->type_image_key, // name of field
					'type'  => 'image',
				),
			);

			return $fields;
		}

		public function get_attribute_taxonomy( $attribute_name ) {
			$taxonomy_attributes = wc_get_attribute_taxonomies();

			// $attribute_name = str_ireplace( 'pa_', '', wc_sanitize_taxonomy_name( $attribute_name ) );
			if ( 'pa_' === substr( $attribute_name, 0, 3 ) ) {
				$attribute_name = str_replace( 'pa_', '', wc_sanitize_taxonomy_name( $attribute_name ) );
			}

			foreach ( $taxonomy_attributes as $attribute ) {
				if ( $attribute->attribute_name !== $attribute_name ) {
					continue;
				}

				return $attribute;
			}

			return false;
		}

		public function get_pro_link(): string {
			$affiliate_id = apply_filters( 'gwp_affiliate_id', 0 );

			$link_args = array();

			if ( ! empty( $affiliate_id ) ) {
				$link_args['ref'] = esc_html( $affiliate_id );
			}

			$link_args = apply_filters( 'woo_variation_swatches_get_pro_link_args', $link_args );

			return add_query_arg( $link_args, 'https://getwooplugins.com/plugins/woocommerce-variation-swatches/' );
		}
	}
}
