(function() {
  'use strict';

  const STUN = { urls: 'stun:stun.l.google.com:19302' };
  const SIGNAL_URL = '/api/call/signal.php';
  const POLL_INTERVAL = 1500;
  const ICE_POLL_INTERVAL = 1000;

  let pc = null;
  let localStream = null;
  let remoteStream = null;
  let room = '';
  let role = '';
  let callActive = false;
  let pollTimer = null;
  let icePollTimer = null;
  let lastIceTime = 0;
  let onRemoteConnected = null;
  let onCallEnded = null;
  let onError = null;
  let heartbeatTimer = null;
  let endedByUser = false;

  // ── Public API ──────────────────────────────────────────────
  window.SmakCall = {

    init(roomId, userRole, callbacks) {
      room = roomId;
      role = userRole; // 'customer' or 'admin'
      onRemoteConnected = callbacks.onRemoteConnected || (() => {});
      onCallEnded = callbacks.onCallEnded || (() => {});
      onError = callbacks.onError || ((e) => console.error('SmakCall:', e));
    },

    async start() {
      try {
        localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        if (this._getEl('localAudio')) this._getEl('localAudio').srcObject = localStream;
        this._updateStatus('connecting', 'Connecting...');
        await this._createPeerConnection();

        if (role === 'customer') {
          await this._createOffer();
        } else {
          await this._waitForOffer();
        }
        this._startPolls();
        callActive = true;
      } catch (e) {
        onError('Could not access microphone: ' + e.message);
        this._updateStatus('error', 'Microphone access denied');
      }
    },

    hangup() {
      endedByUser = true;
      this._cleanup();
      this._updateStatus('ended', 'Call ended');
    },

    isActive() { return callActive; },

    // ── Internal helpers ─────────────────────────────────────

    _getEl(id) { return document.getElementById(id); },

    _updateStatus(state, text) {
      const dot = this._getEl('callStatusDot');
      const label = this._getEl('callStatusLabel');
      if (dot) { dot.className = 'status-dot ' + state; }
      if (label) { label.textContent = text; }
    },

    async _createPeerConnection() {
      pc = new RTCPeerConnection({ iceServers: [STUN] });

      pc.oniceconnectionstatechange = () => {
        if (pc.iceConnectionState === 'disconnected' || pc.iceConnectionState === 'failed') {
          if (callActive && !endedByUser) {
            this._updateStatus('error', 'Connection lost');
            this._cleanup();
            onCallEnded('Connection lost');
          }
        } else if (pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed') {
          this._updateStatus('connected', 'Connected');
          onRemoteConnected();
        }
      };

      pc.onconnectionstatechange = () => {
        if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed' || pc.connectionState === 'closed') {
          if (callActive && !endedByUser) {
            this._updateStatus('error', 'Call disconnected');
            this._cleanup();
            onCallEnded('Call disconnected');
          }
        }
      };

      pc.ontrack = (event) => {
        remoteStream = event.streams[0];
        const audioEl = this._getEl('remoteAudio');
        if (audioEl) { audioEl.srcObject = remoteStream; }
        this._updateStatus('connected', 'Connected');
        onRemoteConnected();
      };

      pc.onicecandidate = (event) => {
        if (event.candidate) {
          this._sendIceCandidate(event.candidate);
        }
      };

      if (localStream) {
        localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
      }
    },

    async _createOffer() {
      const offer = await pc.createOffer({ offerToReceiveAudio: true });
      await pc.setLocalDescription(offer);
      await this._postSignal('offer', { sdp: pc.localDescription.sdp });
      this._updateStatus('connecting', 'Waiting for admin...');
    },

    async _waitForOffer() {
      this._updateStatus('connecting', 'Waiting for call...');
      let waited = 0;
      const maxWait = 60000; // 60s timeout

      while (!endedByUser) {
        const resp = await this._getSignal('offer');
        if (resp.available) {
          await pc.setRemoteDescription({ type: 'offer', sdp: resp.sdp });
          const answer = await pc.createAnswer({ offerToReceiveAudio: true });
          await pc.setLocalDescription(answer);
          await this._postSignal('answer', { sdp: pc.localDescription.sdp });
          this._updateStatus('connecting', 'Connecting...');
          return;
        }
        if (waited > maxWait) {
          onError('Call request timed out');
          this._cleanup();
          return;
        }
        waited += 1500;
        await this._sleep(1500);
      }
    },

    async _waitForAnswer() {
      this._updateStatus('connecting', 'Waiting for admin to answer...');
      let waited = 0;
      const maxWait = 120000;

      while (!endedByUser) {
        const resp = await this._getSignal('answer');
        if (resp.available) {
          await pc.setRemoteDescription({ type: 'answer', sdp: resp.sdp });
          this._updateStatus('connecting', 'Connecting...');
          return;
        }
        if (waited > maxWait) {
          onError('Admin did not answer');
          this._cleanup();
          return;
        }
        waited += 1500;
        await this._sleep(1500);
      }
    },

    _startPolls() {
      this._stopPolls();
      if (role === 'customer') {
        pollTimer = setInterval(() => this._pollAnswer(), POLL_INTERVAL);
      }
      icePollTimer = setInterval(() => this._pollIce(), ICE_POLL_INTERVAL);
      heartbeatTimer = setInterval(() => this._heartbeat(), 30000);
    },

    _stopPolls() {
      if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
      if (icePollTimer) { clearInterval(icePollTimer); icePollTimer = null; }
      if (heartbeatTimer) { clearInterval(heartbeatTimer); heartbeatTimer = null; }
    },

    async _pollAnswer() {
      if (!pc || !pc.remoteDescription) {
        const resp = await this._getSignal('answer');
        if (resp.available) {
          try {
            await pc.setRemoteDescription({ type: 'answer', sdp: resp.sdp });
          } catch (e) {
            // already set, ignore
          }
        }
      }
    },

    async _pollIce() {
      if (!callActive) return;
      const resp = await fetch(SIGNAL_URL + '?room=' + room + '&action=ice&from=' + role + '&since=' + lastIceTime);
      const data = await resp.json();
      if (data.success && data.candidates) {
        for (const c of data.candidates) {
          if (c.time > lastIceTime) lastIceTime = c.time;
          if (pc && c.candidate) {
            try { await pc.addIceCandidate(new RTCIceCandidate(c.candidate)); } catch (e) { /* ignore stale */ }
          }
        }
      }
    },

    async _sendIceCandidate(candidate) {
      try {
        await fetch(SIGNAL_URL + '?room=' + room + '&action=ice&from=' + role, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ candidate: candidate })
        });
      } catch (e) { /* ignore */ }
    },

    async _postSignal(action, body) {
      await fetch(SIGNAL_URL + '?room=' + room + '&action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
    },

    async _getSignal(action) {
      const resp = await fetch(SIGNAL_URL + '?room=' + room + '&action=' + action);
      return await resp.json();
    },

    async _heartbeat() {
      try {
        await fetch(SIGNAL_URL + '?room=' + room + '&action=status');
      } catch (e) { /* ignore */ }
    },

    async _cleanup() {
      callActive = false;
      this._stopPolls();

      try {
        await fetch(SIGNAL_URL + '?room=' + room + '&action=end');
      } catch (e) { /* ignore */ }

      if (pc) {
        try {
          pc.getTransceivers().forEach(t => {
            if (t.stop) t.stop();
          });
        } catch (e) { /* ignore */ }
        try {
          pc.close();
        } catch (e) { /* ignore */ }
        pc = null;
      }

      if (localStream) {
        localStream.getTracks().forEach(t => t.stop());
        localStream = null;
      }

      if (remoteStream) {
        remoteStream.getTracks().forEach(t => t.stop());
        remoteStream = null;
      }

      const remoteAudio = this._getEl('remoteAudio');
      if (remoteAudio) remoteAudio.srcObject = null;
      const localAudio = this._getEl('localAudio');
      if (localAudio) localAudio.srcObject = null;
    },

    _sleep(ms) { return new Promise(r => setTimeout(r, ms)); }
  };

})();
