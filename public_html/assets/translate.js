(function(){
  'use strict';

  async function translateText(text, targetLang) {
    if (targetLang === 'en' || !text) return text;
    var url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=' + targetLang + '&dt=t&q=' + encodeURIComponent(text.substr(0, 2000));
    try {
      var r = await fetch(url);
      if (!r.ok) return text;
      var d = await r.json();
      return d[0].map(function(i){return i[0]}).join('');
    } catch(e) { return text; }
  }

  window.translateSite = async function(targetLang) {
    localStorage.setItem('smak_lang', targetLang);
    var elements = document.querySelectorAll('[data-translate]');
    var promises = Array.from(elements).map(async function(el) {
      var baseText = el.getAttribute('data-original');
      if (!baseText) {
        baseText = el.innerText.trim();
        el.setAttribute('data-original', baseText);
      }
      if (targetLang !== 'en') {
        el.innerText = await translateText(baseText, targetLang);
      } else {
        el.innerText = baseText;
      }
    });
    await Promise.all(promises);
  };

  window.retranslate = function() {
    var savedLang = localStorage.getItem('smak_lang') || 'en';
    if (savedLang !== 'en') {
      window.translateSite(savedLang);
    }
  };

  (function init() {
    var savedLang = localStorage.getItem('smak_lang') || 'en';
    var sel = document.getElementById('langSelect');
    if (sel) {
      sel.value = savedLang;
      sel.addEventListener('change', function() {
        window.translateSite(this.value);
      });
    }
    if (savedLang !== 'en') {
      setTimeout(function() { window.translateSite(savedLang); }, 500);
    }
  })();
})();
