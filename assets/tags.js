/* Loads Google Tag Manager (or plain GA4 when no GTM) from the IDs saved in Admin -> Settings.
   Kept as an external file so the site's Content-Security-Policy needs no inline scripts. */
(function () {
  var me = document.currentScript;
  if (!me) return;
  var gtm = me.getAttribute("data-gtm"),
    ga = me.getAttribute("data-ga");
  var w = window;
  w.dataLayer = w.dataLayer || [];
  function load(src) {
    var s = document.createElement("script");
    s.async = true;
    s.src = src;
    document.head.appendChild(s);
  }
  if (gtm && /^GTM-[A-Z0-9]{4,10}$/.test(gtm)) {
    w.dataLayer.push({ "gtm.start": Date.now(), event: "gtm.js" });
    load(
      "https://www.googletagmanager.com/gtm.js?id=" + encodeURIComponent(gtm),
    );
  } else if (ga && /^G-[A-Z0-9]{6,14}$/.test(ga)) {
    w.gtag = function () {
      w.dataLayer.push(arguments);
    };
    w.gtag("js", new Date());
    w.gtag("config", ga);
    load(
      "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(ga),
    );
  }
})();
