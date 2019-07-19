(function(){
    var popup;
    window.addEventListener("message", function (event) {
        if (event.origin + '/' !== mapas.url){
            return;
        }
        var ok = true;
        if(ok){
            Cookies.set('mcProcurationToken', event.data);
            window.resolveProcurationToken(event.data);
        } else {
            window.rejectProcurationToken();
        }
        popup.close();
        
    }, false);
    
    window.getProcurationToken = function(){
        return new Promise((resolve, reject) => {
            var token = Cookies.get('mcProcurationToken');
            if(token){
                resolve(token);
            } else {
                window.resolveProcurationToken = resolve;
                window.rejectProcurationToken = reject;
                
                popup = window.open(mapas.url + 'auth/getProcuration/permission:manageEventAttendance/attorney:' + mapas.publicKey, 
                    'popup', "width=800, height=600, top=100, left=110, scrollbars=yes ");
            }
        });
    }
})();