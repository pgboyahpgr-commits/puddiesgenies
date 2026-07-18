<?php require_once __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
#map { height: 60vh; border-radius: var(--radius-lg); z-index: 1; }
.leaflet-container { background: #FDFDFC !important; }
.leaflet-popup-content-wrapper { background: var(--bg-card) !important; backdrop-filter: blur(20px); color: var(--text) !important; border: 1px solid var(--border); border-radius: 16px !important; box-shadow: var(--shadow-card) !important; }
.leaflet-popup-tip { background: var(--bg-card) !important; border: 1px solid var(--border); }
.leaflet-popup-close-button { color: var(--text-muted) !important; }
.leaflet-control-zoom a { background: var(--bg-card) !important; color: var(--text) !important; border: 1px solid var(--border) !important; }
.leaflet-control-attribution { background: rgba(0,0,0,0.3) !important; color: var(--text-dim) !important; }
.leaflet-control-attribution a { color: var(--primary) !important; }
</style>
<main class="max-w-5xl mx-auto px-4 pb-12">
  <div class="text-center mb-6 pt-4">
    <h1 class="text-5xl font-bold">📍 <span class="gradient-text" data-translate>Find Us</span></h1>
    <p style="color:var(--text-dim);" class="mt-2" data-translate>Visit our restaurant for an amazing dining experience!</p>
  </div>

  <div class="glass-card p-5 mb-6">
    <div id="map"></div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="glass-card p-5">
      <h3 class="font-bold mb-3" data-translate>📍 Address</h3>
      <p style="color:var(--text-muted);" class="text-sm leading-relaxed">
        SmakAI Restaurant<br>
        Connaught Place, Block G<br>
        New Delhi, Delhi 110001<br>
        India
      </p>
      <a href="https://www.google.com/maps/dir/?api=1&destination=Connaught+Place+New+Delhi" target="_blank" class="btn-primary mt-3 inline-block text-sm" data-translate>🗺️ Get Directions</a>
    </div>
    <div class="glass-card p-5">
      <h3 class="font-bold mb-3" data-translate>🕐 Opening Hours</h3>
      <div class="text-sm space-y-1" style="color:var(--text-muted);">
        <div class="flex justify-between"><span data-translate>Monday – Friday</span><span data-translate>11:00 AM – 11:00 PM</span></div>
        <div class="flex justify-between"><span data-translate>Saturday</span><span data-translate>10:00 AM – 12:00 AM</span></div>
        <div class="flex justify-between"><span data-translate>Sunday</span><span data-translate>10:00 AM – 10:00 PM</span></div>
      </div>
    </div>
  </div>

  <div class="glass-card p-5">
    <h3 class="font-bold mb-3" data-translate>📞 Contact</h3>
    <div class="text-sm space-y-2" style="color:var(--text-muted);">
      <div>📞 <a href="tel:+911234567890" style="color:var(--primary);" data-translate>+91 12345 67890</a></div>
      <div>✉️ <a href="mailto:hello@smakai.com" style="color:var(--primary);" data-translate>hello@smakai.com</a></div>
    </div>
  </div>

  <div class="flex gap-3 justify-center mt-6 flex-wrap">
    <a href="/menu.php" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>📋 Menu</a>
    <a href="/" class="btn-bouncy btn-outline px-5 py-2.5 no-underline text-sm" data-translate>🏠 Home</a>
  </div>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
  var map = L.map('map', {
    center: [28.6315, 77.2167],
    zoom: 16,
    zoomControl: true,
    scrollWheelZoom: true
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://openstreetmap.org" data-translate>OpenStreetMap</a>',
    maxZoom: 19
  }).addTo(map);

  var svgIcon = L.divIcon({
    html: '<div style="background:var(--primary,#538bdf);color:#fff;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;box-shadow:0 4px 20px rgba(108,92,231,0.4);border:3px solid #fff;" data-translate>🍽️</div>',
    className: '',
    iconSize: [44, 44],
    iconAnchor: [22, 22],
    popupAnchor: [0, -24]
  });

  var marker = L.marker([28.6315, 77.2167], { icon: svgIcon }).addTo(map);
  marker.bindPopup(
    '<div style="text-align:center;min-width:180px;">' +
      '<strong style="font-size:16px;" data-translate>🍽️ SmakAI Restaurant</strong><br>' +
      '<span style="font-size:13px;opacity:0.7;" data-translate>Connaught Place, New Delhi</span><br>' +
      '<a href="https://www.google.com/maps/dir/?api=1&destination=Connaught+Place+New+Delhi" target="_blank" style="display:inline-block;margin-top:8px;padding:6px 16px;border-radius:20px;background:#538bdf;color:#fff;text-decoration:none;font-size:12px;font-weight:600;" data-translate>🗺️ Directions</a>' +
    '</div>'
  ).openPopup();
})();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
