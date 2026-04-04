/**
 * QR Preview Modal
 * Opens a large QR preview pop-up when a QR trigger is clicked.
 * Triggers:  [data-qr-preview]  with data attributes:
 *   data-qr-src      — URL to the QR image
 *   data-qr-name     — Animal name
 *   data-qr-code     — Animal ID code
 *   data-qr-download — Download URL (defaults to data-qr-src)
 */

let focusTrapActive = false;
let lastFocusedElement = null;

function getMotionDuration() {
  return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 220;
}

/**
 * Focus Trap Implementation
 * Traps keyboard focus within modal and cycles through focusable elements
 */
function getFocusableElements(container) {
  const selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
  return Array.from(container.querySelectorAll(selector));
}

function trapFocus(event, modal) {
  if (event.key !== 'Tab' || !focusTrapActive) return;
  
  const focusableElements = getFocusableElements(modal);
  if (focusableElements.length === 0) return;
  
  const firstElement = focusableElements[0];
  const lastElement = focusableElements[focusableElements.length - 1];
  
  if (event.shiftKey) {
    // Shift + Tab: moving backwards
    if (document.activeElement === firstElement) {
      event.preventDefault();
      lastElement.focus();
    }
  } else {
    // Tab: moving forwards
    if (document.activeElement === lastElement) {
      event.preventDefault();
      firstElement.focus();
    }
  }
}

function openModal(modal) {
  lastFocusedElement = document.activeElement;
  
  modal.hidden = false;
  modal.removeAttribute('data-closing');
  modal.setAttribute('aria-hidden', 'false');
  
  // Focus first focusable element
  const focusableElements = getFocusableElements(modal);
  if (focusableElements.length > 0) {
    focusableElements[0].focus();
  }
  
  focusTrapActive = true;
}

function closeModal(modal) {
  focusTrapActive = false;
  
  // Add closing state for exit animation
  modal.setAttribute('data-closing', 'true');
  
  // Wait for animation to complete
  setTimeout(() => {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('data-closing');
    
    const image = document.getElementById('qr-preview-image');
    if (image) image.src = '';
    
    // Restore focus to trigger element
    if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
      lastFocusedElement.focus();
    }
  }, getMotionDuration());
}

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
    
    openModal(modal);
    return;
  }

  // Handle close trigger
  const closeBtn = event.target.closest('[data-qr-close]');
  if (closeBtn) {
    event.preventDefault();
    const modal = document.getElementById('qr-preview-modal');
    if (modal && !modal.hidden) {
      closeModal(modal);
    }
  }
  
  // Handle backdrop click (click outside)
  if (event.target.classList.contains('qr-preview-backdrop')) {
    const modal = document.getElementById('qr-preview-modal');
    if (modal && !modal.hidden) {
      closeModal(modal);
    }
  }
});

document.addEventListener('keydown', (event) => {
  const modal = document.getElementById('qr-preview-modal');
  if (!modal || modal.hidden) return;
  
  // Handle ESC key
  if (event.key === 'Escape') {
    closeModal(modal);
  }
  
  // Handle focus trap
  trapFocus(event, modal);
});
