/* ATELIER HAIR TATTOO – frontend interactions (multi-page) */
(function () {
  'use strict';

  /* ---- Mobile menu ---- */
  var burger = document.getElementById('burger');
  var navLinks = document.getElementById('navLinks');
  if (burger && navLinks) {
    burger.addEventListener('click', function () {
      burger.classList.toggle('open');
      navLinks.classList.toggle('open');
    });
  }

  /* ---- Active nav based on current page ---- */
  if (navLinks) {
    var path = location.pathname.split('/').pop() || 'index.html';
    var page = path.replace('.html', '');
    if (page === '') page = 'index';
    navLinks.querySelectorAll('a').forEach(function (a) {
      if (a.getAttribute('data-page') === page) a.classList.add('active');
    });
  }

  /* ---- Reveal on scroll ---- */
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }

  /* ---- i18n Language Switcher ---- */
  if (typeof STB_TRANSLATIONS !== 'undefined') {

    var STORAGE_KEY = 'stb_lang';
    var currentLang = localStorage.getItem(STORAGE_KEY) || 'en';

    function applyLang(lang) {
      var t = STB_TRANSLATIONS[lang];
      if (!t) return;
      currentLang = lang;
      localStorage.setItem(STORAGE_KEY, lang);
      document.documentElement.lang = lang;

      // Plain text
      document.querySelectorAll('[data-i18n]').forEach(function (el) {
        var key = el.getAttribute('data-i18n');
        if (t[key] !== undefined) el.textContent = t[key];
      });

      // HTML (spans with classes inside)
      document.querySelectorAll('[data-i18n-html]').forEach(function (el) {
        var key = el.getAttribute('data-i18n-html');
        if (t[key] !== undefined) el.innerHTML = t[key];
      });

      // Active button
      document.querySelectorAll('.lang-switcher button').forEach(function (btn) {
        btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
      });

      // Update contact form error messages reference
      window._i18n = t;
    }

    // Language buttons
    document.querySelectorAll('.lang-switcher button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        applyLang(btn.getAttribute('data-lang'));
      });
    });

    // Apply on load
    applyLang(currentLang);
  }

  /* ---- Contact form → contact.php ---- */
  var form = document.getElementById('contactForm');
  if (form) {
    var msg = document.getElementById('formMsg');
    var btn = document.getElementById('submitBtn');
    form.addEventListener('submit', async function (ev) {
      ev.preventDefault();
      msg.className = 'form-msg';
      var t = window._i18n || {};
      var data = {
        firstName: form.firstName.value.trim(),
        lastName:  form.lastName.value.trim(),
        email:     form.email.value.trim(),
        message:   form.message.value.trim()
      };
      if (!data.email) {
        msg.classList.add('err');
        msg.textContent = t.contact_err_email || 'Please enter your email.';
        return;
      }
      btn.disabled = true;
      var original = btn.textContent;
      btn.textContent = t.contact_sending || 'Sending…';
      try {
        var res = await fetch('contact.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
        });
        var json = await res.json();
        if (json.success) {
          msg.classList.add('ok');
          msg.textContent = t.contact_ok || 'Thanks for submitting!';
          form.reset();
        } else {
          msg.classList.add('err');
          msg.textContent = json.error || t.contact_err_generic || 'Something went wrong.';
        }
      } catch (err) {
        msg.classList.add('err');
        msg.textContent = t.contact_err_conn || 'Connection failed. Please try again later.';
      } finally {
        btn.disabled = false;
        btn.textContent = original;
      }
    });
  }

/* ---- Welcome modal (Beauty opening) ---- */
  var welcome = document.getElementById('welcomeModal');
  if (welcome) {
    var SEEN_KEY = 'stb_welcome_seen';
    var closeWelcome = function () {
      welcome.classList.remove('show');
      welcome.setAttribute('aria-hidden', 'true');
      try { sessionStorage.setItem(SEEN_KEY, '1'); } catch (e) {}
    };
    if (!sessionStorage.getItem(SEEN_KEY)) {
      setTimeout(function () {
        welcome.classList.add('show');
        welcome.setAttribute('aria-hidden', 'false');
      }, 600);
    }
    var wClose = document.getElementById('welcomeClose');
    var wDismiss = document.getElementById('welcomeDismiss');
    if (wClose) wClose.addEventListener('click', closeWelcome);
    if (wDismiss) wDismiss.addEventListener('click', closeWelcome);
    welcome.addEventListener('click', function (e) {
      if (e.target === welcome) closeWelcome();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && welcome.classList.contains('show')) closeWelcome();
    });
  }

/* ---- Smart header on scroll ---- */
  var hdr = document.querySelector('.site-header');
  if (hdr && document.body.classList.contains('home')) {
    var onScroll = function () {
      hdr.classList.toggle('scrolled', window.scrollY > 60);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

})();
