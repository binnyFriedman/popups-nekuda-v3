declare const popupAdmin: {
    ajaxUrl: string;
    nonce: string;
};

declare const jQuery: any;
declare const tinymce: any;
declare const quicktags: any;

(function($: any) {
    'use strict';

    function initSlidesRepeater(): void {
        $('.popup-slides-repeater').each(function(this: HTMLElement) {
            const $repeater = $(this);
            const $list = $repeater.find('.popup-slides-list');
            const $addBtn = $repeater.find('.popup-add-slide');
            const type = $repeater.data('type');
            const key = type === 'desktop' ? '_popup_slides_desktop' : '_popup_slides_mobile';

            // Add slide via AJAX
            $addBtn.on('click', function() {
                const currentCount = $list.find('.popup-slide-item').length;
                const newIndex = currentCount;

                $addBtn.prop('disabled', true).text('Adding...');

                $.post(popupAdmin.ajaxUrl, {
                    action: 'popup_get_editor',
                    nonce: popupAdmin.nonce,
                    key: key,
                    index: newIndex
                }, function(response: any) {
                    if (response.success) {
                        const $newSlide = $(response.data.html);
                        $list.append($newSlide);

                        // Initialize TinyMCE for new editor
                        const editorId = key + '_' + newIndex;
                        initTinyMCE(editorId);

                        updateSlideNumbers($list);
                        bindRemoveButton($newSlide);
                    }
                    $addBtn.prop('disabled', false).text('Add Slide');
                }).fail(function() {
                    $addBtn.prop('disabled', false).text('Add Slide');
                    alert('Failed to add slide. Please try again.');
                });
            });

            // Bind remove buttons for existing slides
            $list.find('.popup-slide-item').each(function(this: HTMLElement) {
                bindRemoveButton($(this));
            });
        });
    }

    function bindRemoveButton($slide: any): void {
        $slide.find('.popup-remove-slide').on('click', function(this: HTMLElement) {
            const $list = $slide.closest('.popup-slides-list');
            const slideCount = $list.find('.popup-slide-item').length;

            if (slideCount <= 1) {
                alert('You must have at least one slide.');
                return;
            }

            // Remove TinyMCE instance before removing DOM
            const editorId = $slide.find('textarea').attr('id');
            if (editorId && typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
                tinymce.get(editorId).remove();
            }

            $slide.remove();
            updateSlideNumbers($list);
            reindexSlides($list);
        });
    }

    function updateSlideNumbers($list: any): void {
        $list.find('.popup-slide-item').each(function(this: HTMLElement, index: number) {
            $(this).find('.popup-slide-title').text('Slide ' + (index + 1));
        });
    }

    function reindexSlides($list: any): void {
        const $repeater = $list.closest('.popup-slides-repeater');
        const type = $repeater.data('type');
        const key = type === 'desktop' ? '_popup_slides_desktop' : '_popup_slides_mobile';

        $list.find('.popup-slide-item').each(function(this: HTMLElement, index: number) {
            const $item = $(this);
            $item.attr('data-index', index);

            // Update textarea name
            const $textarea = $item.find('textarea');
            if ($textarea.length) {
                $textarea.attr('name', key + '[' + index + ']');
            }
        });
    }

    function initTinyMCE(editorId: string): void {
        if (typeof tinymce === 'undefined') return;

        // Default WordPress TinyMCE settings
        const settings = {
            selector: '#' + editorId,
            wpautop: true,
            indent: false,
            toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,spellchecker,fullscreen,wp_adv',
            toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
            plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview',
            height: 200,
            menubar: false,
            branding: false,
            convert_urls: false,
            relative_urls: false,
            remove_script_host: false,
        };

        tinymce.init(settings);

        // Initialize quicktags if available
        if (typeof quicktags !== 'undefined') {
            quicktags({ id: editorId });
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        initSlidesRepeater();
    });
})(jQuery);
