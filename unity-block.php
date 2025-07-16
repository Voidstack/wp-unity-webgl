<?php

// Tableau des string à traduirer.
function wpunity_get_translatable_strings(): array {
    return [
        'buildChoose' => __('buildChoose', 'wpunity'),
    ];
}

// Unity WebGL Block, ajoute le bloc Unity Webgl dans les blocs wordpress.
function unity_enqueue_block(): void
{
    // Ajout du script JS pour le block
    wp_register_script(
        'mon-plugin-unity-block',
        plugins_url('block/index.js', __FILE__),
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n'],
        filemtime(plugin_dir_path(__FILE__) . 'block/index.js')
    );

    // Ajout des trad dans le script
    wp_localize_script('mon-plugin-unity-block', 'WP_I18N', wpunity_get_translatable_strings());
    
    // Ajout du css pour le block
    wp_register_style(
        'mon-plugin-unity-block-style',
        plugins_url('block/style.css', __FILE__),
        [],
        filemtime(plugin_dir_path(__FILE__) . 'block/style.css')
    );

    // Je sais pas
    register_block_type('mon-plugin/unity-webgl', [
        'editor_script' => 'mon-plugin-unity-block',
        'editor_style' => 'mon-plugin-unity-block-style',
        'style' => 'mon-plugin-unity-block-style',
    ]);
}

// enqueue_block_editor_assets ne s'exécute que dans l'éditeur de blocs (page/post avec Gutenberg).
add_action('enqueue_block_editor_assets', 'unity_enqueue_block');

/*
Cette fonction crée un tableau builds des dossiers présents dans uploads/unity_webgl.
Elle le passe à JS sous le nom global unityBuildsData.
JS peut ensuite lire unityBuildsData.builds pour afficher la liste.
*/
function unity_webgl_localize_builds(): void
{
    $upload_dir = wp_upload_dir();
    $builds_dir = $upload_dir['basedir'] . '/unity_webgl';

    $builds = [];
    if (is_dir($builds_dir)) {
        foreach (scandir($builds_dir) as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_dir($builds_dir . '/' . $entry)) {
                $builds[] = $entry;
            }
        }
    }

    wp_localize_script('mon-plugin-unity-block', 'unityBuildsData', ['builds' => $builds]);
}
add_action('enqueue_block_editor_assets', 'unity_webgl_localize_builds');
