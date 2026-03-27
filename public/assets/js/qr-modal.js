/**
 * QR Preview Modal
 * Opens a large QR preview pop-up when a QR trigger is clicked.
 * Triggers:  [data-qr-preview]  with data attributes:
 *   data-qr-src      — URL to the QR image
 *   data-qr-name     — Animal name
 *   data-qr-code     — Animal ID code
 *   data-qr-download — Download URL (defaults to data-qr-src)
 */

document.addEventListener('click', (event) => {
  // Handle open trigger
  const trigger = event.target.closest('[data-qr-preview]');
  if (trigger) {
    event.preventDefault();
    const modal = document.getElementById('qr-preview-modal');
    if (!modal) return;
    
    const image = document.getElementById('qr-preview-image');
    const name = document.getElementById('qr-preview-name');
    const code = document.getElementById('qr-preview-id');
    const download = document.getElementById('qr-preview-download');
    
    if (image) image.src = trigger.dataset.qrSrc || '';
    if (name) name.textContent = trigger.dataset.qrName || 'Animal';
    if (code) code.textContent = trigger.dataset.qrCode || '';
    if (download) download.href = trigger.dataset.qrDownload || trigger.dataset.qrSrc || '';
    
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    return;
  }

  // Handle close trigger
  const closeBtn = event.target.closest('[data-qr-close]');
  if (closeBtn) {
    event.preventDefault();
    const modal = document.getElementById('qr-preview-modal');
    if (modal) {
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      const image = document.getElementById('qr-preview-image');
      if (image) image.src = '';
    }
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    const modal = document.getElementById('qr-preview-modal');
    if (modal && !modal.hidden) {
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      const image = document.getElementById('qr-preview-image');
      if (image) image.src = '';
    }
  }
});
