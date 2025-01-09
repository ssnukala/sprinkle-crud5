/**
 * crud5 widget.  Sets up dropdowns, modals, etc for a table of crud5.
 */

/**
 * Set up the form in a modal after being successfully attached to the body.
 */
function attachCrud5Form() {
  $("body").on("renderSuccess.ufModal", function (data) {
    var modal = $(this).ufModal("getModal");
    var form = modal.find(".js-form");

    /**
     * Set up modal widgets
     */
    // Set up any widgets inside the modal
    form.find(".js-select2").select2({
      width: "100%",
    });

    // Auto-generate slug
    form.find("input[name=name]").on("input change", function () {
      var manualSlug = form.find("#form-crud5-slug-override").prop("checked");
      if (!manualSlug) {
        var slug = getSlug($(this).val());
        form.find("input[name=slug]").val(slug);
      }
    });

    form.find("#form-crud5-slug-override").on("change", function () {
      if ($(this).prop("checked")) {
        form.find("input[name=slug]").prop("readonly", false);
      } else {
        form.find("input[name=slug]").prop("readonly", true);
        form.find("input[name=name]").trigger("change");
      }
    });

    // Fontawesome-iconpicker
    // Starcraft icons
    var sc_icons = [
      {
        title: "sc sc-terran",
        searchTerms: ["starcraft", "terran"],
      },
      {
        title: "sc sc-zerg",
        searchTerms: ["starcraft", "zerg"],
      },
      {
        title: "sc sc-protoss",
        searchTerms: ["starcraft", "protoss"],
      },
    ];

    $(".icp-auto").iconpicker({
      // this is a hacky way to add in our custom icons to the default FA5 icons.
      // See https://github.com/farbelous/fontawesome-iconpicker/issues/77
      icons: typeof sc_icons != "undefined" ? $.merge(sc_icons, $.iconpicker.defaultOptions.icons) : null,
    });

    // Set icon when changed
    form.find("input[name=icon]").on("input change", function () {
      $(this).prev(".icon-preview").find("i").removeClass().addClass($(this).val());
    });

    $(".icp-auto").iconpicker();

    // Set up the form for submission
    form
      .ufForm({
        validator: page.validators,
      })
      .on("submitSuccess.ufForm", function () {
        // Reload page on success
        window.location.reload();
      });
  });
}

/**
 * Link Crud5 action buttons, for example in a table or on a specific Crud5's page.
 * @param {module:jQuery} el jQuery wrapped element to target.
 * @param {{delete_redirect: string}} options Options used to modify behaviour of button actions.
 */
function bindCrud5Buttons(el, options) {
  if (!options) options = {};
  /**
   * Link row buttons after table is loaded.
   */

  /**
   * Buttons that launch a modal dialog
   */
  // Edit Crud5 details button
  el.find(".js-crud5-edit").click(function (e) {
    e.preventDefault();

    $("body").ufModal({
      sourceUrl: site.uri.public + "/modals/crud5/" + options.crud_slug + "/edit",
      ajaxParams: {
        id: $(this).data("id"),
      },
      msgTarget: $("#alerts-page"),
    });

    attachCrud5Form();
  });

  // Delete Crud5 button
  el.find(".js-crud5-delete").click(function (e) {
    e.preventDefault();

    $("body").ufModal({
      sourceUrl: site.uri.public + "/modals/crud5/" + options.crud_slug + "/confirm-delete",
      ajaxParams: {
        id: $(this).data("id"),
      },
      msgTarget: $("#alerts-page"),
    });

    $("body").on("renderSuccess.ufModal", function () {
      var modal = $(this).ufModal("getModal");
      var form = modal.find(".js-form");

      form.ufForm().on("submitSuccess.ufForm", function () {
        // Navigate or reload page on success
        if (options.delete_redirect) window.location.href = options.delete_redirect;
        else window.location.reload();
      });
    });
  });
}

function bindCrud5CreationButton(el, options) {
  if (!options) options = {};
  // Link create button
  el.find(".js-crud5-create").click(function (e) {
    e.preventDefault();

    $("body").ufModal({
      sourceUrl: site.uri.public + "/modals/crud5/" + options.crud_slug + "/create",
      msgTarget: $("#alerts-page"),
    });

    attachCrud5Form();
  });
}

export { attachCrud5Form, bindCrud5Buttons, bindCrud5CreationButton };
