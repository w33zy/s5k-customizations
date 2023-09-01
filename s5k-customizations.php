<?php
/**
 * Plugin Name:     Scotia 5K Customizations
 * Plugin URI:      https://wzymedia.com
 * Description:     Customizations for the Scotia 5K Run website
 * Author:          w33zy
 * Author URI:      https://wzymedia.com
 * Text Domain:     wzy-media
 * Version:         1.3.0
 *
 * @package         S5K_Customizations
 */
class S5K_Customizations {

	public static array $variation_matrix = [
		218 => [ 'XXL', '2023 Race T-shirt Pink Print' ],
		219 => [ 'XXL', '2023 Race T-shirt Multicolour Print' ],

		220 => [ 'XS', '2023 Race T-shirt Pink Print' ],
		221 => [ 'XS', '2023 Race T-shirt Multicolour Print' ],

		222 => [ 'S', '2023 Race T-shirt Pink Print' ],
		223 => [ 'S', '2023 Race T-shirt Multicolour Print' ],

		224 => [ 'M', '2023 Race T-shirt Pink Print' ],
		225 => [ 'M', '2023 Race T-shirt Multicolour Print' ],

		226 => [ 'L', '2023 Race T-shirt Pink Print' ],
		227 => [ 'L', '2023 Race T-shirt Multicolour Print' ],

		228 => [ 'XL', '2023 Race T-shirt Pink Print' ],
		229 => [ 'XL', '2023 Race T-shirt Multicolour Print' ],

		230 => [ 'XXL', '2023 Race T-shirt Pink Print' ],
		231 => [ 'XXL', '2023 Race T-shirt Multicolour Print' ],
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
            require __DIR__ .  '/registration-codes.php';

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
		add_filter( 'woocommerce_after_checkout_form', [ __CLASS__, 'insert_checkout_page_script' ], 99 );

        // Show the cheque payment method only if the cart contains a product from the "Group Registration" category
		add_filter( 'woocommerce_available_payment_gateways', [ __CLASS__, 'show_cheque_payment_checkout_page' ], 99 );
    }

