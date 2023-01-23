window.addEventListener("load", function () {
  const widgetConfig = {};

  // To initiate Widget
  const cashfree = CF_Widget(widgetConfig); // <merchantId>

  // To call offersWidget by passing Merchant's Config
  cashfree.load();
});
