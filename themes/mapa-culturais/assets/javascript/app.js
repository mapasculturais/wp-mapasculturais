import Vue from 'vue';

const app = new Vue({
    el: '#app',
})

jQuery(document).ready(function(){
    scrolledMenu();

    if(jQuery('.card-special').length == 0){
        jQuery('.main-header').addClass('scrolled')
    }

})

jQuery(window).scroll(function(){
    scrolledMenu();
})

function scrolledMenu(){
    if(jQuery(window).scrollTop() > 0){
        jQuery('.main-header').addClass('scrolled')
    }else if(jQuery('.card-special').length > 0){
        jQuery('.main-header').removeClass('scrolled')
    }
}

window.printElem = function (elem) {
    var mywindow = window.open('', 'PRINT', 'height=400,width=600');

    mywindow.document.write(`
    <html><head>
    <link rel="stylesheet" href="/wp-content/themes/melhoresfilmes/dist/app.css" type="text/css" />
    <link rel="stylesheet" href="/wp-content/plugins/gutenberg/build/block-library/style.css?ver=1540928884" type="text/css" />
    `);
    mywindow.document.write('</head><body >');
    mywindow.document.write(document.getElementById(elem).innerHTML);
    mywindow.document.write('</body></html>');

    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10*/

    setTimeout(() => {
        mywindow.print();
        mywindow.close();
    }, 500);

    return true;
}
