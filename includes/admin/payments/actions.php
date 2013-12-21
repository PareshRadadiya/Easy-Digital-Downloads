<?php
/**
 * Admin Payment Actions
 *
 * @package     EDD
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2013, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Process the payment details edit
 *
 * @access      private
 * @since       1.9
 * @return      void
*/
function edd_update_payment_details( $data ) {

	if( ! current_user_can( 'edit_shop_payment', $data['edd_payment_id' ] ) ) {
		wp_die( __( 'You do not have permission to edit this payment record', 'edd' ), __( 'Error', 'edd' ) );
	}

	check_admin_referer( 'edd_update_payment_details_nonce' );

	$payment_id = absint( $data['edd_payment_id']      );
	$status     = $data['edd-payment-status'];
	$user_id    = absint( $data['edd-payment-user-id'] );
	$date       = sanitize_text_field( $data['edd-payment-date'] );
	$hour       = sanitize_text_field( $data['edd-payment-time-hour'] );
	$minute     = sanitize_text_field( $data['edd-payment-time-min'] );
	$email      = sanitize_text_field( $data['edd-payment-user-email'] );
	$names      = sanitize_text_field( $data['edd-payment-user-name'] );
	$address    = array_map( 'trim', $data['edd-payment-address'][0] );

	$total      = edd_sanitize_amount( $_POST['edd-payment-total'] );
	$tax        = isset( $_POST['edd-payment-tax'] ) ? edd_sanitize_amount( $_POST['edd-payment-tax'] ) : 0;
	$meta       = edd_get_payment_meta( $payment_id );

	// Setup date from input values
	$date       = date( 'Y-m-d', strtotime( $date ) ) . ' ' . $hour . ':' . $minute . ':00';

	// Setup first and last name from input values
	$names      = explode( ' ', $names );
	$first_name = ! empty( $names[0] ) ? $names[0] : '';
	$last_name  = ! empty( $names[1] ) ? $names[1] : '';

	// Retrieve meta
	$meta       = edd_get_payment_meta( $payment_id );
	$user_info  = edd_get_payment_meta_user_info( $payment_id );

	// Update main payment record
	wp_update_post( array(
		'ID'        => $payment_id,
		'post_date' => $date
	) );

	// Set new status
	edd_update_payment_status( $payment_id, $status );

	// Set new meta values
	$user_info['id']         = $user_id;
	$user_info['first_name'] = $first_name;
	$user_info['last_name']  = $last_name;
	$user_info['address']    = $address;
	$meta['user_info']       = $user_info;
	$meta['tax']             = $tax;

	// Check for payment notes
	if ( ! empty( $data['edd-payment-note'] ) ) {

		$note    = wp_kses( $data['edd-payment-note'], array() );
		$note_id = edd_insert_payment_note( $payment_id, $note );

	}

	update_post_meta( $payment_id, '_edd_payment_user_id',    $user_id );
	update_post_meta( $payment_id, '_edd_payment_user_email', $email   );
	update_post_meta( $payment_id, '_edd_payment_meta',       $meta    );
	update_post_meta( $payment_id, '_edd_payment_total',      $total   );

	wp_safe_redirect( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&edd-message=details-updated&id=' . $payment_id ) );
	exit;
}
add_action( 'edd_update_payment_details', 'edd_update_payment_details' );