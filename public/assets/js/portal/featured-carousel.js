(function (ns) {
  ns.registerInitializer(function bindFeaturedCarousel(root) {
    const carousel = root.querySelector('[data-featured-carousel]');
    if (!carousel || carousel.dataset.carouselBound === 'true') {
      return;
    }

    carousel.dataset.carouselBound = 'true';

    const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));
    const indicators = Array.from(carousel.querySelectorAll('[data-carousel-indicator]'));
    const previousButton = root.querySelector('[data-carousel-prev]');
    const nextButton = root.querySelector('[data-carousel-next]');

    if (slides.length === 0) {
      return;
    }

    let activeIndex = 0;
    let autoRotateTimer = null;

    function setSlideState(slide, state) {
      slide.classList.toggle('is-active', state === 'active');
      slide.classList.toggle('is-preview', state === 'next');
      slide.classList.toggle('is-previous', state === 'previous');
    }

    function updateUi(index) {
      activeIndex = index;
      const previewIndex = slides.length > 1 ? (activeIndex + 1) % slides.length : -1;
      const prevIndex = slides.length > 2 ? (activeIndex - 1 + slides.length) % slides.length : -1;

      slides.forEach((slide, slideIndex) => {
        const isActive = slideIndex === activeIndex;
        const isPreview = slideIndex === previewIndex && !isActive;
        const isPrevious = slideIndex === prevIndex && !isActive && !isPreview;

        setSlideState(slide, isActive ? 'active' : isPreview ? 'next' : isPrevious ? 'previous' : 'idle');
        slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        slide.tabIndex = isActive ? 0 : -1;
      });

      indicators.forEach((indicator, indicatorIndex) => {
        const isActive = indicatorIndex === activeIndex;
        indicator.classList.toggle('is-active', isActive);
        indicator.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      if (previousButton) {
        previousButton.disabled = slides.length <= 1;
      }

      if (nextButton) {
        nextButton.disabled = slides.length <= 1;
      }
    }

    function showIndex(index) {
      const safeIndex = (index + slides.length) % slides.length;
      updateUi(safeIndex);
    }

    function stopAutoRotate() {
      if (autoRotateTimer !== null) {
        window.clearTimeout(autoRotateTimer);
        autoRotateTimer = null;
      }
    }

    function scheduleAutoRotate() {
      stopAutoRotate();
      if (slides.length <= 1 || document.hidden) {
        return;
      }

      autoRotateTimer = window.setTimeout(() => {
        showIndex(activeIndex + 1);
        scheduleAutoRotate();
      }, 3000);
    }

    function goToSlideHref(slide) {
      const href = String(slide?.dataset.slideHref ?? '').trim();
      if (!href) {
        return;
      }

      window.location.href = href;
    }

    previousButton?.addEventListener('click', () => {
      showIndex(activeIndex - 1);
      scheduleAutoRotate();
    });

    nextButton?.addEventListener('click', () => {
      showIndex(activeIndex + 1);
      scheduleAutoRotate();
    });

    indicators.forEach((indicator) => {
      indicator.addEventListener('click', () => {
        showIndex(Number(indicator.dataset.slideTo || 0));
        scheduleAutoRotate();
      });
    });

    slides.forEach((slide) => {
      slide.addEventListener('click', (event) => {
        if (event.target.closest('a, button')) {
          return;
        }

        goToSlideHref(slide);
      });

      slide.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
          return;
        }

        event.preventDefault();
        goToSlideHref(slide);
      });
    });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        stopAutoRotate();
        return;
      }

      scheduleAutoRotate();
    });

    updateUi(0);
    scheduleAutoRotate();
  });
})(window.CatarmanPortal);
