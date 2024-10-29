(function($){
    // Function to build form data
    function build_data(prompt){
        var formData = new FormData();
        formData.append('type', 'custom_prompt');
        formData.append('toneOfVoice', $('#articleTone').val()); // Adjusted to match your form fields
        formData.append('language', $('#language').val() || 'en'); // Assuming a language field or default 'en'
        formData.append('text', $('#text').val() || ''); // Assuming a text field if needed
        formData.append('temperature', $('#temperature').val() || '0.7'); // Assuming a default temperature
        formData.append('custom_prompt', prompt);
        formData.append('model', $('#modelSelection').val());
        return formData;
    }

    // Function to toggle advanced options
    function toggleAdvancedOptions(event) {
        event.preventDefault();
        $('#advancedOptions').toggle();
    }

    // Function to generate the article
    function generate_article() {
        // Validate required fields
        if ($('#articleTopic').val() === '' || $('#articleKeywords').val() === '') {
            alert('Article topic and keywords cannot be empty.');
            return;
        }

        // Build the prompt
        var topic = $('#articleTopic').val();
        var keywords = $('#articleKeywords').val();
        
        // Build the prompt using available form data
        var prompt = `Generate a detailed article on the topic of ${topic} with the following keywords: ${keywords}`;
        
        // Add optional parameters to the prompt
        var callToAction = $('#callToAction').val();
        if (callToAction) prompt += ` with a call to action: ${callToAction}`;
        
        var references = $('#references').val();
        if (references) prompt += ` with references: ${references}`;
        
        var articleTone = $('#articleTone').val();
        if (articleTone) prompt += ` with the tone of voice: ${articleTone}`;
        
        var targetAudience = $('#targetAudience').val();
        if (targetAudience) prompt += ` for the target audience: ${targetAudience}`;
        
        var articleLength = $('#articleLength').val();
        if (articleLength) prompt += ` with the article length: ${articleLength}`;
        
        var writingStyle = $('#writingStyle').val();
        if (writingStyle) prompt += ` with the writing style: ${writingStyle}`;
        
        var articleStructure = $('#articleStructure').val();
        if (articleStructure) prompt += ` with the article structure: ${articleStructure}`;
        
        var internalLinks = $('#internalLinks').val();
        if (internalLinks) prompt += ` with internal links: ${internalLinks}`;
        
        var externalLinks = $('#externalLinks').val();
        if (externalLinks) prompt += ` with external links: ${externalLinks}`;

        // Build the form data
        var formData = build_data(prompt);

        // Include action and nonce
        formData.append('action', 'rapidtextai_generate_article');
        formData.append('nonce', rapidtextai_ajax.nonce);

        // Show a loading indicator (optional)
        $('#generateArticleButton').text('Generating...');
        $('#generateArticleButton').prop('disabled', true);

        // Make the AJAX request
        $.ajax({
            url: rapidtextai_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response){
                // Reset the button text
                $('#generateArticleButton').text('Generate');
                // also disable click so it does not trigger onclick again until response is received
                $('#generateArticleButton').prop('disabled', false);


                if (response.success) {
                   var content =  marked.parse(response.data);

                    // Insert content into editor
                    if ( typeof tinymce !== 'undefined' && tinymce.activeEditor ) {
                        // Classic Editor
                        tinymce.activeEditor.setContent( content );
                    } else if ( wp.data ) {
                        // Block Editor
                        wp.data.dispatch('core/editor').editPost({ content: content });
                    } else {
                        // Fallback
                        $('#content').val( content );
                    }

                    alert('Article generated and inserted into the editor.');
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown){
                $('#generateArticleButton').text('Generate');
                $('#generateArticleButton').prop('disabled', false);
                alert('AJAX error: ' + textStatus);
            }
        });
    }

    // Attach event handlers
    $(document).ready(function(){
        $('#showAdvancedOptions').on('click', toggleAdvancedOptions);
        $('#generateArticleButton').on('click', function(event){
            event.preventDefault();
            generate_article();
        });
    });
})(jQuery);
