<?php 
створення кнопки на ордерах для їх видалення, та перепису у новий 
// Displaying the form fields (buttons and hidden fields)
add_action( 'woocommerce_order_item_meta_end', 'display_remove_order_item_button', 10, 3 );
function display_remove_order_item_button( $item_id, $item, $order ){
    // Avoiding displaying buttons on email notification
    if( ! ( is_wc_endpoint_url( 'view-order' ) || is_wc_endpoint_url( 'order-received' ) ) )
        return;

    echo '<form class="cart item-'.$item_id.'" method="post" style= "margin-top:12px;">
    <input type="hidden" name="item_id" value="'.$item_id.'" />
    <input type="hidden" name="order_id" value="'.$order->get_id().'" />
    <input type="submit" class="button" name="remove_item_'.$item_id.'" value="Complete Cancellation" />
    </form>';
}
// Processing the request
add_action( 'template_redirect', 'process_remove_order_item' );
function process_remove_order_item(){
	global $woocommerce;
    // Avoiding displaying buttons on email notification
    if( ! ( is_wc_endpoint_url( 'view-order' ) || is_wc_endpoint_url( 'order-received' ) ) )
        return;

    if( isset($_POST['item_id']) && isset($_POST['remove_item_'.$_POST['item_id']]) && isset($_POST['order_id'])
    && is_numeric($_POST['order_id']) && get_post_type($_POST['order_id']) === 'shop_order' ) {
        // Get the WC_Order Object
        $order = wc_get_order( absint($_POST['order_id']) );

        // Remove the desired order item
        if( is_a($order, 'WC_Order') && is_numeric($_POST['item_id']) ) {
			// соврення нового ордера та виделення товару
			// Loop through order items
			foreach ( $order->get_items() as $item_key => $item ) {
				// Get product
				$product = $item->get_product(absint($_POST['item_id']));
				$product_id = $item->get_id();
                

		        if($product_id == absint($_POST['item_id'])){
					$backorder_order = wc_create_order();
					// Add product to 'backorder' order
					$backorder_order->add_product( $product, $item['quantity'] );
				}
				// Delete item from original order
				$order->remove_item( absint($_POST['item_id']) );
			}
            $backorder_order->update_status( 'on-hold' );
            // Recalculate and save original order
            $order->calculate_totals();
            $order->save();
            
            // Obtain necessary information
            // Get address
            $address = array(
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
                'email'      => $order->get_billing_email(),
                'phone'      => $order->get_billing_phone(),
                'address_1'  => $order->get_billing_address_1(),
                'address_2'  => $order->get_billing_address_2(),
                'city'       => $order->get_billing_city(),
                'state'      => $order->get_billing_state(),
                'postcode'   => $order->get_billing_postcode(),
                'country'    => $order->get_billing_country()
            );
    
            // Get shipping
            $shipping = array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name'  => $order->get_shipping_last_name(),
                'address_1'  => $order->get_shipping_address_1(),
                'address_2'  => $order->get_shipping_address_2(),
                'city'       => $order->get_shipping_city(),
                'state'      => $order->get_shipping_state(),
                'postcode'   => $order->get_shipping_postcode(),
                'country'    => $order->get_shipping_country()
            );
            
            // Get order currency
            $currency = $order->get_currency();
    
            // Get order payment method
            $payment_gateway = $order->get_payment_method();
            
            // Required information has been obtained, assign it to the 'backorder' order
            // Set address
            $backorder_order->set_address( $address, 'billing' );
            $backorder_order->set_address( $shipping, 'shipping' );
    
            // Set the correct currency and payment gateway
            $backorder_order->set_currency( $currency );
            $backorder_order->set_payment_method( $payment_gateway );
    
            // Calculate totals
            $backorder_order->calculate_totals();
    
            // Set order note with original ID
            $backorder_order->add_order_note( 'Automated backorder. Created from the original order ID: ' . $order_id );
		
				// Optional: give the new 'backorder' order the correct status
				//$backorder_order->update_status( 'backorder' );
		}
			// кінець
            // $order->remove_item( absint($_POST['item_id']) );
            // $order->calculate_totals();

            // Optionally display a notice
            // wc_add_notice( __('Order item removed successfully'), 'success' );
    }
}