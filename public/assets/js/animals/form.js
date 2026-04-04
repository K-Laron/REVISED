(function (ns) {
  function toggleConditionalSection(section, isVisible) {
    if (!section) {
      return;
    }

    section.hidden = !isVisible;
    section.querySelectorAll('input, select, textarea, button').forEach((field) => {
      if (field.type === 'hidden') {
        return;
      }

      field.disabled = !isVisible;
    });
  }

  ns.registerInitializer(function bindAnimalForm(root) {
    const form = root.getElementById('animal-form');
    if (!form || form.dataset.formBound === 'true') {
      return;
    }

    form.dataset.formBound = 'true';

    const photoInput = form.querySelector('[data-photo-input]');
    const preview = form.querySelector('[data-photo-preview]');
    const speciesSelect = form.querySelector('[data-breed-species]');
    const breedSelect = form.querySelector('[data-breed-select]');
    const intakeTypeSelect = form.querySelector('[data-intake-type]');
    const locationField = form.querySelector('[data-location-found-field]');
    const surrenderField = form.querySelector('[data-surrender-reason-field]');
    const broughtBySection = form.querySelector('[data-brought-by-section]');
    const authoritySection = form.querySelector('[data-authority-section]');

    function updateBreedOptions() {
      const species = speciesSelect?.value || '';
      Array.from(breedSelect?.options || []).forEach((option) => {
        if (!option.dataset.species) {
          return;
        }

        option.hidden = option.dataset.species !== species;
      });

      if (breedSelect?.selectedOptions?.[0]?.hidden) {
        breedSelect.value = '';
      }
    }

    function updateConditionalFields() {
      const intakeType = intakeTypeSelect?.value || '';
      const showLocationField = intakeType === 'Stray';
      const showSurrenderField = intakeType === 'Owner Surrender';
      const showBroughtBySection = ['Owner Surrender', 'Confiscated', 'Transfer'].includes(intakeType);
      const showAuthoritySection = ['Stray', 'Confiscated'].includes(intakeType);

      toggleConditionalSection(locationField, showLocationField);
      toggleConditionalSection(surrenderField, showSurrenderField);
      toggleConditionalSection(broughtBySection, showBroughtBySection);
      toggleConditionalSection(authoritySection, showAuthoritySection);
    }

    speciesSelect?.addEventListener('change', updateBreedOptions);
    intakeTypeSelect?.addEventListener('change', updateConditionalFields);
    updateBreedOptions();
    updateConditionalFields();

    photoInput?.addEventListener('change', () => {
      if (!preview) {
        return;
      }

      preview.innerHTML = '';
      Array.from(photoInput.files || []).forEach((file) => {
        const reader = new FileReader();
        reader.onload = () => {
          const img = document.createElement('img');
          img.src = reader.result;
          preview.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const mode = form.dataset.mode;
      const token = form.querySelector('input[name="_token"]')?.value || '';

      try {
        let result;
        if (mode === 'create') {
          ({ data: result } = await ns.apiRequest('/api/animals', {
            method: 'POST',
            csrfToken: token,
            body: new FormData(form)
          }));
        } else {
          const params = new URLSearchParams(new FormData(form));
          ({ data: result } = await ns.apiRequest('/api/animals/' + form.dataset.animalId, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            csrfToken: token,
            body: params.toString()
          }));
        }

        if (!result || result.error) {
          window.toast?.error('Animal save failed', ns.extractError(result));
          return;
        }

        window.toast?.success('Animal saved', result.message);
        window.CatarmanApp?.navigate?.(result.data.redirect) || (window.location.href = result.data.redirect);
      } catch (error) {
        console.error(error);
        window.toast?.error('Animal save failed', 'Unexpected error while saving the animal.');
      }
    });
  });
})(window.CatarmanAnimals);
