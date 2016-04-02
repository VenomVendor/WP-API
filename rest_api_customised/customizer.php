<?php
/*
Plugin Name: WP REST API Customized
Plugin URI: http://VenomVendor.com
Description: A plugin to add customized functions without touching /themes/functi0n.php
Version: 1.0
Author: VenomVendor
Author URI: http://VenomVendor.com
License: Apache v2
*/

require_once('config.php');

function enable_date($public_vars) {
    //http://codex.wordpress.org/Class_Reference/WP_Query#Date_Parameters
    return array_merge($public_vars, array('date_query'));
}

add_filter('rest_query_vars', 'enable_date');

function remove_fields_rest_api( $data, $post, $request ) {
    $_data = $data->data;
    $params = $request->get_params();
    if ( ! isset( $params['id'] ) ) {
        unset( $_data['guid'] );
        unset( $_data['modified'] );
        unset( $_data['modified_gmt'] );
        unset( $_data['type'] );
        unset( $_data['author'] );
        unset( $_data['comment_status'] );
        unset( $_data['ping_status'] );
        unset( $_data['sticky'] );
        unset( $_data['format'] );
        unset( $_data['categories'] );
        unset( $_data['tags'] );
    }
    $data->data = $_data;
    return $data;
}

function add_fields_rest_api( $data, $post, $request ) {
    $_data = $data->data;
    $params = $request->get_params();
    if ( ! isset( $params['id'] ) ) {
        $_data['mobigyaan'] = true;
    }
    $data->data = $_data;
    return $_data;
}

function customize_fields_rest_api( $data, $post, $request ) {
    $_removed = remove_fields_rest_api( $data, $post, $request );
    $_data = add_fields_rest_api( $_removed, $post, $request );

    $token = $request->get_header('token');

    if(check_token($token)) {
        return $_data;
    }
    return null;
}

add_filter( 'rest_prepare_post', 'customize_fields_rest_api', 10, 3 );

/* Since _embed requires a copy of _link alternatively
 * https://github.com/BraadMartin/better-rest-api-featured-images
 * can be used, renamed to avoid conflict in future.
 */
function custom_better_rest_api_featured_images_init() {
    $post_types = get_post_types( array( 'public' => true ), 'objects' );
    foreach ( $post_types as $post_type ) {
        $post_type_name     = $post_type->name;
        $show_in_rest       = ( isset( $post_type->show_in_rest ) && $post_type->show_in_rest ) ? true : false;
        $supports_thumbnail = post_type_supports( $post_type_name, 'thumbnail' );
        // Only proceed if the post type is set to be accessible over the REST API
        // and supports featured images.
        if ( $show_in_rest && $supports_thumbnail ) {
            // Compatibility with the REST API v2 beta 9+
            if ( function_exists( 'register_rest_field' ) ) {
                register_rest_field( $post_type_name,
                    'featured_image',
                    array(
                        'get_callback' => 'custom_better_rest_api_featured_images_get_field',
                        'schema'       => null,
                    )
                );
            } elseif ( function_exists( 'register_api_field' ) ) {
                register_api_field( $post_type_name,
                    'featured_image',
                    array(
                        'get_callback' => 'custom_better_rest_api_featured_images_get_field',
                        'schema'       => null,
                    )
                );
            }
        }
    }
}

function custom_better_rest_api_featured_images_get_field( $object, $field_name, $request ) {
    // Only proceed if the post has a featured image.
    if ( ! empty( $object['featured_media'] ) ) {
        $image_id = (int)$object['featured_media'];
    } elseif ( ! empty( $object['featured_image'] ) ) {
        $image_id = (int)$object['featured_image'];
    } else {
        return null;
    }
    $image = get_post( $image_id );
    if ( ! $image ) {
        return null;
    }
    // This is taken from WP_REST_Attachments_Controller::prepare_item_for_response().
    $featured_image['id']            = $image_id;
    $featured_image['media_type']    = wp_attachment_is_image( $image_id ) ? 'image' : 'file';
    $featured_image['media_details'] = wp_get_attachment_metadata( $image_id );
    $featured_image['post']          = ! empty( $image->post_parent ) ? (int) $image->post_parent : null;
    $featured_image['source_url']    = wp_get_attachment_url( $image_id );
    if ( empty( $featured_image['media_details'] ) ) {
        $featured_image['media_details'] = new stdClass;
    } elseif ( ! empty( $featured_image['media_details']['sizes'] ) ) {
        // $img_url_basename = wp_basename( $featured_image['source_url'] );
        foreach ( $featured_image['media_details']['sizes'] as $size => &$size_data ) {
            $image_src = wp_get_attachment_image_src( $image_id, $size );
            if ( ! $image_src ) {
                continue;
            }
            $size_data['source_url'] = $image_src[0];
        }
    } else {
        $featured_image['media_details']['sizes'] = new stdClass;
    }
    return apply_filters( 'better_rest_api_featured_image', $featured_image, $image_id );
}

add_action( 'init', 'custom_better_rest_api_featured_images_init', 12 );

add_action( 'rest_api_init', function () {
    register_rest_route( 'wp/v2', '/token', array(
        'methods' => 'GET',
        'callback' => 'get_token',
    ) );
} );

function get_token( WP_REST_Request $request ) {
    $api_key = $request->get_header('api_key');
    $unique_id = $request->get_header('unique_id');
    $is_valid = strcmp($api_key, API_KEY) == 0 && !empty($unique_id);

    if($is_valid) {
        $data = array('status' => 'success');
    } else {
        $data = array('status' => 'error');
    }

    $response = new WP_REST_Response( $data );

   if($is_valid) {
        $response->header( 'token',  create_token($unique_id));
    } else {
        $response->set_status( 401 );
    }

    return $response;
}

function create_token($unique_id) {
//    session_start();
    $random_string = generateRandomString();
    $_SESSION[$unique_id] = $random_string;
    return $random_string;
}

function generateRandomString($length = 10) {
    return hash('sha256', RANDOM_HASH . strtotime('now'), false);
}

function check_token($token) {
//    session_start();
    $session_avail = true; //TODO-Change this to false.

//    if(!is_null($_SESSION)) {
//        foreach ($_SESSION as $key=>$val) {
//            if(strcmp($token, $val) == 0) {
//                $session_avail = true;
//                break;
//            }
//        }
//    }

    return $session_avail;
}
