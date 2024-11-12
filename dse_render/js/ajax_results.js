(function ($, Drupal, once) {
    Drupal.behaviors.resultsDisplay = {
        attach: (context, settings) => {
            once('resultsDisplay', '.dse-found-item').forEach(function (elt) {
                $(elt).on('click', function(event) {
                    let data = JSON.parse(settings.dse_render.js_array);
                    let name = $(elt).attr('id');

                    for (const [key, val] of Object.entries(data)) {
                            if (val._id === name) {      
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

                                    let baseLink = val.ajax_url.split('api')[0];
                                    $(result).find('a').attr('href', baseLink + $(result).find('a').attr('href'));
                                    $(result).find('a').attr('target', '_blank');

                                    let search_styles = '.ajax-render.' + val.style 
                                    $(search_styles).replaceWith(result);

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
