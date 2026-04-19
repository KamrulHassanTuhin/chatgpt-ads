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
