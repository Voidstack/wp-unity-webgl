<?php
require_once __DIR__ . '/utils.php';

/**
* Returns an array of strings to translate for the JS interface.
*/
function wpunityGetTranslatableStrings(): array {
    return [
        'buildChoose' => __('Choose a unity build', 'webgl-embedder-for-unity') . ' : ',
        'buildSelectionne' => __('Selected build', 'webgl-embedder-for-unity'),
        'warnExpectedRatio' => '⚠️ ' . __("Expected format: number/number (4/3) \nIf the format is invalid, the default value will be 4/3.", 'webgl-embedder-for-unity'),
        'showOptions' => __('Display options', 'webgl-embedder-for-unity'),
        'showOnMobile' => __('Display game on mobile', 'webgl-embedder-for-unity'),
        'showLogs' => __('Display logs in the console', 'webgl-embedder-for-unity')
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
        filemtime(plugin_dir_path(__FILE__) . '../js/editor-unity-block.js'),
        true
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
    
    $builds = Utils::listBuilds($builds_dir);
    
    wp_localize_script('wpunity-unity-block', 'unityBuildsData', ['builds' => $builds]);
}
add_action('enqueue_block_editor_assets', 'unityWebglLocalizeBuilds');
