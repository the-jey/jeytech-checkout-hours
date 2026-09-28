(function () {
 'use strict';
 let pending = false;
 function placeCheckoutNotice() {
  const notice = document.querySelector('[data-jeytech-ch-checkout]');
  const checkout = document.querySelector('.wp-block-woocommerce-checkout');
  if (notice && checkout) checkout.insertAdjacentElement('beforebegin', notice);
 }
 async function refresh() {
  placeCheckoutNotice();
  const notices = Array.from(document.querySelectorAll('[data-jeytech-ch-status]'));
  if (!notices.length || pending) return;
  pending = true;
  try {
   const url = new URL(notices[0].dataset.url, window.location.href);
   url.searchParams.set('_ch', Date.now().toString());
   const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
   if (!response.ok) return;
   const status = await response.json();
   notices.forEach(function (notice) {
    notice.textContent = typeof status.message === 'string' ? status.message : '';
    notice.hidden = !notice.textContent;
   });
  } catch (_) { /* Native checkout still enforces the schedule on the server. */ }
  finally { pending = false; }
 }
 refresh();
 setInterval(refresh, 30000);
 document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
 window.addEventListener('focus', refresh);
 document.addEventListener('DOMContentLoaded', refresh);
}());
