!(function (e) {
    "use strict";
    var t = new Date();
    t.setDate(t.getDate()),
        e("#dashboardDate").html(
            ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"][t.getDay()] +
                ", " +
                t.getDate() +
                " " +
                ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"][t.getMonth()]
        ),
        setInterval(function () {
            var t = new Date().getSeconds();
            e("#sec").html((t < 10 ? "0" : "") + t);
        }, 1e3),
        setInterval(function () {
            var t = new Date().getMinutes();
            e("#min").html((t < 10 ? "0" : "") + t);
        }, 1e3),
        setInterval(function () {
            var t = new Date().getHours();
            e("#hours").html((t < 10 ? "0" : "") + t);
        }, 1e3);
})(jQuery);
