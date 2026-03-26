/**
 * QR Preview Modal
 * Opens a large QR preview pop-up when a QR trigger is clicked.
 * Triggers:  [data-qr-preview]  with data attributes:
 *   data-qr-src      — URL to the QR image
 *   data-qr-name     — Animal name
 *   data-qr-code     — Animal ID code
 *   data-qr-download — Download URL (defaults to data-qr-src)
 */
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('qr-preview-modal');
  if (!modal) return;

  const image = document.getElementById('qr-preview-image');
  const name = document.getElementById('qr-preview-name');
  const code = document.getElementById('qr-preview-id');
  const download = document.getElementById('qr-preview-download');

  function open(src, animalName, animalCode, downloadUrl) {
    image.src = src;
    name.textContent = animalName || 'Animal';
    code.textContent = animalCode || '';
    download.href = downloadUrl || src;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
  }

  function close() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    image.src = '';
  }

  // Close buttons
  modal.querySelectorAll('[data-qr-close]').forEach((el) => {
    el.addEventListener('click', close);
  });

  // Escape key
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      close();
    }
  });

  // Delegation: any [data-qr-preview] click
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-qr-preview]');
    if (!trigger) return;
    event.preventDefault();
    open(
      trigger.dataset.qrSrc || '',
      trigger.dataset.qrName || '',
      trigger.dataset.qrCode || '',
      trigger.dataset.qrDownload || trigger.dataset.qrSrc || ''
    );
  });
});
