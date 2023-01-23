window.addEventListener("load", function () {
  const widgetConfig = {
    clientID: "1848d0ce8441fb8ffa258bc98481",
  };

  // To initiate Widget
  const cashfree = CF_Widget(widgetConfig); // <merchantId>

  // To call offersWidget by passing Merchant's Config
  cashfree.load();
});