	public static function add_actions(): void {

        // Insert a JS script on the single product page
		add_action( 'woocommerce_after_single_product', [ __CLASS__, 'insert_single_product_script' ], 99 );
		add_action( 'woocommerce_after_single_product', [ __CLASS__, 'get_tshirt_variation_stock' ], 100 );

        // Show the number of tickets available on the single product page, if exceeded
		add_action( 'woocommerce_before_add_to_cart_button', [ __CLASS__, 'insert_ticket_count' ], 99 );

        // Decrease the number of tickets available
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'update_ticket_count' ], 99, 2 );

        // Decrease the stock count for the selected t-shirt size and design
		add_action( 'woocommerce_checkout_create_order', [ __CLASS__, 'update_tshirt_count' ], 100, 2 );

        // Update the registration code
		add_action( 'woocommerce_checkout_order_created', [ __CLASS__, 'assign_registration_code' ], 99 );

        // Add the registration code to the order emails
		add_action( 'woocommerce_email_order_meta', [ __CLASS__, 'add_registration_code_to_emails' ], 99, 3 );
	}

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
				document.addEventListener('DOMContentLoaded', function() {
					let mta = parseInt(<?php echo get_option('_s5k_male_tickets_available'); ?>);
					let form = document.querySelector('form.cart') || false;

					console.log('MTA: ', mta);
					
					if (form) {
						form.addEventListener('submit', function(e) {
							let smt = 0;
							const selectFields = form.querySelectorAll('select');

							selectFields.forEach(function (select) {
								const selectedText = select.options[select.selectedIndex]?.text;

								if ('Male' === selectedText) {
									++smt;
								}
							});

							console.log('Selected male tickets: ', smt);

							if (smt > mta) {
								e.preventDefault();
								console.log('Male tickets quota has been exceeded!');

								let p = document.createElement('p');
								p.style.padding = 0;

								let divElement = document.querySelector('.s5k-ticket-exceeded');
								divElement.style.padding = '1rem';
								divElement.style.border = '2px solid #E33B31';
								divElement.style.borderRadius = '4px';
								divElement.style.backgroundColor = '#ff00001a';
								divElement.style.margin = '1rem 0';

								p.textContent = `Male tickets quota has been exceeded. You have selected ${smt} tickets but only ${mta} are available.`;
								divElement.appendChild(p);								
							}
						});
					}
				});
        	</script>
	<?php 
	}

	public static function get_tshirt_variation_stock(): void { ?>
        <script>
          function hasAncestorWithClass(element, className) {
            let currentElement = element.parentElement;

            while (currentElement !== null) {
              if (currentElement.classList.contains(className)) {
                console.log(currentElement.classList)
                return true;
              }
              currentElement = currentElement.parentElement;
            }

            return false;
          }

          document.addEventListener('DOMContentLoaded', function() {
            // Add an event listener to the document for change events on elements with class ".rnInputPrice"
            document.addEventListener('change', function(e) {
              if (e.target && e.target.classList.contains('rnInputPrice')) {
                hasAncestorWithClass(e.target, 'rndropdown')
                const selectedOption = e.target.options[e.target.selectedIndex];
                const selectedText = selectedOption.textContent;

                console.log(`Selected text: ${selectedText}, Selected value: ${selectedOption.value}`);
              }
            });

          });
        </script>
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
	 * @param  array      $data   The data from the checkout form
	 *
	 * @throws \Exception
     *
     * @return void
	 */
	public static function update_ticket_count( \WC_Order $order, array $data ): void {
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

			update_option( '_s5k_male_tickets_available', ( $current - $count ) );
		}
	}

	/**
     * Decrease the stock count for the selected t-shirt size and design
     *
	 * @param  \WC_Order  $order
	 * @param  array      $data
	 *
	 * @return void
	 */
	public static function update_tshirt_count( \WC_Order $order, array $data ): void {
        $sizes    = self::get_field_from_order( $order, 'tshirtsize' );
        $designs  = self::get_field_from_order( $order, 'shirtdesign' );
        $combined = self::combine_arrays_to_associative( $sizes, $designs );

		foreach ( self::$variation_matrix as $key => $value ) {
			foreach ( $combined as $selection ) {
				if ( serialize( $value ) === serialize( $selection ) ) {
					wc_update_product_stock( $key, 1, 'decrease' );
				}
			}
		}
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
            $reg_code = self::get_next_available_registration_code();
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
				update_post_meta( $order->get_id(), '_registration_code',  $reg_code );

				$new_codes = self::update_registration_codes(  $reg_code, $order->get_id() );
				update_option( '_s5k_registration_codes', $new_codes );
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
				echo '<p><strong>' . __( 'Registration Code' ).':</strong><br> ' . get_post_meta( $order->get_id(), '_registration_code', true ) . '</p>';
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
	                echo '<h2>' . __( 'Registration Code' ).'</h2><p>' . get_post_meta( $order->get_id(), '_registration_code', true ) . '</p><br>';
                }
			}
		}
    }

	public static function insert_checkout_page_script(): void { ?>
        <script>
          (function($) {
            console.log('Checkout page script loaded', $);

            // Disable the registration code field
            // $('#registration_code').prop('disabled', true);
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

		foreach ( WC()->cart->get_cart() as $key => $values ) {
			$product = $values['data'];

			if ( has_term( $categories, 'product_cat', $product->id ) ) {
				$group = true;
				break;
            }
        }

        if ( ! $group ) {
            unset( $available_gateways['cheque'] );
        }

        return $available_gateways;
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
	private static function get_field_from_order( WC_Order $order, string $field ): array {
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

		for ( $i = 0, $iMax = count( $keys ); $i < $iMax; $i++ ) {
			$key = $keys[$i];
			$value = $values[$i];
			// $new_array[$key] = $value;
			$new_array[] = [ $key, $value ];
		}

		return $new_array;
	}

	private static function get_product_variations_stock( $product_id ) {
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

			$variations_stock[] = [
				'variation_id'   => $variation_id,
				'attributes'     => $variation['attributes'],
				'stock_quantity' => $stock_quantity,
			];
		}

		return $variations_stock;
	}
}

S5K_Customizations::start();

register_activation_hook( __FILE__, [ 'S5K_Customizations', 'activate' ] );
