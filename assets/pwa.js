(function () {
  'use strict';
  function status() {
    var el = document.getElementById('network-status');
    if (!el) return;
    el.textContent = navigator.onLine ? '● Online' : '● Offline · cache';
    el.classList.toggle('offline', !navigator.onLine);
  }
  window.addEventListener('online', status); window.addEventListener('offline', status); status();
  if ('serviceWorker' in navigator) window.addEventListener('load', function () { navigator.serviceWorker.register('service-worker.js').catch(function () {}); });
}());
