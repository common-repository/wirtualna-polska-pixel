<?php
/**
* Plugin Name: Wirtualna Polska Pixel
* Plugin URI: 
* Description: A tool for measuring actions taken by users visiting the website and increasing the effectiveness of advertising on the WP Advertising Network.
* Version: 1.2.2
* Author: Wirtualna Polska Media S.A.
* Text Domain: wirtualna-polska-pixel
* Domain Path: /languages
* Author URI: https://wp.pl/
**/

defined( 'ABSPATH' ) || exit;

class WPHPixelCore {
	public $plugin_name = '';

	public $pixel_option = 'wphopt_pixel_id';

	private $shop_ajax_listing;
    private $shop_ajax_product;
	
	private $size_color = [];
	
	public function __construct()
	{
	    $this->plugin_name = plugin_basename(__FILE__);

        $this->shop_ajax_listing = get_option('wphopt_pixel_ajax_listing', '0');
        $this->shop_ajax_product = get_option('wphopt_pixel_ajax_product', '0');

		// all pages
		add_action( 'wp_head', [$this, 'init_pixel']); // inicjalizacja wph pixel
		
		// dodanie do koszyka
		// 3. ajax add to cart on category page and hotspots
        if($this->shop_ajax_product) {
            // 2. ajax add to cart woocommerce_after_add_to_cart_button
            add_action('wp_footer', [$this, 'pixel_add_to_cart']); // dodanie zdarzenia po dodaniu do koszyka
        } else {
            // 1. refresh page (not ajax)
            add_action( 'wp_footer', [$this, 'pixel_add_to_cart_refresh']);
        }
        if($this->shop_ajax_listing) {
            add_action('wp_enqueue_scripts', [$this, 'pixel_add_to_cart_box']);
            add_action( 'wp_ajax_wph_ajax_get_product', [$this, 'wph_ajax_get_product'] );
            add_action( 'wp_ajax_nopriv_wph_ajax_get_product', [$this, 'wph_ajax_get_product'] );
        }

        if('yes' === get_option( 'woocommerce_cart_redirect_after_add' )) {
            add_action( 'init', [$this, 'init_session'] );
            add_action('woocommerce_add_to_cart', [$this, 'pixel_add_to_cart_redirect'], 10, 4);
        }
		
		// home page
		add_action( 'wp_footer', [$this, 'pixel_home']); // kod na stronie głównej
		
		// product page
		add_action( 'wp_footer', [$this, 'pixel_product']); // strona produktu
		
		// category page
		add_action( 'wp_footer', [$this, 'pixel_category']); // strona kategorii
		
		// received order page
		add_action( 'wp_footer', [$this, 'pixel_purchase']); // strona podsumowania zamowienia
		
		// landing pages, other pages
		add_action( 'wp_footer', [$this, 'pixel_otherpages']); // strony stworzone przez użytkownika
	}

	public function init_session()
    {
        if ( ! session_id() ) {
            session_start();
        }
    }
	
	public function init_pixel()
	{
		$pixel_id = get_option($this->pixel_option, '');
?>
<script>
	!function(d,m,e,v,n,t,s){d['WphTrackObject'] = n;
	d[n] = window[n] || function() {(d[n].queue=d[n].queue||[]).push(arguments)},
	d[n].l = 1 * new Date(), t=m.createElement(e), s=m.getElementsByTagName(e)[0],
	t.async=1;t.src=v;s.parentNode.insertBefore(t,s)}(window,document,'script',
	'https://pixel.wp.pl/w/tr.js', 'wph');
	wph('init', '<?php echo esc_html($pixel_id) ?>', {
        plugin_name: "Wordpress",
        plugin_version: "1.2.2"
    });
</script>
<?php
	}
	
	public function pixel_home()
	{
		if(!is_front_page())
			return;
?>
<script>
    wph('track', 'ViewContent', { 'content_type': 'Site', 'content_name': 'View' })
</script>
<?php
	}
	
	public function pixel_otherpages()
	{
		if(!is_page() || is_front_page() || is_home() || is_category() || is_single() || is_woocommerce() || is_shop() || is_product_category() || is_product_tag() || is_product() || is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url())
			return;
?>
<script>
    wph('track', 'ViewContent', { 'content_type': 'Site', 'content_name': 'LandingPage' })
</script>
<?php
	}
	
