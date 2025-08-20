<?php
/**
 * Template Name: Template de Código Puro
 *
 * Este template renderiza o HTML, CSS e JS salvos nos meta fields,
 * ignorando completamente o tema do WordPress.
 */

// Pega o ID do post atual
$post_id = get_the_ID();

// Pega o código salvo nos campos personalizados (meta fields)
$html_code = get_post_meta($post_id, '_html_code', true);
$css_code  = get_post_meta($post_id, '_css_code', true);
$js_code   = get_post_meta($post_id, '_js_code', true);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?></title>
    
    <?php
    // Injeta o CSS diretamente no <head>
    if ( ! empty($css_code) ) {
        echo '<style type="text/css">' . $css_code . '</style>';
    }
    ?>
</head>
<body>

    <?php 
    // Renderiza o código HTML
    // Usamos stripslashes para remover barras invertidas que o WP pode adicionar
    echo stripslashes($html_code); 
    ?>

    <?php
    // Injeta o JavaScript antes do fechamento do </body>
    if ( ! empty($js_code) ) {
        echo '<script type="text/javascript">' . stripslashes($js_code) . '</script>';
    }
    ?>

</body>
</html>