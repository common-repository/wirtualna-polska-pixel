<?php
class WPHPixelAdmin {
    public $plugin_name;
	
	public function __construct($plugin_name)
	{
        $this->plugin_name = $plugin_name;

		add_action( 'init', [$this, 'init_translations'] );

		add_action( 'admin_menu', [$this, 'wph_add_admin_page'] );
		
		add_filter( 'plugin_action_links_'.$this->plugin_name, [$this, 'add_action_links'], 10, 2);
	}
	
	public function init()
	{
		
	}

	public function init_translations()
	{
		load_plugin_textdomain( 'wirtualna-polska-pixel', false, dirname( plugin_basename( $this->plugin_name ) ) . '/languages/' );
	}
	
	public function wph_admin_init()
	{
        if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            add_action( 'admin_notices', [$this, 'wph_plugin_notice'] );

            deactivate_plugins( $this->plugin_name );

            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }

		add_settings_section(
			'wph_admin',
			esc_html__('General'),
			'',
			'wph_page'
		);

		register_setting(
			'wph_page',
			'wphopt_pixel_id'
		);

		add_settings_field(
			'wphopt-pixel-id',
			__('WP Pixel ID', 'wirtualna-polska-pixel'),
			[$this, 'wph_admin_output_field'],
			'wph_page',
			'wph_admin'
		);

        register_setting(
            'wph_page',
            'wphopt_pixel_ajax_listing'
        );

        add_settings_field(
            'wphopt-pixel-ajax-listing',
			esc_html__('In your store, on the product/category list page, does adding to the cart take place without reloading the page?', 'wirtualna-polska-pixel'),
            [$this, 'wph_admin_output_field_checkbox'],
            'wph_page',
            'wph_admin',
            ['id_field' => 'wphopt_pixel_ajax_listing']
        );

        register_setting(
            'wph_page',
            'wphopt_pixel_ajax_product'
        );

        add_settings_field(
            'wphopt-pixel-ajax-product',
			esc_html__('In your store, does adding to the cart take place on the product page without reloading the page?', 'wirtualna-polska-pixel'),
            [$this, 'wph_admin_output_field_checkbox'],
            'wph_page',
            'wph_admin',
            ['id_field' => 'wphopt_pixel_ajax_product']
        );
	}

	public function wph_plugin_notice() {
	    echo sprintf('<div class="error"><p>%s</p></div>', esc_html__('Error, Wirtualna Polska Pixel requires the Woocommerce plugin to be installed.', 'wirtualna-polska-pixel'));
    }
	
	public function add_action_links ( $actions, $plugin_file ) {
	   	$mylinks = [
		  	'<a href="' . admin_url( 'options-general.php?page=wph_page' ) . '">'. esc_html__('Settings') .'</a>',
	   	];
	   	$actions = array_merge( $actions, $mylinks );
		return $actions;
	}
	
	public function wph_add_admin_page()
	{
		add_options_page(
			sprintf('%s %s', __('WP Pixel', 'wirtualna-polska-pixel'), esc_html__('Settings')),
			'WP Pixel',
			'manage_options',
			'wph_page',
			[$this, 'wph_show_admin_page']
		);
		
		add_action( 'admin_init', [$this, 'wph_admin_init'] );
	}
	
	function wph_show_admin_page() {
		if (!current_user_can('manage_options')) {
			return;
		}
	?>
<div class="wrap">
	<h2><?php echo sprintf('%s %s', __('WP Pixel', 'wirtualna-polska-pixel'), __('Settings')) ?></h2>
	 <?php //settings_errors();?>
	<form action="options.php" method="post">
	<?php settings_fields( 'wph_page' ); ?>
	<?php do_settings_sections( 'wph_page' ); ?>
	<?php submit_button(); ?>

	</form>
</div>
	<?php
	}
	
	public function wph_admin_output_field()
	{
		$idField = 'wphopt_pixel_id';

		$options = get_option($idField, '');

		echo "<input id='".esc_attr($idField)."' name='".esc_attr($idField)."' size='40' type='text' value='".esc_attr($options)."' />";
	}

    public function wph_admin_output_field_checkbox($args)
    {
        $options = get_option($args['id_field'], '');

        $checked = ($options == 1) ? 'checked="checked"' : '';

        echo "<label><input id='".esc_attr($args['id_field'])."' name='".esc_attr($args['id_field'])."' type='checkbox' value='1' ".esc_js($checked)." /> ".esc_html__('Check if yes', 'wirtualna-polska-pixel')."</label>";

        if(is_plugin_active( 'woo-ajax-add-to-cart/woo-ajax-add-to-cart.php' ) || is_plugin_active( 'woocommerce-ajax-cart/wooajaxcart.php' )) {
            echo spirntf("<br><small>%s</small>", esc_html__('A plugin has been detected that allows you to add products to the cart without reloading the page, we recommend enabling the above option', 'wirtualna-polska-pixel'));
        }
    }
}