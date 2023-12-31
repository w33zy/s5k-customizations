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

const variationNames = {
  'white-unisex-for-my-t-shirt': 'White Unisex "For My" T-shirt',
  'white-unisex-multicolour-print-t-shirt': 'White Unisex Multicolour Print T-shirt',
}

function printExceededStockMessage(userSelectionsCount, availableStock, variationNames) {
  const exceededStockArray = [];

  for (const key in userSelectionsCount) {
    if (userSelectionsCount.hasOwnProperty(key) && availableStock.hasOwnProperty(key)) {
      const userCount = userSelectionsCount[key];
      const stockCount = availableStock[key];

      if (userCount > stockCount) {
        // const exceededAmount = userCount - stockCount;
        exceededStockArray.push({ key, userCount });
      }
    }
  }

  const exceededStockMessages = exceededStockArray.map(item => {
    const keyParts = item.key.split('-');
    const variationKey = keyParts.slice(1).join('-'); // Remove characters up to the first hyphen
    const variationName = variationNames[variationKey];

    return `You have selected ${item.userCount} ${variationName}(s) but there are ${availableStock[item.key]} available.`;
  });

  return exceededStockMessages;
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
      if ( (selection === variation) && count > stock) {
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

    const repeaterIndex = parseInt(repeater.dataset.index) || 0;
    const selectFields = repeater.querySelectorAll('select');

    for (const selectField of selectFields) {

      if ('' !== selectField.options[selectField.selectedIndex].value) {
        let selectedText = selectField.options[selectField.selectedIndex]?.text;
        if (!selectedText.includes('Male')
          && !selectedText.includes('Female')
          && !selectedText.includes('Gulf City Mall')
          && !selectedText.includes('Queen’s Park Oval Ballroom')
        ) {

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

      let stockExceeded = [];
      let repeaters = document.querySelectorAll('.rnRepeaterItem');
      if (repeaters.length === 0) {
        repeaters = document.querySelectorAll('.rednaoExtraProductForm');
      }

      const variationsStock = window?.s5k?.wpData?.variationsStock;

      selectedOptions = getUserSelections(repeaters);
      normalizedSelectedOptions = normalizeUserSelections(selectedOptions);
      userSelectionsCount = countUserSelections(normalizedSelectedOptions);

      stockExceeded = getExceededStockVariations(userSelectionsCount, variationsStock);

      if (stockExceeded.length > 0) {

        for (const index of getOutOfStockIndices(normalizedSelectedOptions, stockExceeded)) {
          const repeater = document.querySelector(`.rnRepeaterItem[data-index="${index}"]`);

          if (repeater) {
            const selectFields = repeater.querySelectorAll('select');
            let count = 0;
            for (const selectField of selectFields) {
              count++;
              if (count === 1) continue; // Skip the first select field as its the gender field

              selectField.parentElement.style.outline = '2px solid red';
              selectField.parentElement.style.padding = '10px';
              selectField.parentElement.style.borderRadius = '4px';


            }
          }
        }

        const message = printExceededStockMessage(userSelectionsCount, variationsStock, variationNames).join('\n');
        alert(message + '\n\nPlease select another design or size.');
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
