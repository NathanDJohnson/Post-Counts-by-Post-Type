<?php
	/*
	 * Plugin Name: Post Counts by Post Type
	 * Plugin URI: https://github.com/NathanDJohnson/Post-Counts-by-Post-Type
	 * Description: Adds Post Counts for Custom Post Types in User List
	 * Version: 1.0.0
	 * Author: Nathan Johnson
	 * Author URI: http://atmoz.org/
	 * License: GPL2
	 */

add_filter( 'manage_users_columns' , 'pcbpt_add_extra_user_column' );
function pcbpt_add_extra_user_column( $columns ) {
    /**
     *   Hook into manage_users_columns and add columns for custom post types
     **/
    $args = array(
      'public'   => true,
      '_builtin' => false,
    );
    $post_types = get_post_types( $args , $output = 'objects' );

    foreach( $post_types as $post_type )
        $post_names[ $post_type->name  . ' num' ] = $post_type->label;

    return array_merge( $columns, $post_names );
}

add_action( 'manage_users_custom_column' , 'pcbpt_manage_users_custom_column' , 10 , 3 );
function pcbpt_manage_users_custom_column( $custom_column , $column_name , $user_id ) {
    /**
     *   Hook into manage_users_custom_column and add number of 
     *   posts for each custom post type.
     **/
    $args = array(
      'public'   => true,
      '_builtin' => false,
    );
    $post_types = get_post_types( $args );

    $slug = str_replace( ' num', '', $column_name );

    if ( in_array( $slug, $post_types ) )
      return pcbpt_get_author_post_type_counts( $slug , $user_id );
    return $custom_column;
}

function pcbpt_get_author_post_type_counts( $post_type , $user_id ) {
    /**
     *   Helper function to count the number of posts for each 
     *   custom post type.
     **/
    global $wpdb;

    $posts = $wpdb->get_results( $wpdb->prepare( 
	"
	SELECT      post_type,
                    post_author,
                    COUNT(*) AS post_count
	FROM        $wpdb->posts
	WHERE       post_type = %s
                    AND post_author = %s
                    AND post_type NOT IN ('revision','nav_menu_item')
                    AND post_status IN ('publish','pending')
        GROUP BY    post_type,
                    post_author
	",
	$post_type,
        $user_id
    ) );

    foreach($posts as $post) {
      $post_type_object = get_post_type_object( $post->post_type );

      if( isset( $post->post_count ) && $post->post_count > 0 )
        return $post->post_count;
      return 0;
    }
    return 0;
}
