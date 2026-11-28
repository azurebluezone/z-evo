/**
 * Analytics report charts (payments, courier, returns, coupons).
 *
 * The page carries a chart specification in a JSON data attribute; this file
 * only renders what the server computed. No number is invented here, and a
 * page with no chart spec renders nothing (the Blade shows an explicit empty
 * state instead of a broken canvas).
 *
 * Spec shape:
 *   { charts: [ { el, type, labels, series, height?, stacked? } ] }
 */
(function () {
    "use strict";

    var holder = document.querySelector("[data-report-analytics]");
    if (!holder || typeof ApexCharts === "undefined") {
        return;
    }

    var payload;
    try {
        payload = JSON.parse(holder.getAttribute("data-report-analytics"));
    } catch (error) {
        return;
    }

    var money = function (value) {
        return "BDT " + Number(value || 0).toLocaleString("en-US");
    };

    var palette = ["#696cff", "#71dd37", "#ffab00", "#03c3ec", "#ff3e1d", "#8592a3"];

    (payload.charts || []).forEach(function (spec) {
        var el = document.querySelector(spec.el);
        if (!el) {
            return;
        }

        var options = {
            chart: {
                type: spec.type,
                height: spec.height || 300,
                parentHeightOffset: 0,
                toolbar: { show: false },
                stacked: spec.stacked === true
            },
            series: spec.series || [],
            labels: spec.labels || [],
            colors: spec.colors || palette,
            dataLabels: { enabled: false },
            stroke: { curve: "smooth", width: 3 },
            grid: { borderColor: "#e7e9f0", strokeDashArray: 6 },
            legend: { position: "bottom", horizontalAlign: "left" },
            xaxis: {
                categories: spec.labels || [],
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { fontSize: "12px" } }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return money(value);
                    }
                }
            }
        };

        if (spec.type === "donut" || spec.type === "pie") {
            options.legend = { show: false };
            options.stroke = { width: 2, colors: ["#ffffff"] };
        }

        if (spec.type === "bar") {
            options.plotOptions = {
                bar: { columnWidth: "45%", borderRadius: 4 }
            };
        }

        try {
            new ApexCharts(el, options).render();
        } catch (error) {
            /* A chart that cannot render must not break the page. */
        }
    });
})();
