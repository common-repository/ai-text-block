<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add meta box to post edit screen
function rapidtextai_add_meta_box() {
    add_meta_box(
        'rapidtextai_meta_box',
        __('RapidTextAI', 'rapidtextai'),
        'rapidtextai_meta_box_callback',
        ['post', 'page'], // You can add custom post types here
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'rapidtextai_add_meta_box' );

// Meta box callback function
function rapidtextai_meta_box_callback( $post ) {
    // Get the API key option
    $current_api_key = get_option('rapidtextai_api_key', '');

    // Include nonce field for security
    wp_nonce_field( 'rapidtextai_meta_box_nonce', 'rapidtextai_meta_box_nonce' );

    // Output the form
    ?>
    <div id="articleForm">
        <div class="mb-3">
            <label for="articleTopic" class="form-label">Write an article about</label>
            <input type="text" class="form-control" id="articleTopic" placeholder="Enter topic here" required>
        </div>
        <div class="mb-3">
            <label for="articleKeywords" class="form-label">Focus Keywords</label>
            <input type="text" class="form-control" id="articleKeywords" placeholder="keyword 1, keyword 2" required>
        </div>
        <div class="mb-3">
            <label for="modelSelection" class="form-label">Select Model</label>
            <select class="form-select" id="modelSelection">
                <option value="gemini-1.5-flash">Gemini 1.5</option>
                <option value="gemini-1.5-pro">Gemini Plus</option>
                <option value="gemini-1.0-pro">Gemini 1</option>
                <option value="gpt-4">GPT-4</option>
                <option value="gpt-3.5">GPT-3.5</option>
            </select>
        </div>

        <!-- Advanced Options Link -->
        <a href="#" id="showAdvancedOptions">Show Advanced Options</a> <small>(For Pro Users)</small>

        <!-- Advanced Options Section (Initially Hidden) -->
        <div id="advancedOptions" style="display:none; margin-top: 20px;">
            <div class="mb-3">
                <label for="articleLength" class="form-label">Preferred Article Length</label>
                <select class="form-select" id="articleLength">
                    <option value="">Select Length</option>
                    <option value="500">500-700 words</option>
                    <option value="1000">1000-1500 words</option>
                    <option value="2000">2000-3000+ words</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="targetAudience" class="form-label">Target Audience</label>
                <input type="text" class="form-control" id="targetAudience" placeholder="e.g., students, professionals">
            </div>

            <div class="mb-3">
                <label for="articleTone" class="form-label">Tone</label>
                <select class="form-select" id="articleTone">
                    <option value="">Select Tone</option>
                    <option value="formal">Formal</option>
                    <option value="conversational">Conversational</option>
                    <option value="persuasive">Persuasive</option>
                    <option value="friendly">Friendly</option>
                    <option value="neutral">Neutral</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="writingStyle" class="form-label">Writing Style</label>
                <select class="form-select" id="writingStyle">
                    <option value="">Select Style</option>
                    <option value="informative">Informative</option>
                    <option value="narrative">Narrative</option>
                    <option value="technical">Technical</option>
                    <option value="descriptive">Descriptive</option>
                    <option value="explanatory">Explanatory</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="references" class="form-label">References/Sources (Optional)</label>
                <textarea class="form-control" id="references" rows="3" placeholder="Enter references, URLs, or sources"></textarea>
            </div>

            <div class="mb-3">
                <label for="articleStructure" class="form-label">Article Structure</label>
                <select class="form-select" id="articleStructure">
                    <option value="">Select Structure</option>
                    <option value="introduction-body-conclusion">Introduction, Body, Conclusion</option>
                    <option value="problem-solution">Problem, Solution</option>
                    <option value="cause-effect">Cause, Effect</option>
                    <option value="listicle">Listicle</option>
                    <option value="how-to">How-to</option>
                </select>
            </div>
            <!-- Internal links -->
            <div class="mb-3">
                <label for="internalLinks" class="form-label">Internal Links</label>
                <input type="text" class="form-control" id="internalLinks" placeholder="e.g., https://example.com/page1, https://example.com/page2">
            </div>
            <!-- External links -->
            <div class="mb-3">
                <label for="externalLinks" class="form-label">External Links</label>
                <input type="text" class="form-control" id="externalLinks" placeholder="e.g., https://example.com/page1, https://example.com/page2">
            </div>
            <div class="mb-3">
                <label for="callToAction" class="form-label">Include a Call-to-Action</label>
                <input type="text" class="form-control" id="callToAction" placeholder="e.g., Subscribe now, Learn more">
            </div>
        </div>
        <div class="mb-3">
            <a style="font-size:1.5rem;" href="#" class="btn btn-primary" id="generateArticleButton">Generate</a>
        </div>
    </div>
    <?php
}

// Enqueue scripts
function rapidtextai_metabox_enqueue_scripts( $hook ) {
    // Only enqueue on post edit screen
    if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
        return;
    }
    wp_enqueue_style( 'rapidtextai_styles', plugin_dir_url( __FILE__ ) . 'assets/css/rapidtextai-styles.css', array(), '1.0' );
    wp_enqueue_script( 'rapidtextai_marked_js', plugin_dir_url( __FILE__ ) . 'assets/js/marked.min.js', array(), '4.3.0', true );
    wp_enqueue_script( 'rapidtextai_script', plugin_dir_url( __FILE__ ) . 'assets/js/rapidtextai.js', array( 'jquery' ), '1.3', true );
    

    // Localize script to pass data
    wp_localize_script( 'rapidtextai_script', 'rapidtextai_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'rapidtextai_nonce' ),
        'api_key'  => get_option('rapidtextai_api_key', ''),
    ) );
}
add_action( 'admin_enqueue_scripts', 'rapidtextai_metabox_enqueue_scripts' );

