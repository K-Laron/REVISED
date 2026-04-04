(function (ns) {
  function formatErrors(errors) {
    const parts = [];
    Object.values(errors || {}).forEach((messages) => {
      if (Array.isArray(messages)) {
        parts.push(...messages);
      }
    });

    return parts.join(' ');
  }

  function compactRegisterError(fieldName, message) {
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
  }

  const registerValidators = {
    first_name: (value) => (!value.trim() || value.trim().length >= 2) ? '' : 'Use at least 2 characters.',
    last_name: (value) => (!value.trim() || value.trim().length >= 2) ? '' : 'Use at least 2 characters.',
    middle_name: (value) => value.trim().length > 100 ? 'Use 100 characters or fewer.' : '',
    phone: (value) => !value.trim() || /^(?:\+63\d{10}|09\d{9})$/.test(value.trim()) ? '' : 'Use a valid PH mobile number.',
    email: (value) => !value.trim() || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim()) ? '' : 'Enter a valid email.',
    zip_code: (value) => !value.trim() || /^[A-Za-z0-9-]+$/.test(value.trim()) ? '' : 'Use letters, numbers, or dashes.',
    address_line1: () => '',
    address_line2: (value) => value.trim().length > 255 ? 'Use 255 characters or fewer.' : '',
    city: () => '',
    province: () => '',
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
    password_confirmation: (value, values) => !value || value === values.password ? '' : 'Passwords do not match.'
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

  function getRegisterValues(form) {
    return Object.fromEntries(new FormData(form).entries());
  }

  function evaluatePasswordStrength(value) {
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
  }

  function setFieldError(form, fieldName, message, isInvalid = Boolean(message)) {
    const input = form.elements[fieldName];
    const errorNode = form.querySelector(`[data-field-error="${fieldName}"]`);

    if (!input || !errorNode) {
      return;
    }

    errorNode.textContent = message;
    input.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
  }

  function setRegisterSummary(form, message) {
    const summaryNode = form.querySelector('#portal-register-errors');
    if (!summaryNode) {
      return;
    }

    summaryNode.hidden = !message;
    summaryNode.textContent = message || '';
  }

  function isRequiredRegisterFieldEmpty(fieldName, values) {
    return requiredRegisterFields.has(fieldName) && String(values[fieldName] ?? '').trim() === '';
  }

  function isRequiredRegisterServerError(fieldName, message) {
    if (!requiredRegisterFields.has(fieldName) || !message) {
      return false;
    }

    return String(message).trim().toLowerCase().includes('required');
  }

  function updatePasswordStrength(form) {
    const shell = form.querySelector('[data-password-strength]');
    const text = form.querySelector('[data-password-strength-text]');
    const passwordValue = String(form.elements.password?.value ?? '');

    if (!shell || !text) {
      return;
    }

    const result = evaluatePasswordStrength(passwordValue);
    shell.dataset.strengthLevel = result.level;
    text.textContent = result.label;
  }

  function validateRegisterField(form, fieldName) {
    const validator = registerValidators[fieldName];
    const values = getRegisterValues(form);

    if (!validator) {
      return true;
    }

    const message = validator(String(values[fieldName] ?? ''), values);
    setFieldError(form, fieldName, message, isRequiredRegisterFieldEmpty(fieldName, values) || message !== '');

    return !isRequiredRegisterFieldEmpty(fieldName, values) && message === '';
  }

  function validateRegisterForm(form) {
    return Object.keys(registerValidators).every((fieldName) => validateRegisterField(form, fieldName));
  }

  function applyRegisterServerErrors(form, errors) {
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
  }

  ns.registerInitializer(function bindRegisterForm(root) {
    const form = root.getElementById('portal-register-form');
    if (!form || form.dataset.registerBound === 'true') {
      return;
    }

    form.dataset.registerBound = 'true';
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
        const result = await ns.parseResponse(await fetch('/api/adopt/register', {
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
  });
})(window.CatarmanPortal);
