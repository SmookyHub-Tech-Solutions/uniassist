// Show/hide-password toggles (paired with pw_toggle_btn() in includes/helpers.php).
// One delegated listener covers every eye on the page, including ones added later.
// Clicking flips the matching box between dots and text and swaps the eye icon.
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-showfor]');
  if (!b) return;
  var inp = document.getElementById(b.dataset.showfor);
  if (!inp) return;
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  b.querySelector('.eye-on').classList.toggle('hidden', show);
  b.querySelector('.eye-off').classList.toggle('hidden', !show);
  b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  b.setAttribute('aria-pressed', show ? 'true' : 'false');
});
