<?php
require_once 'utils.php';

// Tableau des string à traduirer.
function wpunity_get_translatable_strings(): array {
    return [
        'buildChoose' => __('buildChoose', 'wpunity'),
        'buildSelectionne' => __('Build sélectionné', 'wpunity'),
        'warnExpectedRatio' => __("⚠️ Format attendu : nombre/nombre (ex: 4/3) \nSi le format est invalide, la valeur par défaut utilisée sera 4/3.", 'wpunity'),
        'showOptions' => __('Afficher les options', 'wpunity'),
        'showOnMobile' => __('Afficher sur mobile', 'wpunity'),
        'showLogs' => __('Afficher les logs dans la console', 'wpunity')
    ];
}

// Unity WebGL Block, ajoute le bloc Unity Webgl dans les blocs wordpress.
function unity_enqueue_block(): void
{
    // Ajout du script JS pour le block
    wp_register_script(
        'mon-plugin-unity-block',
        plugins_url('../js/editor-unity-block.js', __FILE__),
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n'],
        filemtime(plugin_dir_path(__FILE__) . '../js/editor-unity-block.js')
    );
    
    // Ajout des trad dans le script
    wp_localize_script('mon-plugin-unity-block', 'WP_I18N', wpunity_get_translatable_strings());
    
    wp_localize_script('mon-plugin-unity-block', 'UnityWebGLData', [
        'urlAdmin' => admin_url('/admin.php'),
    ]);
    
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
    
    $builds = Utils::list_builds($builds_dir);
    
    wp_localize_script('mon-plugin-unity-block', 'unityBuildsData', ['builds' => $builds]);
}
add_action('enqueue_block_editor_assets', 'unity_webgl_localize_builds');
