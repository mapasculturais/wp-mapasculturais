(function($){
    $(function(){
        $('.js-mapas--import-new-entities').on('click', API.importNewEntities);
    });

    var API = {
        importNewEntities: function(){
            $.post('/mcapi/import-new-entities', {classes: ['agent', 'space', 'event']}).success(function(r){
                $('.js-mapas--api-output').html(r);
            });
        }
    };
})(jQuery);