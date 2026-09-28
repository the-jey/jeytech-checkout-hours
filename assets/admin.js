(function () {
 'use strict';
 const root = document.querySelector('.jeytech-ch-admin');
 if (!root) return;
 root.querySelectorAll('.jeytech-ch-day').forEach(function (day) {
  const ranges = day.querySelector('.jeytech-ch-ranges');
  const add = day.querySelector('.jeytech-ch-add');
  let counter = ranges.children.length;
  function update() { add.disabled = ranges.children.length >= 12; }
  function append() {
   if (ranges.children.length >= 12) return;
   const template = document.createElement('template');
   template.innerHTML = day.querySelector('template').innerHTML.replaceAll('__INDEX__', String(counter++));
   ranges.append(template.content.cloneNode(true));
   update();
  }
  add.addEventListener('click', append);
  ranges.addEventListener('click', function (event) {
   const button = event.target.closest('.jeytech-ch-remove');
   if (button) { button.closest('.jeytech-ch-range').remove(); update(); }
  });
  day.querySelector('.jeytech-ch-full').addEventListener('click', function () {
   ranges.replaceChildren(); append();
   ranges.querySelector('.jeytech-ch-start').value = '00:00';
   ranges.querySelector('.jeytech-ch-end').value = '24:00';
  });
  update();
 });
}());
