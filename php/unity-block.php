<?php
require_once __DIR__ . '/utils.php';

/**
 * Returns an array of strings to translate for the JS interface.
 */
function wpunityGetTranslatableStrings(): array {
    return [
        'buildChoose' => __('buildChoose', 'wpunity'),
        'buildSelectionne' => __('Build sélectionné', 'wpunity'),
        'warnExpectedRatio' => __("⚠️ Format attendu : nombre/nombre (ex: 4/3) \nSi le format est invalide, la valeur par défaut utilisée sera 4/3.", 'wpunity'),
        'showOptions' => __('Afficher les options', 'wpunity'),
        'showOnMobile' => __('Afficher sur mobile', 'wpunity'),
        'showLogs' => __('Afficher les logs dans la console', 'wpunity')
    ];
}

/**
 * Registers the Unity WebGL block and enqueues the necessary editor script.
 */
function unityEnqueueBlock(): void
{
    // Register the editor JavaScript file
    wp_register_script(
        'wpunity-unity-block',
        plugins_url('../js/editor-unity-block.js', __FILE__),
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-i18n'],
        filemtime(plugin_dir_path(__FILE__) . '../js/editor-unity-block.js')
    );
    
    // Ajout des trad dans le script
    wp_localize_script('wpunity-unity-block', 'WP_I18N', wpunityGetTranslatableStrings());
    
    // Pass global plugin data to JS
    wp_localize_script('wpunity-unity-block', 'UnityWebGLData', [
        'urlAdmin' => admin_url('/admin.php'),
    ]);
    
    // Register the block type
    register_block_type('wpunity/unity-webgl', [
        'editor_script' => 'wpunity-unity-block',
        'editor_style' => 'wpunity-unity-block-style',
        'style' => 'wpunity-unity-block-style',
    ]);
}

// enqueue_block_editor_assets ne s'exécute que dans l'éditeur de blocs (page/post avec Gutenberg).
add_action('enqueue_block_editor_assets', 'unityEnqueueBlock');

/*
Cette fonction crée un tableau builds des dossiers présents dans uploads/unity_webgl.
Elle le passe à JS sous le nom global unityBuildsData.
JS peut ensuite lire unityBuildsData.builds pour afficher la liste.
*/
function unityWebglLocalizeBuilds(): void
{
    $upload_dir = wp_upload_dir();
    $builds_dir = $upload_dir['basedir'] . '/unity_webgl';
    
    $builds = Utils::list_builds($builds_dir);
    
    wp_localize_script('wpunity-unity-block', 'unityBuildsData', ['builds' => $builds]);
}
add_action('enqueue_block_editor_assets', 'unityWebglLocalizeBuilds');
