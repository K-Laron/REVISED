const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

function getClassNames(node) {
  return node.className.split(/\s+/).filter(Boolean);
}

function setClassNames(node, names) {
  node.className = Array.from(new Set(names.filter(Boolean))).join(' ');
}

class FakeElement {
  constructor({ className = '', dataset = {}, name = null, type = 'div', textContent = '' } = {}) {
    this.className = className;
    this.dataset = { ...dataset };
    this.name = name;
    this.type = type;
    this.textContent = textContent;
    this.children = [];
    this.listeners = {};
    this.hidden = false;
    this.src = '';
    this.value = '';
    this.files = [];
    this.disabled = false;
    this.draggable = false;
    this.parentNode = null;
    this.classList = {
      add: (...tokens) => setClassNames(this, [...getClassNames(this), ...tokens]),
      remove: (...tokens) => setClassNames(this, getClassNames(this).filter((token) => !tokens.includes(token))),
      contains: (token) => getClassNames(this).includes(token),
      toggle: (token, force) => {
        const hasToken = getClassNames(this).includes(token);
        const shouldAdd = typeof force === 'boolean' ? force : !hasToken;
        if (shouldAdd) {
          this.classList.add(token);
          return true;
        }

        this.classList.remove(token);
        return false;
      },
    };
  }

  addEventListener(type, listener) {
    this.listeners[type] = listener;
  }

  async dispatch(type, event = {}) {
    const listener = this.listeners[type];
    if (!listener) {
      return;
    }

    await listener({
      type,
      target: this,
      defaultPrevented: false,
      preventDefault() {
        this.defaultPrevented = true;
      },
      stopPropagation() {
      },
      ...event,
    });
  }

  appendChild(child) {
    child.parentNode = this;
    this.children.push(child);
    return child;
  }

  replaceChildren(...children) {
    children.forEach((child) => {
      child.parentNode = this;
    });
    this.children = children;
  }

  querySelector(selector) {
    return this.querySelectorAll(selector)[0] ?? null;
  }

  querySelectorAll(selector) {
    const selectors = selector.split(',').map((entry) => entry.trim());
    const matches = [];
    const visit = (node) => {
      node.children.forEach((child) => {
        if (selectors.some((entry) => matchesSelector(child, entry))) {
          matches.push(child);
        }

        visit(child);
      });
    };

    visit(this);
    return matches;
  }
}

function matchesSelector(node, selector) {
  if (selector === '.animal-photo-upload-form') {
    return getClassNames(node).includes('animal-photo-upload-form');
  }

  if (selector === '[data-photo-upload-input]') {
    return Object.hasOwn(node.dataset, 'photoUploadInput');
  }

  if (selector === '[data-photo-upload-preview]') {
    return Object.hasOwn(node.dataset, 'photoUploadPreview');
  }

  if (selector === '[data-animal-photo-grid]') {
    return Object.hasOwn(node.dataset, 'animalPhotoGrid');
  }

  if (selector === '[data-animal-photo-stage]') {
    return Object.hasOwn(node.dataset, 'animalPhotoStage');
  }

  if (selector === '[data-animal-photo-empty]') {
    return Object.hasOwn(node.dataset, 'animalPhotoEmpty');
  }

  if (selector === '[data-animal-photo-heading]') {
    return Object.hasOwn(node.dataset, 'animalPhotoHeading');
  }

  if (selector === '[data-animal-photo-item]') {
    return Object.hasOwn(node.dataset, 'animalPhotoItem');
  }

  if (selector === '[data-animal-photo-action]') {
    return Object.hasOwn(node.dataset, 'animalPhotoAction');
  }

  if (selector === '[data-animal-photo-collection]') {
    return Object.hasOwn(node.dataset, 'animalPhotoCollection');
  }

  if (selector.startsWith('input[name="')) {
    const expected = selector.slice('input[name="'.length, -2);
    return node.type === 'input' && node.name === expected;
  }

  return false;
}

