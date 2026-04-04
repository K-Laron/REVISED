(function (ns) {
  function createPhotoUploadSubmitHandler(options) {
    return async function handlePhotoUploadSubmit(event) {
      event?.preventDefault?.();

      const { animalId, csrfToken, formFactory } = options;
      const { data: result } = await ns.apiRequest('/api/animals/' + animalId + '/photos', {
        method: 'POST',
        csrfToken,
        body: formFactory()
      });

      if (result.error) {
        window.toast?.error('Photo upload failed', ns.extractError(result));
        return;
      }

      window.toast?.success('Photos uploaded', result.message);
      window.CatarmanApp?.reload?.() || window.location.reload();
    };
  }

  ns.createPhotoUploadSubmitHandler = createPhotoUploadSubmitHandler;

  ns.registerInitializer(function bindPhotoUpload(root) {
    const form = root.querySelector('.animal-photo-upload-form');
    if (!form || form.dataset.photoUploadBound === 'true') {
      return;
    }

    form.dataset.photoUploadBound = 'true';

    const handler = createPhotoUploadSubmitHandler({
      animalId: form.dataset.animalId,
      csrfToken: form.querySelector('input[name="_token"]')?.value || '',
      formFactory: () => new FormData(form),
    });

    form.addEventListener('submit', (event) => {
      handler(event).catch((error) => {
        console.error(error);
        window.toast?.error('Photo upload failed', 'Unexpected error while uploading photos.');
      });
    });
  });
})(window.CatarmanAnimals);
