(function() {
  var iframe = document.getElementById('kitchenIframe');
  var loadingEl = document.getElementById('streamLoading');
  var errorEl = document.getElementById('streamError');
  var offlineEl = document.getElementById('streamOffline');
  var liveBadge = document.getElementById('liveBadge');
  var statusText = document.getElementById('kitchenStatus');
  var tapOverlay = document.getElementById('tapToPlay');
  var streamConfigured = iframe && iframe.src && iframe.src !== '' && iframe.src !== 'about:blank';
  var hasGoneLive = false;
  var pollTimer = null;
  var lastUpdated = '';
  var initTime = Date.now();

  function showState(state) {
    if (loadingEl) loadingEl.style.display = state === 'loading' ? 'flex' : 'none';
    if (errorEl) errorEl.style.display = state === 'error' ? 'flex' : 'none';
    if (offlineEl) offlineEl.style.display = state === 'offline' ? 'flex' : 'none';
    if (liveBadge) liveBadge.style.display = state === 'live' ? 'flex' : 'none';
    if (tapOverlay) tapOverlay.style.display = state === 'tap' ? 'flex' : 'none';
    if (statusText) {
      var labels = { loading: 'Connecting...', live: '\uD83D\uDD34 LIVE', offline: '\u23F8\uFE0F Offline', error: '\u26A0\uFE0F Error', tap: '\u25B6\uFE0F Tap to play' };
      statusText.textContent = labels[state] || '';
      statusText.className = 'text-xs mt-1 ' + (state === 'live' ? 'text-red-500 font-bold' : state === 'error' ? 'text-red-400' : 'text-gray-400');
    }
  }

  function goLive() {
    if (hasGoneLive) return;
    hasGoneLive = true;
    showState('live');
  }

  function goError() {
    if (hasGoneLive) return;
    hasGoneLive = true;
    showState('error');
  }

  function goOffline() {
    showState('offline');
  }

  function onMessage(event) {
    if (event.origin.indexOf('youtube.com') === -1 && event.origin.indexOf('youtu.be') === -1) return;
    try {
      var data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
      if (data.event === 'onReady' || (data.info && typeof data.info.currentTime !== 'undefined')) {
        goLive();
      } else if (data.event === 'onError') {
        goError();
      }
    } catch(e) {}
  }

  window.addEventListener('message', onMessage);

  if (iframe) {
    iframe.addEventListener('load', function() {
      if (streamConfigured) goLive();
      else goOffline();
    });
    iframe.addEventListener('error', goError);

    if (streamConfigured) {
      showState('loading');
      // Safety: if YouTube load event doesn't fire, show LIVE after 5s
      setTimeout(function() {
        if (!hasGoneLive) goLive();
      }, 5000);
      // Total timeout: if nothing works in 20s, show error
      setTimeout(function() {
        if (!hasGoneLive) goError();
      }, 20000);
    } else {
      goOffline();
    }
  } else {
    goOffline();
  }

  if (tapOverlay) {
    tapOverlay.addEventListener('click', function() {
      if (iframe && iframe.src) {
        iframe.src = iframe.src.replace('mute=1', 'mute=0');
      }
      showState('loading');
    });
  }

  function pollStatus() {
    var url = '/admins/stream-status.php' + (lastUpdated ? '?since=' + encodeURIComponent(lastUpdated) : '');
    fetch(url, { credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.last_updated) lastUpdated = d.last_updated;
        if (d.reload) location.reload();
      })
      .catch(function() {});
  }

  pollTimer = setInterval(pollStatus, 5000);

  window.addEventListener('beforeunload', function() {
    if (pollTimer) clearInterval(pollTimer);
  });
})();
