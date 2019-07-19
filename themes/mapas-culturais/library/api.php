<?php
add_action('rest_api_init', function () {
    register_rest_route('observatorio/v1', '/card-section', array(
        'methods' => 'POST',
        'callback' => 'card_section_posts',
    ));
});

function card_section_posts(WP_REST_Request $request)
{
    // You can access parameters via direct array access on the object:
    $args = get_transient($request['hash']);

    if( isset($request['args']['terms']) && count($request['args']['terms']) > 0 ){
        $args['tax_query'][0]['terms'] = $request['args']['terms'];
    }

    $args['paged'] = $request['args']['page'];
    $query_posts = new WP_Query($args);
    
    $taxonomy = isset($args['tax_query'][0]['taxonomy']) ? $args['tax_query'][0]['taxonomy'] : 'category';

    if ($query_posts->have_posts()) {
        while ($query_posts->have_posts()) {
            $query_posts->the_post();

            $posts[] = prepare_post($request['subhome']);
        }
    }

    return [ 'posts_count' => $query_posts->post_count , 'posts' => $posts];
}
  