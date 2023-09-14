<?php

/**
 * Plugin Name:     Scotia 5K Customizations
 * Plugin URI:      https://wzymedia.com
 * Description:     Customizations for the Scotia 5K Run website
 * Author:          w33zy
 * Author URI:      https://wzymedia.com
 * Text Domain:     wzy-media
 * Version:         1.13.1
 *
 * @package         S5K_Customizations
 */
class S5K_Customizations {

	public static array $variation_names = [
		"xs-white-unisex-for-my-t-shirt"              => 'XS, White Unisex "For My" T-shirt',
		"xs-white-unisex-multicolour-print-t-shirt"   => 'XS, White Unisex Multicolour Print T-shirt',
		"s-white-unisex-for-my-t-shirt"               => 'S, White Unisex "For My" T-shirt',
		"s-white-unisex-multicolour-print-t-shirt"    => 'S, White Unisex Multicolour Print T-shirt',
		"m-white-unisex-for-my-t-shirt"               => 'M, White Unisex "For My" T-shirt',
		"m-white-unisex-multicolour-print-t-shirt"    => 'M, White Unisex Multicolour Print T-shirt',
		"l-white-unisex-for-my-t-shirt"               => 'L, White Unisex "For My" T-shirt',
		"l-white-unisex-multicolour-print-t-shirt"    => 'L, White Unisex Multicolour Print T-shirt',
		"xl-white-unisex-for-my-t-shirt"              => 'XL, White Unisex "For My" T-shirt',
		"xl-white-unisex-multicolour-print-t-shirt"   => 'XL, White Unisex Multicolour Print T-shirt',
		"xxl-white-unisex-for-my-t-shirt"             => 'XXL, White Unisex "For My" T-shirt',
		"xxl-white-unisex-multicolour-print-t-shirt"  => 'XXL, White Unisex Multicolour Print T-shirt',
		"xxxl-white-unisex-for-my-t-shirt"            => 'XXXL, White Unisex "For My" T-shirt',
		"xxxl-white-unisex-multicolour-print-t-shirt" => 'XXXL, White Unisex Multicolour Print T-shirt',
	];

	public static array $variation_matrix = [
		218 => [ 'XXXL', 'White Unisex "For My" T-shirt' ],
		219 => [ 'XXXL', 'White Unisex Multicolour Print T-shirt' ],

		220 => [ 'XS', 'White Unisex "For My" T-shirt' ],
		221 => [ 'XS', 'White Unisex Multicolour Print T-shirt' ],

		222 => [ 'S', 'White Unisex "For My" T-shirt' ],
		223 => [ 'S', 'White Unisex Multicolour Print T-shirt' ],

		224 => [ 'M', 'White Unisex "For My" T-shirt' ],
		225 => [ 'M', 'White Unisex Multicolour Print T-shirt' ],

		226 => [ 'L', 'White Unisex "For My" T-shirt' ],
		227 => [ 'L', 'White Unisex Multicolour Print T-shirt' ],

		228 => [ 'XL', 'White Unisex "For My" T-shirt' ],
		229 => [ 'XL', 'White Unisex Multicolour Print T-shirt' ],

		230 => [ 'XXL', 'White Unisex "For My" T-shirt' ],
		231 => [ 'XXL', 'White Unisex Multicolour Print T-shirt' ],
	];

	public static function start(): void {
		static $started = false;

		if ( ! $started ) {
			self::add_filters();
			self::add_actions();

			$started = true;
		}
	}

	// On plugin activation store the initial number of tickets available
	public static function activate(): void {
		global $registration_codes;
		if ( false === get_option( '_s5k_male_tickets_available' ) ) {
			update_option( '_s5k_male_tickets_available', 500 );
		}

		if ( false === get_option( '_s5k_registration_codes' ) ) {
			require __DIR__ . '/registration-codes.php';

			update_option( '_s5k_registration_codes', $registration_codes );
		}
	}


