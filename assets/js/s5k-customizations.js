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

function normalizeUserSelections(input) {
  let result = [];

  // Loop through an array of arrays
  for (let i = 0; i < input.length; i++) {
    let currentArray = input[i];

    // Convert each array to slug and push it to the result array
    for (let j = 0; j < currentArray.length; j++) {
      currentArray[j] = currentArray[j].toLowerCase().replace(/\s/g, '-');
    }

    result.push(currentArray.join(' - '));
  }

  return result;
}

function countUserSelections(input) {
  const countObject = {};

  input.forEach(item => {
    if (countObject[item]) {
      countObject[item]++; // Increment the count if the item exists in the object
    } else {
      countObject[item] = 1; // Initialize the count to 1 if the item doesn't exist in the object
    }
  });

  const resultArray = [];

  for (const key in countObject) {
    if (countObject.hasOwnProperty(key)) {
      resultArray.push({ [key]: countObject[key] });
    }
  }

  return resultArray;
}

document.addEventListener('DOMContentLoaded', function() {
  console.log('s5k-customizations.js loaded');

  let selectedClasses;
  let selectedOptions;
  let groupedOptions;
  let availableVariations;
  let form = document.querySelector('form.cart') || false;

  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      selectedClasses = [];
      selectedOptions = [];
      groupedOptions = [];
      const selectFields = document.querySelectorAll('.rndropdown select')

      selectFields.forEach(function (select) {
        if ('' !== select.options[select.selectedIndex].value) {
          let selectedText = select.options[select.selectedIndex]?.text;
          if (!selectedText.includes('Male') && !selectedText.includes('Female')) {
            selectedClasses.push(getParentClass(select, 'rnColumnContainer'));
            selectedOptions.push(selectedText);
          }
        }
      })

      for (let i = 0; i < selectedOptions.length; i += 2) {
        groupedOptions.push([selectedOptions[i], selectedOptions[i + 1]]);
      }

      jQuery.post(
        window?.s5k?.wpData?.ajaxUrl,
        {
          'action': 'fetch_product_variations_stock',
          'nonce': window?.s5k?.wpData?.nonce,
          'product_ID': window?.s5k?.wpData?.productID
        },
        function(response) {
          // Return an array of objects, instead of an object of objects
          availableVariations = Object.entries(response.data).map((e) => ({[e[0]]: e[1]}));
        }
      );


      console.log('Selected options: ', groupedOptions);
      console.log('Normalized selected options: ', normalizeUserSelections(groupedOptions));
      console.log('Selected count: ', countUserSelections(normalizeUserSelections(groupedOptions)));
      console.log('Available variations: ', availableVariations);
      // console.log('Selected classes: ', selectedClasses);
    });
  }

});
