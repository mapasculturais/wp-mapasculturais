<!DOCTYPE html>
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes();?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo('charset');?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1.0">
	<?php wp_head()?>
	<title><?= is_front_page() ? get_bloginfo('name') : wp_title()?></title>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
	<link rel="icon" href="<?= get_stylesheet_directory_uri() ?>/assets/images/favicon.ico?v=2" />
</head>
<body <?php body_class();?> >
	<header class="main-header">
		<div class="row">
			
			<div class="column large-3">
				<i>redes sociais </i><?php the_social_networks_menu(true) ?>
			</div>

			<div class="column large-3 text-center">
				<div class="logo">
					<a href="/"><img src="<?= get_theme_logo()  ?>" alt="<?= get_bloginfo('name') ?>"></a>
				</div>
			</div>

			<div class="column large-3 search-form">
				<form action="/">
					<input type="text" name="s" id="s">
				</form>
				<a href="javascript:void(0);" onclick="jQuery('.search-form').toggleClass('active')"><i class="fa fa-search fz-18"></i></a>
			</div>
		</div>
	</header>

	<div class="main-menu">
		<div class="row">
			<div class="column large-12">
					<div class="responsive-logo show-for-small-only logo">
						<a href="#" class="show-for-small-only p-15 pr-0" onclick="jQuery('.responsive-logo, .menu-menu-principal-container').removeClass('active')"><i class="fas fa-times"></i></a>
					</div>
				<a href="javascript:void(0);" class="show-for-small-only fz-24 mt-5 d-block" onclick="jQuery('.responsive-logo, .menu-menu-principal-container').addClass('active')"><i class="fa fa-bars fz-18"></i></a>
				<?=wp_nav_menu(['theme_location' => 'main-menu', 'container' => 'nav', 'menu_id' => 'main-menu', 'menu_class' => 'menu'])?>
			</div>
		</div>
	</div>
	<div id="app">