	public function pixel_product()
	{
		if(!is_product())
			return;
		
		if(isset($_REQUEST['add-to-cart']))
			return;
		
		global $product;
		
		$sizes = $this->wph_viewproduct_sizes($product);

		$price = $this->wph_price(wc_get_price_including_tax($product));

?>
        <script>
            wph('track', 'ViewContent', {
                content_type: 'Site',
                content_name: 'ViewProduct',
                contents: [{
                    id: '<?php echo esc_html( $product->get_id() ) ?>',
                    name: '<?php echo esc_html( $product->get_name() ) ?>',
                    ean: '<?php echo esc_html( $product->get_sku() ) ?>',
                    <?php if($sizes) echo 'sizes: '. wp_json_encode($sizes) .','; ?>
                    <?php if($this->size_color) echo 'colour: '. wp_json_encode($this->size_color) .','; ?>
                    category: '<?php echo esc_js($this->wph_getcategory($product)) ?>',
                    <?php if($price): ?>price: <?php echo esc_js($price) ?>,<?php endif; ?>
                    in_stock: <?php echo esc_html( $this->wph_instock($product->get_stock_status()) ) ?>
                }],
            });
        </script>
<?php
	}
	
	public function pixel_add_to_cart_box()
	{
		wp_enqueue_script(
			'wph-ajax-get-product',
			plugin_dir_url( __FILE__ ) . 'assets/js/category_add_to_cart.min.js',
			['jquery']
		);

		wp_localize_script(
			'wph-ajax-get-product',
			'wph_get_product',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce('wph-ajax-get-product')
			]
		);
	}
	
	public function wph_ajax_get_product()
	{
		if ( isset($_REQUEST) && is_numeric($_REQUEST['product_id']) ) {

			$product_id = sanitize_text_field($_REQUEST['product_id']);

			$product = wc_get_product($product_id);
			
			$sizes = $this->wph_addtocart_sizes();
			
			$sizes = str_replace("['", '', $sizes);
			$sizes = str_replace("']", '', $sizes);
			
			$data = [
				'name'=>$product->get_name(),
				'id'=>(string)$product->get_id(),
				'sizes'=>[$sizes],
				'category'=>$this->wph_getcategory($product),
				'in_stock'=>$this->wph_instock($product->get_stock_status())
			];
			
			$price = $this->wph_price(wc_get_price_including_tax($product));
			if($price) {
                $data['price'] = floatval($price);
            }

			
			if($this->size_color)
				$data['colour'] = $this->size_color;
			
			echo json_encode($data);
		}

	   die();
	}

	public function pixel_add_to_cart_redirect($cart_id, $id_product, $quantity, $id_variation)
    {
        $_SESSION['wph_add_to_cart_product_id'] = [
            'id_product' => $id_product,
            'quantity' => $quantity,
            'id_variation' => $id_variation
        ];
    }
	
	public function pixel_add_to_cart_refresh()
	{
        $variation_id = NULL;

        if(isset($_REQUEST['add-to-cart'])) {
	        $addToCart = $_REQUEST['add-to-cart'];
	        $quantity = $_REQUEST['quantity'];
            if(isset($_REQUEST['variation_id']) && $_REQUEST['variation_id']) {
                $variation_id = $_REQUEST['variation_id'];
            }
        } else if(isset($_SESSION['wph_add_to_cart_product_id']) && is_array($_SESSION['wph_add_to_cart_product_id'])) {
            $addToCart = $_SESSION['wph_add_to_cart_product_id']['id_product'];
            $quantity = $_SESSION['wph_add_to_cart_product_id']['quantity'];

            if($_SESSION['wph_add_to_cart_product_id']['id_variation']) {
                $variation_id = $_SESSION['wph_add_to_cart_product_id']['id_variation'];
            }

            unset($_SESSION['wph_add_to_cart_product_id']);
        } else {
	        return;
        }

		if(!isset($addToCart) || !is_numeric($addToCart))
			return;
		
		global $product;

		if($variation_id) {
            $product = new WC_Product_Variation($variation_id);

            if(!is_a( $product, 'WC_Product_Variation' )) {
                return;
            }
        } else {
            $product = wc_get_product($addToCart);

            if(is_a( $product, 'WC_Product' )) {
                if($product->get_type() == 'variable') {
                    return;
                }
            } else {
                return;
            }
        }

		$product_id = $product->get_parent_id();

        if(!$product_id)
            $product_id = $product->get_id();

        $sizes = $this->wph_addtocart_sizes();

        $price = $this->wph_price(wc_get_price_including_tax($product));

?>
        <script>
            wph('track', 'AddToCart', {
                contents: [
                    {
                        id: '<?php echo esc_html( $product_id ) ?>',
                        name: '<?php echo esc_html( $product->get_name() ) ?>',
                        <?php if($price): ?>
                        price: <?php echo esc_js($price) ?>,
                        <?php endif; ?>
                        quantity: <?php echo esc_js( $this->wph_quantity($quantity) ) ?>,
                        sizes: ['<?php echo esc_js($sizes) ?>'],
                        <?php if($this->size_color[0]): ?>
                        colour: ['<?php echo esc_js($this->size_color[0]) ?>'],
                        <?php endif; ?>
                        category: '<?php echo esc_html($this->wph_getcategory($product)) ?>',
                        in_stock: <?php echo  esc_html( $this->wph_instock($product->get_stock_status()) ) ?>
                    }
                ]
            });
        </script>
<?php
	}
	
	public function pixel_add_to_cart()
	{
		if(!is_product())
			return;
		
		global $product;
		
		$price = wc_get_price_including_tax($product);

        $jsVariant = '';
        $attrLists = [];
        $rozmiar = [];
        $color = '';

		if($product->get_type() == 'variable') {
			$current_products = $product->get_children();
			
			if(is_array($current_products)) {
				$jsVariant = 'var pv = [];';
				foreach($current_products as $variant) {
					$ProductVariant = wc_get_product($variant);
					$price = $this->wph_price($ProductVariant->get_price());
                    $price = ($price) ? $price : '0';
					$jsVariant .= 'pv['.esc_html( $variant ).'] = '.$price.';';
				}
			}
			
			$attributes = $product->get_attributes();

			if(is_array($attributes)) {
				foreach($attributes as $attribute => $obj) {
					$data = $obj->get_data();

					if($data['variation'] && $data['visible']) {
						$label = wc_attribute_label($attribute);
						
						if($this->wph_check_is_size($label)) {
							$rozmiar = [
								'attr'=>'attribute_'.esc_html( $attribute ),
								'label'=>esc_html( $label ),
							];
						}
						
						if($this->wph_check_is_color($label))
							$color = 'attribute_'.esc_html( $attribute );
					}
				}
			}
		}
		
		/*
			Potrzebna obsluga .add_to_cart_button czyli produktow na kategorii
			dodanie do koszyka na karcie produktu
		*/
?>
        <script>
            (function($){

                $('*[name="add-to-cart"], .single_add_to_cart_button').on('click', function(e) {

                    if ($(this).hasClass('disabled')) {
                        return;
                    }

                    <?php if($product->get_type() == 'variable'): ?>
                    var a = <?php echo  json_encode($attrLists, JSON_FORCE_OBJECT) ?>;

                    <?php if($color): ?>
                    var c = $('[name="<?php echo esc_js($color) ?>"]').find(':selected').val();
                    <?php endif; ?>

                    <?php if($rozmiar['attr']): ?>
                    var s = $('[name="<?php echo esc_js($rozmiar['attr']) ?>"]').find(':selected').val();
                    <?php endif; ?>

                    <?php endif; ?>

                    var q = parseInt($('[name="quantity"]').val());
                    <?php echo esc_js($jsVariant) ?>

                    var sp = <?php echo esc_js($this->wph_price($price)) ?>;

                    <?php if($jsVariant): ?>
                    var cv = $('[name="variation_id"]').val();
                    sp = pv[cv];
                    <?php endif; ?>

                    var objwph = {
                        "id": '<?php echo  esc_js($product->get_id()) ?>',
                        "name": '<?php echo esc_js($product->get_name()) ?>',
                        "quantity": q,
                        "sizes": <?php echo  ($product->get_type() == 'variable' && $rozmiar['attr']) ? "[s]" : "['onesize']" ?>,
                        <?php if($color): ?>"colour": [c],<?php endif; ?>
                        "category": '<?php echo  esc_html($this->wph_getcategory($product)) ?>',
                        "in_stock": <?php echo esc_html( $this->wph_instock($product->get_stock_status()) ) ?>
                    };

                    if(sp)
                        objwph.price = sp;

                    wph('track', 'AddToCart', {
                        contents: [objwph]
                    });
                });
            })(jQuery);
        </script>
<?php
	}
	
	public function pixel_category()
	{
		if(get_post_type() != 'product')
			return;
		
		if(is_product())
			return;
		
		global $wp_query;
		
		$contents = [];
		
		if($wp_query->have_posts()):
			
		while ( $wp_query->have_posts() ) : $wp_query->the_post();
		
		global $post;
		$id_post = $post->ID;
		
		$productOne = wc_get_product( $id_post );

		if(!$productOne) {
		    continue;
        }

		$pid = esc_html( $productOne->get_id() );
		$pname = esc_html( $productOne->get_name() );
		
		$sizes = $this->wph_viewproduct_sizes($productOne);
		
		$stock = $this->wph_instock($productOne->get_stock_status(), true);

        $price = $this->wph_price(wc_get_price_including_tax($productOne));

		$cat = $this->wph_getcategory($productOne);

        $oneObj = [
            'id'=>(string)$pid,
            'name'=>$pname,
            'category'=>$cat,
            'sizes'=>$sizes,
            'in_stock'=>$stock
        ];

        if($this->size_color)
            $oneObj['colour'] = $this->size_color;

        if($price)
            $oneObj['price'] = floatval($price);


            $contents[] = $oneObj;
		endwhile;
		
		endif;
?>
        <script>
            wph('track', 'ViewContent', {
                content_type: 'Site',
                content_name: 'ProductList',
                name: '<?php echo esc_js( $wp_query->queried_object->name ) ?>',
                contents: <?php echo wp_json_encode($contents) ?>
            });
        </script>
<?php
	}
	
	public function pixel_purchase()
	{
		if( !is_wc_endpoint_url( 'order-received' ) ) return;

        global $wp;

        $order_id  = absint( $wp->query_vars['order-received'] );
		
		if( $order = wc_get_order( $order_id ) ) {
			$data = $order->get_data();
			$items = $order->get_items();
		}

		$coupons = $order->get_coupon_codes();
		
		$shipping_total = $data['shipping_total'] + $data['shipping_tax'];
		
		if(!$shipping_total) $shipping_total = 0.00;
		
		$value_gross = $data['total'] - $shipping_total;
		$value = $value_gross - $data['cart_tax'];
		
		
		if(is_array($coupons)) {
            $coupons_show = implode(',', $coupons);
            $coupons_show = esc_html($coupons_show);
        }

		// end prepare
        $value = $this->wph_price($value);
		$value_gross = $this->wph_price($value_gross);
        $shipping_total = $this->wph_price($shipping_total);


?>
        <script>
            wph('track', 'Purchase',
                {
                    transaction_id: '<?php echo esc_html( $order->get_id() ) ?>',
                    value: <?php echo esc_js($value) ?>,
                    value_gross: <?php echo esc_js($value_gross) ?>,
                    shipping_cost: <?php echo esc_js($shipping_total) ?>,
                    <?php if(isset($coupons[0])) echo "discount_code: '{$coupons_show}'," ?>
                    contents: [
                        <?php foreach($items as $item):
                        $item_data = $item->get_data();
                        $product = $item->get_product();
                        $ean = $product->get_sku();
                        $item_sizes = $item->get_meta_data();
                        $item_price = wc_get_price_including_tax($product);

                        $sizes = $this->wph_purchase_sizes($item_sizes);
                        $colour = $this->size_color;
                        $price = $this->wph_price($item_price);

                        $product_id = $product->get_parent_id();

                        if(!$product_id)
                            $product_id = $product->get_id();

                        ?>
                        {
                            id: '<?php echo esc_html( $product_id ) ?>',
                            name: '<?php echo esc_html( $product->get_name() ) ?>',
                            <?php if($ean): ?>ean: '<?php echo esc_html( $ean ) ?>',<?php endif; ?>
                            category: '<?php echo esc_html($this->wph_getcategory($product)) ?>',
                            sizes: <?php echo wp_json_encode($sizes) ?>,
                            <?php if($this->size_color): ?>colour: <?php echo wp_json_encode($colour) ?>,<?php endif; ?>
                            <?php if($price): ?>price: <?php echo esc_js($price); ?>,<?php endif; ?>
                            quantity: <?php echo esc_html( $this->wph_quantity($item_data['quantity']) ) ?>,
                            in_stock: <?php echo esc_html( $this->wph_instock($product->get_stock_status()) ) ?>
                        },
                        <?php endforeach; ?>
                    ]
                }
            );
        </script>
<?php
	}
	
	public function wph_price($price)
	{
		// from wc_get_price_including_tax($product)
		$outPrice = (is_numeric($price)) ? number_format($price, 2, '.', '') : "";
			
		return $outPrice;
	}

	public function wph_instock($status, $raw = false)
	{
		// from $product->get_stock_status()
		// ($status == 'onbackorder') = false
        if($raw)
            return ($status == 'instock') ? true : false;
        else
		    return ($status == 'instock') ? 'true' : 'false';
	}

	public function wph_quantity($quantity)
	{
		// from $product->get_stock_quantity()
		return (is_numeric($quantity)) ? esc_html($quantity) : "1";
	}

	public function wph_purchase_sizes($meta)//$attributes
	{
		$output = [];
        $this->size_color = [];
		
		if(is_array($meta)) {
			foreach($meta as $single_meta) {
				$single_data = $single_meta->get_data();
				
				$label = wc_attribute_label($single_data['key']);
				
				if($this->wph_check_is_color($label))
						$this->size_color[] = $single_data['value'];
				
				if($this->wph_check_is_size($label))
					$output[] = $single_data['value'];
			}
		}

		if(is_array($this->size_color) && !count($this->size_color)) {
            $this->size_color[] = 'onecolour';
        }
		
		if(!$output) $output[] = 'onesize';
		return $output;
	}

	public function wph_addtocart_sizes() // data from $_REQUEST
	{
		$output = '';
        $this->size_color = [];
		if(is_array($_REQUEST)) {	
			foreach($_REQUEST as $attr => $val) {
				
				if(strpos($attr, 'attribute_') !== false) {
					
					$attr_prod = str_replace('attribute_', '', $attr);
					
					$label = wc_attribute_label($attr_prod);
					
					if($this->wph_check_is_color($label))
                        $this->size_color[] = $val;
					
					if($this->wph_check_is_size($label)) {
						$output = $val;
					}
				}
			}
		}

        if(is_array($this->size_color) && !count($this->size_color)) {
            $this->size_color[] = 'onecolour';
        }
		
		if(!$output) $output = 'onesize';
		return $output;
	}
	
	private function wph_viewproduct_sizes($product)
	{
		$attributes = $product->get_attributes();
		
		$output = [];
        $this->size_color = [];
		if(is_array($attributes)) {
			foreach($attributes as $key => $value)
			{
				$label = wc_attribute_label($key);

				if($value->get_variation() && $this->wph_check_is_color($label)) {
					$attribute_slug = wc_get_product_terms( get_the_ID(), $key, array( 'fields' => 'slugs' ) );

					if(is_array($attribute_slug)) {
						foreach($attribute_slug as $one) {
						    $one_esc = esc_html($one);
							$this->size_color[] = $one_esc;
						}
					}
					
				}

                if($value->get_variation() && $this->wph_check_is_size($label)) {
					$attribute_slug = wc_get_product_terms( get_the_ID(), $key, array( 'fields' => 'slugs' ) );

					if(is_array($attribute_slug)) {
						foreach($attribute_slug as $one) {
                            $one_esc = esc_html($one);
							$output[] = $one_esc;
						}
					}
                }
			}
		}

        if(is_array($this->size_color) && !count($this->size_color)) {
            $this->size_color[] = 'onecolour';
        }

		if(!$output) $output[] = 'onesize';
		return $output;
	}
	
	private function wph_getcategory($product)
	{
		$id = $product->get_type() == 'variation' ? wp_get_post_parent_id($product->get_id()) : $product->get_id();
		
		$terms = wc_get_product_terms(
            $id,
            'product_cat',
            [
                'orderby' => 'parent',
                'order'   => 'DESC',
            ]
        );
		
		return $terms[0]->name;
	}
	
	private function wph_check_is_size($label)
	{
		return strpos(strtolower($label), 'rozmiar') !== false || strpos(strtolower($label), 'size') !== false;
	}
	
	private function wph_check_is_color($label)
	{
		return strpos(strtolower($label), 'color') !== false || strpos(strtolower($label), 'kolor') !== false || strpos(strtolower($label), 'colour') !== false;
	}
}

$WPH_Core = new WPHPixelCore();

require_once('admin/admin.php');

$Admin = new WPHPixelAdmin($WPH_Core->plugin_name);
$Admin->init();
