(function($){
    $(function(){
        $('.js-mapas--import-new-entities').on('click', API.importNewEntities);

        if(typeof mc_errors !== 'undefined'){
            for(var prop in mc_errors){
                $('input#' + prop).css({border: '2px solid red'});
                var errors = mc_errors[prop].concat(', ');
                $('input#' + prop).after("<br><em>" + errors + '</em>');
            }
        }

        function checkLabel(evt){
            var $el = $(this);
            if($el.is(':checked')){
                $el.parents(".checkbox-selector").addClass('selected');
            } else {
                $el.parents(".checkbox-selector").removeClass('selected');
            }
        }   
        $('.checkbox-selector input:checked').each(checkLabel);
        $('.checkbox-selector input').on('change', checkLabel);

    });

    var API = {
        importNewEntities: function(){
            $.post( mapas.wpUrl + '/mcapi/import-entities/?skip-cron', {classes: ['agent', 'space', 'event']}).success(function(r){
                $('.js-mapas--api-output').html(r);
            });
        }
    };
})(jQuery);