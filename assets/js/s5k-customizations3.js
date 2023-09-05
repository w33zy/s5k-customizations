function getParentClass(element, className) {
  let currentElement = element.parentElement;

  while (currentElement !== null) {
    if (currentElement.classList.contains(className)) {
      // Filter "rndropdown", "rednaoWooField" from array
      return Array.from(currentElement.classList).filter((item) => item !== 'rndropdown' && item !== 'rednaoWooField')[0];
    }
    currentElement = currentElement.parentElement;
  }

  return '';
}

function getParentElement(element, className) {
  let currentElement = element.parentElement;

  while (currentElement !== null) {
    if (currentElement.classList.contains(className)) {
      // Filter "rndropdown", "rednaoWooField" from array
      return currentElement;
    }
    currentElement = currentElement.parentElement;
  }

  return undefined;
}

/**
 *
 * @param {Map<int, string>} map
 * @param {string[]} targetValues
 * @returns {int[]}
 */
function getOutOfStockIndices(map, targetValues) {
  const keys = [];

  map.forEach((value, key) => {
    if (targetValues.includes(value)) {
      keys.push(key);
    }
  });

  return keys;
}

/**
 * Gets the variations that have exceeded the stock
 * @param {{selection: string, count: int}}userSelectionsCount
 * @param {{variation: string, stock: int}}variationsStock
 * @returns {string[]}
 */
function getExceededStockVariations(userSelectionsCount, variationsStock) {

  let stockExceeded = [];

  for (const [selection, count] of Object.entries(userSelectionsCount)) {
    for (const [variation, stock] of Object.entries(variationsStock)){
      if (count > stock) {
        stockExceeded.push(variation);
      }
    }
  }

  return stockExceeded;
}

/**
 * Gets the user selections from the form
 * @param {NodeList} nodeList
 * @returns {Map<int, Set>}
 */
function getUserSelections(nodeList) {
  let selectedOptions = new Map();

  for (const repeater of nodeList) {

    const repeaterIndex = parseInt(repeater.dataset.index);
    const selectFields = repeater.querySelectorAll('select');

    for (const selectField of selectFields) {

      if ('' !== selectField.options[selectField.selectedIndex].value) {
        let selectedText = selectField.options[selectField.selectedIndex]?.text;
        if (!selectedText.includes('Male') && !selectedText.includes('Female')) {

          if (selectedOptions.has(repeaterIndex)) {
            selectedOptions.get(repeaterIndex).add(selectedText);
          } else {
            selectedOptions.set(repeaterIndex, (new Set()).add(selectedText));
          }

        }
      }

    }

  }

  return selectedOptions;
}

/**
 * Counts the number of occurrences of each value in a Map
 * @param {Map<int, string>} map
 * @returns {{selection: string, count: int}}
 */
function countUserSelections(map) {
  const countObject = {};

  map.forEach((value) => {
    if (countObject[value]) {
      countObject[value]++;
    } else {
      countObject[value] = 1;
    }
  });

  return countObject;
}

/**
 * Converts a Map of Sets to a Map of a slugified string
 * @param {Map<int, Set>} input
 * @returns {Map<int, string>}
 */
function normalizeUserSelections(input) {
  let result = new Map;

  for (const [key, value] of input) {
    result.set(key, Array.from(value).map(slugify).join('-') );
  }

  return result;
}

/**
 * Converts a string to a slug
 *
 * @param {string} text
 * @returns {string}
 */
function slugify(text) {
  return text
    .toLowerCase()
    .replace(/ /g, "-")
    .replace(/[^\w-]+/g, "");
}

document.addEventListener('DOMContentLoaded', function() {

  let selectedOptions;
  let normalizedSelectedOptions;
  let userSelectionsCount;

  if (document.querySelector('form.cart')) {

    document.querySelector('form.cart').addEventListener('submit', function(e) {
      e.preventDefault();

      let stockExceeded = [];
      const repeaters = document.querySelectorAll('.rnRepeaterItem');
      const variationsStock = window?.s5k?.wpData?.variationsStock;

      selectedOptions = getUserSelections(repeaters);
      normalizedSelectedOptions = normalizeUserSelections(selectedOptions);
      userSelectionsCount = countUserSelections(normalizedSelectedOptions);

      console.log('User Selection Count: ', userSelectionsCount);

      stockExceeded = getExceededStockVariations(userSelectionsCount, variationsStock);
      console.log('Stock Exceeded: ', stockExceeded);

      if (stockExceeded.length > 0) {

        getOutOfStockIndices(normalizedSelectedOptions, stockExceeded)

        for (const index of getOutOfStockIndices(normalizedSelectedOptions, stockExceeded)) {
          const repeater = document.querySelector(`.rnRepeaterItem[data-index="${index}"]`);

          if (repeater) {
            repeater.style.border = '2px solid red';
            repeater.style.padding = '1rem';
          }
        }

        e.preventDefault();
      } else {

        for (const [key, value] of selectedOptions) {
          const repeater = document.querySelector(`.rnRepeaterItem[data-index="${key}"]`);
          repeater.style.border = 'none';
          repeater.style.padding = '0px';
        }
      }

    });

  }

});