	public static function add_filters(): void {
		// Make sure we cannot have to products from the "Group Registration" category in the cart
		add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'is_product_the_same_cat' ], 99, 2 );

		// add_filter( 'woocommerce_checkout_fields', [ __CLASS__, 'insert_registration_code_checkout_field' ], 99 );

		// Remove the state and postcode fields from the checkout form
		add_filter( 'woocommerce_checkout_fields', [ __CLASS__, 'remove_state_and_zipcode_fields' ], 100 );

		// Insert the registration code field on WP admin order details page
		add_filter( 'woocommerce_admin_order_data_after_shipping_address', [ __CLASS__, 'insert_registration_code_on_admin_order_meta' ], 99 );

		// Insert a JS script on the checkout page
		// add_filter( 'woocommerce_after_checkout_form', [ __CLASS__, 'insert_checkout_page_script' ], 99 );

		// Show the cheque payment method only if the cart contains a product from the "Group Registration" category
		add_filter( 'woocommerce_available_payment_gateways', [ __CLASS__, 'show_cheque_payment_checkout_page' ], 99 );

		// Load custom WooCommerce templates from the plugin
		add_filter( 'woocommerce_template_loader_files', [ __CLASS__, 'get_wc_template_name' ], 99, 2 );
		add_filter( 'template_include', [ __CLASS__, 'include_template_name' ], 99 );
		add_filter( 'wc_get_template_part', [ __CLASS__, 'get_template_part' ], 99, 3 );
		add_filter( 'woocommerce_locate_template', [ __CLASS__, 'locate_template' ], 99, 2 );
	}


	public static function add_actions(): void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );

		// Insert a JS script on the single product page
		add_action( 'woocommerce_after_single_product', [ __CLASS__, 'insert_single_product_script' ], 99 );
		// add_action( 'woocommerce_after_single_product', [ __CLASS__, 'get_tshirt_variation_stock' ], 100 );

		// Show the number of tickets available on the single product page, if exceeded
		add_action( 'woocommerce_before_add_to_cart_button', [ __CLASS__, 'insert_ticket_count' ], 99 );

		// Decrease the number of tickets available
		add_action( 'woocommerce_checkout_order_created', [ __CLASS__, 'update_ticket_count' ], 99 );

		// Decrease the stock count for the selected t-shirt size and design
		add_action( 'woocommerce_checkout_order_created', [ __CLASS__, 'decrement_tshirt_count' ], 100 );

		// Increase the stock count for the selected t-shirt size and design
		add_action( 'woocommerce_order_status_failed', [ __CLASS__, 'increment_tshirt_count' ], 100 );
		add_action( 'woocommerce_order_status_cancelled', [ __CLASS__, 'increment_tshirt_count' ], 100 );

		// Update the registration code
		add_action( 'woocommerce_checkout_order_created', [ __CLASS__, 'assign_registration_code' ], 99 );

		// Add the registration code to the order emails
		// add_action( 'woocommerce_email_order_meta', [ __CLASS__, 'add_registration_code_to_emails' ], 99, 3 );

		// add_action( 'wp_ajax_nopriv_fetch_product_variations_stock', [ __CLASS__, 'fetch_product_variations_stock' ] );
		// add_action( 'wp_ajax_fetch_product_variations_stock', [ __CLASS__, 'fetch_product_variations_stock' ] );

		add_action( 'woocommerce_cart_contents', [ __CLASS__, 'add_tt_post_message' ] );
		add_action( 'woocommerce_review_order_after_cart_contents', [ __CLASS__, 'add_tt_post_message' ] );
	}

	public static function enqueue_scripts(): void {
		$data = [
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'fetch_product_variations_stock' ),
			'productID'       => 155,
			'variationsStock' => self::get_product_variations_stock( 155 ),
		];

		if ( is_singular( 'product' ) ) {
			wp_enqueue_script( 's5k-customizations', plugin_dir_url( __FILE__ ) . 'assets/js/s5k-customizations3.js', [ 'jquery' ], '1.13.1', true );
			wp_add_inline_script(
				's5k-customizations',
				'window.s5k = window.s5k || {}; s5k.wpData = ' . wp_json_encode( $data )
			);
		}
	}

	public static function add_tt_post_message(): void {
		$tt_post = false;

		if ( ! empty( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                if ( ! empty( $cart_item['rn_line_items'] ) ) {
                    foreach ( $cart_item['rn_line_items'] as $line_item ) {
                        if ( 'Street name' === $line_item->Label && ! empty( $line_item->Value ) ) {
                            $tt_post = true;
                            break;
                        }
                    }
                }
			}
		}

		?>
        <tr class="woocommerce-cart-form__cart-item cart_item s5k-tt-post" style="background: #fff;">
            <?php echo is_page( 'cart' ) ? '<td colspan="2" class="tt-post"></td>' : ''; ?>
            <td colspan="2" class="tt-post" style="color:#EC111A; font-family:'ScotiaBold',Helvetica,Arial,sans-serif; font-size:20px;">
				<?php echo $tt_post ? esc_html( 'A TTPost delivery fee of $30 has been added to your cart.' ) : ''; ?>
            </td>
        </tr>
	<?php }

	/**
	 * Prevents 2 products from the "Group Registration" category from being added to the cart
	 *
	 * @hook woocommerce_add_to_cart_validation
	 *
	 * @param $valid
	 * @param $product_id
	 *
	 * @return bool
	 */
	public static function is_product_the_same_cat( $valid, $product_id ): bool {
		$cat_slugs        = [];
		$target_cat_slugs = [];
		$target           = 'group-registration';

		if ( 0 === WC()->cart->get_cart_contents_count() ) {
			return $valid;
		}

		foreach ( WC()->cart->get_cart() as $key => $values ) {
			$_product     = $values['data'];
			$terms        = get_the_terms( $_product->id, 'product_cat' );
			$target_terms = get_the_terms( $product_id, 'product_cat' );

			foreach ( $terms as $term ) {
				$cat_slugs[] = $term->slug;
			}

			foreach ( $target_terms as $term ) {
				$target_cat_slugs[] = $term->slug;
			}
		}

		$same_cat = array_intersect( $cat_slugs, $target_cat_slugs );

		if ( count( $same_cat ) > 0 && in_array( $target, $same_cat, true ) ) {
			wc_add_notice( 'You have already booked another group registration.', 'error' );

			return false;
		}

		return $valid;
	}

	/**
	 * Insert the script that checks the number of tickets available
	 *
	 * @return void
	 */
	public static function insert_single_product_script(): void { ?>
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            let mta = parseInt(<?php echo get_option( '_s5k_male_tickets_available' ); ?>)
            let form = document.querySelector('form.cart') || false

            console.log('MTA: ', mta)

            if (form) {
              form.addEventListener('submit', function (e) {
                let smt = 0
                const selectFields = form.querySelectorAll('select')
                const divElement = document.querySelector('.s5k-ticket-exceeded')

                divElement.innerHTML = ''

                selectFields.forEach(function (select) {
                  const selectedText = select.options[select.selectedIndex]?.text

                  if ('Male' === selectedText) {
                    ++smt
                  }
                })

                console.log('SMT: ', smt)

                if (smt > mta) {
                  e.preventDefault()
                  console.log('Male tickets quota has been exceeded!')

                  let p = document.createElement('p')
                  p.style.padding = 0

                  divElement.style.padding = '1rem'
                  divElement.style.border = '2px solid #E33B31'
                  divElement.style.borderRadius = '4px'
                  divElement.style.backgroundColor = '#ff00001a'
                  divElement.style.margin = '1rem 0'

                  p.textContent = `Male tickets quota has been exceeded. You have selected ${smt} ticket(s) but only ${mta} are available.`
                  divElement.appendChild(p)
                }
              })
            }

          })
        </script>
		<?php
	}

	public static function get_tshirt_variation_stock(): void { ?>

	<?php }

	/**
	 * Insert the ticket count element, used to display the number of tickets available on errors
	 *
	 * @return void
	 */
	public static function insert_ticket_count(): void {
		if ( is_singular( 'product' ) ) { ?>
            <div class="s5k-ticket-exceeded"></div>
			<?php
		}
	}

	/**
	 * Update the number of tickets available
	 *
	 * @param  \WC_Order  $order  The order object
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	public static function update_ticket_count( \WC_Order $order ): void {
		$counts = array_count_values( self::get_field_from_order( $order, 'gender' ) );
		$count  = $counts['Male'] ?? 0;

		if ( $count ) {
			$current = get_option( '_s5k_male_tickets_available' );

			// If current ticket count is less than or equal to 0, then we don't have any tickets left, throw an error
			if ( $current <= 0 ) {
				throw new \Exception( 'No more tickets available' );
			}

			// If the number of tickets selected is greater than the number of tickets available, throw an error
			if ( $current < $count ) {
				throw new \Exception(
					sprintf(
						_n(
							'You have selected %1$s male ticket',
							'You have selected %1$s male tickets',
							$count,
						),
						$count
					)
					. ' ' .
					sprintf(
						_n(
							'but only %1$s ticket is available.',
							'but only %1$s tickets are available.',
							$current,
						),
						$current
					)
				);
			}

			$updated = update_option( '_s5k_male_tickets_available', ( $current - $count ) );

			error_log( '+++++++++++++++++++++++++++++++++' );
			error_log( sprintf( 'Order #%1$d received', $order->get_id() ) );
            if ( $updated ) {
	            error_log( sprintf( 'Male tickets count reduced by %1$d, the current ticket count is %2$d', $count, ( $current - $count ) ) );
            }
			error_log( '+++++++++++++++++++++++++++++++++' );
		}
	}

	/**
	 * Decrease the stock count for the selected t-shirt size and design
	 *
	 * @param  \WC_Order  $order
	 *
	 * @return void
	 */
	public static function decrement_tshirt_count( \WC_Order $order ): void {
		$sizes    = self::get_field_from_order( $order, 'tshirtsize' );
		$designs  = self::get_field_from_order( $order, 'shirtdesign' );
		$combined = self::combine_arrays_to_associative( $sizes, $designs );

        error_log( '---------------------------------' );
        error_log( sprintf( 'Order #%1$d received', $order->get_id() ) );

		foreach ( self::$variation_matrix as $key => $value ) {
			foreach ( $combined as $selection ) {
				if ( serialize( $value ) === serialize( $selection ) ) {
					$result = wc_update_product_stock( $key, 1, 'decrease' );
					error_log( sprintf( 'Inventory decreased for variation #%1$d, stock is now at %2$d', $key, $result ) );
				}
			}
		}

		error_log( '---------------------------------' );
	}

	/**
     * Increase the stock count for the selected t-shirt size and design
     * for failed and cancelled orders
     *
	 * @param  int  $order_id
	 *
	 * @return void
	 */
	public static function increment_tshirt_count( int $order_id ): void {
        $order    = wc_get_order( $order_id );
		$sizes    = self::get_field_from_order( $order, 'tshirtsize' );
		$designs  = self::get_field_from_order( $order, 'shirtdesign' );
		$combined = self::combine_arrays_to_associative( $sizes, $designs );

		error_log( '---------------------------------' );
		error_log( sprintf( 'Order #%1$d failed', $order->get_id() ) );

		foreach ( self::$variation_matrix as $key => $value ) {
			foreach ( $combined as $selection ) {
				if ( serialize( $value ) === serialize( $selection ) ) {
					$result = wc_update_product_stock( $key, 1, 'increase' );
					error_log( sprintf( 'Inventory increased for variation #%1$d, stock is now at %2$d', $key, $result ) );
				}
			}
		}

		error_log( '---------------------------------' );
    }

	/**
	 * Remove the state and postcode fields from the checkout form
	 *
	 * @hook woocommerce_checkout_fields
	 *
	 * @param  array  $fields
	 *
	 * @return array
	 */
	public static function remove_state_and_zipcode_fields( array $fields ): array {
		unset(
			$fields['billing']['billing_state'],
			$fields['billing']['billing_postcode'],
			$fields['shipping']['shipping_state'],
			$fields['shipping']['shipping_postcode']
		);

		return $fields;
	}

	public static function insert_registration_code_checkout_field( array $fields ): array {
		$group      = false;
		$categories = [ 'group-registration' ];

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( has_term( $categories, 'product_cat', (int) $cart_item['product_id'] ) ) {
				$group = true;
				break;
			}
		}

		if ( $group ) {
			$reg_code                               = self::get_next_available_registration_code();
			$fields['billing']['registration_code'] = [
				'label'       => 'Registration Code',
				'placeholder' => 'GRPREG000',
				'required'    => true,
				'class'       => [ 'form-row-wide', 's5k-reg-code' ],
				'clear'       => true,
				'priority'    => 55,
				'type'        => 'text', // TODO: Change to hidden
				'default'     => $reg_code,
			];
		}

		return $fields;
	}

	/**
	 * Assign the registration code to the order
	 *
	 * @hook woocommerce_checkout_order_created
	 *
	 * @param  \WC_Order  $order
	 *
	 * @return void
	 */
	public static function assign_registration_code( \WC_Order $order ): void {
		$categories = [ 'group-registration' ];

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( has_term( $categories, 'product_cat', $item->get_product_id() ) ) {
				$reg_code = self::get_next_available_registration_code();

                if ( $reg_code ) {
	                update_post_meta( $order->get_id(), '_registration_code', $reg_code );

	                $new_codes = self::update_registration_codes( $reg_code, $order->get_id() );
	                update_option( '_s5k_registration_codes', $new_codes );
                }
			}
		}
	}

	/**
	 * Insert the registration code on the WP admin order details page
	 *
	 * @hook woocommerce_admin_order_data_after_shipping_address
	 *
	 * @param  \WC_Order  $order
	 *
	 * @return void
	 */
	public static function insert_registration_code_on_admin_order_meta( \WC_Order $order ): void {
		$categories = [ 'group-registration' ];

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( has_term( $categories, 'product_cat', $item->get_product_id() ) ) {
				echo '<p><strong>' . __( 'Registration Code' ) . ':</strong><br> ' . get_post_meta( $order->get_id(), '_registration_code', true ) . '</p>';
			}
		}
	}

	/**
	 * Add the registration code to the emails
	 *
	 * @hook  woocommerce_email_order_meta
	 *
	 * @param  \WC_Order  $order
	 * @param  bool       $sent_to_admin
	 * @param  bool       $plain_text
	 *
	 * @return void
	 */
	public static function add_registration_code_to_emails( WC_Order $order, bool $sent_to_admin, bool $plain_text ): void {
		$categories = [ 'group-registration' ];

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( has_term( $categories, 'product_cat', $item->get_product_id() ) ) {
				if ( $plain_text ) {
					echo "\n" . __( 'Registration Code' ) . ":\n" . get_post_meta( $order->get_id(), '_registration_code', true ) . "\n";
				} else {
					echo '<h2>' . __( 'Registration Code' ) . '</h2><p>' . get_post_meta( $order->get_id(), '_registration_code', true ) . '</p><br>';
				}
			}
		}
	}

	public static function fetch_product_variations_stock(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'fetch_product_variations_stock' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		wp_send_json_success( self::get_product_variations_stock( (int) $_POST['product_ID'] ) );
	}

	public static function insert_checkout_page_script(): void { ?>
        <script>
          (function ($) {
            console.log('Checkout page script loaded', $)
          })(jQuery)
        </script>

	<?php }

	/**
	 * Show the cheque payment method only if the cart contains a product from the "Group Registration" category
	 *
	 * @hook woocommerce_available_payment_gateways
	 *
	 * @param  array  $available_gateways
	 *
	 * @return array
	 */
	public static function show_cheque_payment_checkout_page( array $available_gateways ): array {
		$group      = false;
		$categories = [ 'group-registration' ];

		if ( is_admin() ) {
			return $available_gateways;
		}

		if ( null === WC()->cart ) {
			return $available_gateways;
		}

		foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
			/** @var \WC_Product $product */
			$product = $cart_item['data'];

			if ( has_term( $categories, 'product_cat', $product->get_id() ) ) {
				$group = true;
				break;
			}
		}

		if ( ! $group ) {
			unset( $available_gateways['cheque'] );
		}

		return $available_gateways;
	}

	public static function get_wc_template_name( $templates, $template_name ) {
		// Capture/cache the $template_name which is a file name like single-product.php
		wp_cache_set( 's5k_wc_main_template', $template_name ); // cache the template name

		return $templates;
	}

	public static function include_template_name( $template ) {
		// Get the cached $template_name
		if ( $template_name = wp_cache_get( 's5k_wc_main_template' ) ) {
			wp_cache_delete( 's5k_wc_main_template' ); // delete the cache

			if ( $file = self::wc_template_file( $template_name ) ) {
				return $file;
			}
		}

		return $template;
	}

	public static function get_template_part( $template, $slug, $name ) {
		$file = self::wc_template_file( "{$slug}-{$name}.php" );

		return $file ?: $template;
	}

	public static function locate_template( $template, $template_name ) {
		$file = self::wc_template_file( $template_name );

		return $file ?: $template;
	}

	private static function wc_template_file( string $template_name ): ?string {
		// Check theme folder first - e.g. wp-content/themes/my-theme/woocommerce.
		$file = wp_normalize_path( get_stylesheet_directory() . '/woocommerce/templates/' . $template_name );
		if ( @file_exists( $file ) ) {
			return $file;
		}

		// Now check plugin folder - e.g. wp-content/plugins/my-plugin/woocommerce/templates.
		$file = wp_normalize_path( __DIR__ . '/woocommerce/templates/' . $template_name );
		if ( @file_exists( $file ) ) {
			return $file;
		}

		return null;
	}

	/**
	 * Get the next available registration code
	 *
	 * @return string
	 */
	private static function get_next_available_registration_code(): string {
		$codes = get_option( '_s5k_registration_codes' );

		foreach ( $codes as $key => $value ) {
			if ( 'unassigned' === $value ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Update the registration codes
	 *
	 * @param  string  $code
	 * @param  int     $order_id
	 *
	 * @return array
	 */
	private static function update_registration_codes( string $code, int $order_id ): array {
		$codes     = get_option( '_s5k_registration_codes' );
		$new_codes = [];

		foreach ( $codes as $key => $value ) {
			if ( $code === $key ) {
				$new_codes[ $key ] = $order_id;
			} else {
				$new_codes[ $key ] = $value;
			}
		}

		return $new_codes;
	}

	/**
	 * Get the field value from the order
	 *
	 * @param  \WC_Order  $order
	 * @param  string     $field
	 *
	 * @return array
	 */
	public static function get_field_from_order( WC_Order $order, string $field ): array {
		$result = [];

		foreach ( $order->get_items() as $item ) {
			foreach ( $item->get_meta_data() as $meta ) {
				$data = $meta->get_data();
				if ( str_contains( $data['key'], $field ) ) {
					$result[] = self::extract_string_value( $data );
				}
			}
		}

		return $result;
	}

	private static function extract_string_value( $data ): ?string {
		if ( is_array( $data ) && isset( $data['value'] ) ) {
			if ( is_string( $data['value'] ) ) {
				return $data['value'];
			}

			if ( is_array( $data['value'] ) && count( $data['value'] ) > 0 ) {
				return self::extract_string_value( [ 'value' => $data['value'][0] ] );
			}
		}

		return null;
	}

	private static function combine_arrays_to_associative( array $keys, array $values ): array {
		$new_array = [];

		for ( $i = 0, $iMax = count( $keys ); $i < $iMax; $i ++ ) {
			$key   = $keys[ $i ];
			$value = $values[ $i ];
			// $new_array[$key] = $value;
			$new_array[] = [ $key, $value ];
		}

		return $new_array;
	}

	public static function get_product_variations_stock( $product_id, $simple = true ): array {
		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return []; // Not a variable product or product not found
		}

		$variations       = $product->get_available_variations();
		$variations_stock = [];

		foreach ( $variations as $variation ) {
			$variation_id   = $variation['variation_id'];
			$variation_obj  = wc_get_product( $variation_id );
			$stock_quantity = $variation_obj->get_stock_quantity();

			if ( $simple ) {
				$variations_stock[ $variation['attributes']['attribute_pa_size'] . '-' . $variation['attributes']['attribute_pa_design'] ] = $stock_quantity;
			} else {
				$variations_stock[] = [
					'variation_id'   => $variation_id,
					'attributes'     => $variation['attributes'],
					'stock_quantity' => $stock_quantity,
				];
			}
		}

		return $variations_stock;
	}

	/**
	 * Get the value of a meta key from the order item meta table
	 *
	 * @param  int     $order_id
	 * @param  string  $meta_key
	 *
	 * @return mixed|string
	 */
	public static function get_order_item_meta_value( int $order_id, string $meta_key ) {
		global $wpdb;

		// Prepare the SQL query with $wpdb->prepare() to prevent SQL injection
		$query = $wpdb->prepare(
			"SELECT meta.meta_value
            FROM {$wpdb->prefix}woocommerce_order_itemmeta AS meta
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS items
            ON meta.order_item_id = items.order_item_id
            WHERE items.order_id = %d
            AND meta.meta_key = %s",
			$order_id,
			$meta_key
		);

		// Use $wpdb->get_var() to retrieve a single value
		$result = $wpdb->get_var( $query );

		// Return the result
		return maybe_unserialize( $result );
	}

	public static function flatten_array( $array ): array {
		if ( ! is_array( $array ) ) {
			return [];
		}

		$result = [];
		foreach ( $array as $item ) {
			if ( is_array( $item ) ) {
				$result = array_merge( $result, self::flatten_array( $item ) );
			} else {
				$result[] = $item;
			}
		}

		return $result;
	}
}

S5K_Customizations::start();

register_activation_hook( __FILE__, [ 'S5K_Customizations', 'activate' ] );
