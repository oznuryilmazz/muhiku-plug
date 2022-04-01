/* global mhk_data, jconfirm, PerfectScrollbar, mhkSetClipboard, mhkClearClipboard */
(function ($, mhk_data) {
  var $builder;

  var MHKPanelBuilder = {
    /**
     * Start the panel builder.
     */
    init: function () {
      $(document).ready(function ($) {
        if (!$("mhk-panel-integrations-button a").hasClass("active")) {
          $("#muhiku-plug-panel-integrations")
            .find(".muhiku-plug-panel-sidebar a")
            .first()
            .addClass("active");
          if (
            $("#muhiku-plug-panel-integrations")
              .find(".muhiku-plug-panel-sidebar a")
              .hasClass("active")
          ) {
            $("#muhiku-plug-panel-integrations")
              .find(".muhiku-plug-panel-sidebar a")
              .next(".muhiku-plug-active-connections")
              .first()
              .addClass("active");
          }
          $(".muhiku-plug-panel-content")
            .find(".mhk-panel-content-section")
            .first()
            .addClass("active");
        }
      });

      $(document).ready(function ($) {
        if ("1" === $(".muhiku-plug-min-max-date-format input").val()) {
          $(".muhiku-plug-min-date")
            .addClass("flatpickr-field")
            .flatpickr({
              disableMobile: true,
              onChange: function (selectedDates, dateStr, instance) {
                $(".muhiku-plug-min-date").val(dateStr);
              },
              onOpen: function (selectedDates, dateStr, instance) {
                instance.set("maxDate", $(".muhiku-plug-max-date").val());
              },
            });

          $(".muhiku-plug-max-date")
            .addClass("flatpickr-field")
            .flatpickr({
              disableMobile: true,
              onChange: function (selectedDates, dateStr, instance) {
                $(".muhiku-plug-max-date").val(dateStr);
              },
              onOpen: function (selectedDates, dateStr, instance) {
                instance.set("minDate", $(".muhiku-plug-min-date").val());
              },
            });
        }
      });

      if (!$("mhk-panel-payments-button a").hasClass("active")) {
        $("#muhiku-plug-panel-payments")
          .find(".muhiku-plug-panel-sidebar a")
          .first()
          .addClass("active");
        $(".muhiku-plug-panel-content")
          .find(".mhk-payment-setting-content")
          .first()
          .addClass("active");
      }

      // Copy shortcode from the builder.
      $(document.body)
        .find("#copy-shortcode")
        .on("click", this.copyShortcode)
        .on("aftercopy", this.copySuccess)
        .on("aftercopyfailure", this.copyFail);

      // Copy shortcode from form list table.
      $(document.body)
        .find(".mhk-copy-shortcode")
        .each(function () {
          $(this)
            .on("click", MHKPanelBuilder.copyShortcode)
            .on("aftercopy", MHKPanelBuilder.copySuccess)
            .on("aftercopyfailure", MHKPanelBuilder.copyFail);
        });

      // Document ready.
      $(document).ready(MHKPanelBuilder.ready);

      // Page load.
      $(window).on("load", MHKPanelBuilder.load);

      // Initialize builder UI fields.
      $(document.body)
        .on("mhk-init-builder-fields", function () {
          MHKPanelBuilder.bindFields();
        })
        .trigger("mhk-init-builder-fields");

      // Adjust builder width.
      $(document.body)
        .on("adjust_builder_width", function () {
          var adminMenuWidth = $("#adminmenuwrap").width();

          $("#muhiku-plug-builder-form").css({
            width: "calc(100% - " + adminMenuWidth + "px)",
          });
        })
        .trigger("adjust_builder_width");

      $(document.body).on("click", "#collapse-button", function () {
        $("#muhiku-plug-builder-form").width("");
        $(document.body).trigger("adjust_builder_width");
      });

      $(window)
        .on("resize orientationchange", function () {
          var resizeTimer;

          clearTimeout(resizeTimer);
          resizeTimer = setTimeout(function () {
            $("#muhiku-plug-builder").width("");
            $(document.body).trigger("adjust_builder_width");
          }, 250);
        })
        .trigger("resize");
    },

    /**
     * Copy shortcode.
     *
     * @param {Object} evt Copy event.
     */
    copyShortcode: function (evt) {
      mhkClearClipboard();
      mhkSetClipboard(
        $(this).closest(".mhk-shortcode-field").find("input").val(),
        $(this)
      );
      evt.preventDefault();
    },

    /**
     * Display a "Copied!" tip when success copying.
     */
    copySuccess: function () {
      $(this)
        .tooltipster("content", $(this).attr("data-copied"))
        .trigger("mouseenter")
        .on("mouseleave", function () {
          var $this = $(this);

          setTimeout(function () {
            $this.tooltipster("content", $this.attr("data-tip"));
          }, 1000);
        });
    },

    /**
     * Displays the copy error message when failure copying.
     */
    copyFail: function () {
      $(this).closest(".mhk-shortcode-field").find("input").focus().select();
    },

    /**
     * Page load.
     *
     * @since 1.0.0
     */
    load: function () {
      $(".muhiku-plug-overlay").fadeOut();
    },

    /**
     * Document ready.
     *
     * @since 1.0.0
     */
    ready: function () {
      // Cache builder element.
      $builder = $("#muhiku-plug-builder");

      // Bind all actions.
      MHKPanelBuilder.bindUIActions();

      // Bind edit form actions.
      MHKPanelBuilder.bindEditActions();

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

      // Enable Perfect Scrollbar.
      if ("undefined" !== typeof PerfectScrollbar) {
        var tab_content = $(".muhiku-plug-tab-content"),
          mhk_panel = $(".muhiku-plug-panel");

        if (tab_content.length >= 1) {
          window.mhk_tab_scroller = new PerfectScrollbar(
            ".muhiku-plug-tab-content",
            {
              suppressScrollX: true,
            }
          );
        }

        mhk_panel.each(function () {
          var section_panel = $(this);
          var panel_id = section_panel.attr("id");

          if (section_panel.find(".muhiku-plug-panel-sidebar").length >= 1) {
            window.mhk_setting_scroller = new PerfectScrollbar(
              "#" + panel_id + " .muhiku-plug-panel-sidebar"
            );
          }
        });
      }

      // Enable Limit length.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-limit_enabled input",
        function (event) {
          MHKPanelBuilder.updateTextFieldsLimitControls(
            $(event.target)
              .parents(".muhiku-plug-field-option-row-limit_enabled")
              .data().fieldId,
            event.target.checked
          );
        }
      );

      // Enable enhanced select.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-select .muhiku-plug-field-option-row-enhanced_select input",
        function (event) {
          MHKPanelBuilder.enhancedSelectFieldStyle(
            $(event.target)
              .parents(".muhiku-plug-field-option-row-enhanced_select")
              .data().fieldId,
            event.target.checked
          );
        }
      );

      // Enable Multiple options.
      $builder.on(
        "click",
        ".muhiku-plug-field-option-row-choices .muhiku-plug-btn-group span",
        function (event) {
          if (
            $(this).hasClass("upgrade-modal") &&
            "checkbox" === $(this).data("type")
          ) {
            $(this).parent().find("span").addClass("is-active");
            $(this).removeClass("is-active");
            MHKPanelBuilder.updateEnhandedSelectField(
              $(event.target)
                .parents(".muhiku-plug-field-option-row-choices")
                .data().fieldId,
              false
            );
          } else {
            $(this).parent().find("span").removeClass("is-active");
            $(this).addClass("is-active");
            MHKPanelBuilder.updateEnhandedSelectField(
              $(event.target)
                .parents(".muhiku-plug-field-option-row-choices")
                .data().fieldId,
              "multiple" === $(this).data("selection")
            );
          }

          // Show 'Select All' Checkbox for Dropdown field only if multiple selection is active
          if (
            "multiple" === $(this).data("selection") &&
            "checkbox" === $(this).data("type") &&
            $(this).hasClass("is-active")
          ) {
            var $field_id = $(this).parent().parent().data("field-id");
            $(
              "#muhiku-plug-field-option-row-" + $field_id + "-select_all"
            ).show();
          } else {
            var $field_id = $(this).parent().parent().data("field-id");
            $(
              "#muhiku-plug-field-option-row-" + $field_id + "-select_all"
            ).hide();
          }
        }
      );

      // By default hide the 'Select All' checkbox for Dropdown field
      $(document.body).on(
        "click",
        '.muhiku-plug-field, .muhiku-plug-field-select[data-field-type="select"]',
        function () {
          $builder
            .find(
              ".muhiku-plug-field-option-row-choices .muhiku-plug-btn-group span"
            )
            .each(function () {
              var $field_id = $(this).parent().parent().data("field-id");

              if (
                "multiple" === $(this).data("selection") &&
                "checkbox" === $(this).data("type") &&
                $(this).hasClass("is-active")
              ) {
                $("#muhiku-plug-field-option-" + $field_id + "-select_all")
                  .parent()
                  .show();
              } else {
                $("#muhiku-plug-field-option-" + $field_id + "-select_all")
                  .parent()
                  .hide();
              }
            });
        }
      );

      // Search fields input.
      $builder.on("keyup", ".muhiku-plug-search-fields", function () {
        var searchTerm = $(this).val().toLowerCase();

        // Show/hide fields.
        $(".mhk-registered-item").each(function () {
          var $this = $(this);
          (field_type = $this.data("field-type")),
            (field_label = $this.text().toLowerCase());

          if (
            field_type.search(searchTerm) > -1 ||
            field_label.search(searchTerm) > -1
          ) {
            $this.addClass("mhk-searched-item");
            $this.show();
          } else {
            $this.removeClass("mhk-searched-item");
            $this.hide();
          }
        });

        // Show/hide field group.
        $(".muhiku-plug-add-fields-group").each(function () {
          var count = $(this).find(
            ".mhk-registered-item.mhk-searched-item"
          ).length;

          if (0 >= count) {
            $(this).hide();
          } else {
            $(this).show();
          }
        });

        // Show/hide fields not found indicator.
        if ($(".mhk-registered-item.mhk-searched-item").length) {
          $(".muhiku-plug-fields-not-found").addClass("hidden");
        } else {
          $(".muhiku-plug-fields-not-found").removeClass("hidden");
        }
      });

      // Action available for each binding.
      $(document).trigger("everest_forms_ready");
    },

    /**
     * Update text fields limit controls.
     *
     * @since 1.5.10
     *
     * @param {number} fieldId Field ID.
     * @param {bool} checked Whether an option is checked or not.
     */
    updateTextFieldsLimitControls: function (fieldId, checked) {
      if (!checked) {
        $(
          "#muhiku-plug-field-option-row-" + fieldId + "-limit_controls"
        ).addClass("muhiku-plug-hidden");
      } else {
        $(
          "#muhiku-plug-field-option-row-" + fieldId + "-limit_controls"
        ).removeClass("muhiku-plug-hidden");
      }
    },

    /**
     * Enhanced select fields style.
     *
     * @since 1.7.1
     *
     * @param {number} fieldId Field ID.
     * @param {bool} checked Whether an option is checked or not.
     */
    enhancedSelectFieldStyle: function (fieldId, checked) {
      var $primary = $("#muhiku-plug-field-" + fieldId + " .primary-input"),
        isEnhanced = $(
          "#muhiku-plug-field-option-" + fieldId + "-enhanced_select"
        ).is(":checked");

      if (checked && isEnhanced && $primary.prop("multiple")) {
        $primary.addClass("mhk-enhanced-select");
        $(document.body).trigger("mhk-enhanced-select-init");
      } else {
        $primary.removeClass("mhk-enhanced-select enhanced");
        $primary.filter(".select2-hidden-accessible").selectWoo("destroy");
      }
    },

    /**
     * Update enhanced select field component.
     *
     * @since 1.7.1
     *
     * @param {number} fieldId Field ID.
     * @param {bool} isMultiple Whether an option is multiple or not.
     */
    updateEnhandedSelectField: function (fieldId, isMultiple) {
      var $primary = $("#muhiku-plug-field-" + fieldId + " .primary-input"),
        $placeholder = $primary.find(".placeholder"),
        $hiddenField = $(
          "#muhiku-plug-field-option-" + fieldId + "-multiple_choices"
        ),
        $optionChoicesItems = $(
          "#muhiku-plug-field-option-row-" + fieldId + "-choices input.default"
        ),
        selectedChoices = $optionChoicesItems.filter(":checked");

      // Update hidden field value.
      $hiddenField.val(isMultiple ? 1 : 0);

      // Add/remove a `multiple` attribute.
      $primary.prop("multiple", isMultiple);

      // Change a `Choices` fields type:
      //    radio - needed for single selection
      //    checkbox - needed for multiple selection
      $optionChoicesItems.prop("type", isMultiple ? "checkbox" : "radio");

      // For single selection we can choose only one.
      if (!isMultiple && selectedChoices.length) {
        $optionChoicesItems.prop("checked", false);
        $(selectedChoices.get(0)).prop("checked", true);
      }

      // Toggle selection for a placeholder.
      if ($placeholder.length && isMultiple) {
        $placeholder.prop("selected", !isMultiple);
      }

      // Update a primary field.
      MHKPanelBuilder.enhancedSelectFieldStyle(fieldId, isMultiple);
    },

    /**
     * Element bindings.
     *
     * @since 1.0.0
     */
    bindUIActions: function () {
      MHKPanelBuilder.bindDefaultTabs();
      MHKPanelBuilder.checkEmptyGrid();
      MHKPanelBuilder.bindFields();
      MHKPanelBuilder.bindFormPreview();
      MHKPanelBuilder.bindFormPreviewWithKeyEvent();
      MHKPanelBuilder.bindFormEntriesWithKeyEvent();
      MHKPanelBuilder.bindGridSwitcher();
      MHKPanelBuilder.bindFieldSettings();
      MHKPanelBuilder.bindFieldDelete();
      MHKPanelBuilder.bindFieldDeleteWithKeyEvent();
      MHKPanelBuilder.bindCloneField();
      MHKPanelBuilder.bindSaveOption();
      MHKPanelBuilder.bindSaveOptionWithKeyEvent();
      MHKPanelBuilder.bindAddNewRow();
      MHKPanelBuilder.bindRemoveRow();
      MHKPanelBuilder.bindFormSettings();
      MHKPanelBuilder.bindFormEmail();
      MHKPanelBuilder.bindFormIntegrations();
      MHKPanelBuilder.bindFormPayment();
      MHKPanelBuilder.choicesInit();
      MHKPanelBuilder.bindToggleHandleActions();
      MHKPanelBuilder.bindLabelEditInputActions();
      MHKPanelBuilder.bindSyncedInputActions();
      MHKPanelBuilder.init_datepickers();
      MHKPanelBuilder.bindBulkOptionActions();

      // Fields Panel.
      MHKPanelBuilder.bindUIActionsFields();

      if (mhk_data.tab === "field-options") {
        $(".mhk-panel-field-options-button").trigger("click");
      }

      $(document.body).on(
        "muhiku-plug-field-drop",
        ".mhk-registered-buttons .mhk-registered-item",
        function () {
          MHKPanelBuilder.fieldDrop($(this).clone());
        }
      );
    },

    /**
     * Bind user action handlers for the Add Bulk Options feature.
     */
    bindBulkOptionActions: function () {
      // Toggle `Bulk Add` option.
      $(document.body).on("click", ".mhk-toggle-bulk-options", function (e) {
        $(this)
          .closest(".muhiku-plug-field-option")
          .find(".muhiku-plug-field-option-row-add_bulk_options")
          .slideToggle();
      });
      // Toggle presets list.
      $(document.body).on("click", ".mhk-toggle-presets-list", function (e) {
        $(this)
          .closest(".muhiku-plug-field-option")
          .find(".muhiku-plug-field-option-row .mhk-options-presets")
          .slideToggle();
      });
      // Add custom list of options.
      $(document.body).on("click", ".mhk-add-bulk-options", function (e) {
        var $option_row = $(this).closest(".muhiku-plug-field-option-row");
        var field_id = $option_row.data("field-id");

        if ($option_row.length) {
          var $choices = $option_row
            .closest(".muhiku-plug-field-option")
            .find(".muhiku-plug-field-option-row-choices .mhk-choices-list");
          var $bulk_options_container = $option_row.find(
            "textarea#muhiku-plug-field-option-" +
              field_id +
              "-add_bulk_options"
          );
          var options_texts = $bulk_options_container.val().split("\n");

          MHKPanelBuilder.addBulkOptions(options_texts, $choices);
          $bulk_options_container.val("");
        }
      });
      // Add presets of options.
      $(document.body).on("click", ".mhk-options-preset-label", function (e) {
        var $option_row = $(this).closest(".muhiku-plug-field-option-row");
        var field_id = $option_row.data("field-id");

        if ($option_row.length) {
          var options_texts = $(this)
            .closest(".mhk-options-preset")
            .find(".mhk-options-preset-value")
            .val();

          $option_row
            .find(
              "textarea#muhiku-plug-field-option-" +
                field_id +
                "-add_bulk_options"
            )
            .val(options_texts);
          $(this).closest(".mhk-options-presets").slideUp();
        }
      });
      //Add toggle option for password validation and strength meter.
      $(document.body).on(
        "click",
        ".muhiku-plug-field-option-row-password_strength",
        function () {
          if ($(this).find('[type="checkbox"]:first').prop("checked")) {
            $(this)
              .next()
              .find('[type="checkbox"]:first')
              .prop("checked", false);
            // $(this).prev().find('.muhiku-plug-inner-options').hide();
          }
        }
      );
      $(document.body).on(
        "click",
        ".muhiku-plug-field-option-row-password_validation",
        function () {
          if ($(this).find('[type="checkbox"]:first').prop("checked")) {
            $(this)
              .prev()
              .find('[type="checkbox"]:first')
              .prop("checked", false);
            $(this)
              .prev()
              .find(".muhiku-plug-inner-options")
              .addClass("muhiku-plug-hidden");
          }
        }
      );
    },

    /**
     * Add a list of options at once.
     *
     * @param {Array<string>} options_texts List of options to add.
     * @param {object} $choices_container Options container where the options should be added.
     */
    addBulkOptions: function (options_texts, $choices_container) {
      options_texts.forEach(function (option_text) {
        if ("" !== option_text) {
          var $add_button = $choices_container.find("li").last().find("a.add");
          MHKPanelBuilder.choiceAdd(null, $add_button, option_text.trim());
        }
      });
    },

    /**
     * Initialize date pickers like min/max date, disable dates etc.
     *
     * @since 1.6.6
     */
    init_datepickers: function () {
      var date_format = $(".muhiku-plug-disable-dates").data("date-format"),
        selection_mode = "multiple";

      // Initialize "Disable dates" option's date pickers that hasn't been initialized.
      $(".muhiku-plug-disable-dates").each(function () {
        if (!$(this).get(0)._flatpickr) {
          $(this).flatpickr({
            dateFormat: date_format,
            mode: selection_mode,
          });
        }
      });

      // Reformat the selected dates input value for `Disable dates` option when the date format changes.
      $(document.body).on("change", ".mhk-date-format", function (e) {
        var $disable_dates = $(
            ".muhiku-plug-field-option:visible .muhiku-plug-disable-dates"
          ),
          flatpicker = $disable_dates.get(0)._flatpickr,
          selectedDates = flatpicker.selectedDates,
          date_format = $(this).val(),
          formatedDates = [];

        selectedDates.forEach(function (date) {
          formatedDates.push(flatpickr.formatDate(date, date_format));
        });
        flatpicker.set("dateFormat", date_format);
        $disable_dates.val(formatedDates.join(", "));
      });

      // Clear disabled dates.
      $(document.body).on("click", ".mhk-clear-disabled-dates", function () {
        $(".muhiku-plug-field-option:visible .muhiku-plug-disable-dates")
          .get(0)
          ._flatpickr.clear();
      });

      // Triggring Setting Toggler.
      $(".muhiku-plug-field-date-time").each(function () {
        var id = $(this).attr("data-field-id");
        MHKPanelBuilder.dateSettingToggler(
          id,
          $("#muhiku-plug-field-option-" + id + "-datetime_style").val()
        );
      });
    },

    /**
     * Form edit title actions.
     *
     * @since 1.6.0
     */
    bindEditActions: function () {
      // Delegates event to toggleEditTitle() on clicking.
      $("#edit-form-name").on("click", function (e) {
        e.stopPropagation();

        if ("" !== $("#mhk-edit-form-name").val().trim()) {
          MHKPanelBuilder.toggleEditTitle(e);
        }
      });

      // Apply the title change to form name field.
      $("#mhk-edit-form-name")
        .on("change keypress", function (e) {
          var $this = $(this);

          e.stopPropagation();

          if (13 === e.which && "" !== $(this).val().trim()) {
            MHKPanelBuilder.toggleEditTitle(e);
          }

          if ("" !== $this.val().trim()) {
            $("#muhiku-plug-panel-field-settings-form_title").val(
              $this.val().trim()
            );
          }
        })
        .on("click", function (e) {
          e.stopPropagation();
        });

      // In case the user goes out of focus from title edit state.
      $(document)
        .not($(".muhiku-plug-title-desc"))
        .on("click", function (e) {
          var field = $("#mhk-edit-form-name");

          e.stopPropagation();

          // Only allow flipping state if currently editing.
          if (
            !field.prop("disabled") &&
            field.val() &&
            "" !== field.val().trim()
          ) {
            MHKPanelBuilder.toggleEditTitle(e);
          }
        });
    },

    // Toggles edit state.
    toggleEditTitle: function (event) {
      var $el = $("#edit-form-name"),
        $input_title = $el.siblings("#mhk-edit-form-name");

      event.preventDefault();

      // Toggle disabled property.
      $input_title.prop("disabled", function (_, val) {
        return !val;
      });

      if (!$input_title.hasClass("everst-forms-name-editing")) {
        $input_title.focus();
      }

      $input_title.toggleClass("everst-forms-name-editing");
    },

    //--------------------------------------------------------------------//
    // Fields Panel
    //--------------------------------------------------------------------//

    /**
     * Creates a object from form elements.
     *
     * @since 1.6.0
     */
    formObject: function (el) {
      var form = jQuery(el),
        fields = form.find("[name]"),
        json = {},
        arraynames = {};

      for (var v = 0; v < fields.length; v++) {
        var field = jQuery(fields[v]),
          name = field.prop("name").replace(/\]/gi, "").split("["),
          value = field.val(),
          lineconf = {};

        if (
          (field.is(":radio") || field.is(":checkbox")) &&
          !field.is(":checked")
        ) {
          continue;
        }
        for (var i = name.length - 1; i >= 0; i--) {
          var nestname = name[i];
          if (typeof nestname === "undefined") {
            nestname = "";
          }
          if (nestname.length === 0) {
            lineconf = [];
            if (typeof arraynames[name[i - 1]] === "undefined") {
              arraynames[name[i - 1]] = 0;
            } else {
              arraynames[name[i - 1]] += 1;
            }
            nestname = arraynames[name[i - 1]];
          }
          if (i === name.length - 1) {
            if (value) {
              if (value === "true") {
                value = true;
              } else if (value === "false") {
                value = false;
              } else if (
                !isNaN(parseFloat(value)) &&
                parseFloat(value).toString() === value
              ) {
                value = parseFloat(value);
              } else if (
                typeof value === "string" &&
                (value.substr(0, 1) === "{" || value.substr(0, 1) === "[")
              ) {
                try {
                  value = JSON.parse(value);
                } catch (e) {}
              } else if (
                typeof value === "object" &&
                value.length &&
                field.is("select")
              ) {
                var new_val = {};
                for (var i = 0; i < value.length; i++) {
                  new_val["n" + i] = value[i];
                }
                value = new_val;
              }
            }
            lineconf[nestname] = value;
          } else {
            var newobj = lineconf;
            lineconf = {};
            lineconf[nestname] = newobj;
          }
        }
        $.extend(true, json, lineconf);
      }

      return json;
    },

    /**
     * Element bindings for Fields panel.
     *
     * @since 1.2.0
     */
    bindUIActionsFields: function () {
      // Add new field choice.
      $builder.on(
        "click",
        ".muhiku-plug-field-option-row-choices .add",
        function (event) {
          MHKPanelBuilder.choiceAdd(event, $(this));
        }
      );

      // Delete field choice.
      $builder.on(
        "click",
        ".muhiku-plug-field-option-row-choices .remove",
        function (event) {
          MHKPanelBuilder.choiceDelete(event, $(this));
        }
      );

      // Field choices defaults - (before change).
      $builder.on(
        "mousedown",
        ".muhiku-plug-field-option-row-choices input[type=radio]",
        function () {
          var $this = $(this);

          if ($this.is(":checked")) {
            $this.attr("data-checked", "1");
          } else {
            $this.attr("data-checked", "0");
          }
        }
      );

      // Field choices defaults.
      $builder.on(
        "click",
        ".muhiku-plug-field-option-row-choices input[type=radio]",
        function () {
          var $this = $(this),
            list = $this.parent().parent();

          $this
            .parent()
            .parent()
            .find("input[type=radio]")
            .not(this)
            .prop("checked", false);

          if ($this.attr("data-checked") === "1") {
            $this.prop("checked", false);
            $this.attr("data-checked", "0");
          }

          MHKPanelBuilder.choiceUpdate(
            list.data("field-type"),
            list.data("field-id")
          );
        }
      );

      // Field choices update preview area.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-choices input[type=checkbox]",
        function (e) {
          var list = $(this).parent().parent();
          MHKPanelBuilder.choiceUpdate(
            list.data("field-type"),
            list.data("field-id")
          );
        }
      );

      // Updates field choices text in almost real time.
      $builder.on(
        "keyup paste focusout",
        ".muhiku-plug-field-option-row-choices input.label, .muhiku-plug-field-option-row-choices input.value",
        function (e) {
          var list = $(this).parent().parent().parent();
          MHKPanelBuilder.choiceUpdate(
            list.data("field-type"),
            list.data("field-id")
          );
        }
      );

      // Field choices display value toggle.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-show_values input",
        function (e) {
          $(this)
            .closest(".muhiku-plug-field-option")
            .find(".muhiku-plug-field-option-row-choices ul")
            .toggleClass("show-values");
        }
      );

      // Field image choices toggle.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-choices_images input",
        function () {
          var $this = $(this),
            field_id = $this.parent().data("field-id"),
            $fieldOptions = $("#muhiku-plug-field-option-" + field_id),
            $columnOptions = $(
              "#muhiku-plug-field-option-" + field_id + "-input_columns"
            ),
            type = $("#muhiku-plug-field-option-" + field_id)
              .find(".muhiku-plug-field-option-hidden-type")
              .val();

          $this.parent().find(".notice").toggleClass("hidden");
          $fieldOptions
            .find(".muhiku-plug-field-option-row-choices ul")
            .toggleClass("show-images");

          // Trigger columns changes.
          if ($this.is(":checked")) {
            $columnOptions.val("inline").trigger("change");
          } else {
            $columnOptions.val("").trigger("change");
          }

          MHKPanelBuilder.choiceUpdate(type, field_id);
        }
      );

      // Upload or add an image.
      $builder.on(
        "click",
        ".muhiku-plug-attachment-media-view .upload-button",
        function (event) {
          var $el = $(this),
            $wrapper,
            file_frame;

          event.preventDefault();

          // If the media frame already exists, reopen it.
          if (file_frame) {
            file_frame.open();
            return;
          }

          // Create the media frame.
          file_frame = wp.media.frames.everestforms_media_frame = wp.media({
            title: mhk_data.i18n_upload_image_title,
            className: "media-frame muhiku-plug-media-frame",
            frame: "select",
            multiple: false,
            library: {
              type: "image",
            },
            button: {
              text: mhk_data.i18n_upload_image_button,
            },
          });

          // When an image is selected, run a callback.
          file_frame.on("select", function () {
            var attachment = file_frame
              .state()
              .get("selection")
              .first()
              .toJSON();

            if ($el.hasClass("button-add-media")) {
              $el.hide();
              $wrapper = $el.parent();
            } else {
              $wrapper = $el.parent().parent().parent();
            }

            $wrapper.find(".source").val(attachment.url);
            $wrapper.find(".attachment-thumb").remove();
            $wrapper
              .find(".thumbnail-image")
              .prepend(
                '<img class="attachment-thumb" src="' + attachment.url + '">'
              );
            $wrapper.find(".actions").show();

            $builder.trigger("everestFormsImageUploadAdd", [$el, $wrapper]);
          });

          // Finally, open the modal.
          file_frame.open();
        }
      );

      // Remove and uploaded image.
      $builder.on(
        "click",
        ".muhiku-plug-attachment-media-view .remove-button",
        function (event) {
          event.preventDefault();

          var $container = $(this).parent().parent();

          $container.find(".attachment-thumb").remove();
          $container.parent().find(".source").val("");
          $container.parent().find(".button-add-media").show();

          $builder.trigger("everestFormsImageUploadRemove", [
            $(this),
            $container,
          ]);
        }
      );

      // Field choices image upload add/remove image.
      $builder.on(
        "everestFormsImageUploadAdd everestFormsImageUploadRemove",
        function (event, $this, $container) {
          var $el = $container.closest(".mhk-choices-list"),
            type = $el.data("field-type"),
            field_id = $el.data("field-id");

          MHKPanelBuilder.choiceUpdate(type, field_id);
        }
      );

      // Toggle Layout advanced field option.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-input_columns select",
        function () {
          var $this = $(this),
            value = $this.val(),
            field_id = $this.parent().data("field-id"),
            css_class = "";

          if ("inline" === value) {
            css_class = "muhiku-plug-list-inline";
          } else if ("" !== value) {
            css_class = "muhiku-plug-list-" + value + "-columns";
          }

          $("#muhiku-plug-field-" + field_id)
            .removeClass(
              "muhiku-plug-list-inline muhiku-plug-list-2-columns muhiku-plug-list-3-columns"
            )
            .addClass(css_class);
        }
      );

      // Field sidebar tab toggle.
      $builder.on("click", ".muhiku-plug-fields-tab a", function (e) {
        e.preventDefault();
        MHKPanelBuilder.fieldTabChoice($(this).attr("id"));
      });

      // Dragged field and hover over tab buttons - multipart.
      $(document).on(
        "mouseenter",
        '.muhiku-plug-tabs li[class*="part_"]',
        function () {
          if (
            false === $(this).hasClass("active") &&
            ($(document)
              .find(".muhiku-plug-field")
              .hasClass("ui-sortable-helper") ||
              $(document)
                .find(".mhk-registered-buttons button.mhk-registered-item")
                .hasClass("field-dragged"))
          ) {
            $(this).find("a").trigger("click");
          }
        }
      );

      // Display toggle for "Address" field hidden option.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-address input.hide",
        function () {
          var $this = $(this),
            id = $this.parent().parent().data("field-id"),
            subfield = $this.parent().parent().data("subfield");
          $("#muhiku-plug-field-" + id)
            .find(".muhiku-plug-" + subfield)
            .toggleClass("hidden");
        }
      );

      // Real-time updates for "Show Label" field option.
      $builder.on(
        "input",
        ".muhiku-plug-field-option-row-label input",
        function () {
          var $this = $(this),
            value = $this.val(),
            id = $this.parent().data("field-id");
          $label = $("#muhiku-plug-field-" + id).find(".label-title .text");

          if ($label.hasClass("nl2br")) {
            $label.html(value.replace(/\n/g, "<br>"));
          } else {
            $label.html(value);
          }
        }
      );

      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-enable_prepopulate input",
        function (event) {
          var id = $(this).parent().data("field-id");

          $("#muhiku-plug-field-" + id).toggleClass("parameter_name");

          // Toggle "Parameter Name" option.
          if ($(event.target).is(":checked")) {
            $("#muhiku-plug-field-option-row-" + id + "-parameter_name").show();
          } else {
            $("#muhiku-plug-field-option-row-" + id + "-parameter_name").hide();
          }
        }
      );

      // Real-time updates for "Description" field option.
      $builder.on(
        "input",
        ".muhiku-plug-field-option-row-description textarea",
        function () {
          var $this = $(this),
            value = $this.val(),
            id = $this.parent().data("field-id"),
            $desc = $("#muhiku-plug-field-" + id).find(".description");

          if ($desc.hasClass("nl2br")) {
            $desc.html(value.replace(/\n/g, "<br>"));
          } else {
            $desc.html(value);
          }
        }
      );

      // Real-time updates for "Required" field option.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-required input",
        function (event) {
          var id = $(this).parent().data("field-id");

          $("#muhiku-plug-field-" + id).toggleClass("required");

          // Toggle "Required Field Message" option.
          if ($(event.target).is(":checked")) {
            $(
              "#muhiku-plug-field-option-row-" + id + "-required-field-message"
            ).show();
          } else {
            $(
              "#muhiku-plug-field-option-row-" + id + "-required-field-message"
            ).hide();
          }
        }
      );

      // Real-time updates for "Confirmation" field option.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-confirmation input",
        function (event) {
          var id = $(this).parent().data("field-id");

          // Toggle "Confirmation" field option.
          if ($(event.target).is(":checked")) {
            $("#muhiku-plug-field-" + id)
              .find(".muhiku-plug-confirm")
              .removeClass("muhiku-plug-confirm-disabled")
              .addClass("muhiku-plug-confirm-enabled");
            $("#muhiku-plug-field-option-" + id)
              .removeClass("muhiku-plug-confirm-disabled")
              .addClass("muhiku-plug-confirm-enabled");
          } else {
            $("#muhiku-plug-field-" + id)
              .find(".muhiku-plug-confirm")
              .removeClass("muhiku-plug-confirm-enabled")
              .addClass("muhiku-plug-confirm-disabled");
            $("#muhiku-plug-field-option-" + id)
              .removeClass("muhiku-plug-confirm-enabled")
              .addClass("muhiku-plug-confirm-disabled");
          }
        }
      );

      // Real-time updates for "Placeholder" field option.
      $builder.on(
        "input",
        ".muhiku-plug-field-option-row-placeholder input",
        function (e) {
          var $this = $(this),
            value = $this.val(),
            id = $this.parent().data("field-id"),
            $primary = $("#muhiku-plug-field-" + id).find(
              ".widefat:not(.secondary-input)"
            );

          if ($primary.is("select")) {
            if (!value.length) {
              $primary.find(".placeholder").remove();
            } else {
              if ($primary.find(".placeholder").length) {
                $primary.find(".placeholder").text(value);
              } else {
                $primary.prepend(
                  '<option class="placeholder" selected>' + value + "</option>"
                );
              }

              $primary.data("placeholder", value);

              if ($primary.hasClass("enhanced")) {
                $primary
                  .parent()
                  .find(".select2-search__field")
                  .prop("placeholder", value);
              }
            }
          } else {
            $primary.attr("placeholder", value);
          }
        }
      );

      // Real-time updates for "Address Placeholder" field options.
      $builder.on(
        "input",
        ".muhiku-plug-field-option-address input.placeholder",
        function (e) {
          var $this = $(this),
            value = $this.val(),
            id = $this.parent().parent().data("field-id"),
            subfield = $this.parent().parent().data("subfield");
          $("#muhiku-plug-field-" + id)
            .find(".muhiku-plug-" + subfield + " input")
            .attr("placeholder", value);
        }
      );

      // Real-time updates for "Confirmation Placeholder" field option.
      $builder.on(
        "input",
        ".muhiku-plug-field-option-row-confirmation_placeholder input",
        function () {
          var $this = $(this),
            value = $this.val(),
            id = $this.parent().data("field-id");
          $("#muhiku-plug-field-" + id)
            .find(".secondary-input")
            .attr("placeholder", value);
        }
      );

      // Real-time updates for "Hide Label" field option.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-label_hide input",
        function () {
          var id = $(this).parent().data("field-id");
          $("#muhiku-plug-field-" + id).toggleClass("label_hide");
        }
      );

      // Real-time updates for Sub Label visbility field option.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-sublabel_hide input",
        function () {
          var id = $(this).parent().data("field-id");
          $("#muhiku-plug-field-" + id).toggleClass("sublabel_hide");
        }
      );

      // Real-time updates for Date/Time and Name "Format" option.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-datetime_format select, .muhiku-plug-field-option-row-phone_format select, .muhiku-plug-field-option-row-item_price select, .muhiku-plug-field-option-row-format select",
        function (e) {
          var $this = $(this),
            value = $this.val(),
            id = $this.parent().data("field-id");
          $("#muhiku-plug-field-" + id)
            .find(".format-selected")
            .removeClass()
            .addClass("format-selected format-selected-" + value);
          $("#muhiku-plug-field-option-" + id)
            .find(".format-selected")
            .removeClass()
            .addClass("format-selected format-selected-" + value);
        }
      );

      // Setting options toggler.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-datetime_style select",
        function () {
          MHKPanelBuilder.dateSettingToggler(
            $(this).parent().attr("data-field-id"),
            $(this).val()
          );
        }
      );

      // Enable Min Max Toggler.
      $(
        ".muhiku-plug-field-option-row-time_interval_format [id*=enable_min_max_time]"
      ).each(function () {
        if ($(this).prop("checked")) {
          $(this)
            .parent()
            .parent()
            .find(".input-group-col-2")
            .has(" [id*=min_time_hour]")
            .show();
          $(this)
            .parent()
            .parent()
            .find(".input-group-col-2")
            .has(" [id*=max_time_hour]")
            .show();
          $(this)
            .parent()
            .parent()
            .find(".input-group-col-2")
            .has(" [for*=select_min_time]")
            .show();
          $(this)
            .parent()
            .parent()
            .find(".input-group-col-2")
            .has("[for*=select_max_time]")
            .show();
        } else {
          $(this)
            .parent()
            .parent()
            .find(".input-group-col-2")
            .has("[id*=min_time_hour]")
            .hide();
          $(this)
            .parent()
            .parent()
            .find(".input-group-col-2")
            .has("[id*=max_time_hour]")
            .hide();
          $(this).parent().parent().find("[for*=select_min_time]").hide();
          $(this).parent().parent().find("[for*=select_max_time]").hide();
        }
      });

      $builder.on(
        "click",
        ".muhiku-plug-field-option-row-time_interval_format [id*=enable_min_max_time]",
        function () {
          if ($(this).prop("checked")) {
            $(this)
              .parent()
              .parent()
              .find(".input-group-col-2")
              .has(" [id*=min_time_hour]")
              .show();
            $(this)
              .parent()
              .parent()
              .find(".input-group-col-2")
              .has(" [id*=max_time_hour]")
              .show();
            $(this).parent().parent().find("[for*=select_min_time]").show();
            $(this).parent().parent().find("[for*=select_max_time]").show();
          } else {
            $(this)
              .parent()
              .parent()
              .find(".input-group-col-2")
              .has("[id*=min_time_hour]")
              .hide();
            $(this)
              .parent()
              .parent()
              .find(".input-group-col-2")
              .has("[id*=max_time_hour]")
              .hide();
            $(this).parent().parent().find("[for*=select_min_time]").hide();
            $(this).parent().parent().find("[for*=select_max_time]").hide();
          }
        }
      );

      // Time interval changes.
      $builder.on(
        "change",
        ".muhiku-plug-field-option-row-time_interval_format select[id*=time_format]",
        function () {
          min_hour = $(this)
            .parent()
            .siblings(".input-group-col-2")
            .find("[id*=min_time_hour]");
          max_hour = $(this)
            .parent()
            .siblings(".input-group-col-2")
            .find("[id*=max_time_hour]");
          var selected_min = min_hour.find("option:selected").val();
          var selected_max = max_hour.find("option:selected").val();
          var options = "",
            a,
            h;
          for (i = 0; i <= 23; i++) {
            if ($(this).val() === "H:i") {
              options +=
                '<option value = "' +
                i +
                '">' +
                (i < 10 ? "0" + i : i) +
                "</option>";
            } else {
              a = " PM";
              if (i < 12) {
                a = " AM";
                h = i;
              } else {
                h = i - 12;
              }
              if (h == 0) {
                h = 12;
              }
              options += '<option value = "' + i + '">' + h + a + "</option>";
            }
          }
          min_hour.html(options);
          max_hour.html(options);
          min_hour
            .find("option[value=" + selected_min + "]")
            .prop("selected", true);
          max_hour
            .find("option[value=" + selected_max + "]")
            .prop("selected", true);
        }
      );
    },

    /**
     * Setting options for Date Picker and Dropdown Toggler.
     */
    dateSettingToggler: function (id, type) {
      if (type == "picker") {
        // Picker Date Setting Control
        $("#muhiku-plug-field-option-row-" + id + "-placeholder").show();
        $("#muhiku-plug-field-option-" + id + "-disable_dates").show();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-disable_dates]"
        ).show();
        $("#muhiku-plug-field-option-" + id + "-date_mode-range")
          .parents()
          .find("muhiku-plug-checklist")
          .show();
        $(".muhiku-plug-field-option-row-date_format .time_interval").show();
        $("#muhiku-plug-field-option-" + id + "-date_localization").show();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-date_localization]"
        ).show();
        $("#muhiku-plug-field-option-" + id + "-date_default")
          .parent()
          .show();
        $("#muhiku-plug-field-option-" + id + "-enable_min_max")
          .parent()
          .show();
        //Check if min max date enabled.
        if (
          $("#muhiku-plug-field-option-" + id + "-enable_min_max").prop(
            "checked"
          )
        ) {
          $(
            "#muhiku-plug-field-option-row-" +
              id +
              "-date_format .muhiku-plug-min-max-date-option"
          ).removeClass("muhiku-plug-hidden");
        }
        $("#muhiku-plug-field-option-" + id + "-time_interval").show();
        $("#muhiku-plug-field-option-" + id + "-enable_min_max_time").hide();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-enable_min_max_time]"
        ).hide();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-select_min_time]"
        ).hide();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-select_max_time]"
        ).hide();
        $("#muhiku-plug-field-option-" + id + "-min_time_hour")
          .parent()
          .hide();
        $("#muhiku-plug-field-option-" + id + "-max_time_hour")
          .parent()
          .hide();
      } else {
        // Dropdown Date Setting Control
        $("#muhiku-plug-field-option-" + id + "-date_mode-range")
          .parents()
          .find("muhiku-plug-checklist")
          .hide();
        $("#muhiku-plug-field-option-" + id + "-date_default")
          .parent()
          .hide();
        $("#muhiku-plug-field-option-row-" + id + "-placeholder").hide();
        $("#muhiku-plug-field-option-" + id + "-enable_min_max")
          .parent()
          .hide();
        $(
          "#muhiku-plug-field-option-row-" +
            id +
            "-date_format .muhiku-plug-min-max-date-option"
        ).addClass("muhiku-plug-hidden");
        $("#muhiku-plug-field-option-" + id + "-disable_dates").hide();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-disable_dates]"
        ).hide();
        $(
          ".muhiku-plug-field-option-row-date_format .muhiku-plug-checklist"
        ).hide();
        $(".muhiku-plug-field-option-row-date_format .time_interval").hide();
        $("#muhiku-plug-field-option-" + id + "-date_localization").hide();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-date_localization]"
        ).hide();
        $("#muhiku-plug-field-option-" + id + "-time_interval").hide();
        $("#muhiku-plug-field-option-" + id + "-enable_min_max_time").show();
        $(
          "label[for=muhiku-plug-field-option-" + id + "-enable_min_max_time]"
        ).show();
        //Check if min max time enabled.
        if (
          $("#muhiku-plug-field-option-" + id + "-enable_min_max_time").prop(
            "checked"
          )
        ) {
          $(
            "label[for=muhiku-plug-field-option-" + id + "-select_min_time]"
          ).show();
          $(
            "label[for=muhiku-plug-field-option-" + id + "-select_max_time]"
          ).show();
          $("#muhiku-plug-field-option-" + id + "-min_time_hour")
            .parent()
            .show();
          $("#muhiku-plug-field-option-" + id + "-max_time_hour")
            .parent()
            .show();
        }
      }
    },

    /**
     * Make field choices sortable.
     *
     * @since 1.0.0
     *
     * @param {string} selector Selector.
     */
    choicesInit: function (selector) {
      selector = selector || ".muhiku-plug-field-option-row-choices ul";

      $(selector).sortable({
        items: "li",
        axis: "y",
        handle: ".sort",
        scrollSensitivity: 40,
        stop: function (event) {
          var field_id = $(event.target).attr("data-field-id"),
            type = $("#muhiku-plug-field-option-" + field_id)
              .find(".muhiku-plug-field-option-hidden-type")
              .val();

          MHKPanelBuilder.choiceUpdate(type, field_id);
        },
      });
    },

    /**
     * Add new field choice.
     *
     * @since 1.6.0
     */
    choiceAdd: function (event, el, value) {
      if (event && event.preventDefault) {
        event.preventDefault();
      }

      var $this = $(el),
        $parent = $this.parent(),
        checked = $parent.find("input.default").is(":checked"),
        fieldID = $this
          .closest(".muhiku-plug-field-option-row-choices")
          .data("field-id"),
        nextID = $parent.parent().attr("data-next-id"),
        type = $parent.parent().data("field-type"),
        $choice = $parent.clone().insertAfter($parent);

      $choice.attr("data-key", nextID);
      $choice
        .find("input.label")
        .val(value)
        .attr(
          "name",
          "form_fields[" + fieldID + "][choices][" + nextID + "][label]"
        );
      $choice
        .find("input.value")
        .val(value)
        .attr(
          "name",
          "form_fields[" + fieldID + "][choices][" + nextID + "][value]"
        );
      $choice
        .find("input.source")
        .val("")
        .attr(
          "name",
          "form_fields[" + fieldID + "][choices][" + nextID + "][image]"
        );
      $choice
        .find("input.default")
        .attr(
          "name",
          "form_fields[" + fieldID + "][choices][" + nextID + "][default]"
        )
        .prop("checked", false);
      $choice.find(".attachment-thumb").remove();
      $choice.find(".button-add-media").show();

      if (checked === true) {
        $parent.find("input.default").prop("checked", true);
      }

      nextID++;
      $parent.parent().attr("data-next-id", nextID);
      $builder.trigger("everestFormsChoiceAdd");
      MHKPanelBuilder.choiceUpdate(type, fieldID);
    },

    /**
     * Delete field choice.
     *
     * @since 1.6.0
     */
    choiceDelete: function (event, el) {
      event.preventDefault();

      var $this = $(el),
        $list = $this.parent().parent(),
        total = $list.find("li").length;

      if (total < 2) {
        $.alert({
          title: false,
          content: mhk_data.i18n_field_error_choice,
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
      } else {
        $this.parent().remove();
        MHKPanelBuilder.choiceUpdate(
          $list.data("field-type"),
          $list.data("field-id")
        );
        $builder.trigger("everestFormsChoiceDelete");
      }
    },

    /**
     * Update field choices in preview area, for the Fields panel.
     *
     * @since 1.6.0
     */
    choiceUpdate: function (type, id) {
      var $fieldOptions = $("#muhiku-plug-field-option-" + id);
      $primary = $("#muhiku-plug-field-" + id + " .primary-input");

      // Radio and Checkbox use _ template.
      if (
        "radio" === type ||
        "checkbox" === type ||
        "payment-multiple" === type ||
        "payment-checkbox" === type
      ) {
        var choices = [],
          formData = MHKPanelBuilder.formObject($fieldOptions),
          settings = formData.form_fields[id];

        // Order of choices for a specific field.
        $("#muhiku-plug-field-option-" + id)
          .find(".mhk-choices-list li")
          .each(function () {
            choices.push($(this).data("key"));
          });

        var tmpl = wp.template("muhiku-plug-field-preview-choices"),
          type =
            "checkbox" === type || "payment-checkbox" === type
              ? "checkbox"
              : "radio";
        data = {
          type: type,
          order: choices,
          settings: settings,
          amountFilter: MHKPanelBuilder.amountFilter,
        };

        $("#muhiku-plug-field-" + id)
          .find("ul.primary-input")
          .replaceWith(tmpl(data));

        return;
      }

      var new_choice;

      if ("select" === type) {
        new_choice = "<option>{label}</option>";
        $primary.find("option").not(".placeholder").remove();
      }

      $(
        "#muhiku-plug-field-option-row-" + id + "-choices .mhk-choices-list li"
      ).each(function (index) {
        var $this = $(this),
          label = $this.find("input.label").val(),
          selected = $this.find("input.default").is(":checked"),
          choice = $(new_choice.replace("{label}", label));

        $("#muhiku-plug-field-" + id + " .primary-input").append(choice);

        if (!label) {
          return;
        }

        if (true === selected) {
          switch (type) {
            case "select":
              choice.prop("selected", true);
              break;
            case "radio":
            case "checkbox":
              choice.find("input").prop("checked", true);
              break;
          }
        }
      });
    },

    amountFilter: function (data, amount) {
      if ("right" === data.currency_symbol_pos) {
        return amount + " " + data.currency_symbol;
      } else {
        return data.currency_symbol + " " + amount;
      }
    },

    bindFormSettings: function () {
      $("body").on("click", ".mhk-setting-panel", function (e) {
        var data_setting_section = $(this).attr("data-section");
        $(".mhk-setting-panel").removeClass("active");
        $(".muhiku-plug-active-email").removeClass("active");
        $(".mhk-content-section").removeClass("active");
        $(this).addClass("active");
        $(".mhk-content-" + data_setting_section + "-settings").addClass(
          "active"
        );
        e.preventDefault();
      });

      $(".mhk-setting-panel").eq(0).trigger("click");
    },
    bindFormEmail: function () {
      $("body").on(
        "click",
        ".muhiku-plug-panel-sidebar-section-email",
        function (e) {
          $(this).siblings(".muhiku-plug-active-email").removeClass("active");
          $(this).next(".muhiku-plug-active-email").addClass("active");
          var container = $(this)
            .siblings(".muhiku-plug-active-email.active")
            .find(".muhiku-plug-active-email-connections-list li");

          if (container.length) {
            container.children(".user-nickname").first().trigger("click");
          }
          e.preventDefault();
        }
      );
    },
    bindFormIntegrations: function () {
      $("body").on("click", ".mhk-integrations-panel", function (e) {
        var data_setting_section = $(this).attr("data-section");
        $(".mhk-integrations-panel").removeClass("active");
        $("#muhiku-plug-panel-integrations")
          .find(".mhk-panel-content-section")
          .removeClass("active");
        $(this).addClass("active");
        $(this)
          .parent()
          .find(".muhiku-plug-active-connections")
          .removeClass("active");
        $(this).next(".muhiku-plug-active-connections").addClass("active");
        var container = $(this)
          .siblings(".muhiku-plug-active-connections.active")
          .find(".muhiku-plug-active-connections-list li");

        if (container.length) {
          container.children(".user-nickname").first().trigger("click");
        }
        $(".mhk-panel-content-section-" + data_setting_section).addClass(
          "active"
        );
        e.preventDefault();
      });

      $(".mhk-setting-panel").eq(0).trigger("click");
    },
    bindFormPayment: function () {
      $("body").on("click", ".mhk-payments-panel", function (e) {
        var data_setting_section = $(this).attr("data-section");
        $(".mhk-payments-panel").removeClass("active");
        $(this).siblings().removeClass("icon active");
        $(this).addClass("active");
        $(this)
          .parents("#muhiku-plug-panel-payments")
          .find(".mhk-payment-setting-content")
          .removeClass("active")
          .hide();
        $(".mhk-content-" + data_setting_section + "-settings")
          .addClass("active")
          .show();
        e.preventDefault();
      });

      $(".mhk-setting-panel").eq(0).trigger("click");
    },
    removeRow: function (row) {
      $.each(row.find(".muhiku-plug-field"), function () {
        var field_id = $(this).attr("data-field-id"),
          field_options = $("#muhiku-plug-field-option-" + field_id);

        // Remove form field.
        $(this).remove();

        // Remove field options.
        field_options.remove();
      });

      // Remove row.
      row.remove();
    },
    bindRemoveRow: function () {
      $("body").on("click", ".mhk-delete-row", function () {
        var $this = $(this),
          total_rows = $(".mhk-admin-row").length,
          current_row = $this.closest(".mhk-admin-row"),
          current_part = $this
            .parents(".mhk-admin-field-container")
            .attr("data-current-part"),
          multipart_active = $("#muhiku-plug-builder").hasClass(
            "multi-part-activated"
          );

        if (current_part && multipart_active) {
          total_rows = $("#part_" + current_part).find(".mhk-admin-row").length;
        }

        if (total_rows < 2) {
          $.alert({
            title: mhk_data.i18n_row_locked,
            content: mhk_data.i18n_row_locked_msg,
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
        } else {
          $.confirm({
            title: false,
            content: mhk_data.i18n_delete_row_confirm,
            type: "red",
            closeIcon: false,
            backgroundDismiss: false,
            icon: "dashicons dashicons-warning",
            buttons: {
              confirm: {
                text: mhk_data.i18n_ok,
                btnClass: "btn-confirm",
                keys: ["enter"],
                action: function () {
                  MHKPanelBuilder.removeRow(current_row);
                  $(".muhiku-plug-fields-tab").find("a").removeClass("active");
                  $(".muhiku-plug-fields-tab")
                    .find("a")
                    .first()
                    .addClass("active");
                  $(".muhiku-plug-add-fields").show();
                },
              },
              cancel: {
                text: mhk_data.i18n_cancel,
              },
            },
          });
        }
      });
    },
    bindAddNewRow: function () {
      $("body").on("click", ".mhk-add-row span", function () {
        var $this = $(this),
          wrapper = $(".mhk-admin-field-wrapper"),
          row_ids = $(".mhk-admin-row")
            .map(function () {
              return $(this).data("row-id");
            })
            .get(),
          max_row_id = Math.max.apply(Math, row_ids),
          row_clone = $(".mhk-admin-row").eq(0).clone(),
          total_rows = $this.parent().attr("data-total-rows"),
          current_part = $this
            .parents(".mhk-admin-field-container")
            .attr("data-current-part");

        max_row_id++;
        total_rows++;

        if (current_part) {
          wrapper = $(".mhk-admin-field-wrapper").find("#part_" + current_part);
        }

        // Row clone.
        row_clone.find(".mhk-admin-grid").html("");
        row_clone.attr("data-row-id", max_row_id);

        // Row infos.
        $this.parent().attr("data-total-rows", total_rows);
        $this.parent().attr("data-next-row-id", max_row_id);

        // Row append.
        wrapper.append(row_clone);

        // Initialize fields UI.
        MHKPanelBuilder.bindFields();
        MHKPanelBuilder.checkEmptyGrid();
        // Trigger event after row add.
        $this.trigger("muhiku-plug-after-add-row", row_clone);
      });
    },
    bindCloneField: function () {
      $("body").on(
        "click",
        ".muhiku-plug-preview .muhiku-plug-field .muhiku-plug-field-duplicate",
        function () {
          var $field = $(this).closest(".muhiku-plug-field");

          if ($field.hasClass("no-duplicate")) {
            $.alert({
              title: mhk_data.i18n_field_locked,
              content: mhk_data.i18n_field_locked_msg,
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
          } else {
            $.confirm({
              title: false,
              content: mhk_data.i18n_duplicate_field_confirm,
              type: "orange",
              closeIcon: false,
              backgroundDismiss: false,
              icon: "dashicons dashicons-warning",
              buttons: {
                confirm: {
                  text: mhk_data.i18n_ok,
                  btnClass: "btn-confirm",
                  keys: ["enter"],
                  action: function () {
                    MHKPanelBuilder.cloneFieldAction($field);
                  },
                },
                cancel: {
                  text: mhk_data.i18n_cancel,
                },
              },
            });
          }
        }
      );

      $("body").on("click", ".mhk-admin-row .mhk-duplicate-row", function () {
        var $row = $(this).closest(".mhk-admin-row");
        if ($row.find(".muhiku-plug-field").hasClass("no-duplicate")) {
          $.alert({
            title: mhk_data.i18n_field_locked,
            content: mhk_data.i18n_row_locked_msg,
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
        } else {
          $.confirm({
            title: false,
            content: mhk_data.i18n_duplicate_row_confirm,
            type: "orange",
            closeIcon: false,
            backgroundDismiss: false,
            icon: "dashicons dashicons-warning",
            buttons: {
              confirm: {
                text: mhk_data.i18n_ok,
                btnClass: "btn-confirm",
                keys: ["enter"],
                action: function () {
                  MHKPanelBuilder.cloneRowAction($row);
                },
              },
              cancel: {
                text: mhk_data.i18n_cancel,
              },
            },
          });
        }
      });
    },
    cloneRowAction: function (row) {
      (row_ids = $(".mhk-admin-row")
        .map(function () {
          return $(this).data("row-id");
        })
        .get()),
        (max_row_id = Math.max.apply(Math, row_ids)),
        (row_clone = row.clone()),
        (total_rows = $(".mhk-add-row").attr("data-total-rows"));
      max_row_id++;
      total_rows++;

      // New row ID.
      row_clone.attr("data-row-id", max_row_id);
      // Initialize fields UI.
      $(".mhk-add-row").attr("data-total-rows", total_rows);
      $(".mhk-add-row").attr("data-next-row-id", max_row_id);

      var data = {
        action: "everest_forms_get_next_id",
        security: mhk_data.mhk_get_next_id,
        form_id: mhk_data.form_id,
        fields: row_clone.find(".muhiku-plug-field").length,
      };

      $.ajax({
        url: mhk_data.ajax_url,
        data: data,
        type: "POST",
        beforeSend: function () {
          $(document.body).trigger("init_field_options_toggle");
        },
        success: function (response) {
          if (
            typeof response.success === "boolean" &&
            response.success === true
          ) {
            // Row append.
            row.after(row_clone);
            // Duplicating Fields
            $.each(response.data, function (index, data) {
              var field_id = data.field_id;
              var field_key = data.field_key;
              $("#muhiku-plug-field-id").val(field_id);
              field = row_clone.find(".muhiku-plug-field").eq(index);
              var element_field_id = field.attr("data-field-id");
              MHKPanelBuilder.render_node(field, element_field_id, field_key);
              field.remove();
              $(document.body).trigger("init_field_options_toggle");
            });
            // Binding fields.
            MHKPanelBuilder.bindFields();
          }
        },
      });
    },
    cloneFieldAction: function (field) {
      var element_field_id = field.attr("data-field-id");
      var form_id = mhk_data.form_id;
      var data = {
        action: "everest_forms_get_next_id",
        security: mhk_data.mhk_get_next_id,
        form_id: form_id,
      };
      $.ajax({
        url: mhk_data.ajax_url,
        data: data,
        type: "POST",
        beforeSend: function () {
          $(document.body).trigger("init_field_options_toggle");
        },
        success: function (response) {
          if (
            typeof response.success === "boolean" &&
            response.success === true
          ) {
            var field_id = response.data.field_id;
            var field_key = response.data.field_key;
            $("#muhiku-plug-field-id").val(field_id);
            MHKPanelBuilder.render_node(field, element_field_id, field_key);
            $(document.body).trigger("init_field_options_toggle");
          }
        },
      });
    },
    render_node: function (field, old_key, new_key) {
      var option = $(
        ".muhiku-plug-field-options #muhiku-plug-field-option-" + old_key
      );
      var old_field_label = $(
        "#muhiku-plug-field-option-" + old_key + "-label"
      ).val();
      var old_field_meta_key = $(
        "#muhiku-plug-field-option-" + old_key + "-meta-key"
      ).length
        ? $("#muhiku-plug-field-option-" + old_key + "-meta-key").val()
        : "";
      var field_type = field.attr("data-field-type"),
        newOptionHtml = option.html(),
        new_field_label = old_field_label + " " + mhk_data.i18n_copy,
        new_meta_key =
          "html" !== field_type
            ? old_field_meta_key
                .replace(/\(|\)/g, "")
                .toLowerCase()
                .substring(0, old_field_meta_key.lastIndexOf("_")) +
              "_" +
              Math.floor(1000 + Math.random() * 9000)
            : "",
        newFieldCloned = field.clone();
      var regex = new RegExp(old_key, "g");
      newOptionHtml = newOptionHtml.replace(regex, new_key);
      var newOption = $(
        '<div class="muhiku-plug-field-option muhiku-plug-field-option-' +
          field_type +
          '" id="muhiku-plug-field-option-' +
          new_key +
          '" data-field-id="' +
          new_key +
          '" />'
      );
      newOption.append(newOptionHtml);
      $.each(option.find(":input"), function () {
        var type = $(this).attr("type");
        var name = $(this).attr("name") ? $(this).attr("name") : "";
        var new_name = name.replace(regex, new_key);
        var value = "";
        if (type === "text" || type === "hidden") {
          value = $(this).val();
          newOption.find('input[name="' + new_name + '"]').val(value);
          newOption.find('input[value="' + old_key + '"]').val(new_key);
        } else if (type === "checkbox" || type === "radio") {
          if ($(this).is(":checked")) {
            newOption
              .find('input[name="' + new_name + '"]')
              .prop("checked", true)
              .attr("checked", "checked");
          } else {
            newOption
              .find('[name="' + new_name + '"]')
              .prop("checked", false)
              .attr("checked", false);
          }
        } else if ($(this).is("select")) {
          if ($(this).find("option:selected").length) {
            var option_value = $(this).find("option:selected").val();
            newOption
              .find('[name="' + new_name + '"]')
              .find('[value="' + option_value + '"]')
              .prop("selected", true);
          }
        } else {
          if ($(this).val() !== "") {
            newOption.find('[name="' + new_name + '"]').val($(this).val());
          }
        }
      });

      $(".muhiku-plug-field-options").append(newOption);
      $("#muhiku-plug-field-option-" + new_key + "-label").val(new_field_label);
      $("#muhiku-plug-field-option-" + new_key + "-meta-key").val(new_meta_key);

      // Field Clone
      newFieldCloned.attr("class", field.attr("class"));
      newFieldCloned.attr("id", "muhiku-plug-field-" + new_key);
      newFieldCloned.attr("data-field-id", new_key);
      newFieldCloned.attr("data-field-type", field_type);
      newFieldCloned.find(".label-title .text").text(new_field_label);
      field
        .closest(".mhk-admin-grid")
        .find('[data-field-id="' + old_key + '"]')
        .after(newFieldCloned);
      $(document).trigger("everest-form-cloned", [new_key, field_type]);
      MHKPanelBuilder.switchToFieldOptionPanel(new_key); //switch to cloned field options

      // Trigger an event indicating completion of render_node action for cloning.
      $(document.body).trigger("mhk_render_node_complete", [
        field_type,
        new_key,
        newFieldCloned,
        newOption,
      ]);
    },
    bindFieldDelete: function () {
      $("body").on(
        "click",
        ".muhiku-plug-preview .muhiku-plug-field .muhiku-plug-field-delete",
        function () {
          var $field = $(this).closest(".muhiku-plug-field");
          var field_id = $field.attr("data-field-id");
          var option_field = $("#muhiku-plug-field-option-" + field_id);
          var grid = $(this).closest(".mhk-admin-grid");

          if ($field.hasClass("no-delete")) {
            $.alert({
              title: mhk_data.i18n_field_locked,
              content: mhk_data.i18n_field_locked_msg,
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
          } else {
            $.confirm({
              title: false,
              content: mhk_data.i18n_delete_field_confirm,
              type: "red",
              closeIcon: false,
              backgroundDismiss: false,
              icon: "dashicons dashicons-warning",
              buttons: {
                confirm: {
                  text: mhk_data.i18n_ok,
                  btnClass: "btn-confirm",
                  keys: ["enter"],
                  action: function () {
                    $(".mhk-panel-fields-button").trigger("click");
                    $field.fadeOut("slow", function () {
                      var removed_el_id = $field.attr("data-field-id");
                      $(document.body).trigger("mhk_before_field_deleted", [
                        removed_el_id,
                      ]);
                      $field.remove();
                      option_field.remove();
                      MHKPanelBuilder.checkEmptyGrid();
                      $(".muhiku-plug-fields-tab")
                        .find("a")
                        .removeClass("active");
                      $(".muhiku-plug-fields-tab")
                        .find("a")
                        .first()
                        .addClass("active");
                      $(".muhiku-plug-add-fields").show();
                      MHKPanelBuilder.conditionalLogicRemoveField(
                        removed_el_id
                      );
                      MHKPanelBuilder.conditionalLogicRemoveFieldIntegration(
                        removed_el_id
                      );
                      MHKPanelBuilder.paymentFieldRemoveFromQuantity(
                        removed_el_id
                      );
                    });
                  },
                },
                cancel: {
                  text: mhk_data.i18n_cancel,
                },
              },
            });
          }
        }
      );
    },
    bindFieldDeleteWithKeyEvent: function () {
      $("body").on("keyup", function (e) {
        var $field = $(".muhiku-plug-preview .muhiku-plug-field.active");
        if (
          46 === e.which &&
          true === $field.hasClass("active") &&
          false === $field.hasClass("mhk-delete-event-active")
        ) {
          if (false == $(".mhk-admin-row").hasClass("mhk-hover")) {
            return;
          }
          $field.addClass("mhk-delete-event-active");
          var field_id = $field.attr("data-field-id");
          var option_field = $("#muhiku-plug-field-option-" + field_id);
          if ($field.hasClass("no-delete")) {
            $.alert({
              title: mhk_data.i18n_field_locked,
              content: mhk_data.i18n_field_locked_msg,
              icon: "dashicons dashicons-info",
              type: "blue",
              buttons: {
                confirm: {
                  text: mhk_data.i18n_close,
                  btnClass: "btn-confirm",
                  keys: ["enter"],
                  action: function () {
                    $field.removeClass("mhk-delete-event-active");
                  },
                },
              },
            });
          } else {
            $.confirm({
              title: false,
              content: mhk_data.i18n_delete_field_confirm,
              type: "red",
              closeIcon: false,
              backgroundDismiss: false,
              icon: "dashicons dashicons-warning",
              buttons: {
                confirm: {
                  text: mhk_data.i18n_ok,
                  btnClass: "btn-confirm",
                  keys: ["enter"],
                  action: function () {
                    $(".mhk-panel-fields-button").trigger("click");
                    $field.fadeOut("slow", function () {
                      var removed_el_id = $field.attr("data-field-id");
                      $(document.body).trigger("mhk_before_field_deleted", [
                        removed_el_id,
                      ]);
                      $field.remove();
                      option_field.remove();
                      MHKPanelBuilder.checkEmptyGrid();
                      $(".muhiku-plug-fields-tab")
                        .find("a")
                        .removeClass("active");
                      $(".muhiku-plug-fields-tab")
                        .find("a")
                        .first()
                        .addClass("active");
                      $(".muhiku-plug-add-fields").show();
                      MHKPanelBuilder.conditionalLogicRemoveField(
                        removed_el_id
                      );
                      MHKPanelBuilder.conditionalLogicRemoveFieldIntegration(
                        removed_el_id
                      );
                      MHKPanelBuilder.paymentFieldRemoveFromQuantity(
                        removed_el_id
                      );
                    });
                    $field.removeClass("mhk-delete-event-active");
                  },
                },
                cancel: {
                  text: mhk_data.i18n_cancel,
                  action: function () {
                    $field.removeClass("mhk-delete-event-active");
                  },
                },
              },
            });
          }
        }
      });
    },
    bindSaveOption: function () {
      $("body").on("click", ".muhiku-plug-save-button", function () {
        var $this = $(this);
        var $form = $("form#muhiku-plug-builder-form");
        var structure = MHKPanelBuilder.getStructure();
        var form_data = $form.serializeArray();
        var form_title = $("#mhk-edit-form-name").val().trim();

        var select_id_name = {};

        $(".muhiku-plug-field-option-row")
          .find(".mhk-select2-multiple")
          .filter(function () {
            var this_id = $(this).attr("id");
            var this_name = $(this).attr("name");
            var this_parent_id = $(this).parent().attr("id");
            if (
              this_id.split("-option-")[1] ===
              this_parent_id.split("-option-row-")[1]
            ) {
              select_id_name[this_id] = this_name;
            }
            return select_id_name;
          });

        if (Object.keys(select_id_name).length > 0) {
          $.each(select_id_name, function (id, name) {
            var countries = [];
            $.each($("#" + id + " option:selected"), function () {
              countries.push($(this).val());
            });
            form_data.push({ name: name, value: countries.toString() });
          });
        }

        if ("" === form_title) {
          $.alert({
            title: mhk_data.i18n_field_title_empty,
            content: mhk_data.i18n_field_title_payload,
            icon: "dashicons dashicons-warning",
            type: "red",
            buttons: {
              ok: {
                text: mhk_data.i18n_ok,
                btnClass: "btn-confirm",
                keys: ["enter"],
              },
            },
          });
          return;
        }

        // Trigger a handler to let addon manipulate the form data if needed.
        if (
          $form.triggerHandler("everest_forms_process_ajax_data", [
            $this,
            form_data,
          ])
        ) {
          form_data = $form.triggerHandler("everest_forms_process_ajax_data", [
            $this,
            form_data,
          ]);
        }

        $(".muhiku-plug-panel-content-wrap").block({
          message: null,
          overlayCSS: {
            background: "#fff",
            opacity: 0.6,
          },
        });

        /* DB unwanted data erase start */
        var rfields_ids = [];
        $(".muhiku-plug-field[data-field-id]").each(function () {
          rfields_ids.push($(this).attr("data-field-id"));
        });

        var form_data_length = form_data.length;
        while (form_data_length--) {
          if (form_data[form_data_length].name.startsWith("form_fields")) {
            var idflag = false;
            rfields_ids.forEach(function (element) {
              if (
                form_data[form_data_length].name.startsWith(
                  "form_fields[" + element + "]"
                )
              ) {
                idflag = true;
              }
            });
            if (form_data_length > -1 && idflag === false) {
              form_data.splice(form_data_length, 1);
            }
          }
        }
        /* DB fix end */

        var new_form_data = form_data.concat(structure);
        var data = {
          action: "everest_forms_save_form",
          security: mhk_data.mhk_save_form,
          form_data: JSON.stringify(new_form_data),
        };
        $.ajax({
          url: mhk_data.ajax_url,
          data: data,
          type: "POST",
          beforeSend: function () {
            $this.addClass("processing");
            $this.find(".loading-dot").remove();
            $this.append('<span class="loading-dot"></span>');
          },
          success: function (response) {
            $this.removeClass("processing");
            $this.find(".loading-dot").remove();

            if (!response.success) {
              $.alert({
                title: response.data.errorTitle,
                content: response.data.errorMessage,
                icon: "dashicons dashicons-warning",
                type: "red",
                buttons: {
                  ok: {
                    text: mhk_data.i18n_ok,
                    btnClass: "btn-confirm",
                    keys: ["enter"],
                  },
                },
              });
            }

            $(".muhiku-plug-panel-content-wrap").unblock();
          },
        });
      });
    },
    bindSaveOptionWithKeyEvent: function () {
      $("body").on("keydown", function (e) {
        if (e.ctrlKey || e.metaKey) {
          if (
            "s" === String.fromCharCode(e.which).toLowerCase() ||
            83 === e.which
          ) {
            e.preventDefault();
            $(".muhiku-plug-save-button").trigger("click");
          }
        }
      });
    },
    getStructure: function () {
      var wrapper = $(".mhk-admin-field-wrapper");
      var structure = [];

      $.each(wrapper.find(".mhk-admin-row"), function () {
        var $row = $(this),
          row_id = $row.attr("data-row-id");

        $.each($row.find(".mhk-admin-grid"), function () {
          var $grid = $(this),
            grid_id = $grid.attr("data-grid-id");

          var array_index = 0;
          $.each($grid.find(".muhiku-plug-field"), function () {
            var structure_object = { name: "", value: "" };
            var field_id = $(this).attr("data-field-id");
            structure_object.name =
              "structure[row_" +
              row_id +
              "][grid_" +
              grid_id +
              "][" +
              array_index +
              "]";
            array_index++;
            structure_object.value = field_id;
            structure.push(structure_object);
          });
          if ($grid.find(".muhiku-plug-field").length < 1) {
            structure.push({
              name: "structure[row_" + row_id + "][grid_" + grid_id + "]",
              value: "",
            });
          }
        });
      });

      return structure;
    },
    getFieldArray: function (grid) {
      var fields = [];
      $.each(grid.find(".muhiku-plug-field"), function () {
        var field_id = $(this).attr("data-field-id");
        fields.push(field_id);
      });
      return fields;
    },
    checkEmptyGrid: function ($force) {
      $.each($(".mhk-admin-grid"), function () {
        var $fields = $(this).find(
          ".muhiku-plug-field, .mhk-registered-item:not(.ui-draggable-dragging)"
        );
        if ($fields.not(".ui-sortable-helper").length < 1) {
          $(this).addClass("mhk-empty-grid");
        } else {
          $(this).removeClass("mhk-empty-grid");
        }
      });
      MHKPanelBuilder.choicesInit();
    },
    bindDefaultTabs: function () {
      $(document).on("click", ".mhk-nav-tab-wrapper a", function (e) {
        e.preventDefault();
        MHKPanelBuilder.switchTab($(this).data("panel"));
      });
    },
    switchTab: function (panel) {
      var $panel = $("#muhiku-plug-panel-" + panel),
        $panelBtn = $(".mhk-panel-" + panel + "-button");

      $(".mhk-nav-tab-wrapper").find("a").removeClass("nav-tab-active");
      $panelBtn.addClass("nav-tab-active");
      $panel
        .closest(".mhk-tab-content")
        .find(".muhiku-plug-panel")
        .removeClass("active");
      $panel.addClass("active");

      if ("integrations" === panel || "payments" === panel) {
        if (!$panel.find(".muhiku-plug-panel-sidebar a").hasClass("active")) {
          $panel
            .find(".muhiku-plug-panel-sidebar a")
            .first()
            .addClass("active");
        }

        if (
          !$(".muhiku-plug-panel-content")
            .find(".mhk-panel-content-section")
            .hasClass("active")
        ) {
          $(".muhiku-plug-panel-content")
            .find(".mhk-panel-content-section")
            .first()
            .addClass("active");
        }
      }

      history.replaceState(
        {},
        null,
        MHKPanelBuilder.updateQueryString("tab", panel)
      );
      MHKPanelBuilder.switchPanel(panel);
    },
    updateQueryString: function (key, value, url) {
      if (!url) url = window.location.href;
      var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
        hash;

      if (re.test(url)) {
        if (typeof value !== "undefined" && value !== null)
          return url.replace(re, "$1" + key + "=" + value + "$2$3");
        else {
          hash = url.split("#");
          url = hash[0].replace(re, "$1$3").replace(/(&|\?)$/, "");
          if (typeof hash[1] !== "undefined" && hash[1] !== null)
            url += "#" + hash[1];
          return url;
        }
      } else {
        if (typeof value !== "undefined" && value !== null) {
          var separator = url.indexOf("?") !== -1 ? "&" : "?";
          hash = url.split("#");
          url = hash[0] + separator + key + "=" + value;
          if (typeof hash[1] !== "undefined" && hash[1] !== null)
            url += "#" + hash[1];
          return url;
        } else {
          return url;
        }
      }
    },
    switchPanel: function (panel) {
      if (panel === "field-options") {
        MHKPanelBuilder.switchToFieldOptionPanel();
      }
    },
    switchToFieldOptionPanel: function (field_id) {
      $(".muhiku-plug-field-options").find(".no-fields").hide();
      $(".mhk-admin-field-wrapper .muhiku-plug-field").removeClass("active");
      $("#muhiku-plug-panel-fields").addClass("active");
      $(".muhiku-plug-fields-tab").find("a").removeClass("active");
      $(".muhiku-plug-fields-tab").find("a").last().addClass("active");
      $(".muhiku-plug-add-fields").hide();
      $(".muhiku-plug-field-options").show();
      $(".muhiku-plug-field-options").find(".muhiku-plug-field-option").hide();
      $(".mhk-tab-lists").find("li a").removeClass("active");
      $(".mhk-tab-lists")
        .find("li.mhk-panel-field-options-button a")
        .addClass("active");

      $(document.body).trigger("mhk-init-switch-field-options");

      if (typeof field_id !== "undefined") {
        $("#muhiku-plug-field-option-" + field_id).show();
        $("#muhiku-plug-field-" + field_id).addClass("active");
      } else {
        if ($(".mhk-admin-field-wrapper .muhiku-plug-field").length > 0) {
          $(".mhk-admin-field-wrapper .muhiku-plug-field")
            .eq(0)
            .addClass("active");
          $(
            "#muhiku-plug-field-option-" +
              $(".mhk-admin-field-wrapper .muhiku-plug-field")
                .eq(0)
                .attr("data-field-id")
          ).show();
        } else {
          $(".muhiku-plug-field-options").find(".no-fields").show();
        }
      }
    },
    bindFields: function () {
      $(".mhk-admin-field-wrapper")
        .sortable({
          items: ".mhk-admin-row",
          axis: "y",
          cursor: "move",
          opacity: 0.65,
          scrollSensitivity: 40,
          forcePlaceholderSize: true,
          placeholder: "mhk-sortable-placeholder",
          containment: ".muhiku-plug-panel-content",
          start: function (event, ui) {
            ui.item.css({
              backgroundColor: "#f7fafc",
              border: "1px dashed #5d96ee",
            });
          },
          stop: function (event, ui) {
            ui.item.removeAttr("style");
          },
        })
        .disableSelection();

      $(".mhk-admin-grid")
        .sortable({
          items: '> .muhiku-plug-field[data-field-type!="repeater-fields"]',
          delay: 100,
          opacity: 0.65,
          cursor: "move",
          scrollSensitivity: 40,
          forcePlaceholderSize: true,
          connectWith: ".mhk-admin-grid",
          appendTo: document.body,
          containment: ".muhiku-plug-field-wrap",

          out: function (event) {
            $(".mhk-admin-grid").removeClass("mhk-hover");
            $(event.target).removeClass("mhk-item-hover");
            $(event.target).closest(".mhk-admin-row").removeClass("mhk-hover");
            MHKPanelBuilder.checkEmptyGrid();
          },
          over: function (event, ui) {
            $(".mhk-admin-grid").addClass("mhk-hover");
            $(event.target).addClass("mhk-item-hover");
            $(event.target).closest(".mhk-admin-row").addClass("mhk-hover");
            MHKPanelBuilder.checkEmptyGrid();
          },
          receive: function (event, ui) {
            if (ui.sender.is("button")) {
              MHKPanelBuilder.fieldDrop(ui.helper);
            }
          },
          update: function (event, ui) {
            $(document).trigger("mhk_sort_update_complete", {
              event: event,
              ui: ui,
            });
          },
          stop: function (event, ui) {
            ui.item.removeAttr("style");
            MHKPanelBuilder.checkEmptyGrid();
          },
        })
        .disableSelection();

      $(".mhk-registered-buttons button.mhk-registered-item")
        .draggable({
          delay: 200,
          cancel: false,
          scroll: false,
          revert: "invalid",
          scrollSensitivity: 40,
          forcePlaceholderSize: true,
          start: function () {
            $(this).addClass("field-dragged");
          },
          helper: function () {
            return $(this)
              .clone()
              .insertAfter(
                $(this)
                  .closest(".muhiku-plug-tab-content")
                  .siblings(".muhiku-plug-fields-tab")
              );
          },
          stop: function () {
            $(this).removeClass("field-dragged");
          },
          opacity: 0.75,
          containment: "#muhiku-plug-builder",
          connectToSortable: ".mhk-admin-grid",
        })
        .disableSelection();

      // Repeatable grid connect to sortable setter.
      $(".mhk-registered-item.mhk-repeater-field").draggable(
        "option",
        "connectToSortable",
        ".mhk-repeatable-grid"
      );

      // Adapt hover behaviour on mouse event.
      $(".mhk-admin-row").on("mouseenter mouseleave", function (event) {
        if (1 > event.buttons) {
          if ("mouseenter" === event.type) {
            $(this).addClass("mhk-hover");
          } else {
            $(".mhk-admin-row").removeClass("mhk-hover");
          }
        }
      });

      // Refresh the position of placeholders on drag scroll.
      $(".muhiku-plug-panel-content").on("scroll", function () {
        $(".mhk-admin-grid").sortable("refreshPositions");
        $(".mhk-admin-field-wrapper").sortable("refreshPositions");
      });
    },

    /**
     * Toggle fields tabs (Add Fields, Field Options).
     */
    fieldTabChoice: function (id) {
      $(".muhiku-plug-tab-content").scrollTop(0);
      $(".muhiku-plug-fields-tab a").removeClass("active");
      $(".muhiku-plug-field, .muhiku-plug-title-desc").removeClass("active");

      $("#" + id).addClass("active");

      if ("add-fields" === id) {
        $(".muhiku-plug-add-fields").show();
        $(".muhiku-plug-field-options").hide();
      } else {
        if ("field-options" === id) {
          id = $(".muhiku-plug-field").first().data("field-id");
          $(".muhiku-plug-field-options").show();
          $(".muhiku-plug-field").first().addClass("active");
        } else {
          $("#muhiku-plug-field-" + id).addClass("active");
        }
        $(".muhiku-plug-field-option").hide();
        $("#muhiku-plug-field-option-" + id).show();
        $(".muhiku-plug-add-fields").hide();
      }
    },
    bindFormPreview: function () {},
    bindFormPreviewWithKeyEvent: function () {
      $("body").on("keydown", function (e) {
        if (e.ctrlKey || e.metaKey) {
          if (
            "p" === String.fromCharCode(e.which).toLowerCase() ||
            80 === e.which
          ) {
            e.preventDefault();
            window.open(mhk_data.preview_url);
          }
        }
      });
    },
    bindFormEntriesWithKeyEvent: function () {
      $("body").on("keydown", function (e) {
        if (e.ctrlKey || e.metaKey) {
          if (
            "e" === String.fromCharCode(e.which).toLowerCase() ||
            69 === e.which
          ) {
            e.preventDefault();
            window.open(mhk_data.entries_url);
          }
        }
      });
    },
    bindGridSwitcher: function () {
      $("body").on("click", ".mhk-show-grid", function (e) {
        e.stopPropagation();
        MHKPanelBuilder.checkEmptyGrid();
        $(this)
          .closest(".mhk-toggle-row")
          .find(".mhk-toggle-row-content")
          .stop(true)
          .slideToggle(200);
      });
      $(document).on("click", function () {
        MHKPanelBuilder.checkEmptyGrid();
        $(".mhk-show-grid")
          .closest(".mhk-toggle-row")
          .find(".mhk-toggle-row-content")
          .stop(true)
          .slideUp(200);
      });
      var max_number_of_grid = 4;
      $("body").on("click", ".mhk-grid-selector", function () {
        var $this_single_row = $(this).closest(".mhk-admin-row");
        if ($(this).hasClass("active")) {
          return;
        }
        var grid_id = parseInt($(this).attr("data-mhk-grid"), 10);
        if (grid_id > max_number_of_grid) {
          return;
        }

        var grid_node = $(
          '<div class="mhk-admin-grid mhk-grid-' +
            grid_id +
            ' ui-sortable mhk-empty-grid" />'
        );
        var grids = $("<div/>");

        $.each($this_single_row.find(".mhk-admin-grid"), function () {
          $(this)
            .children("*")
            .each(function () {
              grids.append($(this).clone()); // "this" is the current element in the loop
            });
        });
        $this_single_row.find(".mhk-admin-grid").remove();
        $this_single_row.find(".mhk-clear ").remove();
        $this_single_row.append('<div class="clear mhk-clear"></div>');

        for (var $grid_number = 1; $grid_number <= grid_id; $grid_number++) {
          grid_node.attr("data-grid-id", $grid_number);
          $this_single_row.append(grid_node.clone());
        }
        $this_single_row.append('<div class="clear mhk-clear"></div>');
        $this_single_row.find(".mhk-admin-grid").eq(0).append(grids.html());
        $this_single_row.find(".mhk-grid-selector").removeClass("active");
        $(this).addClass("active");
        MHKPanelBuilder.bindFields();
      });
    },
    fieldDrop: function (field) {
      var field_type = field.attr("data-field-type");
      var invalid_fields = [
        "file-upload",
        "payment-total",
        "image-upload",
        "signature",
      ];
      if (
        invalid_fields.includes(field_type) &&
        field.closest(".mhk-admin-row").hasClass("mhk-repeater-fields")
      ) {
        $.confirm({
          title: false,
          content: "This field cannot be added to Repeater Fields",
          type: "red",
          closeIcon: false,
          backgroundDismiss: false,
          icon: "dashicons dashicons-warning",
          buttons: {
            cancel: {
              text: mhk_data.i18n_close,
              btnClass: "btn-default",
            },
          },
        });

        field.remove();
        return false;
      }

      field
        .css({
          left: "0",
          width: "100%",
        })
        .append('<i class="spinner is-active"></i>');

      $.ajax({
        url: mhk_data.ajax_url,
        type: "POST",
        data: {
          action: "everest_forms_new_field_" + field_type,
          security: mhk_data.mhk_field_drop_nonce,
          field_type: field_type,
          form_id: mhk_data.form_id,
        },
        beforeSend: function () {
          $(document.body).trigger("init_field_options_toggle");
        },
        success: function (response) {
          var field_preview = response.data.preview,
            field_options = response.data.options,
            form_field_id = response.data.form_field_id,
            field_type = response.data.field.type,
            dragged_el_id = $(field_preview).attr("id"),
            dragged_field_id = $(field_preview).attr("data-field-id");

          $("#muhiku-plug-field-id").val(form_field_id);
          $(".muhiku-plug-field-options").find(".no-fields").hide();
          $(".muhiku-plug-field-options").append(field_options);
          $(
            ".muhiku-plug-field-option-row-icon_color input.colorpicker"
          ).wpColorPicker({
            change: function (event) {
              var $this = $(this),
                value = $this.val(),
                field_id = $this
                  .closest(".muhiku-plug-field-option-row")
                  .data("field-id");

              $("#muhiku-plug-field-" + field_id + " .rating-icon svg").css(
                "fill",
                value
              );
            },
          });

          field.after(field_preview);

          if (
            null !== $("#muhiku-plug-panel-field-settings-enable_survey") &&
            $("#muhiku-plug-panel-field-settings-enable_survey").prop("checked")
          ) {
            $(
              "#muhiku-plug-field-option-" + dragged_field_id + "-survey_status"
            ).prop("checked", true);
          }

          if (
            null !== $("#muhiku-plug-panel-field-settings-enable_quiz") &&
            $("#muhiku-plug-panel-field-settings-enable_quiz").prop("checked")
          ) {
            $(
              "#muhiku-plug-field-option-" + dragged_field_id + "-quiz_status"
            ).prop("checked", true);
            $("#muhiku-plug-field-option-" + dragged_field_id + "-quiz_status")
              .closest(".muhiku-plug-field-option-row-quiz_status")
              .siblings(".everst-forms-field-quiz-settings")
              .removeClass("muhiku-plug-hidden")
              .addClass("muhiku-plug-show");
          }

          field.remove();

          // Triggers.
          $(document.body).trigger("init_tooltips");
          $(document.body).trigger("init_field_options_toggle");
          $(document.body).trigger("mhk_after_field_append", [dragged_el_id]);

          // Conditional logic append rules.
          MHKPanelBuilder.conditionalLogicAppendField(dragged_el_id);
          MHKPanelBuilder.conditionalLogicAppendFieldIntegration(dragged_el_id);
          MHKPanelBuilder.paymentFieldAppendToQuantity(dragged_el_id);
          MHKPanelBuilder.paymentFieldAppendToDropdown(
            dragged_field_id,
            field_type
          );

          // Initialization Datepickers.
          MHKPanelBuilder.init_datepickers();

          // Hiding time min max options in setting for Datepickers.
          $(
            "#muhiku-plug-field-option-" +
              dragged_field_id +
              "-enable_min_max_time"
          ).hide();
          $(
            "label[for=muhiku-plug-field-option-" +
              dragged_field_id +
              "-enable_min_max_time]"
          ).hide();
          $(
            "label[for=muhiku-plug-field-option-" +
              dragged_field_id +
              "-select_min_time]"
          ).hide();
          $(
            "label[for=muhiku-plug-field-option-" +
              dragged_field_id +
              "-select_max_time]"
          ).hide();
          $("#muhiku-plug-field-option-" + dragged_field_id + "-min_time_hour")
            .parent()
            .hide();
          $("#muhiku-plug-field-option-" + dragged_field_id + "-max_time_hour")
            .parent()
            .hide();

          // Trigger an event indicating completion of field_drop action.
          $(document.body).trigger("mhk_field_drop_complete", [
            field_type,
            dragged_field_id,
            field_preview,
            field_options,
          ]);
          MHKPanelBuilder.checkEmptyGrid();
        },
      });
    },

    conditionalLogicAppendField: function (id) {
      var dragged_el = $("#" + id);
      var dragged_index = dragged_el.index();

      var fields = $(".mhk-field-conditional-field-select");

      var field_type = dragged_el.attr("data-field-type");
      var field_id = dragged_el.attr("data-field-id");
      var field_label = dragged_el.find(".label-title .text ").text();

      $.fn.insertAt = function (elements, index, selected_id) {
        var array = $.makeArray(this.children().clone(true));
        array.splice(index, 0, elements);
        $.each(array, function (index, el) {
          if (selected_id === $(el)[0]["value"]) {
            $(el)[0]["selected"] = true;
            array[index] = el;
          }
        });
        this.empty().append(array);
      };
      var dragged_field_id = field_id;
      fields.each(function (index, el) {
        var selected_id = $(el).val();
        var id_key = id.replace("muhiku-plug-field-", "");
        var name = $(el).attr("name");
        var name_key = name.substring(name.indexOf("[") + 1, name.indexOf("]"));

        if (id_key === name_key) {
          $(".mhk-admin-row .mhk-admin-grid .muhiku-plug-field").each(
            function () {
              var form_field_type = $(this).data("field-type"),
                form_field_id = $(this).data("field-id"),
                form_field_label = $(this)
                  .find(".label-title span")
                  .first()
                  .text();
              field_to_be_restricted = [];
              field_to_be_restricted = [
                "html",
                "title",
                "address",
                "image-upload",
                "file-upload",
                "date-time",
                "hidden",
                "scale-rating",
                "likert",
              ];
              if (
                $.inArray(form_field_type, field_to_be_restricted) === -1 &&
                dragged_field_id !== form_field_id
              ) {
                fields
                  .eq(index)
                  .append(
                    '<option class="mhk-conditional-fields" data-field_type="' +
                      form_field_type +
                      '" data-field_id="' +
                      form_field_id +
                      '" value="' +
                      form_field_id +
                      '">' +
                      form_field_label +
                      "</option>"
                  );
              }
            }
          );
        } else {
          var el_to_append =
            '<option class="mhk-conditional-fields" data-field_type="' +
            field_type +
            '" data-field_id="' +
            field_id +
            '" value="' +
            field_id +
            '">' +
            field_label +
            "</option>";
          if (
            "html" !== field_type &&
            "title" !== field_type &&
            "address" !== field_type &&
            "image-upload" !== field_type &&
            "file-upload" !== field_type &&
            "date-time" !== field_type &&
            "hidden" !== field_type &&
            "likert" !== field_type &&
            "scale-rating" !== field_type
          ) {
            fields.eq(index).insertAt(el_to_append, dragged_index, selected_id);
          }
        }
      });
    },

    paymentFieldAppendToQuantity: function (id) {
      var dragged_el = $("#" + id);

      var fields = $(".muhiku-plug-field-option-row-map_field select");
      var field_type = dragged_el.attr("data-field-type");
      var field_id = dragged_el.attr("data-field-id");
      var field_label = dragged_el.find(".label-title .text ").text();

      var el_to_append =
        '<option value="' + field_id + '">' + field_label + "</option>";
      if (
        "payment-single" === field_type ||
        "payment-multiple" === field_type ||
        "payment-checkbox" === field_type
      ) {
        fields.append(el_to_append);
      }
    },

    paymentFieldAppendToDropdown: function (dragged_field_id, field_type) {
      if ("payment-quantity" === field_type) {
        var match_fields = [
            "payment-checkbox",
            "payment-multiple",
            "payment-single",
            "range-slider",
          ],
          qty_dropdown = $(
            "#muhiku-plug-field-option-" + dragged_field_id + "-map_field"
          );
        match_fields.forEach(function (single_field) {
          $(".muhiku-plug-field-" + single_field).each(function () {
            if ("range-slider" === $(this).attr("data-field-type")) {
              if (
                "true" ===
                $(this)
                  .find(".mhk-range-slider-preview")
                  .attr("data-enable-payment-slider")
              ) {
                var id = $(this).attr("data-field-id"),
                  label = $(this).find(".label-title .text").text();
                var el_to_append =
                  '<option value="' + id + '">' + label + "</option>";
              } else {
                return;
              }
            }
            var id = $(this).attr("data-field-id"),
              label = $(this).find(".label-title .text").text();
            var el_to_append =
              '<option value="' + id + '">' + label + "</option>";
            qty_dropdown.append(el_to_append);
          });
        });
      }
    },

    conditionalLogicAppendFieldIntegration: function (id) {
      var dragged_el = $("#" + id);
      var dragged_index = dragged_el.index();

      var fields = $(".mhk-provider-conditional").find(
        ".mhk-conditional-field-select"
      );

      var field_type = dragged_el.attr("data-field-type");
      var field_id = dragged_el.attr("data-field-id");
      var field_label = dragged_el.find(".label-title .text ").text();

      $.fn.insertAt = function (elements, index) {
        var array = $.makeArray(this.children().clone(true));
        array.splice(index, 0, elements);
        this.empty().append(array);
      };

      fields.each(function (index, el) {
        var id_key = id.replace("muhiku-plug-field-", "");
        var name = $(el).attr("name");
        var name_key = name.substring(name.indexOf("[") + 1, name.indexOf("]"));

        if (id_key === name_key) {
          $(".mhk-admin-row .mhk-admin-grid .muhiku-plug-field").each(
            function () {
              var field_type = $(this).data("field-type"),
                field_id = $(this).data("field-id"),
                field_label = $(this).find(".label-title span").first().text();
              field_to_be_restricted = [];
              field_to_be_restricted = [
                "html",
                "title",
                "address",
                "image-upload",
                "file-upload",
                "date-time",
                "hidden",
                "scale-rating",
                "likert",
                dragged_el.attr("data-field-type"),
              ];

              if ($.inArray(field_type, field_to_be_restricted) === -1) {
                fields
                  .eq(index)
                  .append(
                    '<option class="mhk-conditional-fields" data-field_type="' +
                      field_type +
                      '" data-field_id="' +
                      field_id +
                      '" value="' +
                      field_id +
                      '">' +
                      field_label +
                      "</option>"
                  );
              }
            }
          );
        } else {
          var el_to_append =
            '<option class="mhk-conditional-fields" data-field_type="' +
            field_type +
            '" data-field_id="' +
            field_id +
            '" value="' +
            field_id +
            '">' +
            field_label +
            "</option>";
          if (
            "html" !== field_type &&
            "title" !== field_type &&
            "address" !== field_type &&
            "image-upload" !== field_type &&
            "file-upload" !== field_type &&
            "date-time" !== field_type &&
            "hidden" !== field_type &&
            "likert" !== field_type &&
            "scale-rating" !== field_type
          ) {
            fields.eq(index).insertAt(el_to_append, dragged_index);
          }
        }
      });
    },

    conditionalLogicRemoveField: function (id) {
      $(
        ".mhk-field-conditional-field-select option[value = " + id + " ]"
      ).remove();
    },

    conditionalLogicRemoveFieldIntegration: function (id) {
      $(
        ".mhk-provider-conditional .mhk-conditional-field-select option[value = " +
          id +
          " ]"
      ).remove();
    },

    paymentFieldRemoveFromQuantity: function (id) {
      $(
        ".muhiku-plug-field-option-row-map_field select option[value = " +
          id +
          " ]"
      ).remove();
    },

    bindFieldSettings: function () {
      $("body").on(
        "click",
        ".muhiku-plug-preview .muhiku-plug-field, .muhiku-plug-preview .muhiku-plug-field .muhiku-plug-field-setting",
        function (e) {
          e.preventDefault();
          var field_id = $(this)
            .closest(".muhiku-plug-field")
            .attr("data-field-id");
          $(".muhiku-plug-tab-content").scrollTop(0);
          MHKPanelBuilder.switchToFieldOptionPanel(field_id);
        }
      );
    },

    toggleLabelEdit: function (label, input) {
      $(label).toggleClass("muhiku-plug-hidden");
      $(input).toggleClass("muhiku-plug-hidden");

      if ($(input).is(":visible")) {
        $(input).focus();
      }
    },

    bindToggleHandleActions: function () {
      $("body").on("click", ".toggle-handle", function (e) {
        var label = $(this).data("label"),
          input = $(this).data("input");

        if (!$(input).is(":visible")) {
          MHKPanelBuilder.toggleLabelEdit(label, input);
        }
      });
    },

    bindLabelEditInputActions: function () {
      $("body").on("focusout", ".label-edit-input", function (e) {
        var label = $(this).data("label"),
          input = this;

        MHKPanelBuilder.toggleLabelEdit(label, input);
      });
    },

    /**
     * Sync an input element with other elements like labels. An element with `sync-input` class will be synced to the elements
     * specified in `sync-targets` data.
     *
     * `Warning:` This is an one way sync, meaning only the text `sync-targets` will be updated when the source element's value changes
     * and the source element's value will not be updated if the value of `sync-targets` changes.
     */
    bindSyncedInputActions: function () {
      $("body").on("input", ".sync-input", function (e) {
        var changed_value = $(this).val(),
          sync_targets = $(this).data("sync-targets");

        if (changed_value && sync_targets) {
          $(sync_targets).text(changed_value);
        }
      });
    },
  };

  MHKPanelBuilder.init();
})(jQuery, window.mhk_data);

jQuery(function () {
  if (
    jQuery(
      "#muhiku-plug-panel-field-settingsemail-mhk_send_confirmation_email"
    ).attr("checked") != "checked"
  ) {
    jQuery(
      "#muhiku-plug-panel-field-settingsemail-mhk_send_confirmation_email-wrap"
    )
      .nextAll()
      .hide();
  }

  jQuery(
    "#muhiku-plug-panel-field-settingsemail-mhk_send_confirmation_email"
  ).on("change", function () {
    if (jQuery(this).attr("checked") != "checked") {
      jQuery(
        "#muhiku-plug-panel-field-settingsemail-mhk_send_confirmation_email-wrap"
      )
        .nextAll()
        .hide();
    } else {
      jQuery(
        "#muhiku-plug-panel-field-settingsemail-mhk_send_confirmation_email-wrap"
      )
        .nextAll()
        .show();
    }
  });

  var mySelect = jQuery(
    "#muhiku-plug-panel-field-settings-redirect_to option:selected"
  ).val();

  if (mySelect == "same") {
    jQuery("#muhiku-plug-panel-field-settings-custom_page-wrap").hide();
    jQuery("#muhiku-plug-panel-field-settings-external_url-wrap").hide();
  } else if (mySelect == "custom_page") {
    jQuery("#muhiku-plug-panel-field-settings-custom_page-wrap").show();
    jQuery("#muhiku-plug-panel-field-settings-external_url-wrap").hide();
  } else if (mySelect == "external_url") {
    jQuery("#muhiku-plug-panel-field-settings-external_url-wrap").show();
    jQuery("#muhiku-plug-panel-field-settings-custom_page-wrap").hide();
  }

  jQuery("#muhiku-plug-panel-field-settings-redirect_to").on(
    "change",
    function () {
      if (this.value == "same") {
        jQuery("#muhiku-plug-panel-field-settings-custom_page-wrap").hide();
        jQuery("#muhiku-plug-panel-field-settings-external_url-wrap").hide();
      } else if (this.value == "custom_page") {
        jQuery("#muhiku-plug-panel-field-settings-custom_page-wrap").show();
        jQuery("#muhiku-plug-panel-field-settings-external_url-wrap").hide();
      } else if (this.value == "external_url") {
        jQuery("#muhiku-plug-panel-field-settings-custom_page-wrap").hide();
        jQuery("#muhiku-plug-panel-field-settings-external_url-wrap").show();
      }
    }
  );
  jQuery(".mhk-panel-field-options-button.mhk-disabled-tab").hide();

  // Conditional Logic fields for General Settings in Form for Submission Redirection.

  jQuery(".muhiku-plug-conditional-field-settings-redirect_to").each(
    function () {
      var conditional_rule_selection = this.value;
      if ("custom_page" == conditional_rule_selection) {
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-custom_page")
          .show();
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-external_url")
          .hide();
      } else if ("external_url" == conditional_rule_selection) {
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-custom_page")
          .hide();
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-external_url")
          .show();
      } else {
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-custom_page")
          .hide();
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-external_url")
          .hide();
      }
    }
  );

  jQuery(document).on(
    "change",
    ".muhiku-plug-conditional-field-settings-redirect_to",
    function () {
      if ("custom_page" == this.value) {
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-custom_page")
          .show();
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-external_url")
          .hide();
      } else if ("external_url" == this.value) {
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-custom_page")
          .hide();
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-external_url")
          .show();
      } else {
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-custom_page")
          .hide();
        jQuery(this)
          .parents(".mhk-field-conditional-container")
          .find(".muhiku-plug-conditional-field-settings-external_url")
          .hide();
      }
    }
  );
});

jQuery(function ($) {
  // Add Fields - Open/close.
  $(document.body)
    .on("init_add_fields_toogle", function () {
      $(".muhiku-plug-add-fields").on(
        "click",
        ".muhiku-plug-add-fields-group > a",
        function (event) {
          event.preventDefault();
          $(this)
            .parent(".muhiku-plug-add-fields-group")
            .toggleClass("closed")
            .toggleClass("open");
        }
      );
      $(".muhiku-plug-add-fields").on(
        "click",
        ".muhiku-plug-add-fields-group a",
        function () {
          $(this).next(".mhk-registered-buttons").stop().slideToggle();
        }
      );
      $(".muhiku-plug-add-fields-group.closed").each(function () {
        $(this).find(".mhk-registered-buttons").hide();
      });
    })
    .trigger("init_add_fields_toogle");

  // Fields Options - Open/close.
  $(document.body).on(
    "click",
    ".muhiku-plug-field-option .muhiku-plug-field-option-group > a",
    function (event) {
      event.preventDefault();
      $(this)
        .parent(".muhiku-plug-field-option-group")
        .toggleClass("closed")
        .toggleClass("open");
    }
  );
  $(document.body).on(
    "click",
    ".muhiku-plug-field-option .muhiku-plug-field-option-group a",
    function (event) {
      // If the user clicks on some form input inside, the box should not be toggled.
      if ($(event.target).filter(":input, option, .sort").length) {
        return;
      }

      $(this)
        .next(".muhiku-plug-field-option-group-inner")
        .stop()
        .slideToggle();
    }
  );
  $(document.body)
    .on("init_field_options_toggle", function () {
      $(".muhiku-plug-field-option-group.closed").each(function () {
        $(this).find(".muhiku-plug-field-option-group-inner").hide();
      });
    })
    .trigger("init_field_options_toggle");

  $(document).on("click", function () {
    $(".mhk-smart-tag-lists").hide();
  });

  // Toggle Smart Tags.
  $(document.body).on("click", ".mhk-toggle-smart-tag-display", function (e) {
    e.stopPropagation();
    $(".mhk-smart-tag-lists").hide();
    $(".mhk-smart-tag-lists ul").empty();
    $(this).parent().find(".mhk-smart-tag-lists").toggle("show");

    var type = $(this).data("type");

    var allowed_field = $(this).data("fields");
    get_all_available_field(allowed_field, type, $(this));
  });

  $(document.body).on("click", ".smart-tag-field", function (e) {
    var field_id = $(this).data("field_id"),
      field_label = $(this).text(),
      type = $(this).data("type"),
      $parent = $(this).parent().parent().parent(),
      $input = $parent.find("input[type=text]"),
      $textarea = $parent.find("textarea");
    if (
      field_id !== "fullname" &&
      field_id !== "email" &&
      field_id !== "subject" &&
      field_id !== "message" &&
      "other" !== type
    ) {
      field_label = field_label.split(/[\s-_]/);
      for (var i = 0; i < field_label.length; i++) {
        if (i === 0) {
          field_label[i] =
            field_label[i].charAt(0).toLowerCase() + field_label[i].substr(1);
        } else {
          field_label[i] =
            field_label[i].charAt(0).toUpperCase() + field_label[i].substr(1);
        }
      }
      field_label = field_label.join("");
      field_id = field_label + "_" + field_id;
    } else {
      field_id = field_id;
    }
    if ("field" === type) {
      $input.val($input.val() + '{field_id="' + field_id + '"}');
      $textarea.val($textarea.val() + '{field_id="' + field_id + '"}');
      $textarea.trigger("change");
    } else if ("other" === type) {
      $input.val($input.val() + "{" + field_id + "}");
      $textarea.val($textarea.val() + "{" + field_id + "}");
    }
  });

  // Toggle form status.
  $(document).on(
    "change",
    ".wp-list-table .muhiku-plug-toggle-form input",
    function (e) {
      e.stopPropagation();
      $.post(mhk_data.ajax_url, {
        action: "everest_forms_enabled_form",
        security: mhk_data.mhk_enabled_form,
        form_id: $(this).data("form_id"),
        enabled: $(this).prop("checked") ? 1 : 0,
      });
    }
  );

  // Toggle email notification.
  $(document).on(
    "change",
    ".mhk-content-email-settings .mhk-toggle-switch input",
    function (e) {
      var $this = $(this),
        value = $this.prop("checked");

      if (false === value) {
        $this.val("");
        $this
          .closest(".mhk-content-email-settings")
          .find(".email-disable-message")
          .remove();
        $this
          .closest(".mhk-content-section-title")
          .siblings(".mhk-content-email-settings-inner")
          .addClass("muhiku-plug-hidden");
        $(
          '<p class="email-disable-message muhiku-plug-notice muhiku-plug-notice-info">' +
            mhk_data.i18n_email_disable_message +
            "</p>"
        ).insertAfter($this.closest(".mhk-content-section-title"));
      } else if (true === value) {
        $this.val("1");
        $this
          .closest(".mhk-content-section-title")
          .siblings(".mhk-content-email-settings-inner")
          .removeClass("muhiku-plug-hidden");
        $this
          .closest(".mhk-content-email-settings")
          .find(".email-disable-message")
          .remove();
      }
    }
  );

  $(document).on(
    "click",
    ".muhiku-plug-min-max-date-format input",
    function () {
      var minDate = $(this)
        .closest(".muhiku-plug-date")
        .find(".muhiku-plug-min-date")
        .val();
      var maxDate = $(this)
        .closest(".muhiku-plug-date")
        .find(".muhiku-plug-min-date")
        .val();
      if ($(this).is(":checked")) {
        $(".muhiku-plug-min-max-date-option").removeClass("muhiku-plug-hidden");
        if ("" === minDate) {
          $(".muhiku-plug-min-date")
            .addClass("flatpickr-field")
            .flatpickr({
              disableMobile: true,
              onChange: function (selectedDates, dateStr, instance) {
                $(".muhiku-plug-min-date").val(dateStr);
              },
              onOpen: function (selectedDates, dateStr, instance) {
                instance.set("maxDate", $(".muhiku-plug-max-date").val());
              },
            });
        }
        if ("" === maxDate) {
          $(".muhiku-plug-max-date")
            .addClass("flatpickr-field")
            .flatpickr({
              disableMobile: true,
              onChange: function (selectedDates, dateStr, instance) {
                $(".muhiku-plug-max-date").val(dateStr);
              },
              onOpen: function (selectedDates, dateStr, instance) {
                instance.set("minDate", $(".muhiku-plug-min-date").val());
              },
            });
        }
      } else {
        $(".muhiku-plug-min-max-date-option").addClass("muhiku-plug-hidden");
      }
    }
  );

  function get_all_available_field(allowed_field, type, el) {
    var all_fields_without_email = [];
    var all_fields = [];
    var email_field = [];
    $(".mhk-admin-row .mhk-admin-grid .muhiku-plug-field").each(function () {
      var field_type = $(this).data("field-type");
      var field_id = $(this).data("field-id");
      if (allowed_field === field_type) {
        var e_field_label = $(this).find(".label-title span").first().text();
        var e_field_id = field_id;
        email_field[e_field_id] = e_field_label;
      } else {
        var field_label = $(this).find(".label-title span").first().text();
        all_fields_without_email[field_id] = field_label;
      }
      all_fields[field_id] = $(this).find(".label-title span").first().text();
    });

    if ("other" === type || "all" === type) {
      var other_smart_tags = mhk_data.smart_tags_other;
      for (var key in other_smart_tags) {
        $(el)
          .parent()
          .find(".mhk-smart-tag-lists .mhk-others")
          .append(
            '<li class = "smart-tag-field" data-type="other" data-field_id="' +
              key +
              '">' +
              other_smart_tags[key] +
              "</li>"
          );
      }
    }

    if ("fields" === type || "all" === type) {
      if (allowed_field === "email") {
        if (Object.keys(email_field).length < 1) {
          $(el)
            .parent()
            .find(
              '.mhk-smart-tag-lists .smart-tag-title:not(".other-tag-title")'
            )
            .addClass("muhiku-plug-hidden");
        } else {
          $(el)
            .parent()
            .find(
              '.mhk-smart-tag-lists .smart-tag-title:not(".other-tag-title")'
            )
            .removeClass("muhiku-plug-hidden");
        }
        $(el).parent().find(".mhk-smart-tag-lists .other-tag-title").remove();
        $(el).parent().find(".mhk-smart-tag-lists .mhk-others").remove();
        $(el)
          .parent()
          .find(".mhk-smart-tag-lists")
          .append(
            '<div class="smart-tag-title other-tag-title">Others</div><ul class="mhk-others"></ul>'
          );
        $(el)
          .parent()
          .find(".mhk-smart-tag-lists .mhk-others")
          .append(
            '<li class="smart-tag-field" data-type="other" data-field_id="admin_email">Site Admin Email</li><li class="smart-tag-field" data-type="other" data-field_id="user_email">User Email</li>'
          );
        for (var key in email_field) {
          $(el)
            .parent()
            .find(".mhk-smart-tag-lists .mhk-fields")
            .append(
              '<li class = "smart-tag-field" data-type="field" data-field_id="' +
                key +
                '">' +
                email_field[key] +
                "</li>"
            );
        }
      } else {
        if (Object.keys(all_fields).length < 1) {
          $(el)
            .parent()
            .find(
              '.mhk-smart-tag-lists .smart-tag-title:not(".other-tag-title")'
            )
            .addClass("muhiku-plug-hidden");
        } else {
          $(el)
            .parent()
            .find(
              '.mhk-smart-tag-lists .smart-tag-title:not(".other-tag-title")'
            )
            .removeClass("muhiku-plug-hidden");
        }
        for (var meta in all_fields) {
          $(el)
            .parent()
            .find(".mhk-smart-tag-lists .mhk-fields")
            .append(
              '<li class = "smart-tag-field" data-type="field" data-field_id="' +
                meta +
                '">' +
                all_fields[meta] +
                "</li>"
            );
        }
      }
    }

    if ("calculations" === type) {
      var calculations = ["number", "payment-single", "range-slider"];
      $(document)
        .find(".muhiku-plug-field")
        .each(function () {
          if (
            calculations.includes($(this).attr("data-field-type")) &&
            $(el)
              .parents(".muhiku-plug-field-option-row-calculation_field")
              .attr("data-field-id") !== $(this).attr("data-field-id")
          ) {
            $(el)
              .parent()
              .find(".mhk-smart-tag-lists .calculations")
              .append(
                '<li class = "smart-tag-field" data-type="field" data-field_id="' +
                  $(this).attr("data-field-id") +
                  '">' +
                  $(this).find(".label-title .text").text() +
                  "</li>"
              );
          }
        });
    }
  }
});
