/* global mhk_setup_params */
jQuery(function ($) {
  /**
   * Setup actions.
   */
  var mhk_setup_actions = {
    $setup_form: $(".muhiku-plug-setup--form"),
    $button_install: mhk_data.i18n_activating,
    init: function () {
      this.title_focus();

      // Template actions.
      $(document).on(
        "click",
        ".muhiku-plug-template-install-addon",
        this.install_addon
      );
      $(document).on(
        "click",
        ".muhiku-plug-builder-setup .upgrade-modal",
        this.message_upgrade
      );
      $(document).on(
        "click",
        ".muhiku-plug-builder-setup .mhk-template-preview",
        this.template_preview
      );

      // Select and apply a template.
      this.$setup_form.on(
        "click",
        ".mhk-template-select",
        this.template_select
      );

      // Prevent <ENTER> key for setup actions.
      $(document.body).on(
        "keypress",
        ".muhiku-plug-setup-form-name input",
        this.input_keypress
      );
    },
    title_focus: function () {
      setTimeout(function () {
        $("#muhiku-plug-setup-name").focus();
      }, 100);
    },
    install_addon: function (event) {
      var pluginsList = $(".plugins-list-table").find("#the-list tr"),
        $target = $(event.target),
        success = 0,
        error = 0,
        errorMessages = [];

      wp.updates.maybeRequestFilesystemCredentials(event);

      $(".muhiku-plug-template-install-addon")
        .html(
          '<div class="mhk-loading mhk-loading-active"></div>' +
            mhk_setup_actions.$button_install
        )
        .prop("disabled", true);

      $(document).trigger("wp-plugin-bulk-install", pluginsList);

      // Find all the plugins which are required.
      pluginsList.each(function (index, element) {
        var $itemRow = $(element);

        // Only add inactive items to the update queue.
        if (
          !$itemRow.hasClass("inactive") ||
          $itemRow.find("notice-error").length
        ) {
          return;
        }

        // Add it to the queue.
        wp.updates.queue.push({
          action: "everest_forms_install_extension",
          data: {
            page: pagenow,
            name: $itemRow.data("name"),
            slug: $itemRow.data("slug"),
          },
        });
      });

      // Display bulk notification for install of plugin.
      $(document).on(
        "wp-plugin-bulk-install-success wp-plugin-bulk-install-error",
        function (event, response) {
          var $itemRow = $('[data-slug="' + response.slug + '"]'),
            $bulkActionNotice,
            itemName;

          if (
            "wp-" + response.install + "-bulk-install-success" ===
            event.type
          ) {
            success++;
          } else {
            itemName = response.pluginName
              ? response.pluginName
              : $itemRow.find(".plugin-name").text();
            error++;
            errorMessages.push(itemName + ": " + response.errorMessage);
          }

          wp.updates.adminNotice = wp.template("wp-bulk-installs-admin-notice");

          // Remove previous error messages, if any.
          $(".muhiku-plug-recommend-addons .bulk-action-notice").remove();

          $(".muhiku-plug-recommend-addons .plugins-info").after(
            wp.updates.adminNotice({
              id: "bulk-action-notice",
              className: "bulk-action-notice notice-alt",
              successes: success,
              errors: error,
              errorMessages: errorMessages,
              type: response.install,
            })
          );

          $bulkActionNotice = $("#bulk-action-notice").on(
            "click",
            "button",
            function () {
              // $( this ) is the clicked button, no need to get it again.
              $(this)
                .toggleClass("bulk-action-errors-collapsed")
                .attr(
                  "aria-expanded",
                  !$(this).hasClass("bulk-action-errors-collapsed")
                );
              // Show the errors list.
              $bulkActionNotice
                .find(".bulk-action-errors")
                .toggleClass("hidden");
            }
          );

          if (!wp.updates.queue.length) {
            if (error > 0) {
              $target
                .removeClass("updating-message")
                .text($target.data("originaltext"));
            }
          }

          if (0 === wp.updates.queue.length) {
            $(".muhiku-plug-template-install-addon").remove();
            $(".muhiku-plug-builder-setup .jconfirm-buttons button").show();
          }
        }
      );

      // Check the queue, now that the event handlers have been added.
      wp.updates.queueChecker();
    },
    message_upgrade: function (e) {
      var templateName = $(this).data("template-name-raw");

      e.preventDefault();

      $.alert({
        title: templateName + " " + mhk_setup_params.upgrade_title,
        theme: "jconfirm-modern jconfirm-muhiku-plug",
        icon: "dashicons dashicons-lock",
        backgroundDismiss: false,
        scrollToPreviousElement: false,
        content: mhk_setup_params.upgrade_message,
        type: "red",
        boxWidth: "565px",
        buttons: {
          confirm: {
            text: mhk_setup_params.upgrade_button,
            btnClass: "btn-confirm",
            keys: ["enter"],
            action: function () {
              window.open(mhk_setup_params.upgrade_url, "_blank");
            },
          },
          cancel: {
            text: mhk_data.i18n_ok,
          },
        },
      });
    },
    template_preview: function () {
      var $this = $(this),
        previewLink = $this.data("preview-link");

      $this
        .closest(".muhiku-plug-setup--form")
        .find(".mhk-template-preview-iframe #frame")
        .attr("src", previewLink);
    },
    template_select: function (event) {
      var $this = $(this),
        template = $this.data("template"),
        templateName = $this.data("template-name-raw"),
        formName = "",
        namePrompt = mhk_setup_params.i18n_form_name,
        nameField =
          '<input autofocus="" type="text" id="muhiku-plug-setup-name" class="muhiku-plug-setup-name" placeholder="' +
          mhk_setup_params.i18n_form_placeholder +
          '">',
        nameError =
          '<p class="error">' + mhk_setup_params.i18n_form_error_name + "</p>";

      event.preventDefault();

      $target = $(event.target);

      if (
        $target.hasClass("disabled") ||
        $target.hasClass("updating-message")
      ) {
        return;
      }

      $.confirm({
        title: mhk_setup_params.i18n_form_title,
        theme: "jconfirm-modern jconfirm-muhiku-plug-left",
        backgroundDismiss: false,
        scrollToPreviousElement: false,
        content: function () {
          // Fire AJAX.
          var self = this,
            button = mhk_data.i18n_install_only;

          if (
            $target.closest(".mhk-template").find("span.muhiku-plug-badge")
              .length
          ) {
            var data = {
              action: "everest_forms_template_licence_check",
              plan: $this.attr("data-licence-plan").replace("-lifetime", ""),
              slug: $this.attr("data-template"),
              security: mhk_setup_params.template_licence_check_nonce,
            };

            return $.ajax({
              url: mhk_email_params.ajax_url,
              data: data,
              type: "POST",
            }).done(function (response) {
              self.setContentAppend(
                namePrompt + nameField + nameError + response.data.html
              );

              if (response.data.activate) {
                $(".muhiku-plug-builder-setup .jconfirm-buttons button").show();
              } else {
                if (response.data.html.includes("install-now")) {
                  button = mhk_data.i18n_install_activate;
                  mhk_setup_actions.$button_install = mhk_data.i18n_installing;
                }
                var installButton =
                  '<a href="#" class="muhiku-plug-btn muhiku-plug-btn-primary muhiku-plug-template-install-addon">' +
                  button +
                  "</a>";
                $(".muhiku-plug-builder-setup .jconfirm-buttons").append(
                  installButton
                );
              }
            });
          } else {
            $(".muhiku-plug-builder-setup .jconfirm-buttons button").show();
            return namePrompt + nameField + nameError;
          }
        },
        buttons: {
          Continue: {
            isHidden: true, // Hide the button.
            btnClass: "muhiku-plug-btn muhiku-plug-btn-primary",
            action: function () {
              var $formName = $("#muhiku-plug-setup-name"),
                overlay = $(".muhiku-plug-loader-overlay");

              // Check that form title is provided.
              if (!$formName.val()) {
                formName = templateName;
                var error = this.$content.find(".error");
                $(".muhiku-plug-setup-name")
                  .addClass("muhiku-plug-required")
                  .focus();
                error.show();
                return false;
              } else {
                formName = $formName.val();
              }

              overlay.show();

              var data = {
                title: formName,
                action: "everest_forms_create_form",
                template: template,
                security: mhk_setup_params.create_form_nonce,
              };

              $.post(mhk_setup_params.ajax_url, data, function (response) {
                if (response.success) {
                  window.location.href = response.data.redirect;
                } else {
                  overlay.hide();
                  $(".muhiku-plug-setup-name")
                    .addClass("muhiku-plug-required")
                    .focus();
                  window.console.log(response);
                }
              }).fail(function (xhr) {
                window.console.log(xhr.responseText);
              });
            },
          },
        },
      });
    },
    input_keypress: function (e) {
      var button = e.keyCode || e.which;

      $(this).removeClass("muhiku-plug-required");

      // Enter key.
      if (13 === button && e.target.tagName.toLowerCase() === "input") {
        e.preventDefault();
        return false;
      }
    },
  };

  mhk_setup_actions.init();
});
