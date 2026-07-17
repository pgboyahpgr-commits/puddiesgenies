
<div class="max-w-7xl mx-auto px-4 mt-12">
<footer class="text-center py-6 border-t border-gray-200 text-gray-400 text-sm">
  SmakAI &mdash; Smart Restaurant &bull; Made for Hackathon
</footer>
</div>

<!-- Mobile Sticky Cart Bar (always present, hidden by default) -->
<div id="mobileCartBar" class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-lg border-t border-gray-200 shadow-lg px-4 py-3 z-50 hidden" style="transform:translateY(100%);transition:transform 0.3s cubic-bezier(0.34,1.56,0.64,1);">
  <div class="max-w-6xl mx-auto flex items-center justify-between">
    <div>
      <span id="mobileCartCount" class="font-bold text-sm">0 items</span>
      <span id="mobileCartTotal" class="font-bold text-lg ml-3" style="color:#FF6B6B;">₹0</span>
    </div>
    <a href="/checkout.php" class="btn-bouncy px-8 py-2.5 no-underline text-sm font-bold" style="background:#FF6B6B;color:#fff;">View Cart →</a>
  </div>
</div>

<a href="/checkout.php" id="floatCartBtn" class="no-underline" style="display:none;">
  <div class="float-cart-inner">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
    <span id="floatCartCount">0</span>
  </div>
</a>

<style>
#floatCartBtn { position:fixed; bottom:28px; right:28px; z-index:999; display:none; }
.float-cart-inner { width:60px; height:60px; border-radius:50%; background:#FF6B6B; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 20px rgba(255,107,107,0.4); cursor:pointer; transition:transform 0.2s, box-shadow 0.2s; position:relative; }
.float-cart-inner:hover { transform:scale(1.1); box-shadow:0 6px 28px rgba(255,107,107,0.55); }
#floatCartCount { position:absolute; top:-4px; right:-4px; background:#2D3436; color:#fff; font-size:11px; font-weight:700; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #FF6B6B; }
.float-cart-pulse { animation:floatPulse 1.5s ease-in-out infinite; }
@keyframes floatPulse { 0%,100%{transform:scale(1);box-shadow:0 4px 20px rgba(255,107,107,0.4);} 50%{transform:scale(1.08);box-shadow:0 4px 32px rgba(255,107,107,0.6);} }
</style>

<script src="/assets/script.js"></script>
<script src="/assets/dish-modal.js"></script>
<script>
gsap.from("nav", { y: -20, opacity: 0, duration: 0.6, ease: "power3.out" });
gsap.from("main > *", { y: 30, opacity: 0, duration: 0.5, stagger: 0.08, ease: "back.out(1.7)" });
</script>
</body>
</html>
