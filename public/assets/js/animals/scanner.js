(function (ns) {
  ns.registerInitializer(function bindScanner(root) {
    const modal = root.getElementById('qr-scanner-modal');
    if (!modal || modal.dataset.scannerBound === 'true') {
      return;
    }

    modal.dataset.scannerBound = 'true';

    const openButton = root.querySelector('[data-open-scanner]');
    const closeButton = root.querySelector('[data-close-scanner]');
    const manualButton = root.getElementById('manual-qr-submit');
    const manualInput = root.getElementById('manual-qr-value');
    let scanner;

    async function resolveScan(value) {
      if (!value) {
        return;
      }

      try {
        const { ok, data: result } = await ns.apiRequest('/api/animals/scan/' + encodeURIComponent(value));
        if (ok) {
          window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
          return;
        }
      } catch (error) {
        console.error(error);
      }

      const fallbackTarget = '/animals/' + encodeURIComponent(value);
      window.CatarmanApp?.navigate?.(fallbackTarget) || (window.location.href = fallbackTarget);
    }

    async function openScanner() {
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      if (window.Html5Qrcode && !scanner) {
        scanner = new Html5Qrcode('qr-reader');
        try {
          await scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: 220 },
            async (decodedText) => {
              await scanner.stop();
              await resolveScan(decodedText);
            }
          );
        } catch (error) {
          const qrReader = root.getElementById('qr-reader');
          if (qrReader) {
            qrReader.innerHTML = '<div class="animal-photo-empty">Camera unavailable. Use manual entry.</div>';
          }
        }
      }
    }

    async function closeScanner() {
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      try {
        if (scanner?.isScanning) {
          await scanner.stop();
        }
      } catch (error) {
        console.error(error);
      }
    }

    openButton?.addEventListener('click', () => {
      openScanner().catch(console.error);
    });
    closeButton?.addEventListener('click', () => {
      closeScanner().catch(console.error);
    });
    manualButton?.addEventListener('click', () => {
      resolveScan(manualInput?.value.trim() || '').catch(console.error);
    });
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeScanner().catch(console.error);
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !modal.hidden) {
        closeScanner().catch(console.error);
      }
    });
  });
})(window.CatarmanAnimals);
