(function () {
  if (window.CatarmanKennelUtils) {
    return;
  }

  function groupBy(items, key) {
    return items.reduce((groups, item) => {
      const value = item[key] || 'Unassigned';
      groups[value] = groups[value] || [];
      groups[value].push(item);
      return groups;
    }, {});
  }

  function extractError(result) {
    return window.CatarmanApi.extractError(result);
  }

  function escapeHtml(value) {
    return window.CatarmanDom.escapeHtml(value);
  }

  function escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function extractZoneToken(zone) {
    const parts = String(zone || '')
      .toUpperCase()
      .trim()
      .match(/[A-Z0-9]+/g);

    if (!parts || parts.length === 0) {
      return '';
    }

    const token = parts[parts.length - 1];
    return token.length <= 3 ? token : token.slice(0, 3);
  }

  function generateKennelCode(zoneToken, existingKennelCodes) {
    let nextSequence = 1;
    const pattern = new RegExp(`^K-${escapeRegExp(zoneToken)}(\\d+)$`);

    existingKennelCodes.forEach((code) => {
      const match = String(code).match(pattern);
      if (!match) {
        return;
      }

      nextSequence = Math.max(nextSequence, Number.parseInt(match[1], 10) + 1);
    });

    return `K-${zoneToken}${String(nextSequence).padStart(2, '0')}`;
  }

  function isAnimalCompatible(animal, kennel) {
    const speciesMatch = kennel.allowed_species === 'Any' || animal.species === kennel.allowed_species;
    const sizeMatch = animal.size === kennel.size_category;
    return speciesMatch && sizeMatch;
  }

  window.CatarmanKennelUtils = {
    escapeHtml,
    extractError,
    extractZoneToken,
    generateKennelCode,
    groupBy,
    isAnimalCompatible
  };
})();
