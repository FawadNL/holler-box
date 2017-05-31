<?php
/**
 * Admin UI, register CPT and meta
 * @since       0.1.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'SB_Automation_Admin' ) ) {

    /**
     * SB_Automation_Admin class
     *
     * @since       0.2.0
     */
    class SB_Automation_Admin extends SB_Automation {

        /**
         * @var         SB_Automation_Admin $instance The one true SB_Automation_Admin
         * @since       0.2.0
         */
        private static $instance;
        public static $errorpath = '../php-error-log.php';
        // sample: error_log("meta: " . $meta . "\r\n",3,self::$errorpath);

        /**
         * Get active instance
         *
         * @access      public
         * @since       0.2.0
         * @return      object self::$instance The one true SB_Automation_Admin
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new SB_Automation_Admin();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       0.2.0
         * @return      void
         *
         *
         */
        private function hooks() {

            add_action( 'admin_menu', array( $this, 'settings_page' ) );
            add_action( 'init', array( $this, 'register_cpt' ) );
            add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            add_filter('manage_edit-sb_notification_columns', array( $this, 'notification_columns' ) );
            add_action( 'manage_sb_notification_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
            add_action(  'transition_post_status',  array( $this, 'save_default_meta' ), 10, 3 );

        }

        /**
         * Scripts and styles
         *
         * @access      public
         * @since       0.1
         * @return      void
         */
        public function enqueue_scripts() {

            // Date picker: https://gist.github.com/slushman/8fd9e1cc8161c395ec5b

            // Color picker: https://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/
            wp_enqueue_style( 'sb-admin', SB_Automation_URL . 'assets/css/sb-admin.css', array( 'wp-color-picker' ), SB_Automation_VER );

            wp_enqueue_script( 'sb-admin', SB_Automation_URL . 'assets/js/sb-admin.js', array( 'wp-color-picker', 'jquery-ui-datepicker' ), SB_Automation_VER, true );
            
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       0.1
         * @return      void
         */
        public function load_textdomain() {

            load_plugin_textdomain( 'sb-automation' );
            
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       0.1
         */
        public function settings_page() {

            add_submenu_page( 'edit.php?post_type=sb_notification', 'SB Automation Settings', 'Settings', 'manage_options', 'sb_automation', array( $this, 'render_settings') );
            
        }

        /**
         * Add settings
         *
         * @access      public
         * @since       0.1
         */
        public function render_settings() {

            if( isset( $_POST['sb_email_provider'] ) ) {
                update_option( 'sb_email_provider', $_POST['sb_email_provider'] );
            }

            ?>
            <div id="sb-automation-wrap" class="wrap">          

            <h2><?php _e('Settings', 'sb-automation'); ?></h2>

            <form method="post" action="edit.php?post_type=sb_notification&page=sb_automation">

                <p>To subscribe email opt-ins to your list, choose your provider below. For integration instructions, please see our documentation.</p>
                
                <input type="radio" name="sb_email_provider" value="none" <?php checked("none", get_option('sb_email_provider', 'none' ), true); ?> />
                None<br>
                <input type="radio" name="sb_email_provider" value="mailchimp" <?php checked("mailchimp", get_option('sb_email_provider' ), true); ?> /> MailChimp<br>
                <input type="radio" name="sb_email_provider" value="convertkit" <?php checked("convertkit", get_option('sb_email_provider' ), true); ?> /> Convertkit for WordPress plugin <a href="https://wordpress.org/plugins/convertkit/" target="_blank">(plugin link)</a><br>

            <?php submit_button(); ?>

            </form>

            </div>
            <?php
            
        }

        /**
         * List notifications (not used)
         *
         * @access      public
         * @since       0.1
         */
        public function get_list() {

            $args = array( 'post_type' => 'sb_notification' );
            // The Query
            $the_query = new WP_Query( $args );

            $output = '';

            // The Loop
            if ( $the_query->have_posts() ) {

                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    $id = get_the_id();
                    $output .= '<li class="sb-item">';
                    $output .= '<span class="sb-col sb-item-title"><a href="' . admin_url() . 'admin.php?page=sb_automation&view=single&id=' . $id . '">' . get_the_title() . '</a></span>';
                    $output .= '<span class="sb-col"><label class="sb-switch"><input type="checkbox"><div class="sb-slider sb-round"></div></label></span>';                   
                    $output .= '<span class="sb-col">24</span>';
                    $output .= '<span class="sb-col">' . get_the_date() . '</span>';
                    $output .= '</li>';

                    //$output .= get_post_meta( get_the_id() );
                }

                /* Restore original Post Data */
                wp_reset_postdata();
            } else {
                $output = '';
            }

            return $output;
        }

        /**
         * Add columns
         *
         * @access      public
         * @since       0.1
         * @return      void
         */
        public function notification_columns( $columns ) {
            $date = $columns['date'];
            unset($columns['date']);
            $columns["interactions"] = "Interactions";
            $columns["active"] = "Active";
            $columns['date'] = $date;
            return $columns;
        }

        /**
         * Column content
         *
         * @access      public
         * @since       0.1
         * @return      void
         */
        public function custom_columns( $column, $post_id ) {

            switch ( $column ) {
                case 'interactions':
                    echo get_post_meta( $post_id, 'sb_interactions', 1);
                    break;
                case 'active':
                    echo '<label class="sb-switch"><input data-id="' . $post_id . '" type="checkbox" value="1" ' . checked(1, get_post_meta( $post_id, 'sb_active', true ), false) . ' /><div class="sb-slider sb-round"></div></label>';
                    break;
            }

        }

        // Register sb_notification post type
        public function register_cpt() {

            $labels = array(
                'name'              => __( 'SB Notifications', 'sb-automation' ),
                'singular_name'     => __( 'Notification', 'sb-automation' ),
                'menu_name'         => __( 'SB Notifications', 'sb-automation' ),
                'name_admin_bar'        => __( 'Notification', 'sb-automation' ),
                'add_new'           => __( 'Add New', 'sb-automation' ),
                'add_new_item'      => __( 'Add New Notification', 'sb-automation' ),
                'new_item'          => __( 'New Notification', 'sb-automation' ),
                'edit_item'         => __( 'Edit Notification', 'sb-automation' ),
                'view_item'         => __( 'View Notification', 'sb-automation' ),
                'all_items'         => __( 'All Notifications', 'sb-automation' ),
                'search_items'      => __( 'Search Notifications', 'sb-automation' ),
                'parent_item_colon' => __( 'Parent Notifications:', 'sb-automation' ),
                'not_found'         => __( 'No Notifications found.', 'sb-automation' ),
                'not_found_in_trash' => __( 'No Notifications found in Trash.', 'sb-automation' )
            );

            $args = array(
                'labels'                => $labels,
                'public'                => true,
                'publicly_queryable' => false,
                'show_ui'           => true,
                'show_in_nav_menus' => false,
                'show_in_menu'      => true,
                'show_in_rest'      => false,
                'query_var'         => true,
                // 'rewrite'           => array( 'slug' => 'sb_notifications' ),
                'capability_type'   => 'post',
                'has_archive'       => true,
                'hierarchical'      => true,
                //'menu_position'     => 50,
                //'menu_icon'         => 'dashicons-welcome-add-page',
                'supports'          => array( 'title', 'editor' ),
                'show_in_customizer' => false,
                'register_meta_box_cb' => array( $this, 'notification_meta_boxes' )
            );

            register_post_type( 'sb_notification', $args );
        }

        /**
         * Add Meta Box
         *
         * @since     0.1
         */
        public function notification_meta_boxes() {

            add_meta_box(
                'display_meta_box',
                __( 'Display', 'sb-automation' ),
                array( $this, 'display_meta_box_callback' ),
                'sb_notification',
                'normal',
                'high'
            );

            add_meta_box(
                'settings_meta_box',
                __( 'Advanced Settings', 'sb-automation' ),
                array( $this, 'settings_meta_box_callback' ),
                'sb_notification',
                'normal',
                'high'
            );

            add_meta_box(
                'preview_meta_box',
                __( 'Preview', 'sb-automation' ),
                array( $this, 'preview_meta_box_callback' ),
                'sb_notification',
                'side'
            );

        }

        /**
         * Display appearance meta box
         *
         * @since     0.1
         */
        public function display_meta_box_callback( $post ) {

            ?>

            <?php wp_nonce_field( basename( __FILE__ ), 'sb_notification_meta_box_nonce' ); ?>

            <p>
                <label for="item_type"><?php _e( 'Choose a display type. Settings automatically configure based on your selection to easy defaults. For more customization, view advanced settings below.', 'sb-automation' ); ?></label>
            </p>
            <p>
                <input type="radio" name="item_type" value="default" <?php checked( "default", get_post_meta( $post->ID, 'item_type', 1 ) ); ?> />
                <?php _e( 'Simple message', 'sb-automation' ); ?>
                <input type="radio" id="show_optin" name="item_type" value="optin" <?php checked( "optin", get_post_meta( $post->ID, 'item_type', 1 ) ); ?> />
                <?php _e( 'Email opt-in', 'sb-automation' ); ?>
                <input type="radio" name="item_type" value="chatbox" <?php checked("chatbox", get_post_meta( $post->ID, 'item_type', true ), true); ?> />
                <?php _e( 'Chat box', 'sb-automation' ); ?>
                <input type="radio" name="item_type" value="quickie" <?php checked("quickie", get_post_meta( $post->ID, 'item_type', true ), true); ?> />
                <?php _e( 'Quickie', 'sb-automation' ); ?>
                <div id="show-email-options">

                <?php if( !get_option('sb_email_provider') || get_option('sb_email_provider') === 'none' ) : ?>

                    <label for="opt_in_message"><?php _e( 'Message', 'sb-automation' ); ?></label>
                    <input class="widefat" type="text" name="opt_in_message" id="opt_in_message" value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_message', true ) ); ?>" size="20" />

                    <label for="opt_in_placeholder"><?php _e( 'Placeholder', 'sb-automation' ); ?></label>
                    <input class="widefat" type="text" name="opt_in_placeholder" id="opt_in_placeholder" value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_placeholder', true ) ); ?>" size="20" />

                    <label for="opt_in_send_to"><?php _e( 'Send to email', 'sb-automation' ); ?></label>
                    <input class="widefat" type="email" name="opt_in_send_to" id="opt_in_send_to" value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_send_to', true ) ); ?>" size="20" />

                <?php endif; ?>

                <label for="opt_in_confirmation"><?php _e( 'Confirmation Message', 'sb-automation' ); ?></label>
                <input class="widefat" type="text" name="opt_in_confirmation" id="opt_in_confirmation" value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_confirmation', true ) ); ?>" size="20" />

                </div>
            </p>

            <p>
                <label for="position"><?php _e( 'Position' ); ?></label>
            </p>
            <p>
                <input type="radio" name="position" value="sb-bottomright" <?php checked( "sb-bottomright", get_post_meta( $post->ID, 'position', 1 ) ); ?> />
                <?php _e( 'Bottom right', 'sb-automation' ); ?>
                <input type="radio" name="position" value="sb-bottomleft" <?php checked( "sb-bottomleft", get_post_meta( $post->ID, 'position', 1 ) ); ?> />
                <?php _e( 'Bottom left', 'sb-automation' ); ?>
                <input type="radio" name="position" value="sb-topright" <?php checked( "sb-topright", get_post_meta( $post->ID, 'position', 1 ) ); ?> />
                <?php _e( 'Top right', 'sb-automation' ); ?>
                <input type="radio" name="position" value="sb-topleft" <?php checked( "sb-topleft", get_post_meta( $post->ID, 'position', 1 ) ); ?> />
                <?php _e( 'Top left', 'sb-automation' ); ?>
            </p>

            <p>Button color</p>
            <input type="text" name="button_color1" value="<?php echo esc_html( get_post_meta( $post->ID, 'button_color1', true ) ); ?>" class="sb-automation-colors" data-default-color="#1191cb" />
            
            <p>Background color</p>
            <input type="text" name="bg_color" value="<?php echo esc_html( get_post_meta( $post->ID, 'bg_color', true ) ); ?>" class="sb-automation-colors" data-default-color="#ffffff" />

            <p>
                <input type="checkbox" id="sb_active" name="sb_active" value="1" <?php checked(1, get_post_meta( $post->ID, 'sb_active', true ), true); ?> />
                <label for="sb_active"><?php _e( 'Activate?', 'sb-automation' ); ?></label>
            </p>

        <?php }

        /**
         * Display settings meta box
         *
         * @since     0.1
         */
        public function settings_meta_box_callback( $post ) { ?>

            <h4><?php _e( 'What pages?', 'sb-automation' ); ?></h4>

            <p>
                <input type="radio" name="show_on" value="all" <?php if( get_post_meta( $post->ID, 'show_on', 1 ) === "all" ) echo 'checked="checked"'; ?>> All pages<br>
                <input type="radio" name="show_on" value="limited" <?php if( is_array( get_post_meta( $post->ID, 'show_on', 1 ) ) ) echo 'checked="checked"'; ?>> Certain pages<br>
                <div id="show-certain-pages">
                <p>Enter page/post IDs:</p>
                <input placeholder="Example: 2,25,311" class="widefat" type="text" name="sb_page_ids" id="sb_page_ids" value="<?php echo esc_attr( get_post_meta( $post->ID, 'sb_page_ids', true ) ); ?>" size="20" />
                </div>
            </p>

            <hr>

            <h4>Show to these visitors</h4>

            <p> 
                <input type="radio" name="logged_in" value="logged_in" <?php checked('logged_in', get_post_meta( $post->ID, 'logged_in', true ), true); ?>> Logged in only<br>
                <input type="radio" name="logged_in" value="logged_out" <?php checked('logged_out', get_post_meta( $post->ID, 'logged_in', true ), true); ?>> Logged out only<br>
                <input type="radio" name="logged_in" value="all" <?php checked('all', get_post_meta( $post->ID, 'logged_in', true ), true); ?>> All visitors<br>
            </p>
            <hr>
            <p><label for="visitor"><?php _e( 'New or returning', 'sb-automation' ); ?></label></p>
            <p>
                <input type="radio" name="new_or_returning" value="new" <?php checked('new', get_post_meta( $post->ID, 'new_or_returning', true ), true); ?>> New visitors only<br>
                <input type="radio" name="new_or_returning" value="returning" <?php checked('returning', get_post_meta( $post->ID, 'new_or_returning', true ), true); ?>> Returning visitors only<br>
                <input type="radio" name="new_or_returning" value="all" <?php checked('all', get_post_meta( $post->ID, 'new_or_returning', true ), true); ?>> All visitors<br>
            </p>
            <hr>
            <p>
                <label for="visitor"><?php _e( 'When should we show it?', 'sb-automation' ); ?></label>
            </p>
            <p>
                <input type="radio" name="display_when" value="immediately" <?php checked('immediately', get_post_meta( $post->ID, 'display_when', true ), true); ?>> Immediately<br>
                <input type="radio" name="display_when" value="delay" <?php checked('delay', get_post_meta( $post->ID, 'display_when', true ), true); ?>> Delay of <input type="number" class="sb-number-input" id="scroll_delay" name="scroll_delay" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'scroll_delay', true ) ); ?>" /> seconds<br>
                <input type="radio" name="display_when" value="scroll" <?php checked('scroll', get_post_meta( $post->ID, 'display_when', true ), true); ?>> User scrolls halfway down the page
            </p>
            <hr>
            <p>
                <label for="hide_after"><?php _e( 'After it displays, when should it disappear?', 'sb-automation' ); ?></label>
            </p>
            <p>
                <input type="radio" name="hide_after" value="never" <?php checked('never', get_post_meta( $post->ID, 'hide_after', true ), true); ?>> When user clicks hide<br>
                <input type="radio" name="hide_after" value="delay" <?php checked('delay', get_post_meta( $post->ID, 'hide_after', true ), true); ?>> Delay of <input type="number" class="sb-number-input" id="hide_after_delay" name="hide_after_delay" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'hide_after_delay', true ) ); ?>" /> seconds<br>
            </p>
            <hr>
            <p>
                <label for="show_settings"><?php _e( 'How often should we show it to each visitor?', 'sb-automation' ); ?></label>
            </p>
            <p>
                <input type="radio" name="show_settings" value="always" <?php checked('always', get_post_meta( $post->ID, 'show_settings', true ), true); ?>> Every page load<br>
                <input type="radio" name="show_settings" value="hide_for" <?php checked('hide_for', get_post_meta( $post->ID, 'show_settings', true ), true); ?>> Show, then hide for <input type="number" class="sb-number-input" id="hide_for_days" name="hide_for_days" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'hide_for_days', true ) ); ?>" /> days<br>
            </p>
            <hr>
            <p>
                <input type="checkbox" name="expiration" value="1" <?php checked('1', get_post_meta( $post->ID, 'expiration', true ), true); ?>> Automatically deactivate on a certain date?<br>
                <input type="text" placeholder="05/28/2018" value="<?php echo get_post_meta( $post->ID, 'sb_until_date', true ); ?>" name="sb_until_date" id="sb-until-datepicker" class="sb-datepicker" />
            </p>
            <hr>
            <p>
                <input type="checkbox" id="hide_btn" name="hide_btn" value="1" <?php checked(1, get_post_meta( $post->ID, 'hide_btn', true ), true); ?> />
                <label for="hide_btn"><?php _e( 'Hide the floating button? (Appears when notification is closed)', 'sb-automation' ); ?></label>
            </p>
            <hr>
            <p><label for="avatar_email"><?php _e( 'Gravatar Email', 'sb-automation' ); ?></label></p>
            <p>
                <input type="text" class="widefat" name="avatar_email" size="20" value="<?php echo sanitize_email( get_post_meta( $post->ID, 'avatar_email', true ) ); ?>" /> 
            </p>

        <?php }

        /**
         * Display preview
         *
         * @since     0.1
         */
        public function preview_meta_box_callback( $post ) { ?>

            <!-- <div id="sb-floating-btn"><i class="icon icon-chat"></i></div> -->

            <div id="sb-notification-box">
                
                <div class="sb-box-rows">
                        <?php echo get_avatar('scott@apppresser.com', 50 ); ?>
                    <div class="sb-row" id="sb-first-row"></div>
                </div>

                <div id="sb-note-optin" class="sb-row sb-email-row">
                    <input type="email" name="email" id="sb-email-input" placeholder="Enter email" autocomplete="on" autocapitalize="off" />
                    <button class="sb-email-btn" id="sb-submit-email"><?php echo _e('Send', 'sb-automation' ); ?></button>
                </div>
                
                <div id="sb-chat" class="sb-hide">
                    
                    <div class="sb-row sb-text">
                        <input type="text" id="sb-text-input" placeholder="Type your message" />
                        <i id="sb-submit-text" class="icon icon-mail"></i>
                    </div>
                </div>

                <span id="sb-powered-by"><a href="http://scottbolinger.com" target="_blank">Scottomator</a></span>
                <div class="sb-close"><i class="icon icon-cancel"></i></div>
 
            </div>

        <?php }

        /**
         * Save meta box defaults when new post is created
         *
         * @since     0.1
         */
        public function save_default_meta( $new_status, $old_status, $post ) {
            
            if ( $old_status === 'new' && $new_status === 'auto-draft' && $post->post_type === 'sb_notification' ) {

                // if we already have a setting, bail
                if( !empty( get_post_meta( $post->ID, 'item_type' ) ) )
                    return;

                // set some defaults
                update_post_meta( $post->ID, 'item_type', 'default' );
                update_post_meta( $post->ID, 'show_on', 'all' );
                update_post_meta( $post->ID, 'logged_in', 'all' );
                update_post_meta( $post->ID, 'avatar_email', get_option('admin_email') );
                update_post_meta( $post->ID, 'display_when', 'delay' );
                update_post_meta( $post->ID, 'scroll_delay', 1 );
                update_post_meta( $post->ID, 'show_settings', 'always' );
                update_post_meta( $post->ID, 'new_or_returning', 'all' );
                update_post_meta( $post->ID, 'hide_after', 'never' );
                update_post_meta( $post->ID, 'hide_after_delay', 3 );
                update_post_meta( $post->ID, 'hide_for_days', 1 );
                update_post_meta( $post->ID, 'sb_active', '1' );
                update_post_meta( $post->ID, 'position', 'sb-bottomright' );

            }

        }

        /**
         * Save meta box settings
         *
         * @since     0.1
         */
        public function save_meta_boxes( $post_id ) {

            // nonce check
            if ( !isset( $_POST['sb_notification_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['sb_notification_meta_box_nonce'], basename( __FILE__ ) ) )
                return $post_id;

            $post_type = get_post_type($post_id);

            // If this isn't our post type, don't update it.
            if ( "sb_notification" != $post_type ) 
                return;

            // Check if the current user has permission to edit the post.
            if ( !current_user_can( 'edit_post', $post_id ) )
                return $post_id;

            $keys = array(
                'item_type', 
                'opt_in_message',
                'opt_in_confirmation',
                'opt_in_placeholder',
                'opt_in_send_to',
                'button_color1',
                'bg_color',
                'sb_page_ids',
                'logged_in',
                'new_or_returning',
                'avatar_email',
                'show_settings',
                'hide_for_days',
                'hide_after',
                'hide_after_delay',
                'sb_active',
                'display_when',
                'scroll_delay',
                'position',
                'hide_btn' );

            global $allowedposttags;
            $allowedposttags["iframe"] = array(

                'align' => true,
                'width' => true,
                'height' => true,
                'frameborder' => true,
                'name' => true,
                'src' => true,
                'id' => true,
                'class' => true,
                'style' => true,
                'scrolling' => true,
                'marginwidth' => true,
                'marginheight' => true,
                'allowfullscreen' => true

            );

            // sanitize data
            foreach ($keys as $key => $value) {
                if( empty( $_POST[ $value ] ) ) {
                    delete_post_meta( $post_id, $value );
                    continue;
                }
                $sanitized = wp_kses( $_POST[ $value ], $allowedposttags);
                update_post_meta( $post_id, $value, $sanitized );
            }

            // keys that need special handling
            if( empty( $_POST[ 'show_on' ] ) ) {
                delete_post_meta( $post_id, 'show_on' );
            } elseif( $_POST[ 'show_on' ] === 'limited' && !empty( $_POST[ 'sb_page_ids' ] ) ) {

                // sanitize, remove whitespace, explode into array
                $sanitized = sanitize_text_field( $_POST[ 'sb_page_ids' ] );
                $sanitized = preg_replace('/\s+/', '', $sanitized);
                update_post_meta( $post_id, 'show_on', explode( ',', $sanitized ) );

            } else {
                update_post_meta( $post_id, 'show_on', $_POST[ 'show_on' ] );
            }

            // notification expiration date
            if( empty( $_POST[ 'expiration' ] ) ) {
                delete_post_meta( $post_id, 'expiration' );
                delete_post_meta( $post_id, 'sb_until_date' );
            } elseif( $_POST[ 'expiration' ] === '1' && !empty( $_POST[ 'sb_until_date' ] ) ) {

                $sanitized = wp_kses( $_POST[ 'sb_until_date' ], $allowedposttags);
                update_post_meta( $post_id, 'sb_until_date', $sanitized );
                update_post_meta( $post_id, 'expiration', '1' );

            } else {
                update_post_meta( $post_id, 'expiration', $_POST[ 'expiration' ] );
            }
            
        }

    }

    $sb_automation_admin = new SB_Automation_Admin();
    $sb_automation_admin->instance();

} // end class_exists check