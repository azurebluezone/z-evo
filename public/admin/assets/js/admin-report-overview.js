/**
 * Sales & Profit Overview charts.
 *
 * The page carries its payload in a JSON data attribute; this file only
 * renders. No data is invented here and no chart is drawn when the payload is
 * empty (the Blade shows an explicit empty state instead).
 */
(function () {
    "use strict";

    var holder = document.querySelector("[data-report-overview]");
    if (!holder) {
        return;
    }

    var payload;
    try {
        payload = JSON.parse(holder.getAttribute("data-report-overview"));
    } catch (error) {
        return;
    }

    if (typeof ApexCharts === "undefined") {
        return;
    }

    var money = function (value) {
        return "BDT " + Number(value || 0).toLocaleString("en-US");
    };

    var trend = payload.trend || {};
    var trendEl = document.querySelector("#salesTrendChart");
    if (trendEl && trend.labels && trend.labels.length) {
        new ApexCharts(trendEl, {
            chart: {
                type: "area",
                height: 320,
                parentHeightOffset: 0,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            series: [
                { name: "Ordered", data: trend.ordered || [] },
                { name: "Delivered", data: trend.delivered || [] }
            ],
            colors: ["#696cff", "#71dd37"],
            stroke: { curve: "smooth", width: 3 },
            fill: {
                type: "gradient",
                gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 100] }
            },
            dataLabels: { enabled: false },
            grid: { borderColor: "#e7e9f0", strokeDashArray: 6 },
            xaxis: {
                categories: trend.labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { fontSize: "12px" } }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return money(value);
                    }
                }
            },
            legend: { position: "top", horizontalAlign: "left" },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return money(value);
                    }
                }
            }
        }).render();
    }

    var mix = payload.payments || {};
    var mixEl = document.querySelector("#paymentMixChart");
    if (mixEl && mix.labels && mix.labels.length) {
        new ApexCharts(mixEl, {
            chart: { type: "donut", height: 260 },
            labels: mix.labels,
            series: mix.totals || [],
            colors: ["#696cff", "#71dd37", "#ffab00", "#03c3ec", "#ff3e1d", "#8592a3"],
            legend: { show: false },
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ["#ffffff"] },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return money(value);
                    }
                }
            }
        }).render();
    }
})();
