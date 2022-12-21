// window.onload = () => {
//   // it will check for id="cashfree-offer-widget"
//   let id = "cashfree-offer-widget";

//   window.abc({
//     id,
//   });
// };

const merchantConfig = {
  id: "cashfree-offer-widget", // id on which widget will load
  token: "abc",
};

// To initiate Widget
const cashfree = CF_Offers(); // <merchantId>

// To call offersWidget by passing Merchant's Config
cashfree.offersWidget(merchantConfig);

// document.head.innerHTML +=
//   '<link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet" />';