// AJAX handler
function rapidtextai_generate_article() {
    // Check nonce
    check_ajax_referer( 'rapidtextai_nonce', 'nonce' );

    // Get POST data
    $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
    $tone_of_voice = isset( $_POST['toneOfVoice'] ) ? sanitize_text_field( $_POST['toneOfVoice'] ) : '';
    $language = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : 'en';
    $text = isset( $_POST['text'] ) ? sanitize_textarea_field( $_POST['text'] ) : '';
    $temperature = isset( $_POST['temperature'] ) ? sanitize_text_field( $_POST['temperature'] ) : '0.7';
    $custom_prompt = isset( $_POST['custom_prompt'] ) ? sanitize_textarea_field( $_POST['custom_prompt'] ) : '';
    $chatsession = isset( $_POST['chatsession'] ) ? sanitize_text_field( $_POST['chatsession'] ) : '';
    $userid = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';

    // Handle model
    if ( isset( $_POST['model'] ) && ! empty( $_POST['model'] ) ) {
        $model = sanitize_text_field( $_POST['model'] );
    } else {
        $model = 'default_model'; // Set a default model if not provided
    }

    // Build the API request payload
    $api_payload = array(
        'type' => $type,
        'toneOfVoice' => $tone_of_voice,
        'language' => $language,
        'text' => $text,
        'temperature' => $temperature,
        'custom_prompt' => $custom_prompt,
        'chatsession' => $chatsession,
        'userid' => $userid,
        'model' => $model,
    );

    // Now, make the API call
    $current_api_key = get_option('rapidtextai_api_key', '');

    // API endpoint with API key
    $api_url = "https://app.rapidtextai.com/openai/detailedarticle-v3?gigsixkey=" . urlencode( $current_api_key );

    $args = array(
        'body' => $api_payload,
        'timeout' => 60,
    );

    $response = wp_remote_post( $api_url, $args );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    } else {
        $body = wp_remote_retrieve_body( $response );
        wp_send_json_success( $body );
    }
}
add_action( 'wp_ajax_rapidtextai_generate_article', 'rapidtextai_generate_article' );

