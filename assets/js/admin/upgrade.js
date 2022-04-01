/* global mhk_upgrade, mhk_data */
jQuery(function ($) {
  /**
   * Upgrade actions.
   */
  var mhk_upgrade_actions = {
    init: function () {
      $(document.body).on(
        "click dragstart",
        ".mhk-registered-item.upgrade-modal",
        this.field_upgrade
      );
      $(document.body).on(
        "click dragstart",
        ".mhk-registered-item.enable-stripe-model",
        this.enable_stripe_model
      );
      $(document.body).on(
        "click dragstart",
        ".muhiku-plug-field-option-row.upgrade-modal",
        this.feature_upgrade
      );
      $(document.body).on(
        "click dragstart",
        ".mhk-upgradable-feature, .muhiku-plug-btn-group span.upgrade-modal",
        this.feature_upgrade
      );
    },
    feature_upgrade: function (e) {
      e.preventDefault();

      mhk_upgrade_actions.upgrade_modal(
        $(this).data("feature") ? $(this).data("feature") : $(this).text()
      );
    },
    field_upgrade: function (e) {
      e.preventDefault();

      mhk_upgrade_actions.upgrade_modal(
        $(this).data("feature")
          ? $(this).data("feature")
          : $(this).text() + " field"
      );
    },
    upgrade_modal: function (feature) {
      var message = mhk_upgrade.upgrade_message.replace(/%name%/g, feature);

      $.alert({
        title: feature + " " + mhk_upgrade.upgrade_title,
        icon: "dashicons dashicons-lock",
        content: message,
        type: "red",
        boxWidth: "565px",
        buttons: {
          confirm: {
            text: mhk_upgrade.upgrade_button,
            btnClass: "btn-confirm",
            keys: ["enter"],
            action: function () {
              window.open(mhk_upgrade.upgrade_url, "_blank");
            },
          },
          cancel: {
            text: mhk_data.i18n_ok,
          },
        },
      });
    },
    enable_stripe_model: function (e) {
      e.preventDefault();
      $.alert({
        title: mhk_upgrade.enable_stripe_title,
        content: mhk_upgrade.enable_stripe_message,
        icon: "dashicons dashicons-info",
        type: "blue",
        buttons: {
          confirm: {
            text: mhk_data.i18n_close,
            btnClass: "btn-confirm",
            keys: ["enter"],
          },
        },
      });
    },
  };

  mhk_upgrade_actions.init();
});
