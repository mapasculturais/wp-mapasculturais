<?php 
$query = new WP_Query($query_args); 
$i = 0; 
?>

<div class="slider-events slider">
    <?php if ($query->have_posts()): ?>
        <?php while ($query->have_posts()):
            $query->the_post();
            ?>
            <div class="slider-events--slide slide <?php echo  $i == 0 ? 'active' : '' ?>">
                <div class="slider-events--image" style="background-image: url('<?php echo  images\url('full') ?>')"></div>
                <div class="events--block">
                    <h2 class="card--title"><?php the_title() ?></h2>
                    <div class="card--taxonomy">
                        <?php the_taxonomy('linguagem', ', '); ?>
                    </div>
                    <?php if(has_excerpt()): ?>
                        <div class="card--excerpt"><?php the_excerpt(); ?></div>
                    <?php endif; ?>

                    <a href="javascript:void(0);" class="slider-events--arrows left" data-set-index="<?php echo  $i == 0 ? (count($query->posts) - 1) : ($i - 1) ?>"> 
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="javascript:void(0);" class="slider-events--arrows right" data-set-index="<?php echo  $i == (count($query->posts) - 1) ? '0' : ($i + 1) ?>"> 
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="slider-events--infos">
                    <?php  
                    $api = WPMapasCulturais\ApiWrapper::instance();
                    $api_results = $api->find('eventOccurrence', ['id' => 'EQ('.get_post_meta(get_the_ID(), 'MAPAS:entity_id', true).')' , 'groupBy' => 'event', 'from'=>date('Y-m-d'), 'to'=>date('Y-m-d', strtotime('+2 weeks'))])[0];
                    $occurrence = '';
                    $space = [];
                    $rated = '';

                    $rated = $api_results->classificacaoEtaria;

                    if(count($api_results->occurrences) > 0 ){
                        $space = ['name' => $api_results->occurrences[0]->space->name, 'endereco' =>  $api_results->occurrences[0]->space->endereco];
                        $occurrence = $api_results->occurrences[0]->description;        
                    }
                    ?>

                    <?php if(!empty($occurrence)): ?>
                    <div class="slider-events--info">
                        <i class="far fa-calendar-alt"></i> 
                        <p><?php echo  $occurrence ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if(isset($space['name']) && $space['name'] != ''): ?>
                    <div class="slider-events--info">
                        <i class="fas fa-map-marker-alt"></i> 
                        <p>
                        <strong><?php echo  $space['name'] ?></strong>
                        <br>
                        <?php echo  $space['endereco'] ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($rated)): ?>
                    <div class="slider-events--info">
                        <i class="fas fa-child"></i>

                        <p><?php echo  $rated ?></p>
                    </div>
                    <?php endif; ?>

                    <a href="<?php echo  get_the_permalink() ?>" class="small-link float-right">Mais infos</a>
                </div>
            </div>
        <?php $i++; endwhile; ?>
    <?php endif; ?>

    <?php if ($instance['vermais_href']): ?>
        <a class="posts-list--ver-mais" href="<?php echo  $instance['vermais_href'] ?>"><?php echo  ('Ver mais') ?></a>
    <?php endif; ?>
</div>