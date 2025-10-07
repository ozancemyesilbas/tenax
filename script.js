// assets/js/bands.js
document.addEventListener('DOMContentLoaded', () => {
  const targets = ['c35.html', 'c35d.html', 'v35.html']; // 1., 2., 3. bantın hedefleri
  const bands = document.querySelectorAll('.feature-band.no-text');

  bands.forEach((el, i) => {
    const go = () => { if (targets[i]) location.href = targets[i]; };

    // Erişilebilirlik + tıklama/klavye
    el.setAttribute('role', 'link');
    el.setAttribute('tabindex', '0');
    el.style.cursor = 'pointer';

    el.addEventListener('click', go);
    el.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
    });
  });
});













// Mobil menü aç/kapa + alt menü kontrolü
(function(){
  const btn = document.getElementById('navToggle');
  const nav = document.getElementById('siteNav');
  const overlay = document.getElementById('navOverlay');

  if(!btn || !nav || !overlay) return;

  function openNav(){
    nav.classList.add('open');
    btn.classList.add('active');
    btn.setAttribute('aria-expanded','true');
    overlay.hidden = false;
    document.documentElement.style.overflow = 'hidden'; // body scroll kilidi
  }
  function closeNav(){
    nav.classList.remove('open');
    btn.classList.remove('active');
    btn.setAttribute('aria-expanded','false');
    overlay.hidden = true;
    document.documentElement.style.overflow = '';
  }

  btn.addEventListener('click', () => {
    nav.classList.contains('open') ? closeNav() : openNav();
  });
  overlay.addEventListener('click', closeNav);

  // Alt menü toggle (mobilde)
  nav.addEventListener('click', (e) => {
    const link = e.target.closest('.dropdown-toggle');
    if(!link) return;
    // masaüstünde dokunma yerine hover var; sadece mobil genişlikte çalıştır
    if (window.matchMedia('(max-width: 768px)').matches) {
      e.preventDefault();
      const li = link.closest('.has-dropdown');
      li.classList.toggle('open');
    }
  });

  // Ekran genişlerse menüyü kapat
  window.addEventListener('resize', () => {
    if (!window.matchMedia('(max-width: 768px)').matches) {
      closeNav();
      // mobilde açılmış alt menüleri toparla
      document.querySelectorAll('.has-dropdown.open').forEach(li => li.classList.remove('open'));
    }
  });
})();





























// hero slider autoplay (radio tabanlı)
// HTML varsayımı: <section class="hero"> içinde id'leri s1, s2, s3 olan
// <input type="radio" name="slide"> elemanları, .slides ve ok/nokta label'ları var.

(function initHeroAutoplay(){
  const hero = document.querySelector('.hero');
  if (!hero) return;

  // Bu .hero içindeki "slide" isimli radio'ları sırayla döndürür
  const radios = Array.from(hero.querySelectorAll('input[type="radio"][name="slide"]'));
  if (radios.length < 2) return;

  // Kullanıcı hareket azaltmayı tercih etmişse çalıştırma
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduceMotion) return;

  let idx = radios.findIndex(r => r.checked);
  if (idx < 0) idx = 0;

  let timer = null;
  const INTERVAL_MS = 5000; // hız: 5 sn

  function gotoNext(){
    // Kullanıcının o an seçili yaptığı slayttan devam et
    const cur = radios.findIndex(r => r.checked);
    idx = cur >= 0 ? cur : idx;

    idx = (idx + 1) % radios.length;
    radios[idx].checked = true;
  }

  function start(){
    stop();
    timer = setInterval(gotoNext, INTERVAL_MS);
  }
  function stop(){
    if (timer) { clearInterval(timer); timer = null; }
  }

  // Hover'da durdur/çalıştır (desktop için iyi UX)
  hero.addEventListener('mouseenter', stop);
  hero.addEventListener('mouseleave', start);

  // Oklar veya noktalar tıklanınca sayacı tazele
  hero.addEventListener('click', (e) => {
    if (e.target.matches('.nav, .dots label')) {
      start(); // kullanıcı müdahalesinden sonra süreyi sıfırla
    }
  });

  // DOM yüklendiyse başlat
  start();
})();





























