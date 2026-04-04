const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const assert = require('node:assert/strict');

function loadScript(context, relativePath) {
  const absolutePath = path.join(process.cwd(), relativePath);
  const source = fs.readFileSync(absolutePath, 'utf8');
  vm.runInContext(source, context, { filename: absolutePath });
}

test('photo upload submit handler posts to the animal photos endpoint', async () => {
  const requests = [];
  const formDataEntries = [['photos[]', 'example']];
  class FakeFormData {
    constructor() {
      this.entries = () => formDataEntries[Symbol.iterator]();
    }
  }

  const context = vm.createContext({
    console,
    FormData: FakeFormData,
    window: {
      CatarmanAnimals: {},
      CatarmanApi: {
        request: async (url, options) => {
          requests.push({ url, options });
          return { data: { error: null, message: 'ok' } };
        },
        extractError: (payload) => payload?.error?.message || 'Unknown error',
      },
      CatarmanDom: {
        escapeHtml: (value) => String(value),
      },
      toast: {
        success: () => {},
        error: () => {
          throw new Error('did not expect error toast');
        },
      },
      CatarmanApp: {
        reload: () => {
          requests.push({ reload: true });
        },
      },
      location: {
        reload: () => {
          requests.push({ locationReload: true });
        },
      },
    },
    document: {
      addEventListener: () => {},
    },
  });

  loadScript(context, 'public/assets/js/animals/shared.js');
  loadScript(context, 'public/assets/js/animals/photo-upload.js');

  assert.equal(typeof context.window.CatarmanAnimals.createPhotoUploadSubmitHandler, 'function');

  const handler = context.window.CatarmanAnimals.createPhotoUploadSubmitHandler({
    animalId: 42,
    csrfToken: 'csrf-token',
    formFactory: () => new FakeFormData(),
  });

  await handler({ preventDefault() {} });

  assert.equal(requests[0].url, '/api/animals/42/photos');
  assert.equal(requests[0].options.method, 'POST');
  assert.equal(requests[0].options.csrfToken, 'csrf-token');
  assert.ok(requests.some((entry) => entry.reload === true));
});
