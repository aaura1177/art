(function ($) {
  "use strict";

  var $window = $(window);

  $("#sidebarToggle").on("click", function (e) {
    e.preventDefault();
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled");
  });

  $("body.fixed-nav .sidebar").on("mousewheel DOMMouseScroll wheel", function (e) {
    if ($window.width() > 768) {
      var e0 = e.originalEvent,
        delta = e0.wheelDelta || -e0.detail;
      this.scrollTop += (delta < 0 ? 1 : -1) * 30;
      e.preventDefault();
    }
  });

  $(document).scroll(function () {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
      $(".scroll-to-top").fadeIn();
    } else {
      $(".scroll-to-top").fadeOut();
    }
  });

  $(document).on("click", "a.scroll-to-top", function (event) {
    var $anchor = $(this);
    $("html, body").stop().animate(
      {
        scrollTop: $($anchor.attr("href")).offset().top,
      },
      1000,
      "easeInOutExpo"
    );
    event.preventDefault();
  });

  $(document).ready(function () {
    if ($.fn.dataTable && $.fn.dataTable.moment) {
      $.fn.dataTable.moment("DD-MMM-YYYY");
    }
    if ($.fn.DataTable) {
      if ($("#dataTables").length) {
        $("#dataTables").DataTable();
      }
      if ($("#dataTable").length) {
        $("#dataTable").DataTable({ ordering: false });
      }
    }
    if ($.fn.selectpicker) {
      $(".selectpicker").not("#monthEndpo_buyer_select").selectpicker();
    }
  });
})(jQuery);
