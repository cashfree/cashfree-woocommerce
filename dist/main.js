const merchantConfig = {
  id: "cashfree-offer-widget", // id on which widget will load
  token: "abc",
};

// To initiate Widget
const cashfree = CF_Offers(); // <merchantId>

// To call offersWidget by passing Merchant's Config
cashfree.offersWidget(merchantConfig);
