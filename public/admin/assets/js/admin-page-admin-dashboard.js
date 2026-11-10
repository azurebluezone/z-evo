/* Dashboard analytics charts.
   Moved from backEnd/admin/dashboard.blade.php - server data arrives as one
   JSON data attribute (data-dashboard-charts) rendered by the blade:
     trend7, liveVisitorsTrend, weeklySalesWidget  (each may be empty). */
(function () {
    var cfgEl = document.querySelector('[data-dashboard-charts]');
    var cfg = cfgEl ? (function () {
        try { return JSON.parse(cfgEl.getAttribute('data-dashboard-charts')); }
        catch (e) { return {}; }
    })() : {};

    var trend7 = cfg.trend7 || { labels: [], series: [] };
    var liveVisitorsTrend = cfg.liveVisitorsTrend || { series: [] };
    var weeklySalesWidget = cfg.weeklySalesWidget || { labels: [], revenue: [], visitors: [] };
    var salesChart = null;

    function renderSales() {
      var el = document.querySelector('#sales-analytics');
      if (!el || typeof ApexCharts === 'undefined') return;

      var d = trend7 || { labels: [], series: [] };
      var options = {
        chart: { height: 360, type: 'area', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: 'Revenue', data: d.series }],
        xaxis: { categories: d.labels, axisBorder: { show: false }, axisTicks: { show: false } },
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2, stops: [0, 90, 100] } },
        grid: { strokeDashArray: 4, borderColor: '#e0e0e0' },
        colors: ['#696cff'],
        dataLabels: { enabled: false },
        yaxis: { labels: { formatter: function (val) { return '৳' + val; } } },
        tooltip: { y: { formatter: function (val) { return '৳' + val; } } }
      };

      if (salesChart) { salesChart.destroy(); }
      salesChart = new ApexCharts(el, options);
      salesChart.render();
    }

    function renderLiveVisitors() {
      var el = document.querySelector('#liveVisitors');
      if (!el || typeof ApexCharts === 'undefined') return;

      var series = (liveVisitorsTrend && Array.isArray(liveVisitorsTrend.series)) ? liveVisitorsTrend.series : [];
      var options = {
        chart: {
          height: 200,
          type: 'bar',
          toolbar: { show: false },
          fontFamily: 'Inter, sans-serif'
        },
        series: [{ name: 'Live Visitors', data: series }],
        plotOptions: {
          bar: {
            borderRadius: 6,
            columnWidth: '60%',
            distributed: true
          }
        },
        dataLabels: { enabled: false },
        grid: { show: false, padding: { left: 0, right: 0 } },
        xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { show: false },
        colors: ['#72e128'],
        tooltip: { enabled: true }
      };

      var c = new ApexCharts(el, options);
      c.render();
    }

    function renderWeeklySales() {
      var el = document.querySelector('#weeklySalesChart');
      if (!el || typeof ApexCharts === 'undefined') return;

      var labels = (weeklySalesWidget && Array.isArray(weeklySalesWidget.labels)) ? weeklySalesWidget.labels : [];
      var revenue = (weeklySalesWidget && Array.isArray(weeklySalesWidget.revenue)) ? weeklySalesWidget.revenue : [];
      var visitors = (weeklySalesWidget && Array.isArray(weeklySalesWidget.visitors)) ? weeklySalesWidget.visitors : [];

      var options = {
        chart: {
          height: 235,
          type: 'line',
          stacked: false,
          toolbar: { show: false },
          fontFamily: 'Inter, sans-serif'
        },
        series: [
          { name: 'Revenue', type: 'column', data: revenue },
          { name: 'Visitors', type: 'line', data: visitors }
        ],
        stroke: { width: [0, 3], curve: 'smooth' },
        plotOptions: {
          bar: {
            borderRadius: 8,
            columnWidth: '45%'
          }
        },
        dataLabels: { enabled: false },
        xaxis: { categories: labels, axisBorder: { show: false } },
        yaxis: [
          { show: false },
          { opposite: true, show: false }
        ],
        grid: { strokeDashArray: 4, borderColor: '#e0e0e0' },
        colors: ['#696cff', '#ffab00'],
        legend: { show: false },
        tooltip: { shared: true, intersect: false }
      };

      var c = new ApexCharts(el, options);
      c.render();
    }

    function initCharts() {
      if (typeof ApexCharts === 'undefined') {
        setTimeout(initCharts, 100);
        return;
      }
      renderSales();
      renderLiveVisitors();
      renderWeeklySales();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initCharts);
    } else {
      initCharts();
    }
})();
