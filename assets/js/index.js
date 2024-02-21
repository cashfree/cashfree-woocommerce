const settings_cashfree = window.wc.wcSettings.getSetting('cashfree_data', {});
const label_cashfree = window.wp.htmlEntities.decodeEntities(settings_cashfree.title) || window.wp.i18n.__('Pay with Cashfree', 'cashfree');

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelCashfree = (props) => {
	var icon = React.createElement('img', {
		src: 'https://cashfreelogo.cashfree.com/cashfreepayments/logopng1x/Cashfree_Payments_Logo.png',
		style: {
			display: 'inline',
			marginLeft: '5px', // Adjust the value as needed
		},
	});
	var span = React.createElement('span', {
		className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
	}, window.wp.htmlEntities.decodeEntities(settings_cashfree.title) || defaultLabel, icon);
	return span;
};

const LabelDesc = (props) => {
	var span = React.createElement('span', {
		className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
	}, window.wp.htmlEntities.decodeEntities(settings_cashfree.description));
	return span;
};

const CashfreeCheckout = {
	name: 'cashfree',
	label: React.createElement(LabelCashfree, null),
	content: React.createElement(LabelDesc, null),
	edit: React.createElement(LabelCashfree, null),
	icons: null,
	canMakePayment: () => true,
	ariaLabel: label_cashfree,
	supports: {
		features: settings_cashfree.supports,
	},
};
window.wc.wcBlocksRegistry.registerPaymentMethod( CashfreeCheckout );