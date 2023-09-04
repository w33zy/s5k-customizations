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
  console.log('s5k-customizations2.js loaded');

  let currentIndex;
  let selectedOptions = new Map();

  // Add an event listener to the document for change events on elements with class ".rnInputPrice"
  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('rnInputPrice')) {

      // Get the current repeater
      const selectedRepeater = getParentElement(e.target, 'rnRepeaterItem');
      console.log('selectedRepeater: ', selectedRepeater);

      // Get data-index attribute of the selected repeater
      const selectedRepeaterIndex = selectedRepeater.getAttribute('data-index');
      console.log('selectedRepeaterIndex: ', selectedRepeaterIndex);

      // Set the current index to the selected repeater index
      if (currentIndex !== selectedRepeaterIndex) {
        currentIndex = selectedRepeaterIndex;
      }

      // console.log('currentIndex: ', currentIndex, selectedRepeaterIndex);

      // if (currentIndex === selectedRepeaterIndex) {
      //   console.log('currentIndex === selectedRepeaterIndex');
      //
      //   const selectField = e.target.options[e.target.selectedIndex]?.text;
      //   let selectedSize;
      //   let selectedDesign;
      //   selectedSize = selectField === 'XS' || selectField === 'S' || selectField === 'M' || selectField === 'L' || selectField === 'XL' || selectField === 'XXL' || selectField === 'XXXL' ? selectField : '';
      //
      //   if (selectedSize) {
      //     selectedDesign = selectField === '2023 Unisex Pink Print' || selectField === '2023 Unisex Multicolour Print' ? selectField : '';
      //   }
      //
      //   console.log(selectedDesign, selectedSize);
      // }

      // if (selectedOptions.has(currentIndex)) {
      //   selectedOptions.get(currentIndex).push(e.target.options[e.target.selectedIndex]?.text);
      // } else {
      //   selectedOptions.set(currentIndex, [e.target.options[e.target.selectedIndex]?.text]);
      // }
      //
      // selectedOptions.forEach((value, key) => console.log(key, value));
    }
  });
});
