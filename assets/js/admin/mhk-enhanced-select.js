/*global mhk_enhanced_select_params */
jQuery(function ($) {
  function getEnhancedSelectFormatString() {
    return {
      language: {
        errorLoading: function () {
          // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
          return mhk_enhanced_select_params.i18n_searching;
        },
        inputTooLong: function (args) {
          var overChars = args.input.length - args.maximum;

          if (1 === overChars) {
            return mhk_enhanced_select_params.i18n_input_too_long_1;
          }

          return mhk_enhanced_select_params.i18n_input_too_long_n.replace(
            "%qty%",
            overChars
          );
        },
        inputTooShort: function (args) {
          var remainingChars = args.minimum - args.input.length;

          if (1 === remainingChars) {
            return mhk_enhanced_select_params.i18n_input_too_short_1;
          }

          return mhk_enhanced_select_params.i18n_input_too_short_n.replace(
            "%qty%",
            remainingChars
          );
        },
        loadingMore: function () {
          return mhk_enhanced_select_params.i18n_load_more;
        },
        maximumSelected: function (args) {
          if (args.maximum === 1) {
            return mhk_enhanced_select_params.i18n_selection_too_long_1;
          }

          return mhk_enhanced_select_params.i18n_selection_too_long_n.replace(
            "%qty%",
            args.maximum
          );
        },
        noResults: function () {
          return mhk_enhanced_select_params.i18n_no_matches;
        },
        searching: function () {
          return mhk_enhanced_select_params.i18n_searching;
        },
      },
    };
  }

  try {
    $(document.body)
      .on("mhk-enhanced-select-init", function () {
        // Regular select boxes
        $(":input.mhk-enhanced-select")
          .filter(":not(.enhanced)")
          .each(function () {
            var select2_args = $.extend(
              {
                minimumResultsForSearch: 10,
                allowClear: $(this).data("allow_clear") ? true : false,
                placeholder: $(this).data("placeholder"),
              },
              getEnhancedSelectFormatString()
            );

            $(this).selectWoo(select2_args).addClass("enhanced");
          });

        $(":input.mhk-enhanced-select-nostd")
          .filter(":not(.enhanced)")
          .each(function () {
            var select2_args = $.extend(
              {
                minimumResultsForSearch: 10,
                allowClear: true,
                placeholder: $(this).data("placeholder"),
              },
              getEnhancedSelectFormatString()
            );

            $(this).selectWoo(select2_args).addClass("enhanced");
          });
      })
      .trigger("mhk-enhanced-select-init");

    $("html").on("click", function (event) {
      if (this === event.target) {
        $(".mhk-enhanced-select")
          .filter(".select2-hidden-accessible")
          .selectWoo("close");
      }
    });
  } catch (err) {
    // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
    window.console.log(err);
  }
});