function createPhotoCollectionRoot(animalId) {
  const stage = new FakeElement({ dataset: { animalPhotoStage: '' } });
  const grid = new FakeElement({ dataset: { animalPhotoGrid: '' } });
  const empty = new FakeElement({ dataset: { animalPhotoEmpty: '' } });
  const heading = new FakeElement({ dataset: { animalPhotoHeading: '' } });

  const root = new FakeElement({
    dataset: { animalPhotoCollection: '', animalId: String(animalId), csrfToken: 'test-token' }
  });
  root.replaceChildren(stage, grid, empty, heading);
  return { root, stage, grid, empty, heading };
}

function findActionButton(node, action) {
  return node.querySelectorAll('[data-animal-photo-action]').find((child) => child.dataset.animalPhotoAction === action) ?? null;
}

function createContext({ form, root, requestHandler, reloadCountRef }) {
  const context = {
    console,
    FormData: class FakeFormData {
      constructor(target) {
        this.target = target;
      }
    },
    FileReader: class FakeFileReader {
      readAsDataURL() {
      }
    },
    window: {
      CatarmanApi: {
        request: requestHandler,
        extractError: () => 'Request failed',
      },
      CatarmanDom: {
        escapeHtml: (value) => String(value),
      },
      toast: {
        success() {},
        error(message) {
          throw new Error(message);
        },
      },
      CatarmanApp: {
        reload() {
          reloadCountRef.count += 1;
        },
      },
      location: {
        reload() {
          reloadCountRef.count += 1;
        },
      },
    },
    document: {
      querySelectorAll(selector) {
        if (selector === '.animal-photo-upload-form') {
          return form ? [form] : [];
        }

        if (selector === '[data-animal-photo-collection]') {
          return root ? [root] : [];
        }

        return [];
      },
      querySelector(selector) {
        if (root && selector === '[data-animal-photo-collection][data-animal-id="7"]') {
          return root;
        }

        return null;
      },
      createElement(type) {
        return new FakeElement({ type });
      },
    },
  };

  context.globalThis = context;
  return context;
}

function loadScript(context, relativePath) {
  const absolutePath = path.join(process.cwd(), relativePath);
  const source = fs.readFileSync(absolutePath, 'utf8');
  vm.runInContext(source, context, { filename: absolutePath });
}

function loadAnimalsModules(context) {
  vm.createContext(context);
  loadScript(context, 'public/assets/js/animals/shared.js');
  loadScript(context, 'public/assets/js/animals/photo-upload.js');
  return context.window.CatarmanAnimals;
}

function flushAsync() {
  return new Promise((resolve) => setImmediate(resolve));
}

async function testInlineUpload() {
  const photoInput = new FakeElement({ dataset: { photoUploadInput: '' }, type: 'input' });
  const preview = new FakeElement({ dataset: { photoUploadPreview: '' } });
  const tokenInput = new FakeElement({ name: '_token', type: 'input' });
  tokenInput.value = 'test-token';

  const form = new FakeElement({
    className: 'animal-photo-upload-form',
    dataset: { animalId: '7' },
    type: 'form',
  });
  form.replaceChildren(tokenInput, photoInput, preview);

  const { root, stage, grid, empty, heading } = createPhotoCollectionRoot(7);
  const reloadCountRef = { count: 0 };
  const context = createContext({
    form,
    root,
    reloadCountRef,
    requestHandler: async () => ({
      data: {
        data: [
          { id: 11, file_path: 'uploads/animals/7/animal-photo-1.jpg', is_primary: 1 },
          { id: 12, file_path: 'uploads/animals/7/animal-photo-2.jpg', is_primary: 0 },
        ],
        message: 'Photos uploaded successfully.',
      },
    }),
  });
  const animals = loadAnimalsModules(context);

  animals.bindPhotoUpload(context.document);
  await form.dispatch('submit');
  await flushAsync();

  assert.strictEqual(reloadCountRef.count, 0);
  assert.strictEqual(stage.children.length, 1);
  assert.strictEqual(stage.children[0].src, '/uploads/animals/7/animal-photo-1.jpg');
  assert.strictEqual(grid.children.length, 2);
  assert.strictEqual(grid.children[1].children[0].src, '/uploads/animals/7/animal-photo-2.jpg');
  assert.strictEqual(empty.hidden, true);
  assert.strictEqual(heading.hidden, false);
  assert.strictEqual(photoInput.value, '');
}

