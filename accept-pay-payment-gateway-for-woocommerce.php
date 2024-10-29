<?php
/**
 * Plugin Name: Accept Pay Payment Gateway for WooCommerce
 * Plugin URI: https://www.accept.io/
 * Description: WooCommerce Plugin for accepting payment through Accept Pay Gateway.
 * Version: 1.0.0
 * Author: Accept Ltd
 * Author URI: https://www.accept.io
 * Contributors: patsatech
 * Requires at least: 4.5
 * Tested up to: 6.0.2
 * WC requires at least: 3.0.0
 * WC tested up to: 6.2.1
 *
 * Text Domain: accept-pay-payment-gateway-for-woocommerce
 * Domain Path: /lang/
 *
 * @package Accept Pay Payment Gateway for WooCommerce
 * @author Accept Ltd
 */

add_action('plugins_loaded', 'init_woocommerce_acceptpay', 0);

function init_woocommerce_acceptpay() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }

	load_plugin_textdomain('accept-pay-payment-gateway-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/lang');

	class woocommerce_acceptpay extends WC_Payment_Gateway {

		public function __construct() {
			global $woocommerce;

			$this->id			= 'acceptpay';
			$this->method_title = __( 'Accept Pay', 'accept-pay-payment-gateway-for-woocommerce' );
			$this->icon			= apply_filters( 'woocommerce_acceptpay_icon', '' );
			$this->has_fields 	= false;

			// Load the form fields.
			$this->accept_pay_init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user set variables
			$this->title 		= $this->settings['title'];
			$this->description 	= $this->settings['description'];
			$this->vendor_name  = $this->settings['vendorname'];
			$this->vendor_pass  = $this->settings['vendorpass'];
			$this->mode         = $this->settings['mode'];
			$this->apply3d      = $this->settings['apply3d'];
			$this->transtype    = $this->settings['transtype'];
			$this->vendoremail  = $this->settings['vendoremail'];
			$this->sendemails   = $this->settings['sendemails'];
			$this->emailmessage = $this->settings['emailmessage'];
			$this->send_shipping= $this->settings['send_shipping'];
			$this->notify_url   = str_replace( 'https:', 'http:', home_url( '/wc-api/woocommerce_acceptpay' ) );

			// Actions
			add_action( 'init', array( $this, 'accept_pay_successful_request' ) );
			add_action( 'woocommerce_api_woocommerce_acceptpay', array( $this, 'accept_pay_successful_request' ) );
			add_action( 'woocommerce_receipt_acceptpay', array( $this, 'accept_pay_receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	    }

		/**
		 * get_icon function.
		 *
		 * @access public
		 * @return string
		 */
		function get_icon() {
			global $woocommerce;
			
			$icon = '<img src="' . $this->accept_pay_force_ssl( plugins_url( '/images/accept-pay.png', __FILE__ ) ) . '" alt="' . $this->title . '" />';

			return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
		}

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @since 1.0.0
		 */
		public function admin_options() {

	    	?>
	    	<h3><?php _e('Accept Pay', 'accept-pay-payment-gateway-for-woocommerce'); ?></h3>
	    	<p><?php _e('Accept Pay works by sending the user to Accept Pay to enter their payment information.' , 'accept-pay-payment-gateway-for-woocommerce'); ?></p>
	    	<table class="form-table">
	    	<?php

	    		// Generate the HTML For the settings form.
	    		$this->generate_settings_html();

	    	?>
			</table><!--/.form-table-->
	    	<?php
	    } // End admin_options()

		/**
	     * Initialise Gateway Settings Form Fields
	     */
	    function accept_pay_init_form_fields() {

	    	$this->form_fields = array(
				'enabled' => array(
					'title' => __( 'Enable/Disable', 'accept-pay-payment-gateway-for-woocommerce' ),
					'type' => 'checkbox',
					'label' => __( 'Enable Accept Pay', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => 'yes',
	        		'desc_tip'    => true
				),
				'title' => array(
					'title' => __( 'Title', 'accept-pay-payment-gateway-for-woocommerce' ),
					'type' => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => __( 'Card', 'accept-pay-payment-gateway-for-woocommerce' ),
	          		'desc_tip'    => true
				),
				'description' => array(
					'title' => __( 'Description', 'accept-pay-payment-gateway-for-woocommerce' ),
					'type' => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => __("All transactions are secure and encrypted.", 'accept-pay-payment-gateway-for-woocommerce'),
	          		'desc_tip'    => true
				),
				'vendorname' => array(
					'title' => __( 'Vendor Name', 'accept-pay-payment-gateway-for-woocommerce' ),
					'type' => 'text',
					'description' => __( 'Please enter your vendor name provided by Accept Pay.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => '',
					'desc_tip'    => true
				),
				'vendorpass' => array(
					'title' => __( 'Encryption Password', 'accept-pay-payment-gateway-for-woocommerce' ),
					'type' => 'text',
					'description' => __( 'Please enter your encryption password provided by Accept Pay.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => '',
					'desc_tip'    => true
				),
				'vendoremail' => array(
					'title' => __( 'Vendor E-Mail', 'accept-pay-payment-gateway-for-woocommerce' ),
					'type' => 'text',
					'description' => __( 'An e-mail address on which you can be contacted when a transaction completes.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => '',
					'desc_tip'    => true
				),
				'sendemails' => array(
					'title' => __('Send E-Mail', 'accept-pay-payment-gateway-for-woocommerce'),
					'type' => 'select',
					'options' => array(
						'0' => 'No One',
						'1' => 'Customer and Vendor',
						'2' => 'Vendor Only'
					),
					'description' => __( 'Who to send e-mails to.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => '2',
					'desc_tip'    => true
				),
				'emailmessage' => array(
					'title' => __( 'Customer E-Mail Message', 'accept-pay-payment-gateway-for-woocommerce' ),
					'type' => 'textarea',
					'description' => __( 'A message to the customer which is inserted into the successful transaction e-mails only.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => '',
					'desc_tip'    => true
				),
				'mode' => array(
					'title' => __('Mode Type', 'accept-pay-payment-gateway-for-woocommerce'),
					'type' => 'select',
					'options' => array(
						'test' => 'Test',
						'live' => 'Live'
					),
					'description' => __( 'Select Simulator, Test or Live modes.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => 'live',
					'desc_tip'    => true
				),
				'apply3d' => array(
					'title' => __('Apply 3D Secure', 'accept-pay-payment-gateway-for-woocommerce'),
					'type' => 'select',
					'options' => array(
						'1' => 'Yes',
						'0' => 'No'
					),
					'description' => __( 'Select whether you would like to do 3D Secure Check on Transactions.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => '0',
					'desc_tip'    => true
				),
				'send_shipping' => array(
					'title' => __('Select Shipping Address', 'accept-pay-payment-gateway-for-woocommerce'),
					'type' => 'select',
					'options' => array(
						'auto' => 'Auto',
						'yes' => 'Billing Address'
					),
					'description' => __( 'Select Auto if you want the plugin to decide which address to send based on type of Product. And select Billing Address if you want the plugin to send Billing Address irrespective of the type to Product.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => 'auto',
					'desc_tip'    => true
				),
				'transtype'	=> array(
					'title' => __('Transition Type', 'accept-pay-payment-gateway-for-woocommerce'),
					'type' => 'select',
					'options' => array(
						'PAYMENT' => __('Payment', 'accept-pay-payment-gateway-for-woocommerce'),
						'DEFERRED' => __('Deferred', 'accept-pay-payment-gateway-for-woocommerce'),
						'AUTHENTICATE' => __('Authenticate', 'accept-pay-payment-gateway-for-woocommerce')
					),
					'description' => __( 'Select Payment, Deferred or Authenticated.', 'accept-pay-payment-gateway-for-woocommerce' ),
					'default' => 'PAYMENT',
					'desc_tip'    => true
				),
			);
		} // End accept_pay_init_form_fields()

	    /**
		 * There are no payment fields for acceptpay, but we want to show the description if set.
		 **/
	    function payment_fields() {
	    	if ($this->description) echo wpautop(wptexturize($this->description));
	    }


		/**
		 * Generate the nochex button link
		 **/
	  	public function accept_pay_generate_form( $order_id ) {

			global $woocommerce;

			$order = new WC_Order( $order_id );

			if( $this->mode == 'test' ){
				$gateway_url = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
			}else if( $this->mode == 'live' ){
				$gateway_url = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
			}

			$basket = '';

			// Cart Contents
			$item_loop = 0;


			foreach ( $order->get_items() as $item_id => $item ) {
				$item_loop++;

				$product_id      = $item->get_product_id();
				$variation_id    = $item->get_variation_id();
				$product         = $item->get_product(); // Product object gives you access to all product data
				$product_name    = $item->get_name();
				$quantity        = $item->get_quantity();
				$subtotal        = $item->get_subtotal();
				$total           = $item->get_total();
				$tax_subtotal    = $item->get_subtotal_tax();
				$tax_class       = $item->get_tax_class();
				$tax_status      = $item->get_tax_status();
				$all_meta_data   = $item->get_meta_data();
				$your_meta_data  = $item->get_meta( '_your_meta_key', true );
				$product_type    = $item->get_type();

				
				$item_cost = $item->get_subtotal()/$quantity;
				$item_total_inc_tax = 0;
				$item_total = $item->get_subtotal();
				//$item_sub_total =

				$item_tax = 0;
				if($item_loop > 1){
					$basket .= ':';
				}

				$sku              = $product ? $product->get_sku() : '';

				$basket .= str_replace(':',' = ',$sku).str_replace(':',' = ',$product_name).':'.$quantity.':'.$item_cost.':'.$item_tax.':'.number_format( $item_cost+$item_tax, 2, '.', '' ).':'.$item_total;

			 
			}



			// Fees
			if ( sizeof( $order->get_fees() ) > 0 ) {
				foreach ( $order->get_fees() as $order_item ) {
					$item_loop++;

					$basket .= ':'.str_replace(':',' = ',$order_item['name']).':1:'.$order_item['line_total'].':---:'.$order_item['line_total'].':'.$order_item['line_total'];
				}
			}

			// Shipping Cost item - paypal only allows shipping per item, we want to send shipping for the order
			if ( $order->get_total_shipping() > 0 ) {
				$item_loop++;

				$ship_exc_tax = number_format( $order->get_total_shipping(), 2, '.', '' );

				$basket .= ':'.__( 'Shipping via', 'accept-pay-payment-gateway-for-woocommerce' ) . ' ' . str_replace(':',' = ',ucwords( $order->get_shipping_method() )).':1:'.$ship_exc_tax.':'.$order->get_shipping_tax().':'.number_format( $ship_exc_tax+$order->get_shipping_tax(), 2, '.', '' ).':'.number_format( $order->get_total_shipping()+$order->get_shipping_tax(), 2, '.', '' );
			}

			// Discount
			if ( $order->get_total_discount() > 0 ){
				$item_loop++;

				$basket .= ':Discount:---:---:---:---:-'.$order->get_total_discount();
			}
			
			// Tax
			if ( $order->get_total_tax() > 0 ) {
				$item_loop++;

				$basket .= ':Tax:---:---:---:---:'.$order->get_total_tax();
			}

			$item_loop++;

			$basket .= ':Order Total:---:---:---:---:'.$order->get_total();

			$basket = $item_loop.':'.$basket;

			$time_stamp = date("ymdHis");
			$orderid = $this->vendor_name . "-" . $time_stamp . "-" . $order_id;

			$acceptpay_arg['ReferrerID'] 				= 'CC923B06-40D5-4713-85C1-700D690550BF';
			$acceptpay_arg['Amount'] 					= $order->get_total();
			$acceptpay_arg['CustomerName']				= substr($order->get_billing_first_name().' '.$order->get_billing_last_name(), 0, 100);
			$acceptpay_arg['CustomerEMail'] 			= substr($order->get_billing_email(), 0, 255);
			$acceptpay_arg['BillingSurname'] 			= substr($order->get_billing_last_name(), 0, 20);
			$acceptpay_arg['BillingFirstnames'] 		= substr($order->get_billing_first_name(), 0, 20);
			$acceptpay_arg['BillingAddress1'] 			= substr($order->get_billing_address_1(), 0, 100);
			$acceptpay_arg['BillingAddress2'] 			= substr($order->get_billing_address_2(), 0, 100);
			$acceptpay_arg['BillingCity'] 				= substr($order->get_billing_city(), 0, 40);

			if( $order->get_billing_country() == 'US' ){
	        	$acceptpay_arg['BillingState'] 			= $order->get_billing_state();
			}else{
	        	$acceptpay_arg['BillingState'] 			= '';
			}

			$acceptpay_arg['BillingPostCode'] 			= substr($order->get_billing_postcode(), 0, 10);
			$acceptpay_arg['BillingCountry'] 			= $order->get_billing_country();
			$acceptpay_arg['BillingPhone'] 				= substr($order->get_billing_phone(), 0, 20);

			if( $this->accept_pay_cart_has_virtual_product() == true || $this->send_shipping == 'yes'){

				$acceptpay_arg['DeliverySurname'] 		= $order->get_billing_last_name();
				$acceptpay_arg['DeliveryFirstnames'] 	= $order->get_billing_first_name();
				$acceptpay_arg['DeliveryAddress1'] 		= $order->get_billing_address_1();
				$acceptpay_arg['DeliveryAddress2'] 		= $order->get_billing_address_2();
				$acceptpay_arg['DeliveryCity'] 			= $order->get_billing_city();

				if( $order->get_billing_country() == 'US' ){
					$acceptpay_arg['DeliveryState'] 	= $order->get_billing_state();
				}else{
					$acceptpay_arg['DeliveryState'] 	= '';
				}

				$acceptpay_arg['DeliveryPostCode'] 		= $order->get_billing_postcode();
				$acceptpay_arg['DeliveryCountry'] 		= $order->get_billing_country();

			}else{

				$acceptpay_arg['DeliverySurname'] 		= $order->get_shipping_last_name();
				$acceptpay_arg['DeliveryFirstnames'] 	= $order->get_shipping_first_name();
				$acceptpay_arg['DeliveryAddress1'] 		= $order->get_shipping_address_1();
				$acceptpay_arg['DeliveryAddress2'] 		= $order->get_shipping_address_2();
				$acceptpay_arg['DeliveryCity'] 			= $order->get_shipping_city();

				if( $order->get_shipping_country() == 'US' ){
					$acceptpay_arg['DeliveryState'] 	= $order->get_shipping_state();
				}else{
					$acceptpay_arg['DeliveryState'] 	= '';
				}

				$acceptpay_arg['DeliveryPostCode'] 		= $order->get_shipping_postcode();
				$acceptpay_arg['DeliveryCountry'] 		= $order->get_shipping_country();
			}

			$acceptpay_arg['DeliveryPhone'] 			= substr($order->get_billing_phone(), 0, 20);
			$acceptpay_arg['FailureURL'] 				= $this->notify_url;
			$acceptpay_arg['SuccessURL'] 				= $this->notify_url;
			$acceptpay_arg['Description'] 				= sprintf(__('Order #%s' , 'accept-pay-payment-gateway-for-woocommerce'), ltrim( $order->get_order_number(), '#' ));
			$acceptpay_arg['Currency'] 					= get_woocommerce_currency();
			$acceptpay_arg['VendorTxCode'] 				= $orderid;
			$acceptpay_arg['VendorEMail'] 				= $this->vendoremail;
			$acceptpay_arg['SendEMail'] 				= $this->sendemails;

			if( $order->get_shipping_state() == 'US' ){
	        	$acceptpay_arg['eMailMessage']			= $this->emailmessage;
			}

			$acceptpay_arg['Apply3DSecure'] 			= $this->apply3d;
			$acceptpay_arg['Basket'] 					= $basket;

			$post_values = "";
			foreach( $acceptpay_arg as $key => $value ) {
				$post_values .= "$key=" . trim( $value ) . "&";
			}

			$post_values = substr($post_values, 0, -1);

			$params['VPSProtocol'] = "3.00";
			$params['TxType'] = $this->transtype;
			$params['Vendor'] = $this->vendor_name;
	    	$params['Crypt'] = $this->accept_pay_encryptAndEncode($post_values);

			$acceptpay_arg_array = array();

			foreach ($params as $key => $value) {
				$acceptpay_arg_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
			}
			
			wc_enqueue_js('
				jQuery("body").block({
						message: "'.__('Thank you for your order. We are now redirecting you to Accept Pay to make payment.', 'accept-pay-payment-gateway-for-woocommerce').'",
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
					        padding:        20,
					        textAlign:      "center",
					        color:          "#555",
					        border:         "3px solid #aaa",
					        backgroundColor:"#fff",
					        cursor:         "wait",
					        lineHeight:		"32px"
					    }
					});
				jQuery("#submit_acceptpay_payment_form").click();
			');
			
			return  '<form action="'.esc_url( $gateway_url ).'" method="post" id="acceptpay_payment_form">
					' . implode('', $acceptpay_arg_array) . '
					<input type="submit" class="button" id="submit_acceptpay_payment_form" value="'.__('Pay via Accept Pay', 'accept-pay-payment-gateway-for-woocommerce').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'accept-pay-payment-gateway-for-woocommerce').'</a>
				</form>';

		}

		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );

			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);

		}

		/**
		 * accept_pay_receipt_page
		 **/
		function accept_pay_receipt_page( $order ) {

			echo '<p>'.__('Thank you for your order, please click the button below to pay with Accept Pay.', 'accept-pay-payment-gateway-for-woocommerce').'</p>';

			echo  $this->accept_pay_generate_form( $order );

		}


		/**
		 * Successful Payment!
		 **/
		function accept_pay_successful_request() {
			global $woocommerce;

			if ( isset($_REQUEST['crypt']) && !empty($_REQUEST['crypt']) ) {

				$transaction_response = $this->accept_pay_decode(str_replace(' ', '+',$_REQUEST['crypt']));

				$order_id = explode('-',$transaction_response['VendorTxCode']);

				if ( $transaction_response['Status'] == 'OK' || $transaction_response['Status'] == 'AUTHENTICATED'|| $transaction_response['Status'] == 'REGISTERED' ) {

					$order = new WC_Order( $order_id[2] );

					$order->add_order_note(sprintf(__('Accept Pay Payment Completed. The Reference Number is %s.', 'accept-pay-payment-gateway-for-woocommerce'), $transaction_response['VPSTxId']));

					$order->payment_complete();

					wp_redirect( $this->get_return_url( $order ) ); exit;

				}else{

					wc_add_notice( sprintf(__('Transaction Failed. The Error Message was %s', 'accept-pay-payment-gateway-for-woocommerce'), $transaction_response['StatusDetail'] ), $notice_type = 'error' );

					wp_redirect( get_permalink(get_option( 'woocommerce_checkout_page_id' )) ); exit;

				}
			}
		}

		/**
		* Check if the cart contains virtual product
		*
		* @return bool
		*/
		private function accept_pay_cart_has_virtual_product() {
			global $woocommerce;

			$has_virtual_products = false;

			$virtual_products = 0;

			$products = $woocommerce->cart->get_cart();

			foreach( $products as $product ) {

				$product_id = $product['product_id'];
				$is_virtual = get_post_meta( $product_id, '_virtual', true );
				// Update $has_virtual_product if product is virtual
				if( $is_virtual == 'yes' )
				$virtual_products += 1;
			}
			if( count($products) == $virtual_products ){
				$has_virtual_products = true;
			}

			return $has_virtual_products;

		}

		private function accept_pay_encryptAndEncode($strIn) {
			$strIn = $this->accept_pay_pkcs5_pad($strIn, 16);
			return "@".bin2hex(openssl_encrypt($strIn, 'AES-128-CBC', $this->vendor_pass, OPENSSL_RAW_DATA, $this->vendor_pass));
		}

		private function accept_pay_decodeAndDecrypt($strIn) {
			$strIn = substr($strIn, 1);
			$strIn = pack('H*', $strIn);
			return openssl_decrypt($strIn, 'AES-128-CBC', $this->vendor_pass, OPENSSL_RAW_DATA, $this->vendor_pass);
		}


		private function accept_pay_pkcs5_pad($text, $blocksize)	{
			$pad = $blocksize - (strlen($text) % $blocksize);
			return $text . str_repeat(chr($pad), $pad);
		}

		public function accept_pay_decode($strIn) {
			$decodedString = $this->accept_pay_decodeAndDecrypt($strIn);
			parse_str($decodedString, $sagePayResponse);
			return $sagePayResponse;
		}

		private function accept_pay_force_ssl($url){

			if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$url = str_replace( 'http:', 'https:', $url );
			}

			return $url;
		}

	}

	/**
	 * Add the gateway to WooCommerce
	 **/
	function add_acceptpay_gateway( $methods ) {
		$methods[] = 'woocommerce_acceptpay'; return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_acceptpay_gateway' );

}
