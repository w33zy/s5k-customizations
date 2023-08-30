<?php
/**
 * Plugin Name:     Scotia 5K Customizations
 * Plugin URI:      https://wzymedia.com
 * Description:     Customizations for the Scotia 5K Run website
 * Author:          w33zy
 * Author URI:      https://wzymedia.com
 * Text Domain:     wzy-media
 * Version:         1.1.0
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
		if ( false === get_option( '_s5k_male_tickets_available' ) ) {
			update_option( '_s5k_male_tickets_available', 500 );
		}
	}


	public static function add_filters(): void {}

	public static function add_actions(): void {
		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'insert_single_product_script' ), 99 );
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'insert_ticket_count' ), 99 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'update_ticket_count' ), 99, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'update_tshirt_count' ), 99, 2 );
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
	public static function update_tshirt_count( WC_Order $order, array $data ): void {
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

}

S5K_Customizations::start();

register_activation_hook( __FILE__, array( 'S5K_Customizations', 'activate' ) );
