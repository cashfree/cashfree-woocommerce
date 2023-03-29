/* global wc_cashfree_checkout_params */
jQuery(document).ready(initCashfreeCheckout);
function woocommerceFormSubmit(data, transactionId, transactionStatus, url) {
	const form = document.createElement("form");
	form.name = "cashfreeForm";
	form.method = "POST";
	form.action = url;
  
	const createInput = (name, value) => {
	  const input = document.createElement("input");
	  input.type = "hidden";
	  input.name = name;
	  input.value = value;
	  form.appendChild(input);
	};
  
	createInput("order_id", data.order.orderId);
	createInput("transaction_status", transactionStatus);
	createInput("order_status", data.order.status);
	createInput("transaction_id", transactionId);
	createInput("transaction_msg", data.order.message);
  
	document.body.appendChild(form);
	form.submit();
  }
  
  function initCashfreeCheckout() {
	console.log(wc_cashfree_checkout_params);
	if (!wc_cashfree_checkout_params.in_context) {
	  const cashfree = new Cashfree(wc_cashfree_checkout_params.token);
	  return cashfree.redirect();
	}
  
	let dismissflag = true;
	const env = wc_cashfree_checkout_params.environment;
	const successCallback = (data) => {
	  dismissflag = false;
	  const { transactionId, txStatus } = data.transaction;
	  woocommerceFormSubmit(
		data,
		transactionId,
		txStatus,
		wc_cashfree_checkout_params.capture_url
	  );
	};
	const failureCallback = (data) => {
	  dismissflag = false;
	  let transactionId = "";
	  let transactionStatus = "ERROR";
	  if (data.transaction && data.order.status !== "ERROR") {
		transactionId = data.transaction.transactionId;
		transactionStatus = data.transaction.txStatus;
	  }
	  if (data.order.status === "ERROR") {
		data.order.message = data.order.errorText;
	  }
	  woocommerceFormSubmit(
		data,
		transactionId,
		transactionStatus,
		wc_cashfree_checkout_params.cancel_url
	  );
	};
	const dismissCallback = () => {
	  if (dismissflag) {
		const form = document.createElement("form");
		form.name = "cashfreeDismissForm";
		form.method = "POST";
		form.action = wc_cashfree_checkout_params.dismiss_url;
		document.body.appendChild(form);
		form.submit();
	  }
	};
	Pippin.setOrderMetaPlatform(
	  `jswc-d-${wc_cashfree_checkout_params.cf_version}-${wc_cashfree_checkout_params.woo_version}`
	);
	Pippin(env, wc_cashfree_checkout_params.token, successCallback, failureCallback, dismissCallback);
}