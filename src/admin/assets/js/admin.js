(function($){
    $(function(){
        $('.js-mapas--import-new-entities').on('click', API.importNewEntities);

        if(mc_errors){
            for(var prop in mc_errors){
                $('input#' + prop).css({border: '2px solid red'});
                var errors = mc_errors[prop].concat(', ');
                $('input#' + prop).after("<br><em>" + errors + '</em>');
            }
        }
    });

    var API = {
        importNewEntities: function(){
            $.post('/mcapi/import-new-entities', {classes: ['agent', 'space', 'event']}).success(function(r){
                $('.js-mapas--api-output').html(r);
            });
        }
    };
})(jQuery);