(function ($, Drupal, once) {
    const bootstrap4_conversion = {
        'data-toggle': 'data-bs-toggle',
        'data-placement': 'data-bs-placement',
        'data-content': 'data-bs-content',
        'data-container': 'data-bs-container',
        'data-trigger': 'data-bs-trigger'
        
    }

    const copy_button_svg = '<svg xmlns="http://www.w3.org/2000/svg" style="width: 1em; height: 1em;" viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M384 336l-192 0c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l140.1 0L400 115.9 400 320c0 8.8-7.2 16-16 16zM192 384l192 0c35.3 0 64-28.7 64-64l0-204.1c0-12.7-5.1-24.9-14.1-33.9L366.1 14.1c-9-9-21.2-14.1-33.9-14.1L192 0c-35.3 0-64 28.7-64 64l0 256c0 35.3 28.7 64 64 64zM64 128c-35.3 0-64 28.7-64 64L0 448c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-32-48 0 0 32c0 8.8-7.2 16-16 16L64 464c-8.8 0-16-7.2-16-16l0-256c0-8.8 7.2-16 16-16l32 0 0-48-32 0z"/></svg>'

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

    function wrapJson(json, style) {
        let container = $('<div class="ajax-render mt-3"> </div>').addClass(style);     

        return $(container).prepend($(json)).append($('<div class="copy-entry-link"> </div>'));
    }

    function modalizeLinks(result, parent_origin, datasources) {
        $(result).find('a').each(function() {
            let link = $(this).attr('href');

            if (link !== undefined && !(link.startsWith('http'))) {
                link = parent_origin.base_url + link;
                $(this).attr('href', link);
            }

            function findTrustedOrigin() {
                if (link !== undefined) {
                    for (const [key, val] of Object.entries(datasources)) {
                        if (link.includes(val.base_url)) {
                            return datasources[key];
                        }
                    }
                }

                return null;
            }

            let own_origin = findTrustedOrigin()
            if (own_origin) {
                const node = link.split('node/')[1]
                if (node) {
                    $(this).on('click', function(event) {
                        event.preventDefault()

                        $.ajax({
                            url: own_origin.ajax_url,
                            data: {
                                nid: node
                            },
                            type: 'GET',
                            dataType: 'json'
                        })
                        .done( function(modal_json) {
                            let dialogContent = wrapJson(modal_json[0].render, own_origin.style);

                            convertTags(dialogContent, 'a');
                            convertTags(dialogContent, 'span');
                            
                            modalizeLinks(dialogContent, own_origin, datasources);

                            if ($(".dse-modal").length ) {
                                $(".dse-modal").remove();
                            }
                            let newDialog = Drupal.dialog(dialogContent, {
                                width: 800,
                                dialogClass: 'dse-modal no-close',
                                closeOnEscape: true,
                            })

                            newDialog.showModal();
                                                
                            $('[data-bs-toggle="popover"]').popover();
                            $('[data-bs-toggle="tooltip"]').tooltip();
                            $('html').css({'overflow': 'auto'});
                        })
                    })
                }
            }                                      
        })
    }
    

    Drupal.behaviors.resultsDisplay = {
        attach: (context, settings) => {
            once('resultsDisplay', '.dse-found-item').forEach(function (elt) {
                $('[data-bs-toggle="tooltip"]').tooltip();
                
                $(elt).on('click', function(event) {                 
                    let results_data = JSON.parse(settings.dse_render.js_results);
                    let datasources_data = JSON.parse(settings.dse_render.js_datasources);

                    let name = $(elt).attr('id');

                
                    for (const [key, val] of Object.entries(results_data)) {
                            if (key === name) {
                                let result_id = 'result_' + name;
                                if ($('#' + result_id).length === 0) {
                                    $(elt).append(Drupal.theme.ajaxProgressThrobber('Загрузка...'));

                                    let origin = datasources_data[val.source];
                                    let search_styles = '.ajax-render.' + origin.style


                                    $.ajax({
                                        url: origin.ajax_url,
                                        data: {
                                            nid: val.node                       
                                        } ,
                                        type: "GET",
                                        dataType: "json",
                                    })
                                    .done( function( json ) {
                                        if (json !== undefined && json[0].render) {

                                            let result = wrapJson(json[0].render, val.style);
                                            $(result).attr('id', result_id);
                                                                             
                                            modalizeLinks(result, origin, datasources_data);
    
                                            convertTags(result, 'a');
                                            convertTags(result, 'span');

                                            $(result).find('.copy-entry-link').append(copy_button_svg);
     
                                            $(search_styles).last().append(result);                                       
                                            $('[data-bs-toggle="popover"]').popover();
                                            $('[data-bs-toggle="tooltip"]').tooltip();
                                        } else {
                                            displayError(search_styles, origin.style);
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
                    }
                })
            });
        }
    }
})(jQuery, Drupal, once)
