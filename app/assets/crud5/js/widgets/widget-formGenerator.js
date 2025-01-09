/*!
 * FormGenerator Plugin
 *
 * JQuery plugin for the UserFrosting FormGenerator Sprinkle
 * Based on UserFrosting v3
 *
 * @package UF_FormGenerator
 * @author Louis Charette
 * @link https://github.com/lcharette/UF_FormGenerator
 * @license MIT
 */

(function ($, window, document, undefined) {
  "use strict";

  // Define plugin name and defaults.
  var pluginName = "formGenerator",
    defaults = {
      DEBUG: false,
      mainAlertElement: $("#alerts-page"),
      redirectAfterSuccess: true,
      autofocusModalElement: true,
      successCallback: function (data) {},
    };

  // Utility function to clean up stale modals and backdrops
  function cleanupModal(box_id) {
    $("#" + box_id).remove();
    $(".modal-backdrop").remove();
  }

  // Constructor
  function Plugin(element, options) {
    this.elements = element;
    this.$elements = $(this.elements);
    this.settings = $.extend(true, {}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;

    // Detect changes to element attributes
    this.$elements.attrchange({
      callback: function (event) {
        this.elements = event.target;
      }.bind(this),
    });

    // Initialize ufAlerts
    if (!this.settings.mainAlertElement.data("ufAlerts")) {
      this.settings.mainAlertElement.ufAlerts();
    }

    return this;
  }

  // Functions
  $.extend(Plugin.prototype, {
    display: function () {
      this.$elements.on("click", $.proxy(this._fetchForm, this));
      return this.$elements;
    },
    confirm: function () {
      this.$elements.on("click", $.proxy(this._fetchConfirmModal, this));
      return this.$elements;
    },
    _fetchForm: function (event) {
      var button = event.currentTarget;
      var box_id = $(button).data("target") || "formGeneratorModal";

      cleanupModal(box_id); // Clean up existing modals

      var payload = $.extend({ box_id: box_id }, button.dataset);

      $.ajax({
        type: "GET",
        url: $(button).data("formurl"),
        data: payload,
        cache: false,
      })
        .done($.proxy(this._displayForm, this, box_id, button))
        .fail($.proxy(this._displayFailure, this, button));
    },
    _displayForm: function (box_id, button, data) {
      $(button).trigger("displayForm." + this._name);

      $("body").append(data);
      $("#" + box_id).modal("show");

      if (this.settings.autofocusModalElement) {
        $("#" + box_id).on("shown.bs.modal", function () {
          $(this).find(".modal-body").find(":input:enabled:visible:first").focus();
        });
      }

      var boxMsgTarget = $("#" + box_id + " #form-alerts");
      if (!boxMsgTarget.data("ufAlerts")) {
        boxMsgTarget.ufAlerts();
      }
      boxMsgTarget.ufAlerts("clear").ufAlerts("fetch").ufAlerts("render");

      $("#" + box_id)
        .find("form")
        .ufForm({
          validators: validators,
          msgTarget: boxMsgTarget,
        })
        .on("submitSuccess.ufForm", $.proxy(this._formPostSuccess, this, box_id, button))
        .on("submitError.ufForm", $.proxy(this._displayFormFailure, this, box_id, button));
    },
    _formPostSuccess: function (box_id, button, event, data) {
      $(button).trigger("formSuccess." + this._name, data);
      this.settings.successCallback(data);

      if (this.settings.redirectAfterSuccess) {
        if (data.redirect) {
          window.location.replace(data.redirect);
        } else {
          window.location.reload(true);
        }
      } else {
        $("#" + box_id).modal("hide");
        cleanupModal(box_id);
        this.settings.mainAlertElement.ufAlerts("clear").ufAlerts("fetch").ufAlerts("render");
      }
    },
    _fetchConfirmModal: function (event) {
      var button = event.currentTarget;
      var box_id = $(button).data("target") || "formGeneratorModal";

      cleanupModal(box_id);

      var payload = $.extend(
        {
          box_id: box_id,
          box_title: $(button).data("confirmTitle"),
          confirm_message: $(button).data("confirmMessage"),
          confirm_warning: $(button).data("confirmWarning"),
          confirm_button: $(button).data("confirmButton"),
          cancel_button: $(button).data("cancelButton"),
        },
        button.dataset
      );

      $.ajax({
        type: "GET",
        url: $(button).data("formurl") || site["uri"]["public"] + "/forms/confirm",
        data: payload,
        cache: false,
      })
        .done($.proxy(this._displayConfirmation, this, box_id, button))
        .fail($.proxy(this._displayFailure, this, button));
    },
    _displayConfirmation: function (box_id, button, data) {
      $(button).trigger("displayConfirmation." + this._name);

      $("body").append(data);
      $("#" + box_id).modal("show");

      $("#" + box_id + " .js-confirm").on("click", $.proxy(this._sendConfirmation, this, box_id, button));
    },
    _sendConfirmation: function (box_id, button) {
      var url = $(button).data("postUrl");
      var method = $(button).data("postMethod") || "POST";
      var data = {
        bData: button.dataset,
        csrf_name: $("#" + box_id).find("input[name='csrf_name']").val(),
        csrf_value: $("#" + box_id).find("input[name='csrf_value']").val(),
      };

      $.ajax({
        type: method,
        url: url,
        data: data,
      })
        .done($.proxy(this._confirmationSuccess, this, box_id, button))
        .fail($.proxy(this._displayFailure, this, button));
    },
    _confirmationSuccess: function (box_id, button, data) {
      $(button).trigger("confirmSuccess." + this._name, data);
      this.settings.successCallback(data);

      if (this.settings.redirectAfterSuccess) {
        if (data.redirect) {
          window.location.replace(data.redirect);
        } else {
          window.location.reload(true);
        }
      } else {
        $("#" + box_id).modal("hide");
        cleanupModal(box_id);
      }
    },
    _displayFailure: function (button, response) {
      $(button).trigger("error." + this._name);
      console.error("Error:", response.status, response.responseText);

      this.settings.mainAlertElement.ufAlerts("add", {
        type: "danger",
        message: "An error occurred while processing your request. Please try again.",
      });
    },
    destroy: function () {
      this.$elements.off("." + this._name).removeData(this._name);
    },
  });

  $.fn[pluginName] = function (methodOrOptions) {
    if (this.length == 0) return this;

    var instance = $(this).data(pluginName);
    var method = typeof methodOrOptions === "string" ? methodOrOptions : "display";
    var options = typeof methodOrOptions === "object" ? methodOrOptions : undefined;

    if (!instance) {
      $(this).data(pluginName, new Plugin(this, options));
      instance = $(this).data(pluginName);
    }

    if (typeof instance[method] === "function") {
      return instance[method](options);
    } else {
      $.error("Method " + method + " does not exist.");
    }
  };

})(jQuery, window, document);
