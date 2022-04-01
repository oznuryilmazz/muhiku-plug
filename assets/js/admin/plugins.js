/* global mhk_plugins_params */
jQuery(function ($) {
  $(document.body).on(
    "click",
    'tr[data-plugin="muhiku-plug/muhiku-plug.php"] span.deactivate a',
    function (e) {
      var isUpdateNotice = $(
        'tr.plugin-update-tr[data-plugin="muhiku-plug/muhiku-plug.php"]'
      );
      if (isUpdateNotice.length || $(this).hasClass("hasNotice")) {
        return true;
      }

      e.preventDefault();

      $(this).addClass("hasNotice");

      var data = {
        action: "muhiku_forms_deactivation_notice",
        security: mhk_plugins_params.deactivation_nonce,
      };

      $.post(mhk_plugins_params.ajax_url, data, function (response) {
        $('tr[data-plugin="muhiku-plug/muhiku-plug.php"]')
          .addClass("updated")
          .last()
          .after(response.fragments.deactivation_notice);
      }).fail(function (xhr) {
        window.console.log(xhr.responseText);
      });
    }
  );
});
