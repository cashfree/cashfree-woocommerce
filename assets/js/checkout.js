/* global wc_cashfree_checkout_params */
jQuery(document).ready(initCashfreeCheckout);
function initCashfreeCheckout() {
	if(typeof wc_cashfree_checkout_params != 'undefined') {
		const cashfree = Cashfree({
			mode: wc_cashfree_checkout_params.environment,
		});
		
		return cashfree.checkout({
			paymentSessionId: wc_cashfree_checkout_params.payment_session_id,
			redirectTarget: "_self",
			platformName: "wc",
		});
	}
	
}