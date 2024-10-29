(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, TextareaControl, Button, Spinner } = wp.components;
    const { useState, Fragment, createElement } = wp.element;

    registerBlockType('rapidtextai/ai-text-block', {
        title: 'RapidTextAI Block',
        icon: 'edit',
        category: 'common',
        attributes: {
            prompt: {
                type: 'string',
                default: '',
            },
            generatedContent: {
                type: 'string',
                default: '',
            },
        },
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const [isLoading, setIsLoading] = useState(false);

            const generateContent = function () {
                if (attributes.prompt.trim() !== '') {
                    setIsLoading(true);

                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'rapidtextai_generate_content_block',
                            prompt: attributes.prompt,
                        },
                        success: function (response) {
                            if (response.success) {
                                setAttributes({ generatedContent: response.data.generated_text });
                            } else {
                                setAttributes({ generatedContent: 'Error generating content.' });
                            }
                            setIsLoading(false);
                        },
                        error: function () {
                            setAttributes({ generatedContent: 'Error reaching the server.' });
                            setIsLoading(false);
                        },
                    });
                }
            };

            return createElement(
                Fragment,
                null,
                createElement(
                    InspectorControls,
                    null,
                    createElement(
                        PanelBody,
                        { title: 'Prompt Settings', initialOpen: true },
                        createElement(TextareaControl, {
                            label: 'Enter your AI Prompt',
                            value: attributes.prompt,
                            onChange: function (value) {
                                setAttributes({ prompt: value });
                            },
                        }),
                        createElement(
                            Button,
                            {
                                isPrimary: true,
                                onClick: generateContent,
                                disabled: isLoading,
                            },
                            isLoading ? createElement(Spinner, null) : 'Generate Content'
                        )
                    )
                ),
                createElement(
                    'div',
                    { className: 'rapidtextai-block-preview' },
                    isLoading ? createElement(Spinner, null) : createElement('div', { dangerouslySetInnerHTML: { __html: attributes.generatedContent } })
                )
            );
        },
        save: function (props) {
            // Save the generated content to display it on the frontend
            return createElement('div', {
                dangerouslySetInnerHTML: { __html: props.attributes.generatedContent }
            });
        },
    });
})(window.wp);
