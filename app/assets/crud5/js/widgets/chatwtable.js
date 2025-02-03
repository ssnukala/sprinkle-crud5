const emailRegex = /[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i;
const mobileRegex = /\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/;
const scoreRegex = /\b\d+\b/;
const phoneRegex = /\b\d{3}[-.\s]?\d{3}[-.\s]?\d{4}\b/;
const dateRegex = /\b\d{1,2}\/\d{1,2}\/\d{2,4}|\b\d{1,2}-\d{1,2}-\d{2,4}\b|\b\d{4}-\d{2}-\d{2}\b/;

function parseContactChat(line) {
    var contactDetails = {
        first_name: null,
        last_name: null,
        email: null,
        mobile: null,
        profession: null,
        score: null,
    };

    var parts = line.split(",").map((part) => part.trim());
    parts.forEach(function (part) {
        if (emailRegex.test(part)) {
            contactDetails.email = part;
        } else if (mobileRegex.test(part)) {
            contactDetails.mobile = part;
        } else if (scoreRegex.test(part) && !contactDetails.score) {
            contactDetails.score = parseInt(part);
        } else if (!contactDetails.profession && !part.includes(" ")) {
            contactDetails.profession = part;
        } else if (!contactDetails.first_name && !contactDetails.last_name) {
            var nameParts = part.split(" ");
            if (nameParts.length > 1) {
                contactDetails.first_name = nameParts[0].trim();
                contactDetails.last_name = nameParts.slice(1).join(" ").trim();
            }
        }
    });

    return contactDetails;
}

function scheduleMeeting(line) {
    const meetingDetails = {
        person: null,
        date: null,
        phone: null,
        description: null,
        email: null,
    };

    const parts = line.split(",").map((part) => part.trim());
    parts.forEach((part) => {
        if (emailRegex.test(part)) {
            meetingDetails.email = part;
        } else if (phoneRegex.test(part)) {
            meetingDetails.phone = part;
        } else if (dateRegex.test(part)) {
            meetingDetails.date = part;
        } else if (!meetingDetails.person) {
            meetingDetails.person = part;
        } else {
            meetingDetails.description = part;
        }
    });
    return meetingDetails;
}

$(document).ready(function () {
    // Validators for tables
    $("#chat-submit").on("click", function () {
        var chatText = $("#chat-input").val().trim();
        if (!chatText) {
            alert("Please enter some data.");
            return;
        }

        // Dynamically generate the regex for splitting based on table data-table attributes
        var tableTypes = [];
        $("table[data-table]").each(function () {
            tableTypes.push($(this).data("table"));
        });

        // Build regex dynamically from tableTypes
        var prefixRegex = new RegExp(`(?=#(${tableTypes.join("|")}))`, "g");

        // Split the input into fragments
        const fragments = chatText.split(prefixRegex);

        fragments.forEach((fragment) => {
            let tableType = null;

            // Match the prefix to determine table type
            tableTypes.forEach((type) => {
                if (fragment.startsWith(`#${type}`)) {
                    tableType = type;
                    fragment = fragment.replace(`#${type}`, "").trim();
                }
            });

            if (tableType) {
                var lines = fragment.split("\n");
                //const tableValidators = window.table1_validators[tableType];
                var tableValidators = window.table1_validators[tableType];
                if (!tableValidators || !tableValidators.chatparse_function) {
                    console.error("No validator or parse function found for table type:", tableType);
                    return;
                }

                const parseFunctionName = tableValidators.chatparse_function;
                const parseFunction = window[parseFunctionName];
                if (typeof parseFunction !== "function") {
                    console.error(`Parser function "${parseFunctionName}" is not defined.`);
                    return;
                }
                var $nearestTable = $(`table[data-table="${tableType}"]`);
                var $tableBody = $nearestTable.find("tbody");
                var $headers = $nearestTable.find("thead th");

                lines.forEach(function (line) {
                    var parsedData = parseFunction(line);
                    if (parsedData) {
                        var $row = $("<tr>");
                        $headers.each(function () {
                            var field = $(this).data("column-name");
                            var value = parsedData[field] || "";
                            $row.append($("<td>").text(value));
                        });
                        $tableBody.append($row);
                    }
                });
            }
        });

        // Clear the input
        $("#chat-input").val("");
    });

    // Function to map header columns to their indices
    function getColumnIndices(tableSelector, fields) {
        const columnMap = {};
        const headers = $(`${tableSelector} thead th`);
        headers.each(function (index) {
            const field = $(this).data("field");
            if (fields.includes(field)) {
                columnMap[field] = index; // Map field to column index
            }
        });
        return columnMap;
    }

    // Collect suggestions dynamically from the contact list
    function getContactSuggestions() {
        const suggestions = [];
        const tableSelector = "table[data-table='contact']";
        const columns = getColumnIndices(tableSelector, ["first_name", "last_name", "email"]);

        // Loop through rows to gather suggestions
        $(`${tableSelector} tbody tr`).each(function () {
            const firstName = $(this).find(`td:eq(${columns.first_name})`).text().trim();
            const lastName = $(this).find(`td:eq(${columns.last_name})`).text().trim();
            const email = $(this).find(`td:eq(${columns.email})`).text().trim();

            // Add to suggestions
            if (firstName || lastName) {
                const fullName = `${firstName} ${lastName}`.trim();
                suggestions.push(fullName);
            }
            if (email) {
                suggestions.push(email);
            }
        });
        return suggestions;
    }

    // Initialize autocomplete
    $("#chat-input").autocomplete({
        source: function (request, response) {
            // Get all contact suggestions
            const allSuggestions = getContactSuggestions();

            // Check the context (e.g., #meeting)
            const inputValue = request.term;
            const prefixMatch = inputValue.match(/#(\w+)\s+(.*)/);
            if (prefixMatch) {
                const prefix = prefixMatch[1]; // e.g., "meeting"
                const searchTerm = prefixMatch[2].toLowerCase(); // e.g., "john"

                // Filter suggestions based on the prefix and search term
                if (prefix === "meeting") {
                    response(allSuggestions.filter((item) => item.toLowerCase().includes(searchTerm)));
                } else {
                    response([]); // No suggestions for other prefixes
                }
            } else {
                response([]); // No suggestions if no valid prefix is detected
            }
        },
        minLength: 2, // Minimum characters to trigger autocomplete
        focus: function (event, ui) {
            // Prevent value insertion on focus
            return false;
        },
        select: function (event, ui) {
            // Insert the selected suggestion into the input
            const currentValue = $(this).val();
            const prefixMatch = currentValue.match(/#(\w+)\s+(.*)/);
            if (prefixMatch) {
                const prefix = prefixMatch[1];
                $(this).val(`#${prefix} ${ui.item.value}`);
            }
            return false;
        },
    });
});
