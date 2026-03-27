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
    
    const apiUrl = trigger.dataset.qrSrc || '';
    if (image) image.src = ''; // Clear old image
    if (name) name.textContent = trigger.dataset.qrName || 'Animal';
    if (code) code.textContent = trigger.dataset.qrCode || '';
    if (download) download.href = trigger.dataset.qrDownload || apiUrl;
    
    if (apiUrl) {
      fetch(apiUrl, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(result => {
          const isSuccess = result?.success === true || result?.status === 'success';
          if (isSuccess && result.data && result.data.qr) {
            // Prepend leading slash if missing
            let fileUrl = result.data.qr.file_path;
            if (!fileUrl.startsWith('/')) fileUrl = '/' + fileUrl;
            if (image) image.src = fileUrl;
          }
        })
        .catch(err => console.error('Error fetching QR data:', err));
    }
    
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
