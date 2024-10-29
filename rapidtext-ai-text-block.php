<?php
/*
* Plugin Name: RapidTextAI - AI Text Blocks, Elementor Widget, WP Bakery Widget, Generate Articles
* Description: Add an AI-powered text block using RapidTextAI.com to WP Bakery and elementor, generate articles for your site.
* Version: 1.6.1
* Author: Rapidtextai.com
* Text Domain: rapidtextai
* License: GPL-2.0-or-later
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
require_once plugin_dir_path( __FILE__ ) . 'rapidtext-ai-meta-box.php';



function rapidtextai_register_gutenberg_block() {
    wp_register_script(
        'rapidtextai-block-editor',
        plugins_url('block/rapidtextai-block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'block/rapidtextai-block.js')
    );
    
    register_block_type('rapidtextai/ai-text-block', array(
        'editor_script' => 'rapidtextai-block-editor',
    ));
}
add_action('init', 'rapidtextai_register_gutenberg_block');



// AJAX handler for generating AI content
add_action('wp_ajax_rapidtextai_generate_content', 'rapidtextai_generate_content_callback');
add_action('wp_ajax_nopriv_rapidtextai_generate_content', 'rapidtextai_generate_content_callback');

function rapidtextai_generate_content_callback() {
    if (isset($_POST['prompt']) && isset($_POST['post_id']) && isset($_POST['instance_id'])) {
        $prompt = sanitize_text_field($_POST['prompt']);
        $postid = intval($_POST['post_id']);
        $instance_id = sanitize_text_field($_POST['instance_id']);

        // Call the existing function to generate text
        $generated_text = rapidtextai_generate_text($prompt, $postid, $instance_id);

        // Send the generated content as a response
        if ($generated_text) {
            wp_send_json_success(array('generated_text' => $generated_text));
        } else {
            wp_send_json_error('Error generating content');
        }
    } else {
        wp_send_json_error('Invalid request');
    }
}


add_action('wp_ajax_rapidtextai_generate_content_block', 'rapidtextai_generate_content_callback_block');
add_action('wp_ajax_nopriv_rapidtextai_generate_content_block', 'rapidtextai_generate_content_callback_block');

function rapidtextai_generate_content_callback_block() {
    if (isset($_POST['prompt'])) {
        $prompt = sanitize_text_field($_POST['prompt']);
        // Call the AI text generation function
        $generated_text = rapidtextai_generate_text($prompt, 0, '');

        // Return the generated content
        if ($generated_text) {
            wp_send_json_success(array('generated_text' => $generated_text));
        } else {
            wp_send_json_error('Error generating content');
        }
    } else {
        wp_send_json_error('Invalid request');
    }
}


function rapidtextai_is_wp_bakery_active() {
    return class_exists('Vc_Manager');
}



function rapidtextai_settings_menu() {
    add_menu_page(
        'Rapidtextai Settings',
        'Rapidtextai Settings',
        'manage_options',
        'rapidtextai-settings',
        'rapidtextai_settings_page'
    );
}
add_action('admin_menu', 'rapidtextai_settings_menu');



function rapidtextai_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Retrieve the current API key
    $current_api_key = get_option('rapidtextai_api_key', '');

    ?>
    <div class="wrap">
        <h2><?php esc_html_e('RapidTextAI Settings', 'rapidtextai'); ?></h2>
        <form method="post" id="rapidtextai_auth_form">
            <?php wp_nonce_field('rapidtextai_api_key_nonce', 'rapidtextai_api_key_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php esc_html_e('RapidTextAI Authentication', 'rapidtextai'); ?></label></th>
                    <td>
                        <button type="button" id="rapidtextai_auth_button" class="button button-primary"><?php esc_html_e('Authenticate with RapidTextAI', 'rapidtextai'); ?></button>
                        <p id="rapidtextai_status_message"></p>
                        <?php if (!empty($current_api_key)) { ?>
                            <p><?php esc_html_e('API Key is already set. You can authenticate again to refresh the key.', 'rapidtextai'); ?></p>
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Status', 'rapidtextai'); ?></label></th>
                    <td>
                        <div id="rapidtextai_status">Loading...</div>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#rapidtextai_auth_button').on('click', function(e) {
                e.preventDefault();
                var authWindow = window.open('https://app.rapidtextai.com/log-in?action=popup', 'RapidTextAIAuth', 'width=500,height=600');
            });

            window.addEventListener('message', function(event) {
                // Only accept messages from the trusted RapidTextAI origin
                alert('Authenticated');
                if (event.origin === 'https://app.rapidtextai.com') {
                    var apiKey = event.data.api_key;
                    if (apiKey) {
                        $('#rapidtextai_status_message').html('Authentication successful! Saving API key...');

                        $.post(ajaxurl, {
                            action: 'rapidtextai_save_api_key',
                            api_key: apiKey,
                            _wpnonce: '<?php echo wp_create_nonce('rapidtextai_save_api_key_nonce'); ?>'
                        }, function(response) {
                            $('#rapidtextai_status_message').html(response.message);
                        });
                    }
                }
            });

            /** Get Response using API */
        });

        jQuery(document).ready(function($) {
            // Get the connect key from the input field
            var connectKey = '<?php echo $current_api_key; ?>';

            // Make the AJAX request using jQuery
            $.ajax({
                url: 'https://app.rapidtextai.com/api.php',
                type: 'GET',
                data: {
                    gigsixkey: connectKey
                },
                dataType: 'json',
                success: function(response_data) {
                    var output = '';

                    if (response_data.response_code) {
                        var code = response_data.response_code;

                        if (code == 1 || code == 2 || code == 4) {
                            output += '<table class="form-table">';
                            output += '<tr><th>Created</th><td>' + (code == 1 ? response_data.create_at : 'N/A') + '</td></tr>';
                            output += '<tr><th>Status</th><td>' + (code == 1 ? response_data.subscription_status : 'Trial') + '</td></tr>';
                            output += '<tr><th>Interval</th><td>' + (code == 1 ? response_data.subscription_interval : 'N/A') + '</td></tr>';
                            output += '<tr><th>Start</th><td>' + (code == 1 ? response_data.current_period_start : 'N/A') + '</td></tr>';
                            output += '<tr><th>End</th><td>' + (code == 1 ? response_data.current_period_end : 'N/A') + '</td></tr>';
                            output += '<tr><th>Requests</th><td>' + (code == 1 ? response_data.requests + '/ ∞' : response_data.requests + '/ 100') + '</td></tr>';
                            output += '</table>';
                        } else {
                            output = response_data.message;
                        }
                    } else {
                        output = 'Error retrieving data';
                    }

                    // Place the response in the div with id rapidtextai_status
                    $('#rapidtextai_status').html(output);
                },
                error: function() {
                    $('#rapidtextai_status').html('Error connecting to the server');
                }
            });
        });

    </script>
    <?php
}

