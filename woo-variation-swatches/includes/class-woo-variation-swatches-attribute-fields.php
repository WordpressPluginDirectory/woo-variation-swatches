<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Woo_Variation_Swatches_Attribute_Fields' ) ) :
	class Woo_Variation_Swatches_Attribute_Fields {

	    private string $option = 'woo_variation_swatches_attribute';

		protected function __construct() {
			$this->hooks();
			do_action( 'woo_variation_swatches_attribute_fields_loaded', $this );
		}

		public static function instance(): self {

			static $instance = null;

			if ( is_null( $instance ) ) {
				$instance = new self();
			}

			return $instance;
		}

		protected function hooks(): void {
			add_action('woocommerce_after_add_attribute_fields', array($this, 'add_attribute_fields'));
			add_action('woocommerce_after_edit_attribute_fields', array($this, 'edit_attribute_fields'));
			add_action( 'woocommerce_attribute_added', array( $this, 'save_attribute_fields' ) );
			add_action( 'woocommerce_attribute_updated', array( $this, 'save_attribute_fields' ) );
			add_action( 'woocommerce_attribute_deleted', array( $this, 'delete_attribute_fields' ) );
		}

		public function get_settings_link(): string {
			return woo_variation_swatches()->get_backend()->get_admin_menu()->get_settings_link( 'woo_variation_swatches' );
		}

		public function get_option_name($attribute_id): string {
			return sprintf( '%s__%s', $this->option, $attribute_id );
		}

		public function get_options($attribute_id, $default = array()) {
			$name = $this->get_option_name($attribute_id);

			return get_option( $name, $default );
		}

		public function get_option($attribute_id, $key, $default = null) {

			$options = $this->get_options($attribute_id, array(
				$key=>$default,
			));

			return $options[ $key ] ?? $default;
		}

		public function save_attribute_fields($attribute_id): void {

			if(  isset( $_POST[$this->option] ) ){ //phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce is already verified by woocommerce

				$data = map_deep( $_POST[$this->option], 'sanitize_text_field' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce is already verified by woocommerce

				$data['attribute_id'] = $attribute_id;

				if( 'global' === $data['shape_style'] ){
					unset( $data['shape_style']);
				}

				update_option( $this->get_option_name($attribute_id), $data );
			}
		}

		public function field_name($key): string {
			return sprintf( '%s[%s]', $this->option, $key );
		}

		public function delete_attribute_fields ($attribute_id): void {
			delete_option( $this->get_option_name($attribute_id)  );
		}

		public function get_dependency(): array {
			return array(
				array(
					'#attribute_type'=>array(
						'type'=>'equal',
						'value'=>array('select', 'color', 'image', 'wc-visual'),
					)
				)
			);
		}

		public function add_attribute_fields(): void {
			?>
			<div data-gwp_dependency="<?php echo esc_attr( wc_esc_json(  wp_json_encode( $this->get_dependency()) ) ) ?>" class="form-field">
			<label for="attribute_shape_style"><?php esc_html_e( 'Shape Style', 'woo-variation-swatches' ); ?></label>
			<select name="<?php echo esc_attr( $this->field_name('shape_style') ) ?>" id="attribute_shape_style">
				<option value="global"><?php esc_html_e( 'Global', 'woo-variation-swatches' ); ?></option>
				<option value="rounded"><?php esc_html_e( 'Rounded', 'woo-variation-swatches' ); ?></option>
				<option value="squared"><?php esc_html_e( 'Squared', 'woo-variation-swatches' ); ?></option>
			</select>
			<p class="description">

				<?php esc_html_e( 'Attribute wise shape style.', 'woo-variation-swatches' ); ?>
				<?php printf( '<a target="_blank" href="%s">%s</a>',  esc_url($this->get_settings_link()), esc_html__( 'Global shape style settings.', 'woo-variation-swatches' )); ?>

			</p>
			</div><?php
		}

		public function edit_attribute_fields(): void {

			$attribute_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

			if( 0 === $attribute_id ){
				return;
			}

			$selected = $this->get_option( $attribute_id, 'shape_style', 'global' );

?>
			<tr data-gwp_dependency="<?php echo esc_attr( wc_esc_json(  wp_json_encode( $this->get_dependency()) ) ) ?>" class="form-field">
				<th scope="row" valign="top">
					<label for="attribute_shape_style"><?php esc_html_e( 'Shape Style', 'woo-variation-swatches' ); ?></label>
				</th>
				<td>
					<select name="<?php echo esc_attr( $this->field_name('shape_style') ) ?>" id="attribute_shape_style">
						<option <?php selected( $selected, 'global') ?> value="global"><?php esc_html_e( 'Global', 'woo-variation-swatches' ); ?></option>
						<option <?php selected( $selected, 'rounded') ?> value="rounded"><?php esc_html_e( 'Rounded', 'woo-variation-swatches' ); ?></option>
						<option <?php selected( $selected, 'squared') ?> value="squared"><?php esc_html_e( 'Squared', 'woo-variation-swatches' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Attribute wise shape style.', 'woo-variation-swatches' ); ?>
						<?php printf( '<a target="_blank" href="%s">%s</a>',  esc_url($this->get_settings_link()), esc_html__( 'Global shape style settings.', 'woo-variation-swatches' )); ?>
					</p>
				</td>
			</tr>
<?php
		}
	}
endif;
