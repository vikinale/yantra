!(function (e) {
    var a = echarts.init(document.getElementById("another_basic_funnel"));
    (option = {
        tooltip: { trigger: "item", formatter: "{a} <br/>{b} : {c}%" },
        color: ["#17a2b8", "#dc3545", "#ffc107", "#989ebf", "#24ccda"],
        legend: { data: ["Show", "Click on", "access", "advisory", "Order"], padding: [5, 5, 60, 5] },
        calculable: !0,
        series: [
            {
                name: "Funnel map",
                type: "funnel",
                x: "10%",
                y: 50,
                y2: 50,
                width: " 80%",
                min: 0,
                max: 100,
                minSize: " 0%",
                maxSize: " 100%",
                sort: "descending",
                gap: 10,
                itemStyle: {
                    normal: { borderColor: "#fff", borderWidth: 1, label: { show: !0, position: "inside" }, labelLine: { show: !1, length: 10, lineStyle: { width: 1, type: "solid" } } },
                    emphasis: { borderColor: "#fff", borderWidth: 5, label: { show: !0, formatter: "{b}:{c}%", textStyle: { fontSize: 18, color: "#111" } }, labelLine: { show: !0 } },
                },
                data: [
                    { value: 60, name: "access" },
                    { value: 40, name: "advisory" },
                    { value: 20, name: "Order" },
                    { value: 80, name: "Click on" },
                    { value: 100, name: "Show" },
                ],
            },
        ],
    }),
        a.setOption(option),
        e(window).on("resize", function () {
            null != a && null != a && a.resize();
        });
    var l = echarts.init(document.getElementById("multiples_funnels"));
    (option = {
        color: ["#6156ce", "#07e0c4", "#17a2b8", "#f8538d", "#f58b22"],
        tooltip: { trigger: "item", formatter: "{a} <br/>{b} : {c}%" },
        legend: { data: ["Show", "Click on", "access", "advisory", "Order"] },
        calculable: !0,
        series: [
            {
                name: "expected",
                type: "funnel",
                x: "10%",
                width: "80%",
                itemStyle: { normal: { label: { formatter: "{b} expected" }, labelLine: { show: !1 } }, emphasis: { label: { position: "inside", formatter: "{b} expected : {c}%", textStyle: { color: "#000" } } } },
                data: [
                    { value: 60, name: "access" },
                    { value: 40, name: "advisory" },
                    { value: 20, name: "Order" },
                    { value: 80, name: "Click on" },
                    { value: 100, name: "Show" },
                ],
            },
            {
                name: "actual",
                type: "funnel",
                x: "10%",
                width: "80%",
                maxSize: "80%",
                itemStyle: { normal: { borderColor: "#fff", borderWidth: 2, label: { position: "inside", formatter: "{c}%", textStyle: { color: "#fff" } } }, emphasis: { label: { position: "inside", formatter: "{b}actual : {c}%" } } },
                data: [
                    { value: 30, name: "access" },
                    { value: 10, name: "advisory" },
                    { value: 5, name: "Order" },
                    { value: 50, name: "Click on" },
                    { value: 80, name: "Show" },
                ],
            },
        ],
    }),
        l.setOption(option),
        e(window).on("resize", function () {
            null != l && null != l && l.resize();
        });
})(jQuery);