add_action('wp_ajax_rapidtextai_save_api_key', 'rapidtextai_save_api_key');
function rapidtextai_save_api_key() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied.'));
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'rapidtextai_save_api_key_nonce')) {
        wp_send_json_error(array('message' => 'Nonce verification failed.'));
    }

    $api_key = sanitize_text_field($_POST['api_key']);
    update_option('rapidtextai_api_key', $api_key);

    wp_send_json_success(array('message' => 'API Key saved successfully.'));
}



/**
 * WP Bakery
 */
if(rapidtextai_is_wp_bakery_active()){
    function rapidtextai_ai_text_block_vc_element() {
        vc_map(array(
            'name' => __('AI Text Block', 'rapidtextai'),
            'base' => 'rapidtextai_ai_text_block',
            'category' => __('Content', 'rapidtextai'),
            'params' => array(
                array(
                    'type' => 'textarea',
                    'heading' => esc_html__('Prompt', 'rapidtextai'),
                    'param_name' => 'wpb_input_text',
                    'description' => esc_html__('Enter the prompt to generate AI text, i.e Write an about use section for my company which manufacture light bulbs', 'rapidtextai')
                ),
                array(
                    "type" => "textarea",
                    "heading" => esc_html__( "Prompt Output", 'rapidtextai'),
                    "param_name" => "wpb_input_text_output", 
                    'description' => esc_html__('Prompt response will be here, edit here if needed', 'rapidtextai'),
                ),
            ),
            'shortcode' => 'rapidtextai_ai_text_block_shortcode',
        ));
    }
    add_action('vc_before_init', 'rapidtextai_ai_text_block_vc_element');
    
    

    function rapidtextai_ai_text_block_shortcode($atts, $sc_content = null,$instance_id) {
        extract(shortcode_atts(array(
            'wpb_input_text' => '',
            'wpb_input_text_output' => '',
        ), $atts));

        $postid = get_the_ID();

        global $post;
        $new_value = '';

        $shortcode = 'rapidtextai_ai_text_block';

        // Define the attribute you want to update
        $attribute_to_update = 'wpb_input_text_output';
        $content = $post->post_content;
        // Use a regular expression to find all instances of the shortcode
        $pattern = get_shortcode_regex([$shortcode]);
        preg_match_all('/' . $pattern . '/s', $content, $matches);
    
        //echo 'matches rapidtextai_ai_text_block <pre>';print_r($matches);echo '</pre>';
        if (isset($matches[0]) && isset($atts['wpb_input_text']) && trim($atts['wpb_input_text']) != '') {
            foreach ($matches[0] as $shortcode_instance) {

                $attribute_pattern = '/' . $attribute_to_update . '=["\'](.*?)["\']/';
                preg_match($attribute_pattern, $shortcode_instance, $attribute_match);
                //echo '<pre>';print_r($attribute_match);echo '</pre>';

                // // Check if the attribute was found
                if (!isset($attribute_match[1])) {
                    $new_value = rapidtextai_generate_text($atts['wpb_input_text'],$postid,$instance_id);;                   
                    $updated_shortcode = str_replace('rapidtextai_ai_text_block','rapidtextai_ai_text_block wpb_input_text_output="'.$new_value.'"', $shortcode_instance);
                    $content = str_replace($shortcode_instance, $updated_shortcode, $content);
                }
            }
        }


        
        wp_update_post(array('ID'=>$postid,'post_content'=>$content));
        return isset($atts['wpb_input_text_output']) ? $atts['wpb_input_text_output'] : $new_value;
    } // func
    add_shortcode('rapidtextai_ai_text_block', 'rapidtextai_ai_text_block_shortcode');
}



