(function ($, Drupal, once) {
    const bootstrap4_conversion = {
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

    function displayError(selector, style) {
        let errMessage = $('<div></div>').text("Что-то пошло не так. Повторите попытку загрузки позже.").addClass("alert alert-danger ajax-render mt-3").addClass(style);

        $(selector).replaceWith(errMessage);
    }

    Drupal.behaviors.resultsDisplay = {
        attach: (context, settings) => {
            once('resultsDisplay', '.dse-found-item').forEach(function (elt) {
                $('[data-bs-toggle="tooltip"]').tooltip();
                
                $(elt).on('click', function(event) {
                    $(elt).append(Drupal.theme.ajaxProgressThrobber('Загрузка...'))
                    let data = JSON.parse(settings.dse_render.js_array);
                    let name = $(elt).attr('id');

                
                    for (const [key, val] of Object.entries(data)) {
                        let search_styles = '.ajax-render.' + val.style

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
                                    if (json !== undefined && json[0].render) {
                                        let result = $(json[0].render).addClass("ajax-render mt-3").addClass(val.style);
                                        $(result).find('a').each(function() {
                                            let relLink = $(this).attr('href');
                                            if (relLink !== undefined && !(relLink.startsWith('http'))) {
                                                $(this).attr('href', baseLink + relLink)
                                                let id = relLink.split('node/')[1]
                                                $(this).on('click', function(event) {
                                                    event.preventDefault();
                                                    $.ajax({
                                                        url: val.ajax_url,
                                                        data: {
                                                            nid: id
                                                        },
                                                        type: 'GET',
                                                        dataType: 'json',
                                                    })
                                                    .done( function(modal_json) {
                                                        let newDialog = $(modal_json[0].render);
                                                        console.log(newDialog);
                                                        Drupal.dialog(newDialog, {
                                                            width: 800,
                                                        }).showModal();                       
                                                    })
                                                })
                                            }                                       
                                        })
                                        
                                        convertTags(result, 'a');
                                        convertTags(result, 'span');
 
                                        $(search_styles).replaceWith(result);

                                        $('[data-bs-toggle="popover"]').popover();
                                        $('[data-bs-toggle="tooltip"]').tooltip();
                                       
                                    } else {
                                        displayError(search_styles, val.style);
                                    }

                                    $('.ajax-progress-throbber').remove();
                                })
                                .fail(function( xhr, status, errorThrown ) {
                                    console.log( "Error: " + errorThrown );
                                    console.log( "Status: " + status ); 

                                    displayError(search_styles, val.style);
                                    $('.ajax-progress-throbber').remove();
                                }) 
                        }
                    }
                })
            });
        }
    }
})(jQuery, Drupal, once)
