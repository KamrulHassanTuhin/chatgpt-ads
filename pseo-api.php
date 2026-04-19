<?php
/*
Plugin Name: PSEO API
Description: Custom API for programmatic page creation
Version: 1.4
*/

function pseo_full_html_template_source() {
    return <<<'PHP'
<?php
/*
Template Name: Full HTML Page
*/

$page_id = get_the_ID();
$html = get_post_meta($page_id, '_pseo_full_html', true);

if (!is_string($html) || $html === '') {
    $post = get_post($page_id);
    $html = $post ? (string) $post->post_content : '';
}

if ($html === '') {
    status_header(404);
    exit;
}

$last_modified = get_post_modified_time('D, d M Y H:i:s', true, $page_id);
$last_modified = $last_modified ? $last_modified . ' GMT' : '';
$etag = '"' . md5($html) . '"';

if (!headers_sent() && !is_user_logged_in()) {
    header_remove('Pragma');
    header_remove('Expires');
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: public, max-age=300, s-maxage=3600, stale-while-revalidate=86400');
    header('CDN-Cache-Control: public, s-maxage=3600, stale-while-revalidate=86400');
    header('Surrogate-Control: public, max-age=3600, stale-while-revalidate=86400');
    if ($last_modified) {
        header('Last-Modified: ' . $last_modified);
    }
    header('ETag: ' . $etag);
}

$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim((string) $_SERVER['HTTP_IF_NONE_MATCH']) : '';
$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? trim((string) $_SERVER['HTTP_IF_MODIFIED_SINCE']) : '';

if (!is_user_logged_in() && (($if_none_match && $if_none_match === $etag) || ($last_modified && $if_modified_since === $last_modified))) {
    status_header(304);
    exit;
}

echo $html;
exit;
PHP;
}

// Create full-html template in theme on plugin load
add_action('init', function() {
    $template_file = get_template_directory() . '/template-full-html.php';
    $template_source = pseo_full_html_template_source();

    if (!file_exists($template_file) || file_get_contents($template_file) !== $template_source) {
        file_put_contents($template_file, $template_source);
    }
});

add_action('rest_api_init', function() {
    register_rest_route('pseo/v1', '/create-page', array(
        'methods' => 'POST',
        'callback' => 'pseo_create_page',
        'permission_callback' => 'pseo_check_key',
    ));
    register_rest_route('pseo/v1', '/update-page', array(
        'methods' => 'POST',
        'callback' => 'pseo_update_page',
        'permission_callback' => 'pseo_check_key',
    ));
    register_rest_route('pseo/v1', '/set-template', array(
        'methods' => 'POST',
        'callback' => 'pseo_set_template',
        'permission_callback' => 'pseo_check_key',
    ));
});

function pseo_check_key($request) {
    return $request->get_header('X-PSEO-Key') === 'pseo-secret-2026';
}

function pseo_create_page($request) {
    $data = $request->get_json_params();
    $page_id = wp_insert_post(array(
        'post_title'   => sanitize_text_field($data['title']),
        'post_content' => '',
        'post_name'    => sanitize_title($data['slug']),
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ));
    if (is_wp_error($page_id)) {
        return new WP_Error('create_failed', $page_id->get_error_message(), array('status' => 500));
    }
    update_post_meta($page_id, '_pseo_full_html', $data['content']);
    return array('id' => $page_id, 'slug' => $data['slug'], 'status' => 'created');
}

function pseo_update_page($request) {
    $data = $request->get_json_params();
    $page_id = intval($data['page_id']);
    if ($data['title']) {
        wp_update_post(array('ID' => $page_id, 'post_title' => sanitize_text_field($data['title'])));
    }
    update_post_meta($page_id, '_pseo_full_html', $data['content']);
    return array('id' => $page_id, 'status' => 'updated');
}

function pseo_set_template($request) {
    $data = $request->get_json_params();
    $page_id = intval($data['page_id']);
    $template = sanitize_text_field($data['template']);
    update_post_meta($page_id, '_wp_page_template', $template);
    return array('page_id' => $page_id, 'template' => $template, 'status' => 'updated');
}
