<?php 
return [
    'doctrine.isDev' => false,
    'app.mode' => 'development',
    'slim.debug' => true,
    'app.baseUrl' => 'http://localhost:8080/',

    // LOG --------------------
    'slim.log.level'        => \Slim\Log::DEBUG,
    'slim.log.enabled'      => true,

    // app.log.hook aceita regex para filtrar quais hooks são exibidos no output, 
    // ex: "panel", "^template", "template\(site\.index\.*\):before"
    'app.log.hook'          => false, 
    // 'app.log.query'         => true,
    // 'app.log.requestData'   => true,
    // 'app.log.translations'  => true,
    // 'app.log.apiCache'      => true,
    // 'app.log.apiDql'        => true,
    // 'app.log.assets'        => true,


    // MAILER -----------------
    // 'mailer.user'       => 'you@gmail.com', 
    // 'mailer.psw'        => 'passwd', 
    // 'mailer.protocol'   => 'SSL', 
    // 'mailer.server'     => 'smtp.gmail.com', 
    // 'mailer.port'       => '465', 
    // 'mailer.from'       => 'you@gmail.com', 
    // 'mailer.alwaysTo'   => 'you@gmail.com', // todos os emails serão enviados para este endereço


    // AUTH -------------------
    'auth.provider' => 'Fake', 
    
];