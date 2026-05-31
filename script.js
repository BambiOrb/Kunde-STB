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

  /* ---- Contact form → contact.php ---- */
  var form = document.getElementById('contactForm');
  if (form) {
    var msg = document.getElementById('formMsg');
    var btn = document.getElementById('submitBtn');
    form.addEventListener('submit', async function (ev) {
      ev.preventDefault();
      msg.className = 'form-msg';
      var data = {
        firstName: form.firstName.value.trim(),
        lastName:  form.lastName.value.trim(),
        email:     form.email.value.trim(),
        message:   form.message.value.trim()
      };
      if (!data.email) { msg.classList.add('err'); msg.textContent = 'Bitte E-Mail angeben.'; return; }
      btn.disabled = true; var original = btn.textContent; btn.textContent = 'Senden…';
      try {
        var res = await fetch('contact.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data)
        });
        var json = await res.json();
        if (json.success) { msg.classList.add('ok'); msg.textContent = 'Thanks for submitting!'; form.reset(); }
        else { msg.classList.add('err'); msg.textContent = json.error || 'Something went wrong.'; }
      } catch (err) {
        msg.classList.add('err'); msg.textContent = 'Connection failed. Please try again later.';
      } finally { btn.disabled = false; btn.textContent = original; }
    });
  }
})();