/***
 * Elementor
 */

 // Hook to ensure Elementor is fully loaded before registering the widget
add_action('elementor/init', 'rapidtextai_is_elementor_active');


function rapidtextai_is_elementor_active(){
    // Register the custom widget
    add_action( 'elementor/widgets/widgets_registered', function() {
        class rapidtextai_AITextBlock_Elementor_Widget extends \Elementor\Widget_Base {

            public function get_name() {
                return 'rapidtextai-ai-text-block';
            }

            public function get_title() {
                return __('AI Text Block', 'rapidtextai');
            }

        

            protected function register_controls() {
                
                $this->start_controls_section(
                    'content_section',
                    [
                        'label' => esc_html__('Content', 'rapidtextai'),
                        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                    ]
                );

             
                $this->add_control(
                    'input_text',
                    [
                        'label' => esc_html__('Prompt', 'rapidtextai-ai-text-block-elementor'),
                        'type' => \Elementor\Controls_Manager::TEXTAREA,
                        'placeholder' => esc_html__('Write an about use section for my company which manufacture light bulbs.', 'rapidtextai-ai-text-block-elementor'),
                        'input_type' => 'text',
                        'label_block' => true,
                        'attributes' => [
                            'class' => 'rapidtextai-prompt-textarea',  // Custom class to identify the textarea
                        ],
                    ]
                );
                
             
                $this->add_control(
                    'input_text_output',
                    [
                        'label' => esc_html__( 'Prompt Output', 'rapidtextai-ai-text-block-elementor' ),
                        'description' => esc_html__('Prompt response will be here, edit here if needed', 'rapidtextai'),
                        'type' => \Elementor\Controls_Manager::TEXTAREA
                    ]
                );
           
           
                $this->end_controls_section();
            } // function


            public function render() {
                $postid = get_the_ID();
                $settings = $this->get_settings_for_display();

                $jsonelem_str = get_metadata('post',$postid, '_elementor_data', true );
                $jsonelem_arr = $jsonelem_str ? json_decode( $jsonelem_str, true ) : false;
                $instance_id = $this->get_id();

                if($jsonelem_arr){

                    
                   // echo '<pre>';print_r($jsonelem_arr);echo '</pre>';

                    $input_text = $settings['input_text'];
                    $input_text_output = $settings['input_text_output'];
                
                    $generated_text = '';

                


                    if($input_text_output && trim($input_text_output) != '')
                    $generated_text = $input_text_output;
                    else{
                        if($input_text && trim($input_text) != ''){
                            $generated_text = rapidtextai_generate_text($input_text,$postid,$instance_id);
                            foreach ($jsonelem_arr as $key => $value) {
                                if($value['elements'][0]['elements'][0]['id'] == $instance_id){
                                    $jsonelem_arr[$key]['elements'][0]['elements'][0]['settings']['input_text_output'] = $generated_text;
                                    $jsonvalue = wp_slash( wp_json_encode( $jsonelem_arr ) );
                                    update_metadata( 'post', $postid, '_elementor_data', $jsonvalue );
                                    break;
                                } // if($value['elements'][0
                            } // foreach

                        } // if($input_text && trim($input_text
                    
                    } // ELSE of  if($input_text_output &&
                


                    echo wp_kses_post($generated_text);
                } // $jsonelem_arr
            } // func

            // protected function content_template() {}

            // public function render_plain_content( $instance = [] ) {}

          
            

        }  // clASS
        // Register the widget in Elementor
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \rapidtextai_AITextBlock_Elementor_Widget() );

        });
    //add_action( 'elementor/widgets/register', 'rapidtextai_register_block_widget' );
}



