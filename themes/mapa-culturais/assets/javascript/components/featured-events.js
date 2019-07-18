(function( $ ) {
    $('[data-set-index]').click(function(){
        let $parent = $(this).parents('.slider');
        let index = $(this).data('set-index');
        
        $parent.children(`.slide`).removeClass('active');
        $parent.find(`.slide:eq('${index}')`).addClass('active');
    })
})( jQuery );
