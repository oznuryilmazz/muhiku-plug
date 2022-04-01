/* global everest_forms_ajax_submission_params */
jQuery(function ($) {
  "use strict";
  var mhk_ajax_submission_init = function () {
    var form = $('form[data-ajax_submission="1"]');
    form.each(function (i, v) {
      $(document).ready(function () {
        var formTuple = $(v),
          btn = formTuple.find(".mhk-submit"),
          stripeForms = formTuple.find("[data-gateway*='stripe']");
        // If it's an ajax form containing a stripe gateway, donot latch into the button.

        if (stripeForms.length > 0 && 0 === stripeForms.children.length) {
          return;
        }

        btn.on("click", function (e) {
          var paymentMethod = formTuple
            .find(".muhiku-plug-stripe-gateways-tabs .mhk-tab")
            .has("a.active")
            .data("gateway");
          if (undefined === paymentMethod) {
            paymentMethod = formTuple
              .find(".muhiku-plug-gateway[data-gateway='stripe']")
              .data("gateway");
          }

          if (
            "stripe" === paymentMethod &&
            "none" !==
              formTuple
                .find(".muhiku-plug-gateway[data-gateway='ideal']")
                .closest(".mhk-field")
                .css("display")
          ) {
            return;
          }

          if (typeof tinyMCE !== "undefined") {
            tinyMCE.triggerSave();
          }

          var recaptchaID = btn.get(0).recaptchaID;

          if (recaptchaID === 0) {
            grecaptcha.execute(recaptchaID);
            return false;
          }

          var data = formTuple.serializeArray();
          e.preventDefault();
          // We let the bubbling events in form play itself out.
          formTuple.trigger("focusout").trigger("change").trigger("submit");

          var errors = formTuple.find(".mhk-error:visible");

          if (errors.length > 0) {
            $([document.documentElement, document.body]).animate(
              {
                scrollTop: errors.last().offset().top,
              },
              800
            );
            return;
          }

          // Change the text to user defined property.
          $(this).html(formTuple.data("process-text"));

          // Add action intend for ajax_form_submission endpoint.
          data.push({
            name: "action",
            value: "everest_forms_ajax_form_submission",
          });
          data.push({
            name: "security",
            value: everest_forms_ajax_submission_params.mhk_ajax_submission,
          });
          // Fire the ajax request.
          $.ajax({
            url: everest_forms_ajax_submission_params.ajax_url,
            type: "POST",
            data: data,
          })
            .done(function (xhr, textStatus, errorThrown) {
              var redirect_url =
                xhr.data && xhr.data.redirect_url ? xhr.data.redirect_url : "";
              if (redirect_url) {
                formTuple.trigger("reset");
                window.location = redirect_url;
                return;
              }
              if ("success" === xhr.data.response || true === xhr.success) {
                let pdf_download_message = "";
                let quiz_reporting = "";
                if (
                  xhr.data.form_id !== undefined &&
                  xhr.data.entry_id !== undefined &&
                  xhr.data.pdf_download == true
                ) {
                  pdf_download_message =
                    '<br><small><a href="/?page=mhk-entries-pdf&form_id=' +
                    xhr.data.form_id +
                    "&entry_id=" +
                    xhr.data.entry_id +
                    '">' +
                    xhr.data.pdf_download_message +
                    "</a></small>";
                }
                if (xhr.data.quiz_result_shown == true) {
                  quiz_reporting = xhr.data.quiz_reporting;
                }

                var paymentMethod = formTuple
                  .find(".muhiku-plug-stripe-gateways-tabs .mhk-tab")
                  .has("a.active")
                  .data("gateway");
                if (undefined === paymentMethod) {
                  paymentMethod = formTuple
                    .find(".muhiku-plug-gateway[data-gateway='ideal']")
                    .data("gateway");
                }

                if (
                  "ideal" === paymentMethod &&
                  "none" !==
                    formTuple
                      .find(".muhiku-plug-gateway[data-gateway='ideal']")
                      .closest(".mhk-field")
                      .css("display")
                ) {
                  formTuple.trigger("mhk_process_payment", xhr.data);
                  return;
                }
                formTuple.trigger("reset");
                formTuple
                  .closest(".muhiku-plug")
                  .html(
                    '<div class="muhiku-plug-notice muhiku-plug-notice--success" role="alert">' +
                      xhr.data.message +
                      pdf_download_message +
                      "</div>" +
                      quiz_reporting
                  )
                  .focus();
                localStorage.removeItem(formTuple.attr("id"));
              } else {
                var form_id = formTuple.data("formid");
                var err = JSON.parse(errorThrown.responseText);
                if ("undefined" !== typeof err.data[form_id]) {
                  var error = err.data[form_id].header;
                } else {
                  var error = everest_forms_ajax_submission_params.error;
                }
                var fields = err.data.error;

                if ("string" === typeof err.data.message) {
                  error = err.data.message;
                }

                formTuple
                  .closest(".muhiku-plug")
                  .find(".muhiku-plug-notice")
                  .remove();
                formTuple
                  .closest(".muhiku-plug")
                  .prepend(
                    '<div class="muhiku-plug-notice muhiku-plug-notice--error" role="alert">' +
                      error +
                      "</div>"
                  )
                  .focus();

                // Begin fixing the tamper.
                $(fields).each(function (index, fieldTuple) {
                  var err_msg = Object.values(fieldTuple)[0],
                    fld_id = Object.keys(fieldTuple)[0],
                    err_field,
                    fid,
                    lbl = true;

                  var fld_container_id =
                    "mhk" - +form_id + "-field_" + fld_id + "-container";

                  if (
                    $("#" + fld_container_id).hasClass("mhk-field-signature")
                  ) {
                    //When field type is signature
                    fid = "mhk-signature-img-input-" + fld_id;
                    err_field = $("#" + fid);
                  } else if (
                    $("#" + fld_container_id).hasClass("mhk-field-likert")
                  ) {
                    //When field type is likert
                    fid = "everest_forms-" + form_id + "-field_" + fld_id + "_";
                    err_field = $('[id^="' + fid + '"]');
                    lbl = false;

                    err_field.each(function (index, element) {
                      var tbl_header = $(element)
                          .closest("tr.mhk-" + form_id + "-field_" + fld_id)
                          .find("th"),
                        id =
                          "everest_forms[form_fields][" +
                          fld_id +
                          "][" +
                          (parseInt(tbl_header.closest("tr").index()) + 1) +
                          "]";

                      if (!tbl_header.children().is("label")) {
                        tbl_header.append(
                          '<label id="' +
                            id +
                            '" for="' +
                            id +
                            '" class="mhk-error">' +
                            everest_forms_ajax_submission_params.required +
                            "</label>"
                        );
                      } else {
                        tbl_header
                          .children()
                          .find("#" + id)
                          .show();
                      }
                    });
                  } else if (
                    $("#" + fld_container_id).hasClass("mhk-field-address")
                  ) {
                    //When field type is address
                    fid = "mhk-" + form_id + "-field_" + fld_id;
                    err_field = $('[id^="' + fid + '"]');

                    err_field.each(function (index, element) {
                      var fieldId = String($(element).attr("id"));

                      if (
                        fieldId.includes("-container") ||
                        fieldId.includes("-address2")
                      ) {
                        err_field.splice(index, 1);
                      } else {
                        if ("undefined" !== typeof $(element).val()) {
                          err_field.splice(index, 1);
                        }
                      }
                    });
                  } else {
                    fid = "mhk-" + form_id + "-field_" + fld_id;
                    err_field = $("#" + fid);
                  }

                  err_field.addClass("mhk-error");
                  err_field.attr("aria-invalid", true);
                  err_field
                    .first()
                    .closest(".mhk-field")
                    .addClass("muhiku-plug-invalid mhk-has-error");

                  if (true === lbl && !err_field.is("label")) {
                    err_field
                      .after(
                        '<label id="' +
                          err_field.attr("id") +
                          '-error" class="mhk-error" for="' +
                          err_field.attr("id") +
                          '">' +
                          err_msg +
                          "</label>"
                      )
                      .show();
                  }
                });

                btn
                  .attr("disabled", false)
                  .html(everest_forms_ajax_submission_params.submit);
              }
            })
            .fail(function () {
              btn
                .attr("disabled", false)
                .html(everest_forms_ajax_submission_params.submit);
              formTuple.trigger("focusout").trigger("change");
              formTuple
                .closest(".muhiku-plug")
                .find(".muhiku-plug-notice")
                .remove();
              formTuple
                .closest(".muhiku-plug")
                .prepend(
                  '<div class="muhiku-plug-notice muhiku-plug-notice--error" role="alert">' +
                    everest_forms_ajax_submission_params.error +
                    "</div>"
                )
                .focus();
            })
            .always(function (xhr) {
              var redirect_url =
                xhr.data && xhr.data.redirect_url ? xhr.data.redirect_url : "";
              if (!redirect_url && $(".muhiku-plug-notice").length) {
                $([document.documentElement, document.body]).animate(
                  {
                    scrollTop: $(".muhiku-plug-notice").offset().top,
                  },
                  800
                );
              }
            });
        });
      });
    });
  };

  mhk_ajax_submission_init();
});
