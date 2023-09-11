<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @var WC_Order $order Order object.
 * @var bool $sent_to_admin Whether the email is being sent to the admin or not.
 * @var bool $plain_text Whether the email is plain text or HTML.
 * @var WC_Email $email Email heading, retrieved from get_heading().
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

defined( 'ABSPATH' ) || exit;

$text_align = is_rtl() ? 'right' : 'left';

$product_terms = [];
$order_items   = $order->get_items();

foreach ( $order_items as $order_item ) {
	$product_terms[] = wp_get_post_terms( (int) $order_item['product_id'], 'product_cat', array( 'fields' => 'slugs' ) );
}

do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php if ( 'cheque' === $order->get_payment_method() && in_array( 'group-registration', S5K_Customizations::flatten_array( $product_terms ), true ) ) : ?>
    <h2 class="s5k-email-header">
		<?php
        $team_name = S5K_Customizations::get_field_from_order( $order, 'name_of_company' )[0];
        $reg_code = get_post_meta( $order->get_id(), '_registration_code', true );
		echo sprintf( 'Order #%1$s | %2$s | %3$s', $order->get_order_number(), $reg_code, $team_name );
		?>
    </h2>
<?php elseif ( 'cheque' !== $order->get_payment_method() && in_array( 'group-registration', S5K_Customizations::flatten_array( $product_terms ), true ) ) : ?>
    <h2 class="s5k-email-header">
		<?php
		$team_name = S5K_Customizations::get_field_from_order( $order, 'name_of_company' )[0];
		$reg_code = get_post_meta( $order->get_id(), '_registration_code', true );
		echo sprintf( 'Order #%1$s | %2$s', $order->get_order_number(), $team_name );
		?>
    </h2>
<?php else : ?>
    <h2 class="s5k-email-header">
		<?php
		echo sprintf(  'Order #%1$s', $order->get_order_number() );
		?>
    </h2>
<?php endif; ?>

<?php


// Individual registration confirmation EMAIL (Delivery)
if ( ! empty( S5K_Customizations::get_field_from_order( $order, 'delivery_streetname' )[0] )
     && in_array( 'individual-registration', S5K_Customizations::flatten_array( $product_terms ), true ) ) {

    echo '<p class="s5k-delivery-note">' . esc_html( 'You’ve selected the delivery option. Your package will arrive at the address below between 27-Sept-2023 and 4-Oct-2023.' ) . '</p>';
}

// Individual registration or merch order confirmation EMAIL (Collection)
if ( ! empty( S5K_Customizations::get_field_from_order( $order, 'collection_location' )[0] )
     && in_array( 'individual-registration', S5K_Customizations::flatten_array( $product_terms ), true ) ) {

	echo '<p class="s5k-delivery-note">' . esc_html( sprintf( 'Collection 30-Sept-2023 at %1$s from 10AM to 4PM', S5K_Customizations::get_field_from_order( $order, 'collection_location' )[0] )  ) . '</p>';

	echo '<p class="s5k-collection-note" style="font-weight: bold">Note: you will need to present this email confirmation, along with a valid photo ID when collecting.</p>';
}

// Group registration or merch order confirmation EMAIL (other payment method/cheque)
if ( 'cheque' === $order->get_payment_method()
     && in_array( 'group-registration', S5K_Customizations::flatten_array( $product_terms ), true ) ) {

    echo '<p class="s5k-collection-message">Our team will contact you within 48 hours to facilitate payment. Once your payment is received, your registration will be processed</p>';

    echo '<p class="s5k-collection-location">' . esc_html( sprintf( 'Collection 30-Sept-2023 at %1$s from 10AM to 4PM', S5K_Customizations::get_field_from_order( $order, 'collection_location' )[0] ) ) . '</p>';

    echo '<p class="s5k-collection-note" style="font-weight: bold">Note: you will need to present this email confirmation, along with a valid photo ID when collecting.</p>';
}

if ( 'cheque' !== $order->get_payment_method()
     && ! empty( S5K_Customizations::get_field_from_order( $order, 'name_of_company' )[0] )
     && in_array( 'group-registration', S5K_Customizations::flatten_array( $product_terms ), true ) ) {

	echo '<p class="s5k-collection-location">' . esc_html( sprintf( 'Collection 30-Sept-2023 at %1$s from 10AM to 4PM', S5K_Customizations::get_field_from_order( $order, 'collection_location' )[0] ) ) . '</p>';

	echo '<p class="s5k-collection-note" style="font-weight: bold">Note: you will need to present this email confirmation, along with a valid photo ID when collecting.</p>';
}

?>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$order,
				array(
					'show_sku'      => $sent_to_admin,
					'show_image'    => false,
					'image_size'    => array( 32, 32 ),
					'plain_text'    => $plain_text,
					'sent_to_admin' => $sent_to_admin,
				)
			);
			?>
		</tbody>
		<tfoot>
			<?php
			$item_totals = $order->get_order_item_totals();

			if ( $item_totals ) {
				$i = 0;
				foreach ( $item_totals as $total ) {
					$i++;
					?>
					<tr>
						<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['label'] ); ?></th>
						<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
				}
			}
			if ( $order->get_customer_note() ) {
				?>
				<tr>
					<th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Note:', 'woocommerce' ); ?></th>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
				</tr>
				<?php
			}
			?>
		</tfoot>
	</table>
</div>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
