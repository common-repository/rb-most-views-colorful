<?php  
/**
 * Plugin Name: Rb Most Views Colorful
 * Author: rambu
 * Author URI: http://rambshop.com
 * Description: Show the list of posts that was ordered by views count or comment count with useful color background for each post title. You can choose colors by yoursefl with color picker, pick posttype and number posts per page.
 * Tags: widget
 * Version: 1.0.1
 */
class Rb_Most_Views_Count_Widget extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     **/
    function Rb_Most_Views_Count_Widget() {
        $widget_ops = array( 'classname' => 'rb-most-view-count', 'description' => __('Show mosts views posts','rbmvc' ) );
        $this->WP_Widget( 'rb-most-view-count', 'Rb Most Views', $widget_ops );

        add_action('wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts') );
        add_action('admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts') );
        add_action( 'wp_head', array( &$this, 'start_views_count' ) );
        add_action('publish_post', array(&$this, 'add_views_count') );
        add_action('plugins_loaded', array( &$this, 'lang') );
    }

    /**
     * Outputs the HTML for this widget.
     *
     * @param array  An array of standard parameters for widgets in this theme
     * @param array  An array of settings for this widget instance
     * @return void Echoes it's output
     **/
    function widget( $args, $instance ) {
        extract( $args, EXTR_SKIP );
        echo $before_widget;
        echo $before_title;
        echo $instance['title']; // Can set this with a widget option, or omit altogether
        echo $after_title;
        $instance = wp_parse_args( $instance, array( 
            'post_type' => 'post',
            'title' => '',
            'most-view-type' => 'views',
            'number'    => 5,
            'colors' => array(),
            'view_count' => true,
            'width_compare' => true
        ) );
        $args = array(
            'post_type' => $instance['post_type'],
            'post_status' => 'publish',
            'order'  => 'DESC',
            'ignore_sticky_posts' => 1
        );

        if( strpos($instance['number'], ',') ) {
            $args['post__in'] = explode(',', $instance['number']);
        } else {
            $args['posts_per_page'] = (int) $instance['number'];
        }

        if( $instance['most-view-type'] == 'views' ) {
            $args = array_merge( $args , array(
                'meta_key'  => '_rbmvc_views',
                'orderby' => 'meta_value_num',
                'meta_query' => array(
                   'relation' => 'OR',
                    array(
                        'key' => '_rbmvc_views',
                        'value' => 0,
                        'compare' => 'NOT EXISTS' // doesn't work
                    )
                )
            ) );
        } else {
            $args = array_merge( $args, array(
                'orderby' => 'comment_count'
            ) );
        }

        $mosts_view = new WP_Query( $args );
        
        if( $mosts_view->have_posts() ) {
            echo '<ul class="rbmvc">';
            $i = 0;
            $total = 0;
            while ($mosts_view->have_posts()) { $mosts_view->the_post();
                $width = '100%';
                if( $instance['most-view-type'] == 'views' ) {
                    $views_count = (int)get_post_meta( get_the_ID(), '_rbmvc_views', true );
                    $views_count = $views_count ? $views_count : 0;
                } else {
                    $comments_counts = wp_count_comments( get_the_ID() );
                    $views_count = $comments_counts->approved;
                }
                if( $i == 0 ) {  
                    $total  = $views_count; 
                    if( $total == 0 ) {
                        break;
                    }
                } else {
                    $width = 60 + ( ($views_count * 100 / $total) * 40 / 100 ) . '%';
                }
                if( $width == 0 ) {
                    $width = '60%';
                }
                $i++;
                echo '<li style="';
                if( $instance['width_compare'] ) {
                    echo 'width:'.$width.';';
                }
                echo 'background-color:'.$instance['colors'][$i].'"><span class="color-box" style=""><a href="'.get_permalink().'" title="'.get_the_title().'">'.get_the_title().'</a>';
                if( $instance['view_count'] ) {
                    echo '<span class="views-count">'.$views_count.'</span>';
                }
                echo '</span></li>';
            }
            echo '</ul>';
        }

        echo $after_widget;
    }

    /**
     * Deals with the settings when they are saved by the admin. Here is
     * where any validation should be dealt with.
     *
     * @param array  An array of new settings as submitted by the admin
     * @param array  An array of the previous settings
     * @return array The validated and (if necessary) amended settings
     **/
    function update( $new_instance, $old_instance ) {

        // update logic goes here
        if( !isset($new_instance['view_count']) ) {
            $new_instance['view_count'] = false;
        }
        if( !isset($new_instance['width_compare']) ) {
            $new_instance['width_compare'] = false;
        }
        $updated_instance = $new_instance;
        return $updated_instance;
    }

    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @param array  An array of the current settings for this widget
     * @return void Echoes it's output
     **/
    function form( $instance ) {
        $instance = wp_parse_args( $instance, array( 
            'post_type' => 'post',
            'title' => '',
            'most-view-type' => 'views',
            'number'    => 5,
            'colors' => array(),
            'view_count' => true,
            'width_compare' => true
        ) );

        ?>
        <p><label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title','rbmvc') ?>
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?>" value="<?php echo $instance['title'] ?>" ></label></p>

        <p><label for="<?php echo $this->get_field_id('post_type') ?>"><?php _e('Post Type','rbmvc') ?>
            <select class="widefat" name="<?php echo $this->get_field_name('post_type') ?>" id="<?php echo $this->get_field_id('post_type') ?>">
            <?php  
                $post_typies = get_post_types();
                foreach ($post_typies as $post_type) {
                    echo '<option '.( $instance['post_type']  == $post_type ? 'selected="selected"' : '' ).' value="'.$post_type.'">'.$post_type.'</option>';
                }
            ?>
            </select>
            
        </p>
        <p><label for="<?php echo $this->get_field_id('most-view-type') ?>"><?php _e('Most View by') ?></label>
            <select class="widefat" name="<?php echo $this->get_field_name('most-view-type') ?>" id="<?php echo $this->get_field_id('most-view-type') ?>">
                <option <?php selected( $instance['most-view-type'], 'views' ); ?> value="views"><?php _e('Views') ?></option>
                <option <?php selected( $instance['most-view-type'], 'comments' ); ?> value="comments"><?php _e('Comments') ?></option>
            </select>
        </p>
        <p><label for="<?php echo $this->get_field_id('number') ?>"><?php _e('Posts','rbmvc') ?>
            <input type="text" class="widefat" name="<?php echo $this->get_field_name('number') ?>" id="<?php echo $this->get_field_id('number') ?>" value="<?php echo $instance['number'] ?>" ></label></p>
        <div class="colors">
            <?php  
                if( strpos($instance['number'], ',') ) {
                    $number = count(explode(',', $instance['number']));
                } else {
                    $number = (int) $instance['number'];
                }
                if( $number > 0 ) {
                    for ($i=1; $i <= $number; $i++) { 
                        echo '<p>'.__('Color','dwqa').' '.$i.'<br /><input type="text" class="color-picker" name="'.$this->get_field_name('colors').'['.$i.']" id="'.$this->get_field_id('colors').'_'.$i.'" value="'.(isset($instance['colors'][$i]) ? $instance['colors'][$i] : '#ffffff' ).'" /></p>';
                        
                    }
                }
            ?>
        </div>
            
        <script>
            jQuery(document).ready(function($) {
                $('.color-picker').wpColorPicker();
            });
        </script>

        <p><label for="<?php echo $this->get_field_id('view_count') ?>"><input type="checkbox" name="<?php echo $this->get_field_name('view_count') ?>" value="true" id="<?php echo $this->get_field_id('view_count') ?>" <?php if( $instance['view_count'] ) { echo 'checked="checked"'; } ?>> <?php _e('View count','rbmvc') ?></label></p>

        <p><label for="<?php echo $this->get_field_id('width_compare') ?>"><input type="checkbox" name="<?php echo $this->get_field_name('width_compare') ?>" value="true" id="<?php echo $this->get_field_id('width_compare') ?>" <?php if( $instance['width_compare'] ) { echo 'checked="checked"'; } ?>> <?php _e('Width compare','rbmvc') ?></label></p>
        <?php
    }

    function admin_enqueue_scripts(){
        global $pagenow;
        if( 'widgets.php' == $pagenow ) {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_style( 'wp-color-picker' );
        }
    }

    function wp_enqueue_scripts(){
        $version = 1384829669;
        wp_enqueue_style( 'rbmvc', plugin_dir_url(__FILE__) . 'assets/css/rbmvc.css', false, $version );
    }

    function start_views_count(){
        global $post;
        if( is_single() ) {
            $refer = wp_get_referer();
            if( $refer != get_permalink( $post->ID ) ) {

                $views = get_post_meta( $post->ID, '_rbmvc_views', true );
                if( ! $views ) {
                    $views = 1;
                } else {
                    $views = ( (int) $views ) + 1;
                }
                update_post_meta( $post->ID, '_rbmvc_views', $views );
            }
        }
    }

    function add_views_count( $post_id ){
        update_post_meta( $post_id, '_rbmvc_views', 1 );
    }

    //load plugin languages
    function lang() {
        load_plugin_textdomain( 'rbmvc', false, plugin_dir_path(__FILE__) . 'languages/' ); 
    }

}

add_action( 'widgets_init', create_function( '', "register_widget( 'Rb_Most_Views_Count_Widget' );" ) );

?>