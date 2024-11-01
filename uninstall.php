<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

function wph_plugin_remove()
{
    unregister_setting('wph_page', 'wphopt_pixel_id');
    unregister_setting('wph_page', 'wphopt_pixel_ajax_listing');
    unregister_setting('wph_page', 'wphopt_pixel_ajax_product');
}

wph_plugin_remove();