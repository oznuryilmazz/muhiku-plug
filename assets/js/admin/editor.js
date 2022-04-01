(function ($) {
  "use strict";

  $(function () {
    // Close modal
    var mhkModalClose = function () {
      if ($("#mhk-modal-select-form").length) {
        $("#mhk-modal-select-form").get(0).selectedIndex = 0;
        $("#mhk-modal-checkbox-title, #mhk-modal-checkbox-description").prop(
          "checked",
          false
        );
      }
      $("#mhk-modal-backdrop, #mhk-modal-wrap").css("display", "none");
      $(document.body).removeClass("modal-open");
    };
    // Open modal when media button is clicked
    $(document).on("click", ".mhk-insert-form-button", function (event) {
      event.preventDefault();
      $("#mhk-modal-backdrop, #mhk-modal-wrap").css("display", "block");
      $(document.body).addClass("modal-open");
    });
    // Close modal on close or cancel links
    $(document).on(
      "click",
      "#mhk-modal-close, #mhk-modal-cancel a",
      function (event) {
        event.preventDefault();
        mhkModalClose();
      }
    );
    // Insert shortcode into TinyMCE
    $(document).on("click", "#mhk-modal-submit", function (event) {
      event.preventDefault();
      var shortcode;
      shortcode =
        '[everest_form id="' + $("#mhk-modal-select-form").val() + '"';
      if ($("#mhk-modal-checkbox-title").is(":checked")) {
        shortcode = shortcode + ' title="true"';
      }
      if ($("#mhk-modal-checkbox-description").is(":checked")) {
        shortcode = shortcode + ' description="true"';
      }
      shortcode = shortcode + "]";
      wp.media.editor.insert(shortcode);
      mhkModalClose();
    });
  });
})(jQuery);
