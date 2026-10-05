(function () {
  "use strict";

  const PAYTM_NAME_FALLBACK = "TENVER";
  function paytmName() { return (window.TENVER_BRAND_NAME || PAYTM_NAME_FALLBACK) + " Perfumes"; }

  let paytmUpiID = "";
  let paytmUpiReady = null;

  // Same existing upi.txt use hoga
  async function loadPaytmUPI() {
    if (paytmUpiReady) {
      return paytmUpiReady;
    }

    paytmUpiReady = (async function () {
      const possiblePaths = [
        "./upi.txt",
        "../upi.txt"
      ];

      for (const path of possiblePaths) {
        try {
          const response = await fetch(
            path + "?t=" + Date.now(),
            {
              cache: "no-store"
            }
          );

          if (!response.ok) {
            continue;
          }

          const text = await response.text();
          const upi = text.trim();

          if (upi) {
            paytmUpiID = upi;
            console.log("Paytm UPI loaded:", paytmUpiID);
            return paytmUpiID;
          }
        } catch (error) {
          console.log("UPI path failed:", path);
        }
      }

      throw new Error("UPI ID not found");
    })();

    return paytmUpiReady;
  }


  // Paytm checkout
  async function openPaytm(source, paymentAmount) {

    try {
      await loadPaytmUPI();
    } catch (error) {
      alert(
        "UPI ID load nahi ho paayi.\n\n" +
        "Please check ki existing upi.txt file index.html ke correct folder me hai."
      );
      return;
    }


    const amount = Number(paymentAmount);


    if (!amount || amount <= 0) {
      alert("Payment amount invalid hai.");
      return;
    }


    /*
      Paytm deeplink
    */
    const paytmDeepLink =
      "paytmmp://pay" +
      "?pa=" + encodeURIComponent(paytmUpiID) +
      "&pn=" + encodeURIComponent(paytmName()) +
      "&am=" + encodeURIComponent(amount.toFixed(2)) +
      "&cu=INR";


    /*
      Standard UPI fallback
      Agar Paytm deeplink supported nahi hua
      to installed UPI app chooser open hoga.
    */
    const upiFallback =
      "upi://pay" +
      "?pa=" + encodeURIComponent(paytmUpiID) +
      "&pn=" + encodeURIComponent(paytmName()) +
      "&am=" + encodeURIComponent(amount.toFixed(2)) +
      "&cu=INR";


    const link = document.createElement("a");

    link.href = paytmDeepLink;
    link.style.display = "none";

    document.body.appendChild(link);


    try {
      link.click();
    } catch (error) {
      console.log("Paytm deeplink error:", error);
    }


    /*
      Fallback after 1.5 seconds
    */
    setTimeout(function () {

      try {
        window.location.href = upiFallback;
      } catch (error) {
        console.log("UPI fallback error:", error);
      }

    }, 1500);


    /*
      Remove temporary link
    */
    setTimeout(function () {

      if (document.body.contains(link)) {
        document.body.removeChild(link);
      }

    }, 2500);

  }


  /*
    Make function available to index.html
  */
  window.openPaytm = openPaytm;

})();
