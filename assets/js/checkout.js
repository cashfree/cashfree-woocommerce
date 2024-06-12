/* global wc_cashfree_checkout_params */
jQuery(document).ready(initCashfreeCheckout);
function initCashfreeCheckout() {
	if(typeof wc_cashfree_checkout_params != 'undefined') {
		const cashfree = Cashfree({
			mode: wc_cashfree_checkout_params.environment,
		});
		if(wc_cashfree_checkout_params.order_in_context) {
			let checkoutOptions = {
				paymentSessionId: wc_cashfree_checkout_params.payment_session_id,
				redirectTarget: "_modal",
			};
			cashfree.checkout(checkoutOptions).then((result) => {
				let in_context_form;
				if (result.error) {
					// This will be true whenever user clicks on close icon inside the modal or any error happens during the payment
					in_context_form = document.createElement('FORM');
					in_context_form.method = 'POST';
					in_context_form.action = wc_cashfree_checkout_params.callback_url;
					document.body.appendChild(in_context_form);
					in_context_form.submit();
				}
				if(result.paymentDetails){
					// This will be called whenever the payment is completed irrespective of transaction status
					in_context_form=document.createElement('FORM');
					in_context_form.method='POST';
					in_context_form.action=wc_cashfree_checkout_params.callback_url;
					document.body.appendChild(in_context_form);
					in_context_form.submit();
				}
			});
		} else {
			return cashfree.checkout({
				paymentSessionId: wc_cashfree_checkout_params.payment_session_id,
				redirectTarget: "_self",
				platformName: "wc",
			});
		}
	}
	
}