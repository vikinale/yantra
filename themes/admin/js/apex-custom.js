var options = {
    series: [
        { name: "Net Profit", color: "#0652DD", data: [44, 55, 57, 56, 61, 58, 63, 60, 66] },
        { name: "Revenue", color: "#ef5777", data: [76, 85, 101, 98, 87, 105, 91, 114, 94] },
        { name: "Free Cash Flow", data: [35, 41, 36, 26, 45, 48, 52, 53, 41] },
    ],
    chart: { type: "bar", height: 300 },
    plotOptions: { bar: { horizontal: !1, columnWidth: "55%", endingShape: "rounded" } },
    dataLabels: { enabled: !1 },
    stroke: { show: !0, width: 2, colors: ["transparent"] },
    xaxis: { categories: ["Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct"] },
    yaxis: { title: { text: "$ (thousands)" } },
    fill: { opacity: 1 },
    tooltip: {
        y: {
            formatter: function (e) {
                return "$ " + e + " thousands";
            },
        },
    },
},
chart = new ApexCharts(document.querySelector("#chart"), options);
chart.render();
var options = { series: [44, 55, 41, 17, 15], color: "#0652DD", chart: { type: "donut", height: 300 }, responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: "bottom" } } }] },
chart = new ApexCharts(document.querySelector("#pie-chart"), options);
chart.render();
var options = {
    series: [44, 55, 67, 83],
    color: "#6D5D6E",
    chart: { height: 300, type: "radialBar" },
    plotOptions: {
        radialBar: {
            dataLabels: {
                name: { fontSize: "22px" },
                value: { fontSize: "16px" },
                total: {
                    show: !0,
                    label: "Total",
                    formatter: function (e) {
                        return 249;
                    },
                },
            },
        },
    },
    labels: ["Apples", "Oranges", "Bananas", "Berries"],
},
chart = new ApexCharts(document.querySelector("#chart-donut"), options);
chart.render();
var orderstatistics = {
    chart: { height: 300, type: "bar", animations: { enabled: !0, easing: "easeinout", speed: 1e3 }, dropShadow: { enabled: !0, opacity: 0.1, blur: 2, left: -1, top: 5 }, zoom: { enabled: !1 }, toolbar: { show: !1 } },
    plotOptions: { bar: { horizontal: !1, borderRadius: 3, columnWidth: "50%", endingShape: "rounded" } },
    colors: ["#0652DD", "#198754"],
    dataLabels: { enabled: !1 },
    grid: { borderColor: "#f3f3f4", strokeDashArray: 4, xaxis: { lines: { show: !0 } }, yaxis: { lines: { show: !1 } }, padding: { top: 0, right: 0, bottom: 0, left: 0 } },
    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    legend: { position: "top", horizontalAlign: "right", offsetY: 0, fontSize: "14px", fontFamily: "Nunito Sans, sans-serif", markers: { width: 9, height: 9, strokeWidth: 0, radius: 20 }, itemMargin: { horizontal: 5, vertical: 0 } },
    tooltip: { theme: "light", marker: { show: !0 }, x: { show: !1 } },
    stroke: { show: !0, colors: ["transparent"], width: 3 },
    series: [
        { name: "Sales", data: [4200, 4600, 4200, 3800, 4500, 4300, 3800, 4900, 4600, 4e3, 4800, 5100] },
        { name: "Revenue", data: [4900, 4800, 3900, 3600, 4e3, 3700, 4e3, 4200, 3800, 3900, 4100, 5900] },
    ],
    xaxis: { crosshairs: { show: !0 }, labels: { offsetX: 0, offsetY: 5, style: { colors: "#8380ae", fontSize: "12px" } }, tooltip: { enabled: !1 } },
    yaxis: {
        labels: {
            formatter: function (e, t) {
                return e / 1e3 + "K";
            },
            offsetX: -10,
            offsetY: 0,
            style: { colors: "#8380ae", fontSize: "12px" },
        },
    },
    responsive: [{ breakpoint: 600, options: { chart: { height: 230 }, plotOptions: { bar: { columnWidth: "70%" } } } }],
},
order_statistics = new ApexCharts(document.querySelector("#OrderStatistics"), orderstatistics);
order_statistics.render();
var options = {
    series: [67],
    chart: { height: 200, type: "radialBar", offsetY: -10 },
    plotOptions: {
        radialBar: {
            startAngle: -135,
            endAngle: 135,
            dataLabels: {
                name: { fontSize: "13px", color: void 0, offsetY: 120 },
                value: {
                    offsetY: 50,
                    fontSize: "14px",
                    color: void 0,
                    formatter: function (e) {
                        return e + "%";
                    },
                },
            },
        },
    },
    fill: { type: "gradient", gradient: { shade: "dark", shadeIntensity: 0.15, inverseColors: !1, opacityFrom: 1, opacityTo: 1, stops: [0, 50, 65, 91] } },
    stroke: { dashArray: 4 },
    labels: [""],
},
chart = new ApexCharts(document.querySelector("#redial-chart"), options);
chart.render();
