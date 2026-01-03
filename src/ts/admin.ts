declare const popupAdmin: {
    ajaxUrl: string;
    nonce: string;
};

declare const jQuery: any;
declare const tinymce: any;
declare const quicktags: any;
declare const QTags: any;
declare const wpActiveEditor: string;
declare const wp: any;

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

                        // Execute the WordPress editor scripts if provided
                        if (response.data.scripts) {
                            // Create a temporary container to execute scripts
                            const $scriptContainer = $('<div>').html(response.data.scripts);
                            $scriptContainer.find('script').each(function(this: HTMLElement) {
                                try {
                                    // eslint-disable-next-line no-eval
                                    eval($(this).text());
                                } catch (e) {
                                    console.warn('Script execution error:', e);
                                }
                            });
                        }

                        // Initialize TinyMCE for new editor (match PHP's ID format)
                        const editorId = 'popup_editor_' + type + '_' + newIndex;
                        
                        // Small delay to let WordPress scripts execute first
                        setTimeout(function() {
                            // Only init if WordPress didn't already do it
                            if (!tinymce.get(editorId)) {
                                initTinyMCE(editorId);
                            } else {
                                // Add our change handler to the existing editor
                                const editor = tinymce.get(editorId);
                                editor.on('change keyup', function() {
                                    editor.save();
                                });
                            }
                            initMediaButton(editorId);
                        }, 100);

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

        // Remove existing editor instance if it exists (prevents duplicates)
        const existingEditor = tinymce.get(editorId);
        if (existingEditor) {
            existingEditor.remove();
        }

        // Get default settings from an existing editor if available
        let baseSettings: any = {};
        if (tinymce.editors.length > 0) {
            const existingSettings = tinymce.editors[0].settings;
            baseSettings = {
                ...existingSettings,
                selector: '#' + editorId,
                body_class: editorId,
            };
        } else {
            // Fallback settings
            baseSettings = {
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
        }

        // Add setup callback for syncing
        const originalSetup = baseSettings.setup;
        baseSettings.setup = function(editor: any) {
            if (typeof originalSetup === 'function') {
                originalSetup(editor);
            }
            // Sync content to textarea on every change
            editor.on('change keyup', function() {
                editor.save();
            });
        };

        tinymce.init(baseSettings);

        // Initialize quicktags if available
        if (typeof quicktags !== 'undefined') {
            quicktags({ id: editorId });
            // Trigger quicktags initialization
            if (typeof QTags !== 'undefined') {
                QTags._buttonsInit();
            }
        }
    }

    function initMediaButton(editorId: string): void {
        // Find the media button for this editor
        const $wrap = $('#wp-' + editorId + '-wrap');
        const $mediaButton = $wrap.find('.insert-media');
        
        if ($mediaButton.length && typeof wp !== 'undefined' && wp.media) {
            $mediaButton.off('click').on('click', function(e: Event) {
                e.preventDefault();
                
                // Set the active editor
                (window as any).wpActiveEditor = editorId;
                
                // Open the media library
                const mediaFrame = wp.media({
                    title: 'Insert Media',
                    button: { text: 'Insert' },
                    multiple: false
                });
                
                mediaFrame.on('select', function() {
                    const attachment = mediaFrame.state().get('selection').first().toJSON();
                    const editor = tinymce.get(editorId);
                    
                    if (editor) {
                        let content = '';
                        if (attachment.type === 'image') {
                            content = '<img src="' + attachment.url + '" alt="' + (attachment.alt || '') + '" />';
                        } else {
                            content = '<a href="' + attachment.url + '">' + attachment.title + '</a>';
                        }
                        editor.insertContent(content);
                        editor.save();
                    }
                });
                
                mediaFrame.open();
            });
        }
    }

    // Sync TinyMCE content to textareas before form submit
    function syncTinyMCEOnSubmit(): void {
        // Hook into WordPress's pre-submit using mousedown (fires before click completes)
        $(document).on('mousedown', '#publish, #save-post, input[type="submit"]', function() {
            syncAllEditors();
        });
        
        // Also use the form submit event as backup
        $('form#post').on('submit', function() {
            syncAllEditors();
        });
        
        // Hook into WordPress's heartbeat to periodically sync
        $(document).on('heartbeat-send', function() {
            syncAllEditors();
        });
    }
    
    function syncAllEditors(): void {
        if (typeof tinymce === 'undefined') return;
        
        // First, trigger WordPress's built-in save
        tinymce.triggerSave();
        
        // Then manually sync each popup editor
        for (let i = 0; i < tinymce.editors.length; i++) {
            const editor = tinymce.editors[i];
            if (editor && editor.id && editor.id.startsWith('popup_editor_')) {
                try {
                    const content = editor.getContent();
                    const textarea = document.getElementById(editor.id) as HTMLTextAreaElement;
                    if (textarea && content !== undefined) {
                        textarea.value = content;
                        // Also update the name attribute if needed
                        if (!textarea.name || textarea.name === '') {
                            const match = editor.id.match(/popup_editor_(desktop|mobile)_(\d+)/);
                            if (match) {
                                const type = match[1];
                                const index = match[2];
                                const key = type === 'desktop' ? '_popup_slides_desktop' : '_popup_slides_mobile';
                                textarea.name = key + '[' + index + ']';
                            }
                        }
                    }
                } catch (err) {
                    console.warn('Error syncing editor:', editor.id, err);
                }
            }
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        initSlidesRepeater();
        syncTinyMCEOnSubmit();
        
        // Add change handlers to existing editors after they're initialized
        setTimeout(function() {
            if (typeof tinymce !== 'undefined') {
                for (let i = 0; i < tinymce.editors.length; i++) {
                    const editor = tinymce.editors[i];
                    if (editor && editor.id && editor.id.startsWith('popup_editor_')) {
                        editor.on('change keyup blur', function() {
                            editor.save();
                        });
                    }
                }
            }
        }, 1000);
    });
})(jQuery);