function rapidtextai_generate_text($prompt,$postid,$instance_id){
    $apikey = get_option('rapidtextai_api_key','c52ec1-5c73cd-e411e2-d8dc2d-491514');
    // Define the URL with query parameters
    $url = "https://app.rapidtextai.com/openai/detailedarticle-v2?gigsixkey=" . $apikey;
    $request_data = array(
            'type' => 'custom_prompt',
            'toneOfVoice' => '', // Assuming tone is sent as POST data
            'language' => '', // Assuming language is sent as POST data
            'text' => '',
            'temperature' => '0.7', // Assuming temperature is sent as POST data
            'custom_prompt' => $prompt,
    );
    $json_data = wp_json_encode($request_data);
    
    $response = wp_remote_post($url, array(
        'body' => $json_data,
       'method' => 'POST',
        //'timeout' => 45,
        //'redirection' => 5,
        //'httpversion' => '1.0',
        //'blocking' => true,
        'sslverify' => false,
        'headers' => array('Content-Type' => 'multipart/form-data'),
    ));

    if (!is_wp_error($response)) {
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($http_code === 200) {
            $content = rapidtextai_simple_markdown_to_html($body); 
            return $content;
        }
        else
        return 'Unauthorized Access, check your Rapidtextai.com Key';
    }
}
function rapidtextai_simple_markdown_to_html($markdown) {
    // Convert headers (we'll use a callback inside preg_replace_callback instead of preg_replace)
    $markdown = preg_replace_callback('/^(#{1,6})\s*(.*?)\s*#*\s*(?:\n+|$)/m', function($matches) {
        $level = strlen($matches[1]);
        return "<h{$level}>{$matches[2]}</h{$level}>";
    }, $markdown);

    // Convert bold (**text** or __text__)
    $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
    $markdown = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $markdown);

    // Convert italic (*text* or _text_)
    $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);
    $markdown = preg_replace('/_(.*?)_/', '<em>$1</em>', $markdown);

    // Convert links [text](url)
    $markdown = preg_replace('/\[([^\[]+)\]\((.*?)\)/', '<a href="$2">$1</a>', $markdown);

    // Convert unordered lists
    $markdown = preg_replace('/^\s*[\*\+\-]\s+(.*)/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $markdown);

    // Convert ordered lists
    $markdown = preg_replace('/^\d+\.\s+(.*)/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/(<li>.*<\/li>)/s', '<ol>$1</ol>', $markdown);

    // Convert blockquotes
    $markdown = preg_replace('/^\s*>\s+(.*)/m', '<blockquote>$1</blockquote>', $markdown);

    // Convert code blocks
    $markdown = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $markdown);

    // Convert inline code
    $markdown = preg_replace('/`([^`]+)`/', '<code>$1</code>', $markdown);

    // Convert newlines to paragraphs
    $markdown = preg_replace('/\n\n/', '</p><p>', $markdown);
    $markdown = '<p>' . $markdown . '</p>';  // Wrap with paragraph tags

    // Cleanup multiple paragraph tags
    $markdown = str_replace('<p></p>', '', $markdown);

    return $markdown;
}
