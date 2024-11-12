
(function ($, Drupal, once) {
    Drupal.behaviors.autocompleteOverride = {
        attach: (context, settings) => {
            once('autocompleteOverride', 'html').forEach(function (elt) {
                Drupal.autocomplete.options.source = function customSourceData(request, response) {
                    function showSuggestions(suggestions) {
                    const tagged = Drupal.autocomplete.splitValues(request.term);
                    const il = tagged.length;
                    for (let i = 0; i < il; i++) {
                        const index = suggestions.indexOf(tagged[i]);
                        if (index >= 0) {
                        suggestions.splice(index, 1);
                        }
                    }
                    response(suggestions);
                    }

                    const term = Drupal.autocomplete.extractLastTerm(request.term);

                    const options = $.extend(
                        { success: showSuggestions, data: { q: term } },
                        Drupal.autocomplete.ajax,
                    );
                    $.ajax(this.element.attr('data-autocomplete-path'), options);
                }
            });
        }
    }
})(jQuery, Drupal, once)