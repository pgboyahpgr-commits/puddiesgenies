(function() {
  'use strict';
  var lastOrderCount = 0;
  var notifiedIds = {};

  function loadNotified() {
    try { var d = JSON.parse(localStorage.getItem('smak_notified_orders') || '{}'); notifiedIds = d; } catch(e) {}
  }
  function saveNotified() {
    try { localStorage.setItem('smak_notified_orders', JSON.stringify(notifiedIds)); } catch(e) {}
  }
  loadNotified();

  function playSound() {
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.frequency.value = 800;
      osc.type = 'sine';
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.3);
      setTimeout(function() {
        var osc2 = ctx.createOscillator();
        var gain2 = ctx.createGain();
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.frequency.value = 1000;
        osc2.type = 'sine';
        gain2.gain.setValueAtTime(0.3, ctx.currentTime);
        gain2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
        osc2.start(ctx.currentTime);
        osc2.stop(ctx.currentTime + 0.3);
      }, 200);
    } catch(e) {}
  }

  function pollOrders() {
    fetch('/api/orders.php?count=1&_=' + Date.now(), { cache: 'no-store' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data && data.total !== undefined) {
          if (lastOrderCount > 0 && data.total > lastOrderCount) {
            var newOrders = data.total - lastOrderCount;
            if (document.hidden === undefined || document.hidden) {
              playSound();
            }
            if (typeof window.showToast === 'function') {
              window.showToast(newOrders + ' new order' + (newOrders > 1 ? 's' : '') + '!', 'success');
            }
          }
          lastOrderCount = data.total;
          var badge = document.getElementById('orderBadge');
          if (badge) {
            var pending = data.pending || 0;
            badge.textContent = pending > 0 ? pending : '';
            badge.style.display = pending > 0 ? 'inline' : 'none';
          }
        }
      })
      .catch(function() {});
    setTimeout(pollOrders, 8000);
  }

  if (document.getElementById('ordersPage')) {
    setTimeout(pollOrders, 3000);
  }
})();
