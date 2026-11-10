  (function () {
    const checkAll = document.getElementById('invoice-check-all');
    if (!checkAll) return;
    const items = () => Array.from(document.querySelectorAll('.invoice-check'));
    checkAll.addEventListener('change', function () {
      items().forEach(cb => { cb.checked = checkAll.checked; });
    });
  })();
