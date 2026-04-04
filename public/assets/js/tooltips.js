(function () {
  'use strict';

  const OFFSET = 8; // Gap between tooltip and trigger element (px)
  const VIEWPORT_PADDING = 12; // Minimum distance from viewport edges (px)
  const SHOW_DELAY = 600; // ms delay before showing tooltip
  const HIDE_DELAY = 100; // ms delay before hiding tooltip

  let currentTooltip = null;
  let showTimeout = null;
  let hideTimeout = null;
  let currentTrigger = null;
  let tooltipId = 0;

  function getHideDuration() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 150;
  }

  /**
   * Create tooltip element
   */
  function createTooltip(text) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.setAttribute('role', 'tooltip');
    tooltip.setAttribute('data-visible', 'false');
    tooltip.id = 'app-tooltip-' + (++tooltipId);

    const arrow = document.createElement('div');
    arrow.className = 'tooltip-arrow';

    tooltip.textContent = text;
    tooltip.appendChild(arrow);
    document.body.appendChild(tooltip);

    return tooltip;
  }

  /**
   * Calculate optimal position for tooltip
   */
  function calculatePosition(trigger, tooltip, preferredPosition) {
    const triggerRect = trigger.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();

    const positions = {
      top: {
        x: triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2,
        y: triggerRect.top - tooltipRect.height - OFFSET,
        fits: triggerRect.top - tooltipRect.height - OFFSET >= VIEWPORT_PADDING
      },
      bottom: {
        x: triggerRect.left + triggerRect.width / 2 - tooltipRect.width / 2,
        y: triggerRect.bottom + OFFSET,
        fits: triggerRect.bottom + tooltipRect.height + OFFSET <= window.innerHeight - VIEWPORT_PADDING
      },
      left: {
        x: triggerRect.left - tooltipRect.width - OFFSET,
        y: triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2,
        fits: triggerRect.left - tooltipRect.width - OFFSET >= VIEWPORT_PADDING
      },
      right: {
        x: triggerRect.right + OFFSET,
        y: triggerRect.top + triggerRect.height / 2 - tooltipRect.height / 2,
        fits: triggerRect.right + tooltipRect.width + OFFSET <= window.innerWidth - VIEWPORT_PADDING
      }
    };

    // Try preferred position first
    let position = preferredPosition;
    if (!positions[position].fits) {
      // Fallback order: opposite side, then adjacent sides
      const fallbacks = {
        top: ['bottom', 'right', 'left'],
        bottom: ['top', 'right', 'left'],
        left: ['right', 'top', 'bottom'],
        right: ['left', 'top', 'bottom']
      };

      for (const fallback of fallbacks[position]) {
        if (positions[fallback].fits) {
          position = fallback;
          break;
        }
      }
    }

    let { x, y } = positions[position];

    // Ensure tooltip stays within viewport bounds horizontally
    if (position === 'top' || position === 'bottom') {
      x = Math.max(VIEWPORT_PADDING, Math.min(x, window.innerWidth - tooltipRect.width - VIEWPORT_PADDING));
    }

    // Ensure tooltip stays within viewport bounds vertically
    if (position === 'left' || position === 'right') {
      y = Math.max(VIEWPORT_PADDING, Math.min(y, window.innerHeight - tooltipRect.height - VIEWPORT_PADDING));
    }

    return { x, y, position };
  }

  /**
   * Position and show tooltip
   */
  function showTooltip(trigger) {
    clearTimeout(hideTimeout);

    const text = trigger.getAttribute('data-tooltip');
    if (!text || !text.trim()) return;

    // Reuse existing tooltip or create new one
    if (!currentTooltip) {
      currentTooltip = createTooltip(text);
    } else {
      currentTooltip.firstChild.textContent = text;
    }

    if (currentTrigger && currentTrigger !== trigger) {
      currentTrigger.removeAttribute('aria-describedby');
    }

    currentTrigger = trigger;
    currentTrigger.setAttribute('aria-describedby', currentTooltip.id);

    const preferredPosition = trigger.getAttribute('data-tooltip-position') || 'top';

    // Position tooltip (initially invisible for measurement)
    const { x, y, position } = calculatePosition(trigger, currentTooltip, preferredPosition);

    currentTooltip.style.left = x + 'px';
    currentTooltip.style.top = y + 'px';
    currentTooltip.setAttribute('data-position', position);

    // Show tooltip with animation
    requestAnimationFrame(() => {
      currentTooltip.setAttribute('data-visible', 'true');
    });
  }

  /**
   * Hide tooltip
   */
  function hideTooltip() {
    clearTimeout(showTimeout);

    if (!currentTooltip) return;

    currentTooltip.setAttribute('data-visible', 'false');

    hideTimeout = setTimeout(() => {
      if (currentTrigger) {
        currentTrigger.removeAttribute('aria-describedby');
      }

      if (currentTooltip && currentTooltip.parentNode) {
        currentTooltip.remove();
        currentTooltip = null;
        currentTrigger = null;
      }
    }, getHideDuration());
  }

  /**
   * Handle mouse enter
   */
  function handleMouseEnter(event) {
    const trigger = event.target.closest('[data-tooltip]');
    if (!trigger) return;

    clearTimeout(hideTimeout);
    showTimeout = setTimeout(() => {
      showTooltip(trigger);
    }, SHOW_DELAY);
  }

  /**
   * Handle mouse leave
   */
  function handleMouseLeave(event) {
    const trigger = event.target.closest('[data-tooltip]');
    if (!trigger) return;

    clearTimeout(showTimeout);
    hideTimeout = setTimeout(() => {
      hideTooltip();
    }, HIDE_DELAY);
  }

  /**
   * Handle focus
   */
  function handleFocus(event) {
    const trigger = event.target.closest('[data-tooltip]');
    if (!trigger) return;

    clearTimeout(hideTimeout);
    showTooltip(trigger);
  }

  /**
   * Handle blur
   */
  function handleBlur(event) {
    const trigger = event.target.closest('[data-tooltip]');
    if (!trigger) return;

    hideTooltip();
  }

  /**
   * Handle Escape key
   */
  function handleKeyDown(event) {
    if (event.key === 'Escape' && currentTooltip) {
      hideTooltip();
      if (currentTrigger) {
        currentTrigger.focus();
      }
    }
  }

  /**
   * Handle scroll and resize
   */
  function handleScrollOrResize() {
    if (currentTooltip && currentTrigger) {
      const preferredPosition = currentTrigger.getAttribute('data-tooltip-position') || 'top';
      const { x, y, position } = calculatePosition(currentTrigger, currentTooltip, preferredPosition);

      currentTooltip.style.left = x + 'px';
      currentTooltip.style.top = y + 'px';
      currentTooltip.setAttribute('data-position', position);
    }
  }

  /**
   * Initialize tooltip system
   */
  function init() {
    // Use event delegation for performance
    document.addEventListener('mouseenter', handleMouseEnter, true);
    document.addEventListener('mouseleave', handleMouseLeave, true);
    document.addEventListener('focus', handleFocus, true);
    document.addEventListener('blur', handleBlur, true);
    document.addEventListener('keydown', handleKeyDown);

    // Reposition tooltip on scroll/resize
    window.addEventListener('scroll', handleScrollOrResize, true);
    window.addEventListener('resize', handleScrollOrResize);
  }

  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose API for manual control if needed
  window.tooltips = {
    show: function (element) {
      if (element && element.hasAttribute('data-tooltip')) {
        showTooltip(element);
      }
    },
    hide: hideTooltip
  };
})();
