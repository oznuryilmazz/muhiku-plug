/* global muhiku_forms_admin_tools */
jQuery(function ($) {
  $("#log-viewer-select").on("click", "h2 a.page-title-action", function (evt) {
    evt.stopImmediatePropagation();
    return window.confirm(muhiku_forms_admin_tools.delete_log_confirmation);
  });
});
