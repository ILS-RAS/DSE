(function ($, Drupal, once) {
    Drupal.behaviors.testBehavior = {
        attach: (context, settings) => {
            once('testBehavior', '.dse-found-item').forEach(function (elt) {
                $(elt).one('click', function(event) {
                    let nid = $(elt).find('a')
                    .attr('href')
                    .split('/')
                    .slice(-1);

                    $.ajax({
                        url: "https://affixoid.iling.spb.ru/api/v1/dse-nodes",
                        data: {
                            nid: nid[0]                        
                        } ,
                        type: "GET",
                        dataType: "json",
                    })
                    .done( function( json ) {
                        $( json[0].render )
                        .appendTo($(elt).parent()); 
                    })
                    .fail(function( xhr, status, errorThrown ) {
                        console.log( "Error: " + errorThrown );
                        console.log( "Status: " + status ); 
                    }) 
                })
            });
        }
    }
})(jQuery, Drupal, once)
