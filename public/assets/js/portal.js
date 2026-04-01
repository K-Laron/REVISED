(() => {
  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

  const parseResponse = async (response) => {
    let result;
    try {
      result = await response.json();
    } catch {
      if (!response.ok) {
        throw { message: 'The server returned an unexpected response.', errors: {} };
      }

      return { success: true, data: {}, meta: {}, message: '' };
    }

    if (!response.ok) {
      const errors = result.error?.details ?? {};
      const message = result.error?.message ?? 'Request failed.';
      throw { message, errors };
    }

    return result;
  };

  const formatErrors = (errors) => {
    const parts = [];
    Object.values(errors).forEach((messages) => {
      if (Array.isArray(messages)) {
        parts.push(...messages);
      }
    });

    return parts.join(' ');
  };

  const compactRegisterError = (fieldName, message) => {
    if (!message) {
      return '';
    }

    switch (fieldName) {
      case 'first_name':
      case 'last_name':
        return message.includes('at least 2 characters') ? 'Use at least 2 characters.' : message;
      case 'middle_name':
        return 'Use 100 characters or fewer.';
      case 'phone':
        return message.includes('required') ? '' : 'Use a valid PH mobile number.';
      case 'email':
        return message.includes('required') ? '' : 'Enter a valid email.';
      case 'zip_code':
        return message.includes('required') ? '' : 'Use letters, numbers, or dashes.';
      case 'address_line1':
        return 'Required.';
      case 'address_line2':
        return 'Use 255 characters or fewer.';
      case 'city':
      case 'province':
        return 'Required.';
      case 'password':
        return message.includes('at least 8 characters')
          ? 'Use at least 8 characters.'
          : 'Use uppercase, lowercase, number, and symbol.';
      case 'password_confirmation':
        return 'Passwords do not match.';
      default:
        return message;
    }
  };

  const registerValidators = {
    first_name: (value) => {
      if (!value.trim()) {
        return '';
      }
      if (value.trim().length < 2) {
        return 'Use at least 2 characters.';
      }
      return '';
    },
    last_name: (value) => {
      if (!value.trim()) {
        return '';
      }
      if (value.trim().length < 2) {
        return 'Use at least 2 characters.';
      }
      return '';
    },
    middle_name: (value) => value.trim().length > 100 ? 'Use 100 characters or fewer.' : '',
    phone: (value) => {
      if (!value.trim()) {
        return '';
      }
      if (!/^(?:\+63\d{10}|09\d{9})$/.test(value.trim())) {
        return 'Use a valid PH mobile number.';
      }
      return '';
    },
    email: (value) => {
      if (!value.trim()) {
        return '';
      }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) {
        return 'Enter a valid email.';
      }
      return '';
    },
    zip_code: (value) => {
      if (!value.trim()) {
        return '';
      }
      if (!/^[A-Za-z0-9-]+$/.test(value.trim())) {
        return 'Use letters, numbers, or dashes.';
      }
      return '';
    },
    address_line1: (value) => !value.trim() ? '' : '',
    address_line2: (value) => value.trim().length > 255 ? 'Use 255 characters or fewer.' : '',
    city: (value) => !value.trim() ? '' : '',
    province: (value) => !value.trim() ? '' : '',
    password: (value) => {
      if (!value) {
        return '';
      }
      if (value.length < 8) {
        return 'Use at least 8 characters.';
      }
      if (!/[A-Z]/.test(value) || !/[a-z]/.test(value) || !/\d/.test(value) || !/[^A-Za-z0-9]/.test(value)) {
        return 'Use uppercase, lowercase, number, and symbol.';
      }
      return '';
    },
    password_confirmation: (value, values) => {
      if (!value) {
        return '';
      }
      if (value !== values.password) {
        return 'Passwords do not match.';
      }
      return '';
    }
  };

  const optionalRegisterFields = new Set(['middle_name', 'address_line2']);
  const requiredRegisterFields = new Set([
    'first_name',
    'last_name',
    'phone',
    'email',
    'zip_code',
    'address_line1',
    'city',
    'province',
    'password',
    'password_confirmation'
  ]);

  const getRegisterValues = (form) => Object.fromEntries(new FormData(form).entries());

  const evaluatePasswordStrength = (value) => {
    if (!value) {
      return { level: 'empty', label: 'Use uppercase, lowercase, number, and symbol.' };
    }

    let score = 0;
    if (value.length >= 8) score += 1;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
    if (/\d/.test(value)) score += 1;
    if (/[^A-Za-z0-9]/.test(value)) score += 1;
    if (value.length >= 12 && score < 4) score += 1;

    if (score <= 1) {
      return { level: 'weak', label: 'Weak password.' };
    }
    if (score === 2) {
      return { level: 'fair', label: 'Fair. Add more variation.' };
    }
    if (score === 3) {
      return { level: 'good', label: 'Good. Add length or one more character type.' };
    }

    return { level: 'strong', label: 'Strong password.' };
  };

  const setFieldError = (form, fieldName, message, isInvalid = Boolean(message)) => {
    const input = form.elements[fieldName];
    const errorNode = form.querySelector(`[data-field-error="${fieldName}"]`);

    if (!input || !errorNode) {
      return;
    }

    errorNode.textContent = message;
    input.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
  };

  const setRegisterSummary = (form, message) => {
    const summaryNode = form.querySelector('#portal-register-errors');
    if (!summaryNode) {
      return;
    }

    summaryNode.hidden = !message;
    summaryNode.textContent = message || '';
  };

  const isRequiredRegisterFieldEmpty = (fieldName, values) => requiredRegisterFields.has(fieldName)
    && String(values[fieldName] ?? '').trim() === '';

  const isRequiredRegisterServerError = (fieldName, message) => {
    if (!requiredRegisterFields.has(fieldName) || !message) {
      return false;
    }

    const normalized = String(message).trim().toLowerCase();
    return normalized.includes('required');
  };

  const updatePasswordStrength = (form) => {
    const shell = form.querySelector('[data-password-strength]');
    const text = form.querySelector('[data-password-strength-text]');
    const passwordValue = String(form.elements.password?.value ?? '');

    if (!shell || !text) {
      return;
    }

    const result = evaluatePasswordStrength(passwordValue);
    shell.dataset.strengthLevel = result.level;
    text.textContent = result.label;
  };

  const validateRegisterField = (form, fieldName) => {
    const validator = registerValidators[fieldName];
    const values = getRegisterValues(form);

    if (!validator) {
      return true;
    }

    const message = validator(String(values[fieldName] ?? ''), values);
    setFieldError(form, fieldName, message, isRequiredRegisterFieldEmpty(fieldName, values) || message !== '');

    return !isRequiredRegisterFieldEmpty(fieldName, values) && message === '';
  };

  const validateRegisterForm = (form) => {
    const names = Object.keys(registerValidators);
    const isValid = names.every((fieldName) => validateRegisterField(form, fieldName));
    return isValid;
  };

  const applyRegisterServerErrors = (form, errors) => {
    let consumedFieldError = false;
    Object.keys(registerValidators).forEach((fieldName) => {
      const rawMessage = Array.isArray(errors?.[fieldName]) ? String(errors[fieldName][0] || '') : '';
      const message = compactRegisterError(fieldName, rawMessage);
      const isInvalid = isRequiredRegisterServerError(fieldName, rawMessage) || message !== '';
      if (isInvalid) {
        consumedFieldError = true;
      }
      setFieldError(form, fieldName, message || '', isInvalid);
    });

    return consumedFieldError;
  };

  const bindRegisterForm = () => {
    const form = document.getElementById('portal-register-form');
    if (!form) {
      return;
    }

    updatePasswordStrength(form);

    Object.keys(registerValidators).forEach((fieldName) => {
      const input = form.elements[fieldName];
      if (!input) {
        return;
      }

      const existingError = form.querySelector(`[data-field-error="${fieldName}"]`)?.textContent?.trim() || '';
      if (existingError) {
        input.setAttribute('aria-invalid', 'true');
      }

      input.addEventListener('input', () => {
        setRegisterSummary(form, '');
        if (fieldName === 'password') {
          updatePasswordStrength(form);
        }

        if (optionalRegisterFields.has(fieldName)) {
          const currentError = form.querySelector(`[data-field-error="${fieldName}"]`)?.textContent?.trim() || '';
          if (currentError) {
            validateRegisterField(form, fieldName);
          }
        } else {
          validateRegisterField(form, fieldName);
        }

        if (fieldName === 'password') {
          validateRegisterField(form, 'password_confirmation');
        }
      });

      input.addEventListener('blur', () => {
        validateRegisterField(form, fieldName);
      });
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      setRegisterSummary(form, '');

      if (!validateRegisterForm(form)) {
        return;
      }

      const formData = new FormData(form);
      const payload = Object.fromEntries(formData.entries());

      try {
        const result = await parseResponse(await fetch('/api/adopt/register', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': payload._token
          },
          body: JSON.stringify(payload)
        }));

        window.toast?.success('Account created', 'You can now sign in to the adoption portal.');
        window.location.href = result.data.redirect;
      } catch (error) {
        if (error instanceof TypeError) {
          setRegisterSummary(form, 'Unable to reach the application server. Retrying with a standard form submission.');
          form.submit();
          return;
        }

        const consumedFieldError = applyRegisterServerErrors(form, error.errors ?? {});
        if (!consumedFieldError) {
          setRegisterSummary(form, formatErrors(error.errors ?? {}) || error.message);
          window.toast?.error('Registration failed', formatErrors(error.errors ?? {}) || error.message);
        }
      }
    });
  };

  const renderApplications = (applications) => {
    const list = document.getElementById('portal-my-applications-list');
    if (!list) {
      return;
    }

    if (!Array.isArray(applications) || applications.length === 0) {
      list.innerHTML = '<p class="text-muted">No applications submitted yet.</p>';
      return;
    }

    list.innerHTML = applications.map((application) => `
      <article class="portal-status-card">
        <div class="cluster" style="justify-content: space-between;">
          <strong>${escapeHtml(application.application_number)}</strong>
          <span class="badge badge-info">${escapeHtml(application.status.replaceAll('_', ' '))}</span>
        </div>
        <p class="text-muted">${application.animal_name ? `${escapeHtml(application.animal_name)}${application.animal_code ? ` • ${escapeHtml(application.animal_code)}` : ''}` : 'Preference-based application'}</p>
        <p class="portal-card-meta">Created ${escapeHtml(application.created_at)}</p>
        ${application.rejection_reason ? `<p class="text-muted">Reason: ${escapeHtml(application.rejection_reason)}</p>` : ''}
      </article>
    `).join('');
  };

  const refreshApplications = async () => {
    const response = await fetch('/api/adopt/my-applications', {
      headers: { 'Accept': 'application/json' }
    });
    const result = await parseResponse(response);
    renderApplications(result.data);
  };

  const bindApplyForm = () => {
    const form = document.getElementById('portal-apply-form');
    if (!form) {
      return;
    }

    const errorNode = document.getElementById('portal-apply-errors');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      errorNode.hidden = true;

      const formData = new FormData(form);
      const csrfToken = formData.get('_token');

      ['agrees_to_policies', 'agrees_to_home_visit', 'agrees_to_return_policy'].forEach((field) => {
        if (!formData.has(field)) {
          formData.append(field, '0');
        }
      });

      try {
        await parseResponse(await fetch('/api/adopt/apply', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: formData
        }));

        window.toast?.success('Application submitted', 'Your application is now pending review.');
        form.reset();
        await refreshApplications();
      } catch (error) {
        errorNode.hidden = false;
        errorNode.textContent = formatErrors(error.errors ?? {}) || error.message;
        window.toast?.error('Submission failed', errorNode.textContent);
      }
    });
  };

  const bindLogout = () => {
    const button = document.querySelector('[data-portal-logout]');
    if (!button) {
      return;
    }

    button.addEventListener('click', async () => {
      try {
        const result = await parseResponse(await fetch('/api/auth/logout', {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': button.dataset.csrfToken || ''
          },
          body: JSON.stringify({})
        }));

        window.location.href = result.data.redirect;
      } catch (error) {
        window.toast?.error('Logout failed', error.message || 'Unable to end the current session.');
      }
    });
  };

  const bindFeaturedCarousel = () => {
    const carousel = document.querySelector('[data-featured-carousel]');
    if (!carousel) {
      return;
    }

    if (carousel.dataset.carouselBound === 'true') {
      return;
    }

    carousel.dataset.carouselBound = 'true';

    const slides = Array.from(carousel.querySelectorAll('[data-carousel-slide]'));
    const indicators = Array.from(carousel.querySelectorAll('[data-carousel-indicator]'));
    const previousButton = document.querySelector('[data-carousel-prev]');
    const nextButton = document.querySelector('[data-carousel-next]');

    if (slides.length === 0) {
      return;
    }

    let activeIndex = 0;
    let autoRotateTimer = null;

    const setSlideState = (slide, state) => {
      slide.classList.toggle('is-active', state === 'active');
      slide.classList.toggle('is-preview', state === 'next');
      slide.classList.toggle('is-previous', state === 'previous');
    };

    const updateUi = (index) => {
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
    };

    const showIndex = (index) => {
      const safeIndex = (index + slides.length) % slides.length;
      updateUi(safeIndex);
    };

    const stopAutoRotate = () => {
      if (autoRotateTimer !== null) {
        window.clearTimeout(autoRotateTimer);
        autoRotateTimer = null;
      }
    };

    const scheduleAutoRotate = () => {
      stopAutoRotate();
      if (slides.length <= 1 || document.hidden) {
        return;
      }

      autoRotateTimer = window.setTimeout(() => {
        showIndex(activeIndex + 1);
        scheduleAutoRotate();
      }, 3000);
    };

    const goToSlideHref = (slide) => {
      const href = String(slide?.dataset.slideHref ?? '').trim();
      if (!href) {
        return;
      }

      window.location.href = href;
    };

    if (previousButton) {
      previousButton.addEventListener('click', () => {
        showIndex(activeIndex - 1);
        scheduleAutoRotate();
      });
    }

    if (nextButton) {
      nextButton.addEventListener('click', () => {
        showIndex(activeIndex + 1);
        scheduleAutoRotate();
      });
    }

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
  };

  bindRegisterForm();
  bindApplyForm();
  bindLogout();
  bindFeaturedCarousel();
})();
