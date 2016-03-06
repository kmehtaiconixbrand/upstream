<?php
/*
Plugin Name: HTML Page Sitemap
Plugin URI: http://www.pluginspodcast.com/plugins/html-page-sitemap/
Description: <a href="http://www.pluginspodcast.com/plugins/html-page-sitemap/" target="_blank">HTML Page Sitemap</a> Adds an HTML (Not XML) sitemap of your blog pages (not posts) by entering the shortcode [html_sitemap]. A plugin from <a href="http://www.pluginspodcast.com/" target="_blank">Plugins: The WordPress Plugins Podcast</a>.
Version: 1.3
Contributors: Angelo Mandato, CIO RawVoice and host of the PluginsPodcast.com
Author URI: http://www.pluginspodcast.com/

Requires at least: 3.7
Tested up to: 4.4
Text Domain: html-sitemap
Change Log: See readme.txt for complete change log
Contributors: Angelo Mandato, CIO RawVoice and host of the PluginsPodcast.com
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt

Copyright 2009-2015 Angelo Mandato, host of the Plugins Podcast (http://www.pluginspodcast.com)
*/


/*
	Add the sitemap when shortcode is encountered
	@param $args - arguments or attributes specified in the shortcode tag.
	@param $content - not used
	@return the page sitemap or empty string if not applicable.
*/
function html_sitemap_shortcode_handler( $args, $content = null )
{
	if( is_feed() )
		return '';
	
	$class_tag = '';
	if( isset($args['class']) ) {
		if( !empty($args['class']) )
			$class_tag = $args['class'];
		unset($args['class']);
	}
	
	$id_tag = '';
	if( isset($args['id']) ) {
		if( !empty($args['id']) )
			$id_tag = $args['id'];
		unset($args['id']);
	}
	
	$ordered_type = '';
	if( isset($args['ordered_list_type']) ) {
		if( !empty($args['ordered_list_type'])) {
			switch( $args['ordered_list_type'] ) {
				case '1':
				case 'i':
				case 'I':
				case 'a':
				case 'A': { $ordered_type = $args['ordered_list_type']; }; break;
			}
		}
		unset($args['ordered_list_type']);
	}
	
	$args['echo'] = 0;
	$args['title_li'] = '';
	unset($args['link_before']);
	unset($args['link_after']);
	if( isset($args['child_of']) && $args['child_of'] == 'CURRENT' ) {
		$args['child_of'] = get_the_ID();
	} else if( isset($args['child_of']) && $args['child_of'] == 'PARENT' ) {
		$post = &get_post( get_the_ID() );
		if( $post->post_parent )
			$args['child_of'] = $post->post_parent;
		else
			unset( $args['child_of'] );
	}
	
	$html = wp_list_pages($args);

	// Remove the classes added by WordPress
	$html = preg_replace('/( class="[^"]+")/is', '', $html);
	
	if( !empty($ordered_type) ) {
		// swap the type ul with ol and set the type="$ordered_type"
		$html = preg_replace('/(<ul)/is', '<ol type="'. esc_attr($ordered_type) .'"', $html);
		$html = preg_replace('/(<\/ul)/is', '</ol', $html);
	}
	
	if( empty($ordered_type) ) {
		$prefix = '<ul';
	} else {
		$prefix = '<ol type="'. esc_attr($ordered_type) .'"';
	}
	
	if( !empty($id_tag) ) {
		$prefix .= ' id="'. esc_attr($id_tag) .'"';
	}
	
	if( !empty($class_tag) ) {
		$prefix .= ' class="'. esc_attr($class_tag) .'"';
	}
	$prefix .= '>';
		
	if( empty($ordered_type) ) {
		return $prefix . $html .'</ul>';
	}
	return $prefix . $html .'</ol>';
}

function html_sitemap_something_to_translate() {
	$null = __('HTML Page Sitemap', 'html-sitemap'); // We need to provide something to tranlate so the plugin can be translated at translate.wordpress.org
}

add_shortcode('html-sitemap', 'html_sitemap_shortcode_handler'); // This is no longer recommended as any plugin that creates their own shortcode starting with 'html' will also get the handler call
add_shortcode('htmlsitemap', 'html_sitemap_shortcode_handler');
add_shortcode('html_sitemap', 'html_sitemap_shortcode_handler');

