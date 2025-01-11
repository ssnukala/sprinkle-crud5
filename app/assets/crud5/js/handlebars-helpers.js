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

var DateFormats = {
    short: "MM/DD/YYYY",
    long: "dddd, MMMM do YYYY HH:mm",
};

// Use UI.registerHelper..
Handlebars.registerHelper("formatDate", function (datetime, format) {
    if (moment) {
        // can use other formats like 'lll' too
        format = DateFormats[format] || format;
        return moment(datetime).format(format);
    } else {
        return datetime;
    }
});
