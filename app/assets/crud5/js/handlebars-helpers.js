/**
 * This file contains extra helper functions for Handlebars.js.
 *
 * @see http://handlebarsjs.com/#helpers
 */

/**
 * Improved comparison operator
 * See https://stackoverflow.com/a/16315366/2970321
 */
Handlebars.registerHelper("ifNotEmpty", function (value, returnVal) {
  if (!Handlebars.Utils.isEmpty(value)) {
    return new Handlebars.SafeString(returnVal);
  }
});
