!(function (e) {
    e.fn.classyNav = function (n) {
        var a = e(".classy-nav-container"),
            s = e(".classynav ul"),
            o = e(".classynav > ul > li"),
            i = e(".classy-navbar-toggler"),
            l = e(".classycloseIcon"),
            t = e(".navbarToggler"),
            d = e(".classy-menu"),
            r = e(window),
            c = e.extend({ breakpoint: 991, openCloseSpeed: 500, megaopenCloseSpeed: 800 }, n);
        return this.each(function () {
            function n() {
                window.innerWidth <= c.breakpoint ? a.removeClass("breakpoint-off").addClass("breakpoint-on") : a.removeClass("breakpoint-on").addClass("breakpoint-off");
            }
            i.on("click", function () {
                t.toggleClass("active"), d.toggleClass("menu-on");
            }),
                l.on("click", function () {
                    d.removeClass("menu-on"), t.removeClass("active");
                }),
                o.has(".dropdown").addClass("cn-dropdown-item"),
                o.has(".megamenu").addClass("megamenu-item"),
                s.find("li a").each(function () {
                    e(this).next().length > 0 && e(this).parent("li").addClass("has-down").append('<span class="dd-trigger"></span>');
                }),
                s.find("li .dd-trigger").on("click", function (n) {
                    n.preventDefault(), e(this).parent("li").children("ul").stop(!0, !0).slideToggle(c.openCloseSpeed), e(this).parent("li").toggleClass("active");
                }),
                e(".megamenu-item").removeClass("has-down"),
                s.find("li .dd-trigger").on("click", function (n) {
                    n.preventDefault(), e(this).parent("li").children(".megamenu").slideToggle(c.megaopenCloseSpeed);
                }),
                n(),
                r.on("resize", function () {
                    n();
                }),
                !0 === c.sideMenu && a.addClass("sidebar-menu-on").removeClass("breakpoint-off");
        });
    };
})(jQuery);
