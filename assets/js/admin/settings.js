/* global muhiku_forms_settings_params, jconfirm */
(function ($, params) {
  // Confirm defaults.
  $(document).ready(function () {
    // jquery-confirm defaults.
    jconfirm.defaults = {
      closeIcon: true,
      backgroundDismiss: true,
      escapeKey: true,
      animationBounce: 1,
      useBootstrap: false,
      theme: "modern",
      boxWidth: "400px",
      columnClass: "mhk-responsive-class",
    };
  });

  // Color picker
  $(".colorpick")
    .iris({
      change: function (event, ui) {
        $(this)
          .parent()
          .find(".colorpickpreview")
          .css({ backgroundColor: ui.color.toString() });
      },
      hide: true,
      border: true,
    })

    .on("click focus", function (event) {
      event.stopPropagation();
      $(".iris-picker").hide();
      $(this).closest("td").find(".iris-picker").show();
      $(this).data("original-value", $(this).val());
    })

    .on("change", function () {
      if ($(this).is(".iris-error")) {
        var original_value = $(this).data("original-value");

        if (original_value.match(/^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/)) {
          $(this).val($(this).data("original-value")).change();
        } else {
          $(this).val("").change();
        }
      }
    });

  $("body").on("click", function () {
    $(".iris-picker").hide();
  });

  // Edit prompt
  $(function () {
    var changed = false;

    $("input, textarea, select, checkbox").change(function () {
      changed = true;
    });

    $(".mhk-nav-tab-wrapper a").click(function () {
      if (changed) {
        window.onbeforeunload = function () {
          return params.i18n_nav_warning;
        };
      } else {
        window.onbeforeunload = "";
      }
    });

    $(".submit :input").click(function () {
      window.onbeforeunload = "";
    });
  });

  // Select all/none
  $(".muhiku-plug").on("click", ".select_all", function () {
    $(this).closest("td").find("select option").attr("selected", "selected");
    $(this).closest("td").find("select").trigger("change");
    return false;
  });

  $(".muhiku-plug").on("click", ".select_none", function () {
    $(this).closest("td").find("select option").removeAttr("selected");
    $(this).closest("td").find("select").trigger("change");
    return false;
  });

  // Show/hide based on reCAPTCHA type.
  $("input#muhiku_forms_recaptcha_type")
    .change(function () {
      var recaptcha_v2_site_key = $("#muhiku_forms_recaptcha_v2_site_key")
          .parents("tr")
          .eq(0),
        recaptcha_v2_secret_key = $("#muhiku_forms_recaptcha_v2_secret_key")
          .parents("tr")
          .eq(0),
        recaptcha_v2_invisible_site_key = $(
          "#muhiku_forms_recaptcha_v2_invisible_site_key"
        )
          .parents("tr")
          .eq(0),
        recaptcha_v2_invisible_secret_key = $(
          "#muhiku_forms_recaptcha_v2_invisible_secret_key"
        )
          .parents("tr")
          .eq(0),
        recaptcha_v2_invisible = $("#muhiku_forms_recaptcha_v2_invisible")
          .parents("tr")
          .eq(0),
        recaptcha_v3_site_key = $("#muhiku_forms_recaptcha_v3_site_key")
          .parents("tr")
          .eq(0),
        recaptcha_v3_secret_key = $("#muhiku_forms_recaptcha_v3_secret_key")
          .parents("tr")
          .eq(0);
      recaptcha_v3_threshold_score = $(
        "#muhiku_forms_recaptcha_v3_threshold_score"
      )
        .parents("tr")
        .eq(0);
      (hcaptcha_site_key = $("#muhiku_forms_recaptcha_hcaptcha_site_key")
        .parents("tr")
        .eq(0)),
        (hcaptcha_secret_key = $("#muhiku_forms_recaptcha_hcaptcha_secret_key")
          .parents("tr")
          .eq(0));

      if ($(this).is(":checked")) {
        if ("v2" === $(this).val()) {
          if ($("#muhiku_forms_recaptcha_v2_invisible").is(":checked")) {
            recaptcha_v2_site_key.hide();
            recaptcha_v2_secret_key.hide();
            recaptcha_v2_invisible_site_key.show();
            recaptcha_v2_invisible_secret_key.show();
          } else {
            recaptcha_v2_invisible_site_key.hide();
            recaptcha_v2_invisible_secret_key.hide();
            recaptcha_v2_site_key.show();
            recaptcha_v2_secret_key.show();
          }
          recaptcha_v2_invisible.show();
          recaptcha_v3_site_key.hide();
          recaptcha_v3_secret_key.hide();
          hcaptcha_site_key.hide();
          hcaptcha_secret_key.hide();
          recaptcha_v3_threshold_score.hide();
        } else if ("hcaptcha" === $(this).val()) {
          recaptcha_v2_invisible.hide();
          recaptcha_v2_invisible_site_key.hide();
          recaptcha_v2_invisible_secret_key.hide();
          recaptcha_v3_site_key.hide();
          recaptcha_v3_secret_key.hide();
          recaptcha_v3_threshold_score.hide();
          recaptcha_v2_site_key.hide();
          recaptcha_v2_secret_key.hide();
          hcaptcha_site_key.show();
          hcaptcha_secret_key.show();
        } else {
          recaptcha_v2_site_key.hide();
          recaptcha_v2_secret_key.hide();
          recaptcha_v2_invisible.hide();
          recaptcha_v2_invisible_site_key.hide();
          recaptcha_v2_invisible_secret_key.hide();
          hcaptcha_site_key.hide();
          hcaptcha_secret_key.hide();
          recaptcha_v3_site_key.show();
          recaptcha_v3_secret_key.show();
          recaptcha_v3_threshold_score.show();
        }
      }
    })
    .change();

  $("input#muhiku_forms_recaptcha_v2_invisible").change(function () {
    if ($(this).is(":checked")) {
      $("#muhiku_forms_recaptcha_v2_site_key").parents("tr").eq(0).hide();
      $("#muhiku_forms_recaptcha_v2_secret_key").parents("tr").eq(0).hide();
      $("#muhiku_forms_recaptcha_v2_invisible_site_key")
        .parents("tr")
        .eq(0)
        .show();
      $("#muhiku_forms_recaptcha_v2_invisible_secret_key")
        .parents("tr")
        .eq(0)
        .show();
    } else {
      $("#muhiku_forms_recaptcha_v2_site_key").parents("tr").eq(0).show();
      $("#muhiku_forms_recaptcha_v2_secret_key").parents("tr").eq(0).show();
      $("#muhiku_forms_recaptcha_v2_invisible_site_key")
        .parents("tr")
        .eq(0)
        .hide();
      $("#muhiku_forms_recaptcha_v2_invisible_secret_key")
        .parents("tr")
        .eq(0)
        .hide();
    }
  });

  // Send Test Email.
  $(".muhiku_forms_send_email_test").on("click", function (e) {
    e.preventDefault();
    let email = $("#muhiku_forms_email_send_to").val();
    let data = {
      action: "muhiku_forms_send_test_email",
      email: email,
      security: mhk_email_params.ajax_email_nonce,
    };
    $.ajax({
      url: mhk_email_params.ajax_url,
      data: data,
      type: "post",
      beforeSend: function () {
        var spinner = '<i class="mhk-loading mhk-loading-active"></i>';
        $(".muhiku_forms_send_email_test")
          .closest(".muhiku_forms_send_email_test")
          .append(spinner);
        $(".muhiku-froms-send_test_email_notice").remove();
      },
      complete: function (response) {
        var message_string = "";

        $(".muhiku_forms_send_email_test")
          .closest(".muhiku_forms_send_email_test")
          .find(".mhk-loading")
          .remove();
        $(".muhiku-froms-send_test_email_notice").remove();
        if (true === response.responseJSON.success) {
          $("#muhiku_forms_email_send_to").val("");
          message_string =
            '<div id="message" class="updated inline muhiku-froms-send_test_email_notice"><p><strong>' +
            response.responseJSON.data.message +
            "</strong></p></div>";
        } else {
          message_string =
            '<div id="message" class="error inline muhiku-froms-send_test_email_notice"><p><strong>' +
            response.responseJSON.data.message +
            "</strong></p></div>";
        }

        $(".muhiku-plug-settings").find("h2").after(message_string);
      },
    });
  });
})(jQuery, muhiku_forms_settings_params);
