/* global muhiku_forms_admin, PerfectScrollbar */
(function ($, params) {
  // Colorpicker.
  $(document).on(
    "click",
    ".muhiku-plug-field.muhiku-plug-field-rating",
    function () {
      $(
        ".muhiku-plug-field-option-row-icon_color input.mhk-colorpicker"
      ).wpColorPicker({
        change: function (event) {
          var $this = $(this),
            value = $this.val(),
            id = $this
              .closest(".muhiku-plug-field-option-row")
              .data("field-id"),
            $icons = $("#muhiku-plug-field-" + id + " .rating-icon svg");
          $icons.css("fill", value);
        },
      });
    }
  );

  // Enable Perfect Scrollbar.
  $(document).on("init_perfect_scrollbar", function () {
    var nav_wrapper = $("nav.mhk-nav-tab-wrapper");

    if (nav_wrapper.length >= 1) {
      window.mhk_nav_ps = new PerfectScrollbar("nav.mhk-nav-tab-wrapper", {
        suppressScrollY: true,
        useBothWheelAxes: true,
        wheelPropagation: true,
      });
    }
  });

  // Update Perfect Scrollbar.
  $(window).on("resize orientationchange", function () {
    var resizeTimer,
      nav_wrapper = $("nav.mhk-nav-tab-wrapper");

    if (nav_wrapper.length >= 1) {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        window.mhk_nav_ps.update();
      }, 400);
    }
  });

  // Trigger Perfect Scrollbar.
  $(document).ready(function ($) {
    if ("undefined" !== typeof PerfectScrollbar) {
      $(document).trigger("init_perfect_scrollbar");
    }
  });

  // Field validation error tips.
  $(document.body)
    .on("mhk_add_error_tip", function (e, element, error_type, locale) {
      var offset = element.position();

      if (element.parent().find(".mhk_error_tip").length === 0) {
        element.after(
          '<div class="mhk_error_tip ' +
            error_type +
            '">' +
            locale[error_type] +
            "</div>"
        );
        element
          .parent()
          .find(".mhk_error_tip")
          .css(
            "left",
            offset.left +
              element.width() -
              element.width() / 2 -
              $(".mhk_error_tip").width() / 2
          )
          .css("top", offset.top + element.height())
          .fadeIn("100");
      }
    })

    .on("mhk_remove_error_tip", function (e, element, error_type) {
      element
        .parent()
        .find(".mhk_error_tip." + error_type)
        .fadeOut("100", function () {
          $(this).remove();
        });
    })

    .on("click", "input:not([type=number])", function () {
      $(".mhk_error_tip").fadeOut("100", function () {
        $(this).remove();
      });
    })

    .on(
      "blur",
      ".mhk-input-meta-key[type=text], .mhk-input-number[type=number]",
      function () {
        $(".mhk_error_tip").fadeOut("100", function () {
          $(this).remove();
        });
      }
    )

    .on(
      "change",
      ".mhk-input-meta-key[type=text], .mhk-input-number[type=number]",
      function () {
        var regex;

        if ($(this).is(".mhk-input-number")) {
          regex = new RegExp("[^-0-9]+", "gi");
        } else {
          regex = new RegExp("[^a-z0-9_-]+", "gi");
        }

        var value = $(this).val();
        var newvalue = value.replace(regex, "");

        if (value !== newvalue) {
          $(this).val(newvalue);
        }
      }
    )

    .on("keyup", ".mhk-input-meta-key[type=text]", function () {
      var regex, error;

      if ($(this).is(".mhk-input-meta-key")) {
        regex = new RegExp("[^a-z0-9_-]+", "gi");
        error = "i18n_field_meta_key_error";
      }

      var value = $(this).val();
      var newvalue = value.replace(regex, "");

      if (value !== newvalue) {
        $(document.body).triggerHandler("mhk_add_error_tip", [
          $(this),
          error,
          params,
        ]);
      } else {
        $(document.body).triggerHandler("mhk_remove_error_tip", [
          $(this),
          error,
        ]);
      }
    })

    .on("keyup focus", ".mhk-input-number[type=number]", function () {
      var fieldId = $(this).parent().data("fieldId")
        ? $(this).parent().data("fieldId")
        : $(this).closest(".muhiku-plug-field-option-row").data("field-id");
      var maxField = $(
        "input#muhiku-plug-field-option-" + fieldId + "-max_value"
      );
      var minField = $(
        "input#muhiku-plug-field-option-" + fieldId + "-min_value"
      );
      var maxVal = maxField.val();
      var minVal = minField.val();

      if (0 !== minVal.length && 0 !== maxVal.length) {
        if (parseFloat(minVal) > parseFloat(maxVal)) {
          if ($(this).attr("id").indexOf("min_value") !== -1) {
            $(document.body).triggerHandler("mhk_add_error_tip", [
              $(this),
              "i18n_field_min_value_greater",
              params,
            ]);
          } else {
            $(document.body).triggerHandler("mhk_add_error_tip", [
              $(this),
              "i18n_field_max_value_smaller",
              params,
            ]);
          }
        } else {
          $(document.body).triggerHandler("mhk_remove_error_tip", [
            $(this),
            "i18n_field_max_value_smaller",
          ]);
          $(document.body).triggerHandler("mhk_remove_error_tip", [
            $(this),
            "i18n_field_min_value_greater",
          ]);
        }
      }
    })

    .on("keydown click", ".mhk-max-file-number[type=number]", function (e) {
      if (
        !(
          (e.keyCode > 95 && e.keyCode < 106) ||
          (e.keyCode > 47 && e.keyCode < 58) ||
          e.keyCode == 8
        )
      ) {
        return false;
      }
    })

    .on("click", ".muhiku-plug-field-number", function (e) {
      var $this = $(this);
      var id = $this.data("field-id");
      $(document).on(
        "keydown click",
        "#muhiku-plug-field-option-" + id + "-default_value",
        function (e) {
          if (
            !(
              (e.keyCode > 95 && e.keyCode < 106) ||
              (e.keyCode > 47 && e.keyCode < 58) ||
              e.keyCode == 8
            )
          ) {
            return false;
          }
        }
      );
    })

    .on("click", ".muhiku-plug-field-payment-quantity", function (e) {
      var $this = $(this);
      var id = $this.data("field-id");
      $(document).on(
        "keydown click",
        "#muhiku-plug-field-option-" + id + "-default_value",
        function (e) {
          if (
            !(
              (e.keyCode > 95 && e.keyCode < 106) ||
              (e.keyCode > 47 && e.keyCode < 58) ||
              e.keyCode == 8
            )
          ) {
            return false;
          }
        }
      );
    })

    .on("init_tooltips", function () {
      $(
        ".tips, .help_tip, .muhiku-plug-help-tip, .muhiku-plug-help-tooltip, .muhiku-plug-icon"
      ).tooltipster({
        maxWidth: 200,
        multiple: true,
        interactive: true,
        position: "bottom",
        contentAsHTML: true,
        updateAnimation: false,
        restoration: "current",
        functionInit: function (instance, helper) {
          var $origin = $(helper.origin),
            dataTip = $origin.attr("data-tip");

          if (dataTip) {
            instance.content(dataTip);
          }
        },
      });
    });

  // Dynamic live binding on newly created elements.
  $("body").on(
    "mouseenter",
    ".mhk-content-email-settings-inner .muhiku-plug-help-tooltip:not(.tooltipstered)",
    function () {
      $(this).tooltipster({
        maxWidth: 200,
        multiple: true,
        interactive: true,
        position: "bottom",
        contentAsHTML: true,
        updateAnimation: false,
        restoration: "current",
        functionInit: function (instance, helper) {
          var $origin = $(helper.origin),
            dataTip = $origin.attr("data-tip");
          if (dataTip) {
            instance.content(dataTip);
          }
        },
      });
      $(this).tooltipster("open");
    }
  );

  $(document).on("click", ".muhiku-plug-email-add", function () {
    $(".mhk-content-email-settings-inner .tooltipstered").tooltipster(
      "destroy"
    );
  });

  // Tooltips
  $(document.body).trigger("init_tooltips");

  // Check for new form entries using Heartbeat API.
  $(document).on("heartbeat-send", function (event, data) {
    var $entriesList = $("#muhiku-plug-entries-list"),
      form_id = $entriesList.find("#entries-list").data("form-id");
    last_entry_id = $entriesList.find("#entries-list").data("last-entry-id");

    // Work on entry list table page and check if last entry ID is found.
    if (!$entriesList.length || typeof last_entry_id === "undefined") {
      return;
    }

    // Add custom entries data to Heartbeat data.
    data.mhk_new_entries_form_id = form_id;
    data.mhk_new_entries_last_entry_id = last_entry_id;
  });

  // Display entries list notification if Heartbeat API new form entries check is successful.
  $(document).on("heartbeat-tick", function (event, data) {
    var $entriesList = $("#muhiku-plug-entries-list"),
      columnsCount = $entriesList.find(
        ".wp-list-table thead tr:first-child > :visible"
      ).length;

    // Work on entry list table page and check for new entry notification.
    if (!$entriesList.length || !data.mhk_new_entries_notification) {
      return;
    }

    if (!$entriesList.find(".new-entries-notification").length) {
      $entriesList
        .find(".wp-list-table thead")
        .append(
          '<tr class="new-entries-notification"><td colspan="' +
            columnsCount +
            '"><a href="#new" onClick="window.location.reload(true);"></a></td></tr>'
        );
    }

    $entriesList
      .find(".new-entries-notification a")
      .text(data.mhk_new_entries_notification)
      .slideDown({
        duration: 500,
        start: function () {
          $(this).css({
            display: "block",
          });
        },
      });
  });

  // To play welcome video.
  $(document).on(
    "click",
    "#muhiku-plug-welcome .welcome-video-play",
    function (event) {
      var video =
        '<div class="welcome-video-container"><iframe width="760" height="429" src="https://www.youtube.com/embed/N_HbZccA-Ts?rel=0&amp;showinfo=0&amp;autoplay=1" frameborder="0" allowfullscreen></iframe></div>';

      event.preventDefault();

      $(this).find(".muhiku-froms-welcome-thumb").remove();
      $(this).append(video);
    }
  );

  // Change span with file name when user selects a file.
  $("#muhiku-plug-import").on("change", function (e) {
    var file = $("#muhiku-plug-import").prop("files")[0];

    $("#import-file-name").html(file.name);
  });

  $(".muhiku-plug-export-form-action").on("click", function () {
    var form_id = $(this)
      .closest(".muhiku-plug-export-form")
      .find("#muhiku-plug-form-export")
      .val();

    $(this).closest(".muhiku-plug-export-form").find("#message").remove();

    if (!form_id) {
      $(this)
        .closest(".muhiku-plug-export-form")
        .find("h3")
        .after(
          '<div id="message" class="error inline muhiku-froms-import_notice"><p><strong>' +
            muhiku_forms_admin.i18n_form_export_action_error +
            "</strong></p></div>"
        );
      return false;
    }
  });

  $(".muhiku_forms_import_action").on("click", function () {
    var file_data = $("#muhiku-plug-import").prop("files")[0],
      form_data = new FormData();

    form_data.append("jsonfile", file_data);
    form_data.append("action", "muhiku_forms_import_form_action");
    form_data.append("security", muhiku_forms_admin.ajax_import_nonce);

    $.ajax({
      url: mhk_email_params.ajax_url,
      dataType: "json", // JSON type is expected back from the PHP script.
      cache: false,
      contentType: false,
      processData: false,
      data: form_data,
      type: "POST",
      beforeSend: function () {
        var spinner = '<i class="mhk-loading mhk-loading-active"></i>';
        $(".muhiku_forms_import_action")
          .closest(".muhiku_forms_import_action")
          .append(spinner);
        $(".muhiku-froms-import_notice").remove();
      },
      complete: function (response) {
        var message_string = "";

        $(".muhiku_forms_import_action")
          .closest(".muhiku_forms_import_action")
          .find(".mhk-loading")
          .remove();
        $(".muhiku-froms-import_notice").remove();

        if (true === response.responseJSON.success) {
          message_string =
            '<div id="message" class="updated inline muhiku-froms-import_notice"><p><strong>' +
            response.responseJSON.data.message +
            "</strong></p></div>";
        } else {
          message_string =
            '<div id="message" class="error inline muhiku-froms-import_notice"><p><strong>' +
            response.responseJSON.data.message +
            "</strong></p></div>";
        }

        $(".muhiku-plug-import-form").find("h3").after(message_string);
        $("#muhiku-plug-import").val("");
      },
    });
  });

  // Adding active class for button group
  $(".muhiku-plug-btn-group .muhiku-plug-btn").on("click", function () {
    $(this).siblings().removeClass("is-active");
    $(this).addClass("is-active");
  });
})(jQuery, muhiku_forms_admin);