async function testInlineDelete() {
  const { root, stage, grid, empty, heading } = createPhotoCollectionRoot(7);
  const reloadCountRef = { count: 0 };
  const requests = [];
  const context = createContext({
    form: null,
    root,
    reloadCountRef,
    requestHandler: async (url, options = {}) => {
      requests.push({ url, options });
      return { data: { data: {}, message: 'Photo deleted successfully.' } };
    },
  });
  const animals = loadAnimalsModules(context);

  animals.syncAnimalPhotoCollection(root, [
    { id: 11, file_path: 'uploads/animals/7/animal-photo-1.jpg', is_primary: 1 },
    { id: 12, file_path: 'uploads/animals/7/animal-photo-2.jpg', is_primary: 0 },
  ]);
  animals.bindAnimalPhotoCollections(context.document);

  const deleteButton = findActionButton(grid.children[0], 'delete');
  await deleteButton.dispatch('click');
  await flushAsync();

  assert.strictEqual(reloadCountRef.count, 0);
  assert.strictEqual(requests.length, 1);
  assert.strictEqual(requests[0].url, '/api/animals/7/photos/11');
  assert.strictEqual(requests[0].options.method, 'DELETE');
  assert.strictEqual(stage.children[0].src, '/uploads/animals/7/animal-photo-2.jpg');
  assert.strictEqual(grid.children.length, 1);
  assert.strictEqual(grid.children[0].dataset.photoId, '12');
  assert.strictEqual(empty.hidden, true);
  assert.strictEqual(heading.hidden, false);
}

async function testDragDropReorder() {
  const { root, stage, grid } = createPhotoCollectionRoot(7);
  const reloadCountRef = { count: 0 };
  const requests = [];
  const context = createContext({
    form: null,
    root,
    reloadCountRef,
    requestHandler: async (url, options = {}) => {
      requests.push({ url, options });
      return {
        data: {
          data: [
            { id: 13, file_path: 'uploads/animals/7/animal-photo-3.jpg', is_primary: 1 },
            { id: 11, file_path: 'uploads/animals/7/animal-photo-1.jpg', is_primary: 0 },
            { id: 12, file_path: 'uploads/animals/7/animal-photo-2.jpg', is_primary: 0 },
          ],
          message: 'Photos reordered successfully.',
        },
      };
    },
  });
  const animals = loadAnimalsModules(context);

  animals.syncAnimalPhotoCollection(root, [
    { id: 11, file_path: 'uploads/animals/7/animal-photo-1.jpg', is_primary: 1 },
    { id: 12, file_path: 'uploads/animals/7/animal-photo-2.jpg', is_primary: 0 },
    { id: 13, file_path: 'uploads/animals/7/animal-photo-3.jpg', is_primary: 0 },
  ]);
  animals.bindAnimalPhotoCollections(context.document);

  const dataTransfer = {
    effectAllowed: 'move',
    dropEffect: 'move',
    setData() {
    },
  };

  await grid.children[2].dispatch('dragstart', { dataTransfer });
  await grid.children[0].dispatch('dragover', { dataTransfer });
  await grid.children[0].dispatch('drop', { dataTransfer });
  await grid.children[2].dispatch('dragend', { dataTransfer });
  await flushAsync();

  assert.strictEqual(reloadCountRef.count, 0);
  assert.strictEqual(requests[0].url, '/api/animals/7/photos/reorder');
  assert.strictEqual(requests[0].options.method, 'PUT');
  assert.strictEqual(requests[0].options.body, JSON.stringify({ photo_ids: [13, 11, 12] }));
  assert.strictEqual(stage.children[0].src, '/uploads/animals/7/animal-photo-3.jpg');
  assert.strictEqual(grid.children[0].dataset.photoId, '13');
  assert.strictEqual(grid.children[1].dataset.photoId, '11');
}

async function main() {
  await testInlineUpload();
  await testInlineDelete();
  await testDragDropReorder();
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
