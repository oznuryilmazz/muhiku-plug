/* eslint-disable max-len */
/* global everest_forms_params */
jQuery(function ($) {
  "use strict";

  // everest_forms_params is required to continue, ensure the object exists.
  if (typeof everest_forms_params === "undefined") {
    return false;
  }

  var getEnhancedSelectFormatString = function () {
    return {
      language: {
        noResults: function () {
          return everest_forms_params.i18n_no_matches;
        },
      },
    };
  };

  var everest_forms = {
    $everest_form: $("form.everest-form"),
    init: function () {
      this.init_inputMask();
      this.init_mailcheck();
      this.init_datepicker();
      this.init_datedropdown();
      this.load_validation();
      this.submission_scroll();
      this.randomize_elements();
      this.init_enhanced_select();
      this.checkUncheckAllcheckbox();

      // Inline validation.
      this.$everest_form.on(
        "input validate change",
        ".input-text, select, input:checkbox, input:radio",
        this.validate_field
      );

      // Notify plugins that the core was loaded.
      $(document.body).trigger("everest_forms_loaded");
    },
    init_inputMask: function () {
      // Only load if jQuery inputMask library exists.
      if (typeof $.fn.inputmask !== "undefined") {
        $(".mhk-masked-input").inputmask();
      }
    },
    init_mailcheck: function () {
      // Only load if Mailcheck library exists and enabled.
      if (
        typeof $.fn.mailcheck === "undefined" ||
        !everest_forms_params.mailcheck_enabled
      ) {
        return;
      }

      // Setup default domains for Mailcheck.
      if (everest_forms_params.mailcheck_domains.length > 0) {
        Mailcheck.defaultDomains = Mailcheck.defaultDomains.concat(
          everest_forms_params.mailcheck_domains
        );
      }

      // Setup default top level domains for Mailcheck.
      if (everest_forms_params.mailcheck_toplevel_domains.length > 0) {
        Mailcheck.defaultTopLevelDomains =
          Mailcheck.defaultTopLevelDomains.concat(
            everest_forms_params.mailcheck_toplevel_domains
          );
      }

      // Mailcheck suggestion.
      $(document).on("blur", ".mhk-field-email input", function () {
        var $el = $(this),
          id = $el.attr("id");

        $el.mailcheck({
          suggested: function (el, suggestion) {
            $("#" + id + "_suggestion").remove();
            var suggestion_msg =
              everest_forms_params.i18n_messages_email_suggestion.replace(
                "{suggestion}",
                '<a href="#" class="mailcheck-suggestion" data-id="' +
                  id +
                  '" title="' +
                  everest_forms_params.i18n_messages_email_suggestion_title +
                  '">' +
                  suggestion.full +
                  "</a>"
              );
            $(el).after(
              '<label class="mhk-error mailcheck-error" id="' +
                id +
                '_suggestion">' +
                suggestion_msg +
                "</label>"
            );
          },
          empty: function () {
            $("#" + id + "_suggestion").remove();
          },
        });
      });

      // Apply Mailcheck suggestion.
      $(document).on(
        "click",
        ".mhk-field-email .mailcheck-suggestion",
        function (e) {
          var $el = $(this),
            id = $el.attr("data-id");
          e.preventDefault();
          $("#" + id).val($el.text());
          $el.parent().remove();
        }
      );
    },
    init_datepicker: function () {
      var mhkDateField = $(".mhk-field-date-time");
      if (mhkDateField.length > 0) {
        $(".flatpickr-field").each(function () {
          var timeInterval = 5,
            inputData = $(this).data(),
            disableDates = [];

          // Extract list of disabled dates.
          if (inputData.disableDates) {
            disableDates = inputData.disableDates.split(",");
          }

          switch (inputData.dateTime) {
            case "date":
              // Apply flatpicker to field.
              $(this).flatpickr({
                disableMobile: true,
                mode: inputData.mode,
                minDate: inputData.minDate,
                maxDate: inputData.maxDate,
                dateFormat: inputData.dateFormat,
                disable: disableDates,
              });
              break;
            case "time":
              if (undefined !== inputData.timeInterval) {
                timeInterval = parseInt(inputData.timeInterval, 10);
              }

              // Apply flatpicker to field.
              $(this).flatpickr({
                enableTime: true,
                noCalendar: true,
                minuteIncrement: timeInterval,
                dateFormat: inputData.dateFormat,
                disableMobile: true,
                time_24hr: inputData.dateFormat.includes("H:i"),
              });
              break;
            case "date-time":
              if (undefined !== inputData.timeInterval) {
                timeInterval = parseInt(inputData.timeInterval, 10);
              }

              // Apply flatpicker to field.
              $(this).flatpickr({
                enableTime: true,
                noCalendar: false,
                disableMobile: true,
                mode: inputData.mode,
                minDate: inputData.minDate,
                maxDate: inputData.maxDate,
                minuteIncrement: timeInterval,
                dateFormat: inputData.dateFormat,
                time_24hr: inputData.dateFormat.includes("H:i"),
                disable: disableDates,
              });
              break;
            default:
          }
        });
      }
    },
    init_datedropdown: function () {
      //Dropdown logic here
      $(".date-dropdown-field").each(function () {
        var $this = $(this);
        $this.hide();
        everest_forms.change_minutes($this);
      });

      $("body").on(
        "change",
        ".date-time-container [id*=hour-select]",
        function () {
          var $this = $(this).siblings("input.date-dropdown-field");
          everest_forms.change_minutes($this);
        }
      );

      $("body").on("change", ".date-time-container [id*=-select]", function () {
        var $this = $(this).siblings("input.date-dropdown-field");
        $this.val(everest_forms.format_dropdown_date($this));
      });
    },
    change_minutes: function ($this) {
      //Changes minutes as per hours selected, Time limit option.
      var id = $this.attr("id");
      if (
        typeof $this.siblings("#minute-select-" + id).attr("id") != "undefined"
      ) {
        var max_hour = $this.attr("data-max-hour");
        var min_hour = $this.attr("data-min-hour");
        var max_minute = $this.attr("data-max-minute");
        var min_minute = $this.attr("data-min-minute");
        if (typeof min_hour == "undefined" || min_hour == "") {
          min_hour = 0;
        }
        if (typeof max_hour == "undefined" || max_hour == "") {
          min_hour = 23;
        }
        if (typeof min_minute == "undefined" || min_minute == "") {
          min_minute = 0;
        }
        if (typeof max_minute == "undefined" || max_minute == "") {
          max_minute = 59;
        }
        var options = "";
        for (var i = 0; i <= 59; i++) {
          if (
            $this.siblings("#hour-select-" + id).val() == min_hour &&
            i < min_minute
          ) {
            continue;
          }
          if (
            $this.siblings("#hour-select-" + id).val() == max_hour &&
            i > max_minute
          ) {
            break;
          }
          options +=
            '<option value = "' +
            i +
            '"> ' +
            (i < 10 ? "0" + i : i) +
            "</option>";
        }
        // alert(options);
        $this.siblings("#minute-select-" + id).html(options);
        $this.siblings("#minute-select-" + id).attr(
          "value",
          $this
            .siblings("#minute-select-" + id)
            .find("option:first")
            .val()
        );
      }
      $this.val(everest_forms.format_dropdown_date($this));
    },
    format_dropdown_date: function ($this) {
      var id = $this.attr("id");
      var selectd_date = {
        selected_year: $this.siblings("#year-select-" + id).val(),
        selected_month: $this.siblings("#month-select-" + id).val(),
        selected_day: $this.siblings("#day-select-" + id).val(),
        selected_hour: $this.siblings("#hour-select-" + id).val(),
        selected_minute: $this.siblings("#minute-select-" + id).val(),
      };
      var setting = {
        date_format: $this.attr("data-date-format"),
        date_time: $this.attr("data-date-time"),
      };
      var list_months = [
        "January",
        "Febuary",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
      ];
      var formatted_date = "";
      if (setting.date_time == "date" || setting.date_time == "date-time") {
        selectd_date.selected_day =
          selectd_date.selected_day < 10
            ? "0" + selectd_date.selected_day
            : selectd_date.selected_day;
        if (setting.date_format.match(/F j, Y/)) {
          formatted_date =
            list_months[parseInt(selectd_date.selected_month) - 1] +
            " " +
            selectd_date.selected_day +
            ", " +
            selectd_date.selected_year;
        } else {
          selectd_date.selected_month =
            selectd_date.selected_month < 10
              ? "0" + selectd_date.selected_month
              : selectd_date.selected_month;
          if (setting.date_format.match(/Y-m-d/)) {
            formatted_date =
              selectd_date.selected_year +
              "-" +
              selectd_date.selected_month +
              "-" +
              selectd_date.selected_day;
          } else if (setting.date_format.match(/m\/d\/Y/)) {
            formatted_date =
              selectd_date.selected_month +
              "/" +
              selectd_date.selected_day +
              "/" +
              selectd_date.selected_year;
          } else {
            formatted_date =
              selectd_date.selected_day +
              "/" +
              selectd_date.selected_month +
              "/" +
              selectd_date.selected_year;
          }
        }
      }
      if (setting.date_time == "time" || setting.date_time == "date-time") {
        selectd_date.selected_minute =
          selectd_date.selected_minute < 10
            ? "0" + selectd_date.selected_minute
            : selectd_date.selected_minute;
        if (setting.date_format.match(/H:i/)) {
          selectd_date.selected_hour =
            selectd_date.selected_hour < 10
              ? "0" + selectd_date.selected_hour
              : selectd_date.selected_hour;
          formatted_date +=
            " " +
            selectd_date.selected_hour +
            ":" +
            selectd_date.selected_minute;
        } else {
          var period = "PM";
          if (selectd_date.selected_hour < 12) {
            period = "AM";
            if (selectd_date.selected_hour == 0) {
              selectd_date.selected_hour = 12;
            }
          } else if (selectd_date.selected_hour > 12) {
            selectd_date.selected_hour = selectd_date.selected_hour - 12;
          }

          formatted_date +=
            " " +
            selectd_date.selected_hour +
            ":" +
            selectd_date.selected_minute +
            " " +
            period;
        }
      }
      return formatted_date.trim();
    },
    load_validation: function () {
      if (typeof $.fn.validate === "undefined") {
        return false;
      }

      // Prepend URL field contents with http:// if user input doesn't contain a schema.
      $(".mhk-field-url input[type=url]").on("change", function () {
        var url = $(this).val();
        if (!url) {
          return false;
        }
        if (url.substr(0, 7) !== "http://" && url.substr(0, 8) !== "https://") {
          $(this).val("http://" + url);
        }
      });

      // Validator messages.
      $.extend($.validator.messages, {
        required: everest_forms_params.i18n_messages_required,
        url: everest_forms_params.i18n_messages_url,
        email: everest_forms_params.i18n_messages_email,
        number: everest_forms_params.i18n_messages_number,
      });

      // Validate email addresses.
      $.validator.methods.email = function (value, element) {
        /* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
        var pattern = new RegExp(
          /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i
        );
        return this.optional(element) || pattern.test(value);
      };

      // Validate confirmations.
      $.validator.addMethod(
        "confirm",
        function (value, element, param) {
          return $.validator.methods.equalTo.call(this, value, element, param);
        },
        everest_forms_params.i18n_messages_confirm
      );

      // Validate checkbox choice limit.
      $.validator.addMethod(
        "check-limit",
        function (value, element) {
          var $ul = $(element).closest("ul"),
            $checked = $ul.find('input[type="checkbox"]:checked'),
            choiceLimit = parseInt($ul.attr("data-choice-limit") || 0, 10);

          if (0 === choiceLimit) {
            return true;
          }

          return $checked.length <= choiceLimit;
        },
        function (params, element) {
          var choiceLimit = parseInt(
            $(element).closest("ul").attr("data-choice-limit") || 0,
            10
          );
          return everest_forms_params.i18n_messages_check_limit.replace(
            "{#}",
            choiceLimit
          );
        }
      );

      this.$everest_form.each(function () {
        var $this = $(this);

        // List messages to show for required fields. Use name of the field as key.
        var error_messages = {};
        $(".mhk-field").each(function () {
          var form_id = $(this).closest("form").data("formid");
          var field_id = $(this).data("field-id");
          var error_message = $(this).data("required-field-message");
          var key = "everest_forms[form_fields][" + field_id + "]"; // Name of the input field is used as a key.

          if ($(this).is(".mhk-field-payment-single")) {
            if (
              !$(this).find(".mhk-payment-price").is(".mhk-payment-user-input")
            ) {
              $(this).find(".mhk-payment-price").attr("required", false);
              error_message = null;
            }
          } else if (
            $(this).is(".mhk-field-checkbox, .mhk-field-payment-checkbox")
          ) {
            key = key + "[]";
          } else if ($(this).is(".mhk-field-image-upload")) {
            key = "mhk_" + form_id + "_" + field_id;
          } else if ($(this).is(".mhk-field-signature")) {
            key =
              "everest_forms[form_fields][" + field_id + "][signature_image]";
          } else if ($(this).is(".mhk-field-phone")) {
            key = key + "[phone_field]";
          } else if (
            $(this).is(".mhk-field-email") ||
            $(this).is(".mhk-field-password")
          ) {
            // For when the confirm is disabled.
            key = "everest_forms[form_fields][" + field_id + "]";
            error_messages[key] = {
              required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
            };

            // For when the confirm is enabled.
            key = "everest_forms[form_fields][" + field_id + "][primary]";
            error_messages[key] = {
              required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
            };
            key = "everest_forms[form_fields][" + field_id + "][secondary]";
            error_messages[key] = {
              required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
            };
            error_message = null;
          } else if ($(this).is(".mhk-field-address")) {
            var sub_field_error_messages = {
              address1: $(this).data("required-field-message-address1"),
              city: $(this).data("required-field-message-city"),
              state: $(this).data("required-field-message-state"),
              postal: $(this).data("required-field-message-postal"),
              country: $(this).data("required-field-message-country"),
            };

            var sub_field_types = Object.keys(sub_field_error_messages);
            for (var i = 0; i < sub_field_types.length; i++) {
              var sub_field_type = sub_field_types[i],
                error_message = sub_field_error_messages[sub_field_types[i]];

              key =
                "everest_forms[form_fields][" +
                field_id +
                "][" +
                sub_field_type +
                "]";
              error_messages[key] = {
                required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
              };
            }
            error_message = null;
          } else if ($(this).is(".mhk-field-likert")) {
            var row_keys = $(this).data("row-keys");
            var sub_field_error_messages = {};

            if (row_keys && Array.isArray(row_keys)) {
              for (var i = 0; i < row_keys.length; i++) {
                var row_key = row_keys[i];
                sub_field_error_messages[row_key] = $(this).data(
                  "required-field-message-" + row_key
                );
              }
              for (var i = 0; i < row_keys.length; i++) {
                error_message = sub_field_error_messages[row_keys[i]];
                key =
                  "everest_forms[form_fields][" +
                  field_id +
                  "][" +
                  row_keys[i] +
                  "]";
                error_messages[key] = {
                  required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
                };
              }
            }
            error_message = null;
          } else if ($(this).is(".mhk-field-file-upload")) {
            key = "everest_forms_" + form_id + "_" + field_id;
          }

          /**
           * Check if the error message has been already set (null value in error_message variable
           * should indicate that the message has already been set).
           */
          if (error_message) {
            error_messages[key] = {
              required: error_message, // Set message using 'required' key to avoid conflicts with other validations.
            };
          }
        });

        $this.validate({
          messages: error_messages,
          ignore: "",
          errorClass: "mhk-error",
          validClass: "mhk-valid",
          errorPlacement: function (error, element) {
            if (element.closest(".mhk-field").is(".mhk-field-privacy-policy")) {
              element.closest(".mhk-field").append(error);
            } else if (
              element.closest(".mhk-field").is(".mhk-field-range-slider")
            ) {
              if (
                element.closest(".mhk-field").find(".mhk-field-description")
                  .length
              ) {
                element
                  .closest(".mhk-field")
                  .find(".mhk-field-description")
                  .before(error);
              } else {
                element.closest(".mhk-field").append(error);
              }
            } else if (
              element.closest(".mhk-field").is(".mhk-field-scale-rating")
            ) {
              element
                .closest(".mhk-field")
                .find(".muhiku-plug-field-scale-rating")
                .after(error);
            } else if (
              "radio" === element.attr("type") ||
              "checkbox" === element.attr("type")
            ) {
              if (element.hasClass("muhiku-plug-likert-field-option")) {
                element.closest("tr").children("th").append(error);
              } else {
                element
                  .closest(".mhk-field-checkbox")
                  .find("label.mhk-error")
                  .remove();
                element.parent().parent().parent().append(error);
              }
            } else if (
              element.is("select") &&
              element.attr("class").match(/date-month|date-day|date-year/)
            ) {
              if (
                element.parent().find("label.mhk-error:visible").length === 0
              ) {
                element.parent().find("select:last").after(error);
              }
            } else if (
              element.is("select") &&
              element.hasClass("mhk-enhanced-select")
            ) {
              if (
                element.parent().find("label.mhk-error:visible").length === 0
              ) {
                element.parent().find(".select2").after(error);
              }
            } else if (
              element.hasClass("mhk-smart-phone-field") ||
              element.hasClass("muhiku-plug-field-password-primary") ||
              element.hasClass("muhiku-plug-field-password-secondary")
            ) {
              element.parent().after(error);
            } else {
              error.insertAfter(element);
            }
          },
          highlight: function (element, errorClass, validClass) {
            var $element = $(element),
              $parent = $element.closest(".form-row"),
              inputName = $element.attr("name");

            if (
              $element.attr("type") === "radio" ||
              $element.attr("type") === "checkbox"
            ) {
              $parent
                .find('input[name="' + inputName + '"]')
                .addClass(errorClass)
                .removeClass(validClass);
            } else {
              $element.addClass(errorClass).removeClass(validClass);
            }

            $parent
              .removeClass("muhiku-plug-validated")
              .addClass("muhiku-plug-invalid mhk-has-error");
          },
          unhighlight: function (element, errorClass, validClass) {
            var $element = $(element),
              $parent = $element.closest(".form-row"),
              inputName = $element.attr("name");

            if (
              $element.attr("type") === "radio" ||
              $element.attr("type") === "checkbox"
            ) {
              $parent
                .find('input[name="' + inputName + '"]')
                .addClass(validClass)
                .removeClass(errorClass);
            } else {
              $element.addClass(validClass).removeClass(errorClass);
            }

            $parent.removeClass("mhk-has-error");
          },
          submitHandler: function (form) {
            var $form = $(form),
              $submit = $form.find(".mhk-submit"),
              processText = $submit.data("process-text");
            var recaptchaID = $submit.get(0).recaptchaID;
            // Process form.
            if (processText) {
              $submit.text(processText).prop("disabled", true);
            }

            if (recaptchaID === 0) {
              grecaptcha.execute(recaptchaID);
              return false;
            }

            if (1 !== $form.data("ajax_submission")) {
              form.submit();
            } else {
              return;
            }
          },
          onkeyup: function (element, event) {
            // This code is copied from JQuery Validate 'onkeyup' method with only one change: 'muhiku-plug-novalidate-onkeyup' class check.
            var excludedKeys = [
              16, 17, 18, 20, 35, 36, 37, 38, 39, 40, 45, 144, 225,
            ];

            // Disable onkeyup validation for some elements (e.g. remote calls).
            if ($(element).hasClass("muhiku-plug-novalidate-onkeyup")) {
              return;
            }

            if (
              (9 === event.which && "" === this.elementValue(element)) ||
              -1 !== $.inArray(event.keyCode, excludedKeys)
            ) {
              return;
            } else if (
              element.name in this.submitted ||
              element.name in this.invalid
            ) {
              this.element(element);
            }
          },
          onfocusout: function (element) {
            // This code is copied from JQuery Validate 'onfocusout' method with only one change: 'muhiku-plug-novalidate-onkeyup' class check.
            var validate = false;

            // Empty value error handling for elements with onkeyup validation disabled.
            if (
              $(element).hasClass("muhiku-plug-novalidate-onkeyup") &&
              !element.value
            ) {
              validate = true;
            }

            if (
              !this.checkable(element) &&
              (element.name in this.submitted || !this.optional(element))
            ) {
              validate = true;
            }

            if (validate) {
              this.element(element);
            }
          },
          onclick: function (element) {
            var validate = false,
              type = (element || {}).type,
              $el = $(element);

            if ("checkbox" === type) {
              $el
                .closest(".mhk-field-checkbox")
                .find("label.mhk-error")
                .remove();
              validate = true;
            } else if (!"select-multiple" === type) {
              $(element).valid();
            }

            if (validate) {
              this.element(element);
            }
          },
        });
      });
    },
    validate_field: function (e) {
      var $this = $(this),
        $body = $("body"),
        $parent = $this.closest(".form-row"),
        validated = true,
        validate_required = $parent.is(".validate-required"),
        validate_email = $parent.is(".validate-email"),
        event_type = e.type;

      if ($body.hasClass("muhiku-plug-is-offline")) {
        $parent.removeClass(
          "muhiku-plug-invalid muhiku-plug-invalid-required-field muhiku-plug-invalid-email muhiku-plug-validated"
        );
      } else if (
        $parent.hasClass("mhk-field-address") ||
        $parent.hasClass("mhk-field-payment-single") ||
        $("body").hasClass("muhiku-plug-is-offline")
      ) {
        if (0 === $parent.find("input.mhk-error").length) {
          $parent
            .removeClass(
              "muhiku-plug-invalid muhiku-plug-invalid-required-field muhiku-plug-invalid-email"
            )
            .addClass("muhiku-plug-validated");
        }
      } else {
        if ("input" === event_type) {
          $parent.removeClass(
            "muhiku-plug-invalid muhiku-plug-invalid-required-field muhiku-plug-invalid-email muhiku-plug-validated"
          );
        }

        if ("validate" === event_type || "change" === event_type) {
          if (validate_required) {
            if ($this.hasClass("muhiku-plug-likert-field-option")) {
              if ($parent.find("input.mhk-error").length > 0) {
                $parent
                  .removeClass("muhiku-plug-validated")
                  .addClass(
                    "muhiku-plug-invalid muhiku-plug-invalid-required-field"
                  );
                validated = false;
              }
            } else if (
              "checkbox" === $this.attr("type") &&
              0 === $parent.find("input:checked").length
            ) {
              $parent
                .removeClass("muhiku-plug-validated")
                .addClass(
                  "muhiku-plug-invalid muhiku-plug-invalid-required-field"
                );
              validated = false;
            } else if ("" === $this.val()) {
              $parent
                .removeClass("muhiku-plug-validated")
                .addClass(
                  "muhiku-plug-invalid muhiku-plug-invalid-required-field"
                );
              validated = false;
            }
          }

          if (validate_email) {
            if ($this.val()) {
              /* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
              var pattern = new RegExp(
                /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i
              ); // eslint-disable-line max-len

              if (!pattern.test($this.val())) {
                $parent
                  .removeClass("muhiku-plug-validated")
                  .addClass("muhiku-plug-invalid muhiku-plug-invalid-email");
                validated = false;
              }
            }
          }
          if (validated) {
            $parent
              .removeClass(
                "muhiku-plug-invalid muhiku-plug-invalid-required-field muhiku-plug-invalid-email"
              )
              .addClass("muhiku-plug-validated");
          }
        }
      }
    },
    submission_scroll: function () {
      if ($("div.muhiku-plug-submission-scroll").length) {
        $("html,body").animate(
          {
            scrollTop:
              $("div.muhiku-plug-submission-scroll").offset().top - 100,
          },
          1000
        );
      }
    },
    randomize_elements: function () {
      $(".muhiku-plug-randomize").each(function () {
        var $list = $(this),
          $listItems = $list.children();

        while ($listItems.length) {
          $list.append(
            $listItems.splice(
              Math.floor(Math.random() * $listItems.length),
              1
            )[0]
          );
        }
      });
    },
    init_enhanced_select: function () {
      // Only continue if SelectWoo library exists.
      try {
        $(document.body)
          .on("mhk-frontend-enhanced-select-init", function () {
            if ("undefined" !== typeof $.fn.selectWoo) {
              $("select.mhk-enhanced-select:visible")
                .filter(":not(.mhk-enhanced)")
                .each(function () {
                  var select2_args = $.extend(
                    {
                      minimumResultsForSearch: 10,
                      placeholder: $(this).attr("placeholder") || "",
                      allowClear: $(this).prop("multiple") ? false : true,
                    },
                    getEnhancedSelectFormatString()
                  );

                  $(this).selectWoo(select2_args).addClass("mhk-enhanced");
                });
            }
          })
          .trigger("mhk-frontend-enhanced-select-init");
      } catch (err) {
        // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
        window.console.log(err);
      }
    },
    checkUncheckAllcheckbox: function () {
      // To check and uncheck all the option in checkbox.
      var all_select_all_chk = $("form.everest-form")
        .find(".mhk-field, .mhk-field-checkbox, .form-row")
        .find("ul")
        .find("li.mhk-select-all-checkbox-li")
        .find("#mhkCheckAll");
      if (all_select_all_chk.length) {
        all_select_all_chk.each(function () {
          var $this = $(this);

          $this.on("click", function () {
            if ($(this).prop("checked") == true) {
              $this
                .parent()
                .parent()
                .find("li")
                .find("input:checkbox")
                .not($this)
                .prop("checked", true);
            } else if ($(this).prop("checked") == false) {
              $this
                .parent()
                .parent()
                .find("li")
                .find("input:checkbox")
                .not($this)
                .prop("checked", false);
            }
          });

          $this
            .parent()
            .parent()
            .find("li")
            .find("input:checkbox")
            .not($this)
            .on("change", function () {
              var checked = $this
                .parent()
                .parent()
                .find("li")
                .find("input:checkbox:checked")
                .not($this).length;
              var chck_len = $this
                .parent()
                .parent()
                .find("li")
                .find("input:checkbox")
                .not($this).length;

              if (checked === chck_len) {
                $this.prop("checked", true);
              } else if (checked < chck_len) {
                $this.prop("checked", false);
              }
            });
        });
      }
    },
  };

  everest_forms.init();
});
