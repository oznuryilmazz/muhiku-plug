/**
 * MuhikuPlugEmail JS
 * global mhk_email_params
 */
(function ($) {
  var s;
  var MuhikuPlugEmail = {
    settings: {
      form: $("#muhiku-plug-builder-form"),
      spinner: '<i class="mhk-loading mhk-loading-active" />',
    },
    /**
     * Start the engine.
     *
     */
    init: function () {
      s = this.settings;

      $(".muhiku-plug-active-email-connections-list li")
        .first()
        .addClass("active-user");
      $(".mhk-content-email-settings-inner")
        .first()
        .addClass("active-connection");

      MuhikuPlugEmail.bindUIActions();
    },

    ready: function () {
      s.formID = $("#muhiku-plug-builder-form").data("id");
    },

    /**
     * Element bindings.
     *
     */
    bindUIActions: function () {
      $(document).on("click", ".muhiku-plug-email-add", function (e) {
        MuhikuPlugEmail.connectionAdd(this, e);
      });
      $(document).on(
        "click",
        ".muhiku-plug-active-email-connections-list li",
        function (e) {
          MuhikuPlugEmail.selectActiveAccount(this, e);
        }
      );
      $(document).on("click", ".email-remove", function (e) {
        MuhikuPlugEmail.removeAccount(this, e);
      });
      $(document).on("click", ".email-default-remove", function (e) {
        MuhikuPlugEmail.removeDefaultAccount(this, e);
      });
      $(document).on("input", ".muhiku-plug-email-name input", function (e) {
        MuhikuPlugEmail.renameConnection(this, e);
      });
      $(document).on("focusin", ".muhiku-plug-email-name input", function (e) {
        MuhikuPlugEmail.focusConnectionName(this, e);
      });
      $(document).on(
        "createEmailConnection",
        ".muhiku-plug-email-add",
        function (e, data) {
          MuhikuPlugEmail.addNewEmailConnection($(this), data);
        }
      );
    },

    connectionAdd: function (el, e) {
      e.preventDefault();

      var $this = $(el),
        source = "email",
        type = $this.data("type"),
        namePrompt = mhk_email_params.i18n_email_connection,
        nameField =
          '<input autofocus="" type="text" id="provider-connection-name" placeholder="' +
          mhk_email_params.i18n_email_placeholder +
          '">',
        nameError =
          '<p class="error">' + mhk_email_params.i18n_email_error_name + "</p>",
        modalContent = namePrompt + nameField + nameError;

      modalContent = modalContent.replace(/%type%/g, type);
      $.confirm({
        title: false,
        content: modalContent,
        icon: "dashicons dashicons-info",
        type: "blue",
        backgroundDismiss: false,
        closeIcon: false,
        buttons: {
          confirm: {
            text: mhk_email_params.i18n_email_ok,
            btnClass: "btn-confirm",
            keys: ["enter"],
            action: function () {
              var input = this.$content.find("input#provider-connection-name");
              var error = this.$content.find(".error");
              var value = input.val().trim();
              if (value.length === 0) {
                error.show();
                return false;
              } else {
                var name = value;

                // Fire AJAX
                var data = {
                  action: "muhiku_forms_new_email_add",
                  source: source,
                  name: name,
                  id: s.form.data("id"),
                  security: mhk_email_params.ajax_email_nonce,
                };
                $.ajax({
                  url: mhk_email_params.ajax_url,
                  data: data,
                  type: "POST",
                  success: function (response) {
                    MuhikuPlugEmail.addNewEmailConnection($this, {
                      response: response,
                      name: name,
                    });
                  },
                });
              }
            },
          },
          cancel: {
            text: mhk_email_params.i18n_email_cancel,
          },
        },
      });
    },

    addNewEmailConnection: function (el, data) {
      var $this = el;
      var response = data.response;
      var name = data.name;
      var $connections = $this.closest(".muhiku-plug-panel-sidebar-content");
      var form_title =
        $("#muhiku-plug-panel-field-settings-form_title:first").val() +
        "-" +
        Date.now();
      var cloned_email = $(".mhk-content-email-settings").first().clone();
      $(".mhk-content-email-settings-inner").removeClass("active-connection");
      cloned_email
        .find(
          'input:not(#qt_muhiku_forms_panel_field_email_connection_1_mhk_email_message_toolbar input[type="button"], .mhk_conditional_logic_container input)'
        )
        .val("");

      cloned_email
        .find('.mhk_conditional_logic_container input[type="checkbox"]')
        .prop("checked", false);
      cloned_email
        .find('.muhiku-plug-attach-pdf-to-admin-email input[type="checkbox"]')
        .prop("checked", false);
      cloned_email
        .find(
          '.muhiku-plug-show-header-in-attachment-pdf-file input[type="checkbox"]'
        )
        .prop("checked", false);

      cloned_email
        .find(".muhiku-plug-show-header-in-attachment-pdf-file")
        .hide();
      cloned_email.find(".muhiku-plug-show-pdf-file-name").hide();
      cloned_email.find(".mhk-field-conditional-container").hide();
      cloned_email
        .find(".mhk-field-conditional-wrapper li:not(:first)")
        .remove();
      cloned_email.find(".conditional_or:not(:first)").remove();
      cloned_email.find(".muhiku-plug-email-name input").val(name);

      setTimeout(function () {
        cloned_email.find(".mhk-field-conditional-input").val("");
      }, 2000);

      cloned_email
        .find(".mhk-content-email-settings-inner")
        .attr("data-connection_id", response.data.connection_id);
      cloned_email
        .find(".mhk-content-email-settings-inner")
        .removeClass("muhiku-plug-hidden");

      //Email toggle options.
      cloned_email
        .find(".mhk-toggle-switch input")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][enable_email_notification]"
        );
      cloned_email
        .find(".mhk-toggle-switch input:checkbox")
        .attr("data-connection-id", response.data.connection_id);
      cloned_email
        .find(".mhk-toggle-switch input:checkbox")
        .prop("checked", true);
      cloned_email.find(".mhk-toggle-switch input:checkbox").val("1");

      // Hiding Toggle for Prevous Email Setting.
      $(".mhk-content-email-settings .mhk-content-section-title").css(
        "display",
        "none"
      );
      // Removing email-disable-message;
      $(".email-disable-message").remove();
      // Removing Cloned email-disable-message;
      cloned_email.find(".email-disable-message").remove();
      // Showing Toggle for Current Email Setting.
      cloned_email
        .find(".mhk-toggle-switch")
        .parents(".mhk-content-section-title")
        .css("display", "flex");

      cloned_email
        .find(".mhk-field-conditional-container")
        .attr("data-connection_id", response.data.connection_id);
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-connection_name")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][connection_name]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_to_email")
        .attr(
          "name",
          "settings[email][" + response.data.connection_id + "][mhk_to_email]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_to_email")
        .val("{admin_email}");
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_carboncopy")
        .attr(
          "name",
          "settings[email][" + response.data.connection_id + "][mhk_carboncopy]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_blindcarboncopy")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][mhk_blindcarboncopy]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_from_name")
        .attr(
          "name",
          "settings[email][" + response.data.connection_id + "][mhk_from_name]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_from_name")
        .val(mhk_email_params.from_name);
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_from_email")
        .attr(
          "name",
          "settings[email][" + response.data.connection_id + "][mhk_from_email]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_from_email")
        .val("{admin_email}");
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_reply_to")
        .attr(
          "name",
          "settings[email][" + response.data.connection_id + "][mhk_reply_to]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_email_subject")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][mhk_email_subject]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-email-connection_1-mhk_email_subject")
        .val(mhk_email_params.email_subject);
      cloned_email
        .find("#muhiku_forms_panel_field_email_connection_1_mhk_email_message")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][mhk_email_message]"
        );
      cloned_email
        .find("#muhiku_forms_panel_field_email_connection_1_mhk_email_message")
        .val("{all_fields}");

      cloned_email
        .find(
          "#muhiku-plug-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email"
        )
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][attach_pdf_to_admin_email]"
        );
      cloned_email
        .find(
          "#muhiku-plug-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email"
        )
        .val(1);
      cloned_email
        .find(
          "#muhiku-plug-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email"
        )
        .attr(
          "id",
          "muhiku-plug-panel-field-settingsemail" +
            response.data.connection_id +
            "-attach_pdf_to_admin_email"
        );
      cloned_email
        .find(
          'label[for="muhiku-plug-panel-field-settingsemailconnection_1-attach_pdf_to_admin_email"]'
        )
        .attr(
          "for",
          "muhiku-plug-panel-field-settingsemail" +
            response.data.connection_id +
            "-attach_pdf_to_admin_email"
        );
      cloned_email
        .find(
          'input[name="settings[email][connection_1][attach_pdf_to_admin_email]"]'
        )
        .remove();

      cloned_email
        .find(
          "#muhiku-plug-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file"
        )
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][show_header_in_attachment_pdf_file]"
        );
      cloned_email
        .find(
          "#muhiku-plug-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file"
        )
        .val(1);
      cloned_email
        .find(
          "#muhiku-plug-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file"
        )
        .attr(
          "id",
          "muhiku-plug-panel-field-settingsemail" +
            response.data.connection_id +
            "-show_header_in_attachment_pdf_file"
        );
      cloned_email
        .find(
          'label[for="muhiku-plug-panel-field-settingsemailconnection_1-show_header_in_attachment_pdf_file"]'
        )
        .attr(
          "for",
          "muhiku-plug-panel-field-settingsemail" +
            response.data.connection_id +
            "-show_header_in_attachment_pdf_file"
        );
      cloned_email
        .find(
          'input[name="settings[email][connection_1][show_header_in_attachment_pdf_file]"]'
        )
        .remove();

      cloned_email
        .find("#muhiku-plug-panel-field-settingsemailconnection_1-pdf_name")
        .attr(
          "name",
          "settings[email][" + response.data.connection_id + "][pdf_name]"
        );
      cloned_email
        .find("#muhiku-plug-panel-field-settingsemailconnection_1-pdf_name")
        .val(form_title);
      cloned_email
        .find("#muhiku-plug-panel-field-settingsemailconnection_1-pdf_name")
        .attr(
          "id",
          "muhiku-plug-panel-field-settingsemail" +
            response.data.connection_id +
            "-pdf_name"
        );

      cloned_email
        .find(".muhiku-plug-attach-pdf-to-admin-email")
        .attr(
          "id",
          "muhiku-plug-panel-field-settingsemailconnection_" +
            response.data.connection_id +
            "-attach_pdf_to_admin_email-wrap"
        );
      cloned_email
        .find(".muhiku-plug-show-header-in-attachment-pdf-file ")
        .attr(
          "id",
          "muhiku-plug-panel-field-settingsemailconnection_" +
            response.data.connection_id +
            "-show_header_in_attachment_pdf_file-wrap"
        );

      cloned_email
        .find(
          "#muhiku-plug-panel-field-email-connection_1-conditional_logic_status"
        )
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][conditional_logic_status]"
        );
      cloned_email
        .find('.mhk_conditional_logic_container input[type="hidden"]')
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][conditional_logic_status]"
        );
      cloned_email
        .find(".mhk-field-show-hide")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][conditional_option]"
        );
      cloned_email
        .find(".mhk-field-conditional-field-select")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][conditionals][1][1][field]"
        );
      cloned_email
        .find(".mhk-field-conditional-condition")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][conditionals][1][1][operator]"
        );
      cloned_email
        .find(".mhk-field-conditional-input")
        .attr(
          "name",
          "settings[email][" +
            response.data.connection_id +
            "][conditionals][1][1][value]"
        );
      $cloned_email = cloned_email.append(
        '<input type="hidden" name="settings[email][' +
          response.data.connection_id +
          '][connection_name]" value="' +
          name +
          '">'
      );

      $(".mhk-email-settings-wrapper").append(cloned_email);
      $connections
        .find(".mhk-content-email-settings-inner")
        .last()
        .addClass("active-connection");
      $this
        .parent()
        .find(".muhiku-plug-active-email-connections-list li")
        .removeClass("active-user");
      $this
        .closest(".muhiku-plug-active-email.active")
        .children(".muhiku-plug-active-email-connections-list")
        .removeClass("empty-list");
      $this
        .parent()
        .find(".muhiku-plug-active-email-connections-list ")
        .append(
          '<li class="connection-list active-user" data-connection-id= "' +
            response.data.connection_id +
            '"><a class="user-nickname" href="#">' +
            name +
            '</a><a href="#"><span class="email-remove">Remove</span></a></li>'
        );
    },

    selectActiveAccount: function (el, e) {
      e.preventDefault();

      var $this = $(el),
        connection_id = $this.data("connection-id"),
        active_block = $(".mhk-content-email-settings").find(
          '[data-connection_id="' + connection_id + '"]'
        ),
        lengthOfActiveBlock = $(active_block).length;

      $(".mhk-content-email-settings")
        .find(".mhk-content-email-settings-inner")
        .removeClass("active-connection");

      // Hiding Email Notificaton Trigger (Previous).
      $(".mhk-content-section-title")
        .has(
          "[data-connection-id=" +
            $this.siblings(".active-user").attr("data-connection-id") +
            "]"
        )
        .css("display", "none");
      $this.siblings().removeClass("active-user");
      $this.addClass("active-user");

      if (lengthOfActiveBlock) {
        $(active_block).addClass("active-connection");
      }

      // Removing Email Notification Turn On Message.
      $(".email-disable-message").remove();
      if (
        $(
          "input[data-connection-id=" +
            $this.attr("data-connection-id") +
            "]:last"
        ).prop("checked") == false
      ) {
        $(
          '<p class="email-disable-message muhiku-plug-notice muhiku-plug-notice-info">' +
            mhk_data.i18n_email_disable_message +
            "</p>"
        ).insertAfter(
          $(".mhk-content-section-title").has(
            "[data-connection-id=" + $this.attr("data-connection-id") + "]"
          )
        );
      }

      // Displaying Email Notificaton Trigger (Current).
      $(".mhk-content-section-title")
        .has("[data-connection-id=" + $this.attr("data-connection-id") + "]")
        .css("display", "flex");
    },

    removeAccount: function (el, e) {
      e.preventDefault();

      var $this = $(el),
        connection_id = $this.parent().parent().data("connection-id"),
        active_block = $(".mhk-content-email-settings").find(
          '[data-connection_id="' + connection_id + '"]'
        ),
        lengthOfActiveBlock = $(active_block).length;
      $.confirm({
        title: false,
        content: "Are you sure you want to delete this Email?",
        backgroundDismiss: false,
        closeIcon: false,
        icon: "dashicons dashicons-info",
        type: "orange",
        buttons: {
          confirm: {
            text: mhk_email_params.i18n_email_ok,
            btnClass: "btn-confirm",
            keys: ["enter"],
            action: function () {
              if (lengthOfActiveBlock) {
                var toBeRemoved = $this.parent().parent();
                (active_block_after = $(".mhk-provider-connections").find(
                  '[data-connection_id="' + connection_id + '"]'
                )),
                  (lengthOfActiveBlockAfter = $(active_block).length);
                if (toBeRemoved.prev().length) {
                  toBeRemoved.prev(".connection-list").trigger("click");
                } else {
                  toBeRemoved.next(".connection-list").trigger("click");
                }

                $(active_block).parent().remove();
                toBeRemoved.remove();
              }
            },
          },
          cancel: {
            text: mhk_email_params.i18n_email_cancel,
          },
        },
      });
    },

    removeDefaultAccount: function (el, e) {
      e.preventDefault;
      $.alert({
        title: false,
        content: "Default Email can not be deleted !",
        icon: "dashicons dashicons-info",
        type: "blue",
        buttons: {
          ok: {
            text: mhk_data.i18n_ok,
            btnClass: "btn-confirm",
            keys: ["enter"],
          },
        },
      });
    },

    focusConnectionName: function (el, e) {
      var $this = $(el);
      $this.data("val", $this.val().trim());
    },

    renameConnection: function (el, e) {
      e.preventDefault;
      var $this = $(el);
      var connection_id = $this
        .closest(".mhk-content-email-settings-inner")
        .data("connection_id");
      $active_block = $(".muhiku-plug-active-email-connections-list").find(
        '[data-connection-id="' + connection_id + '"]'
      );
      $active_block.find(".user-nickname").text($this.val());
      if ($this.val().trim().length === 0) {
        $this
          .parent(".muhiku-plug-email-name")
          .find(".muhiku-plug-error")
          .remove();
        $this
          .parent(".muhiku-plug-email-name")
          .append(
            '<p class="muhiku-plug-error muhiku-plug-text-danger">Email name cannot be empty.</p>'
          );
        $this.next(".muhiku-plug-error").fadeOut(3000);
        setTimeout(function () {
          if ($this.val().length === 0) {
            $this.val($this.data("val"));
            $active_block.find(".user-nickname").text($this.data("val"));
          }
        }, 3000);
      }
    },
  };
  MuhikuPlugEmail.init();
})(jQuery);
