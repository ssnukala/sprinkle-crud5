/**
 * Page-specific Javascript file.  Should generally be included as a separate asset bundle in your page template.
 * example: {{ assets.js('js/pages/sign-in-or-register') | raw }}
 *
 * This script depends on widgets/groups.js, uf-table.js, moment.js, handlebars-helpers.js
 *
 * Target page: /groups
 */
import { bindCrud5CreationButton, bindCrud5Buttons } from "../widgets/crudlist";

$(document).ready(function () {
  $("#widget-" + page.cr5model).ufTable({
    dataUrl: site.uri.public + "/api/crud5/" + page.cr5model,
    useLoadingTransition: site.uf_table.use_loading_transition,
  });

  // Bind creation button
  //bindCrud5CreationButton($("#widget-create-" + page.cr5model), { crud_slug: page.cr5model });
  // not needed bc the form generator will handle this

  // Bind table buttons
  $("#widget-" + page.cr5model).on("pagerComplete.ufTable", function () {
    $(".js-displayForm").formGenerator();
    $(".js-displayConfirm").formGenerator("confirm");

    console.log($(".js-displayForm").data("events"));
    //bindCrud5Buttons($(this), { crud_slug: page.cr5model });
    // Not needed because the form generator will handle this
  });
});
