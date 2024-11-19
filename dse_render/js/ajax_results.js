(function ($, Drupal, once) {
    let bootstrap4_conversion = {
        'data-toggle': 'data-bs-toggle',
        'data-placement': 'data-bs-placement',
        'data-content': 'data-bs-content',
        'data-container': 'data-bs-container',
        'data-trigger': 'data-bs-trigger'
        
    }

    function convertTags(source, selector) {
        $(source).find(selector).each(function() {
            Object.keys(bootstrap4_conversion).forEach((tag) => {
                if ($(this).attr(tag)) {
                    let attr_value = $(this).attr(tag);
                    $(this).removeAttr(tag).attr(bootstrap4_conversion[tag], attr_value);
                }
            })
        })
    }

    Drupal.behaviors.resultsDisplay = {
        attach: (context, settings) => {
            once('resultsDisplay', '.dse-found-item').forEach(function (elt) {
                $(elt).on('click', function(event) {
                    let data = JSON.parse(settings.dse_render.js_array);
                    let name = $(elt).attr('id');

                    for (const [key, val] of Object.entries(data)) {
                            if (key === name) {      
                                $.ajax({
                                    url: val.ajax_url,
                                    data: {
                                        nid: val.node                       
                                    } ,
                                    type: "GET",
                                    dataType: "json",
                                })
                                .done( function( json ) {
                                    let result = $(json[0].render).addClass("ajax-render mt-3").addClass(val.style);

                                    let baseLink = val.ajax_url.split('/api')[0];

                                    $(result).find('a').each(function() {
                                        let relLink = $(this).attr('href');
                                        if (relLink !== undefined && !(relLink.startsWith('http'))) {
                                            $(this).attr('href', baseLink + relLink);
                                            $(result).find('a').attr('target', '_blank');
                                        }                                       
                                    })
                                    
                                    convertTags(result, 'a');
                                    convertTags(result, 'span');

                                    let search_styles = '.ajax-render.' + val.style 
                                    $(search_styles).replaceWith(result);

                                    $('[data-bs-toggle="popover"]').popover();
                                    $('[data-bs-toggle="tooltip"]').tooltip();

                                })
                                .fail(function( xhr, status, errorThrown ) {
                                    console.log( "Error: " + errorThrown );
                                    console.log( "Status: " + status ); 
                                }) 
                        }
                    }
                })
            });
        }
    }
})(jQuery, Drupal, once)
