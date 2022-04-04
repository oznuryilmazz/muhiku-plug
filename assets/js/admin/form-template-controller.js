/* global mhk_template_controller */
jQuery(function ($) {
  /**
   * Template actions.
   */
  var mhk_template_controller = {
    all: "#mhk-form-all",
    basic: "#mhk-form-basic",
    pro: "#mhk-form-pro",
    results: mhk_templates.mhk_template_all,
    init: function () {
      mhk_template_controller.latch_hooks();
    },
    latch_hooks: function () {
      $(document.body).ready(function () {
        $(mhk_template_controller.all).click(function (e) {
          e.preventDefault();
          mhk_template_controller.sort_all(this);
        });
        $(mhk_template_controller.basic).click(function (e) {
          e.preventDefault();
          mhk_template_controller.sort_basic(this);
        });
        $(mhk_template_controller.pro).click(function (e) {
          e.preventDefault();
          mhk_template_controller.sort_pro(this);
        });
        $(".page-title-action").click(function (e) {
          e.stopImmediatePropagation();

          $(this).html(
            mhk_templates.template_refresh +
              ' <div  class="mhk-loading mhk-loading-active"></div>'
          );
        });
      });
    },
    sort_all: function (el) {
      mhk_template_controller.class_update($(el));
      mhk_template_controller.render_results(
        mhk_template_controller.results,
        "all"
      );
    },
    sort_basic: function (el) {
      mhk_template_controller.class_update($(el));
      mhk_template_controller.render_results(
        mhk_template_controller.results,
        "free"
      );
    },
    sort_pro: function (el) {
      mhk_template_controller.class_update($(el));
      mhk_template_controller.render_results(
        mhk_template_controller.results,
        "pro"
      );
    },
    class_update: function ($el) {
      $(".muhiku-plug-tab-nav").removeClass("active");
      $el.parent().addClass("active");
    },
    render_results: function (template, allow) {
      var el_to_append = $(".mhk-setup-templates"),
        error = '<div  class="mhk-loading mhk-loading-active"></div>';

      if (!template) {
        $("#message").remove();
        el_to_append.html(error);

        // Adds a loading screen so the async results is populated.
        window.setTimeout(function () {
          mhk_template_controller.render_results(
            mhk_template_controller.results,
            allow
          );
        }, 1000);

        return;
      }

      $(".muhiku-plug-form-template").html("");

      template.forEach(function (tuple) {
        var toAppend = "",
          plan = tuple.plan.includes("free") ? "free" : "pro",
          data_plan = $(".muhiku-plug-form-template").data("license-type");

        if ("all" === allow || "blank" === tuple.slug) {
          toAppend = mhk_template_controller.template_snippet(
            tuple,
            plan,
            data_plan
          );
        } else if (plan === allow) {
          toAppend = mhk_template_controller.template_snippet(
            tuple,
            plan,
            data_plan
          );
        }

        el_to_append.append(toAppend);
      });
    },
    template_snippet: function (template, plan, data_plan) {
      var html = "",
        modal = "mhk-template-select";
      data_plan =
        "" === data_plan ? "free" : data_plan.replace("-lifetime", "");
      if (
        !template.plan.includes("free") &&
        !template.plan.includes(data_plan)
      ) {
        modal = "upgrade-modal";
      }

      html +=
        '<div class="muhiku-plug-template-wrap mhk-template" id="muhiku-plug-template-' +
        template.slug +
        '" data-plan="' +
        plan +
        '">';

      if ("blank" !== template.slug) {
        html += '<figure class="muhiku-plug-screenshot" ';
      } else {
        html += '<figure class="muhiku-plug-screenshot mhk-template-select" ';
      }

      html +=
        'data-template-name-raw="' +
        template.title +
        '" data-template="' +
        template.slug +
        '" data-template-name="' +
        template.title +
        ' template">';
      html +=
        '<img src=" ' +
        mhk_templates.mhk_plugin_url +
        "/assets/" +
        template.image +
        ' ">';

      if ("blank" !== template.slug) {
        html +=
          '<div class="form-action"><a href="#" class="muhiku-plug-btn muhiku-plug-btn-primary ' +
          modal +
          '" data-licence-plan="' +
          data_plan +
          '" data-template-name-raw="' +
          template.title +
          '" data-template-name="' +
          template.title +
          ' template" data-template="' +
          template.slug +
          '">' +
          mhk_templates.i18n_get_started +
          "</a>";
        html +=
          '<a href="' +
          template.preview_link +
          '" target="_blank" class="muhiku-plug-btn muhiku-plug-btn-secondary">' +
          mhk_templates.i18n_get_preview +
          "</a></div>";
      }

      if (!template.plan.includes("free")) {
        var $badge_text = "";

        html +=
          '<span class="muhiku-plug-badge muhiku-plug-badge--success">' +
          $badge_text +
          "</span>";
      }

      html += '</figure><div class="muhiku-plug-form-id-container">';
      html +=
        '<a class="muhiku-plug-template-name ' +
        modal +
        '" href="#" data-template-name-raw="' +
        template.title +
        '" data-licence-plan="' +
        data_plan +
        '" data-template="' +
        template.slug +
        '" data-template-name="' +
        template.title +
        ' template">' +
        template.title +
        "</a></div>";

      return html;
    },
  };

  mhk_template_controller.init();
});
