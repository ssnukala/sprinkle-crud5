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
  bindCrud5CreationButton($("#widget-" + page.cr5model));

  // Bind table buttons
  $("#widget-" + page.cr5model).on("pagerComplete.ufTable", function () {
    bindCrud5Buttons($(this));
  });
});
