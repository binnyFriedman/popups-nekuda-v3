/// <reference path="./types/wordpress.d.ts" />

const WP_SCRIPT_INIT_DELAY_MS = 100;
const TINYMCE_READY_DELAY_MS = 1000;
const DOM_RENDER_DELAY_MS = 500;
const NOTICE_AUTO_DISMISS_MS = 5000;

const BUTTON_TEXT = {
    ADD_SLIDE: 'Add Slide',
    ADDING: 'Adding...',
} as const;

type NoticeType = 'error' | 'warning' | 'success';

function showAdminNotice(message: string, type: NoticeType = 'error'): void {
    const notice = document.createElement('div');
    notice.className = `notice notice-${type} is-dismissible`;
    notice.innerHTML = `<p>${message}</p>`;

    const heading = document.querySelector('.wrap h1');
    if (heading) {
        heading.insertAdjacentElement('afterend', notice);
    } else {
        document.querySelector('.wrap')?.prepend(notice);
    }

    setTimeout(() => {
        notice.style.transition = 'opacity 300ms';
        notice.style.opacity = '0';
        setTimeout(() => notice.remove(), 300);
    }, NOTICE_AUTO_DISMISS_MS);
}

function getSlideKey(type: string): string {
    return type === 'desktop' ? '_popup_slides_desktop' : '_popup_slides_mobile';
}

function initSlidesRepeater(): void {
    document.querySelectorAll<HTMLElement>('.popup-slides-repeater').forEach((repeater) => {
        const list = repeater.querySelector<HTMLElement>('.popup-slides-list');
        const addBtn = repeater.querySelector<HTMLButtonElement>('.popup-add-slide');
        const type = repeater.dataset.type ?? '';

        if (list && addBtn) {
            setupAddSlideButton(addBtn, list, type);
            bindExistingRemoveButtons(list);
        }
    });
}

function setupAddSlideButton(
    addBtn: HTMLButtonElement,
    list: HTMLElement,
    type: string
): void {
    const key = getSlideKey(type);

    addBtn.addEventListener('click', async () => {
        const newIndex = list.querySelectorAll('.popup-slide-item').length;

        addBtn.disabled = true;
        addBtn.textContent = BUTTON_TEXT.ADDING;

        try {
            const formData = new FormData();
            formData.append('action', 'popup_get_editor');
            formData.append('nonce', popupAdmin.nonce);
            formData.append('key', key);
            formData.append('index', String(newIndex));

            const response = await fetch(popupAdmin.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const data: { success: boolean; data: { html: string; scripts?: string } } = await response.json();

            if (!data.success) {
                return;
            }

            const template = document.createElement('template');
            template.innerHTML = data.data.html.trim();
            const newSlide = template.content.firstElementChild as HTMLElement;

            list.appendChild(newSlide);

            if (data.data.scripts) {
                executeScripts(data.data.scripts);
            }

            initNewSlideEditor(type, newIndex);
            updateSlideNumbers(list);
            bindRemoveButton(newSlide, list, type);

            if (type === 'mobile') {
                updateMobileSyncStatus();
            }
        } catch {
            showAdminNotice('Failed to add slide. Please try again.');
        } finally {
            addBtn.disabled = false;
            addBtn.textContent = BUTTON_TEXT.ADD_SLIDE;
        }
    });
}

function executeScripts(scriptsHtml: string): void {
    const scriptContainer = document.createElement('div');
    scriptContainer.innerHTML = scriptsHtml;
    const scripts = scriptContainer.querySelectorAll('script');

    scripts.forEach((oldScript) => {
        const newScript = document.createElement('script');
        newScript.textContent = oldScript.textContent;
        document.body.appendChild(newScript);
        document.body.removeChild(newScript);
    });
}

function initNewSlideEditor(type: string, index: number): void {
    const editorId = `popup_editor_${type}_${index}`;

    setTimeout(() => {
        if (!tinymce.get(editorId)) {
            initTinyMCE(editorId);
        } else {
            addChangeHandlerToEditor(tinymce.get(editorId));
        }
    }, WP_SCRIPT_INIT_DELAY_MS);
}

function addChangeHandlerToEditor(editor: TinyMCEEditor | null): void {
    if (!editor) return;
    editor.on('change keyup', () => {
        editor.save();
    });
}

function bindExistingRemoveButtons(list: HTMLElement): void {
    const repeater = list.closest<HTMLElement>('.popup-slides-repeater');
    const type = repeater?.dataset.type ?? '';

    list.querySelectorAll<HTMLElement>('.popup-slide-item').forEach((slide) => {
        bindRemoveButton(slide, list, type);
    });
}

function bindRemoveButton(slide: HTMLElement, list: HTMLElement, type: string): void {
    const removeBtn = slide.querySelector('.popup-remove-slide');
    if (!removeBtn) return;

    removeBtn.addEventListener('click', () => {
        const slideCount = list.querySelectorAll('.popup-slide-item').length;

        if (slideCount <= 1) {
            showAdminNotice('You must have at least one slide.', 'warning');
            return;
        }

        removeTinyMCEInstance(slide);
        slide.remove();
        updateSlideNumbers(list);
        reindexSlides(list, type);

        if (type === 'mobile') {
            updateMobileSyncStatus();
        }
    });
}

function removeTinyMCEInstance(slide: HTMLElement): void {
    const textarea = slide.querySelector<HTMLTextAreaElement>('textarea');
    const editorId = textarea?.id;
    if (!editorId || typeof tinymce === 'undefined') return;

    const editor = tinymce.get(editorId);
    if (editor) {
        editor.remove();
    }
}

function updateSlideNumbers(list: HTMLElement): void {
    list.querySelectorAll<HTMLElement>('.popup-slide-item').forEach((item, index) => {
        const title = item.querySelector('.popup-slide-title');
        if (title) {
            title.textContent = `Slide ${index + 1}`;
        }
    });
}

function reindexSlides(list: HTMLElement, type: string): void {
    const key = getSlideKey(type);

    list.querySelectorAll<HTMLElement>('.popup-slide-item').forEach((item, index) => {
        item.dataset.index = String(index);

        const textarea = item.querySelector<HTMLTextAreaElement>('textarea');
        if (textarea) {
            textarea.name = `${key}[${index}]`;
        }
    });
}

function initTinyMCE(editorId: string): void {
    if (typeof tinymce === 'undefined') return;

    const existingEditor = tinymce.get(editorId);
    if (existingEditor) {
        existingEditor.remove();
    }

    const baseSettings = buildTinyMCESettings(editorId);
    tinymce.init(baseSettings);
    initQuicktags(editorId);
}

function buildTinyMCESettings(editorId: string): Record<string, unknown> {
    let baseSettings: Record<string, unknown>;

    if (tinymce.editors.length > 0) {
        const existingSettings = tinymce.editors[0].settings;
        baseSettings = {
            ...existingSettings,
            selector: `#${editorId}`,
            body_class: editorId,
        };
    } else {
        baseSettings = getDefaultTinyMCESettings(editorId);
    }

    const originalSetup = baseSettings.setup;
    baseSettings.setup = (editor: TinyMCEEditor) => {
        if (typeof originalSetup === 'function') {
            originalSetup(editor);
        }
        editor.on('change keyup', () => {
            editor.save();
        });
    };

    return baseSettings;
}

function getDefaultTinyMCESettings(editorId: string): Record<string, unknown> {
    return {
        selector: `#${editorId}`,
        wpautop: true,
        indent: false,
        toolbar1:
            'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,spellchecker,fullscreen,wp_adv',
        toolbar2:
            'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
        plugins:
            'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview',
        height: 200,
        menubar: false,
        branding: false,
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false,
    };
}

function initQuicktags(editorId: string): void {
    if (typeof quicktags === 'undefined') return;

    quicktags({ id: editorId });
    if (typeof QTags !== 'undefined') {
        QTags._buttonsInit();
    }
}

function syncTinyMCEOnSubmit(): void {
    // mousedown fires before click completes, ensuring sync happens before WordPress processes the form
    document.addEventListener('mousedown', (e) => {
        const target = e.target as HTMLElement;
        if (
            target.id === 'publish' ||
            target.id === 'save-post' ||
            (target.tagName === 'INPUT' && (target as HTMLInputElement).type === 'submit')
        ) {
            syncAllEditors();
        }
    });

    document.querySelector<HTMLFormElement>('form#post')?.addEventListener('submit', syncAllEditors);

    // WordPress heartbeat API
    document.addEventListener('heartbeat-send', syncAllEditors);
}

function syncAllEditors(): void {
    if (typeof tinymce === 'undefined') return;

    tinymce.triggerSave();

    for (let i = 0; i < tinymce.editors.length; i++) {
        const editor = tinymce.editors[i];
        if (editor?.id?.startsWith('popup_editor_')) {
            syncEditorToTextarea(editor);
        }
    }
}

function syncEditorToTextarea(editor: TinyMCEEditor): void {
    try {
        const content = editor.getContent();
        const textarea = document.getElementById(editor.id) as HTMLTextAreaElement;

        if (!textarea || content === undefined) return;

        textarea.value = content;

        if (!textarea.name || textarea.name === '') {
            assignTextareaName(textarea, editor.id);
        }
    } catch (err) {
        console.warn('Error syncing editor:', editor.id, err);
    }
}

function assignTextareaName(textarea: HTMLTextAreaElement, editorId: string): void {
    const match = editorId.match(/popup_editor_(desktop|mobile)_(\d+)/);
    if (!match) return;

    const [, type, index] = match;
    const key = getSlideKey(type);
    textarea.name = `${key}[${index}]`;
}

function initTabs(): void {
    const tabsContainer = document.querySelector<HTMLElement>('.popup-content-tabs');
    if (!tabsContainer) return;

    const tabBtns = tabsContainer.querySelectorAll<HTMLButtonElement>('.popup-tab-btn');
    const tabPanels = tabsContainer.querySelectorAll<HTMLElement>('.popup-tab-panel');

    tabBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab ?? '';

            tabBtns.forEach((b) => b.classList.remove('is-active'));
            btn.classList.add('is-active');

            tabPanels.forEach((panel) => {
                panel.classList.remove('is-active');
                if (panel.dataset.panel === tabId) {
                    panel.classList.add('is-active');
                }
            });

            try {
                sessionStorage.setItem('popup_active_tab', tabId);
            } catch {
                // sessionStorage not available
            }
        });
    });

    restoreActiveTab(tabBtns);
}

function restoreActiveTab(tabBtns: NodeListOf<HTMLButtonElement>): void {
    try {
        const savedTab = sessionStorage.getItem('popup_active_tab');
        if (savedTab) {
            const targetBtn = Array.from(tabBtns).find((btn) => btn.dataset.tab === savedTab);
            targetBtn?.click();
        }
    } catch {
        // sessionStorage not available
    }
}

function updateMobileSyncStatus(): void {
    const mobilePanel = document.querySelector<HTMLElement>('.popup-tab-panel[data-panel="mobile"]');
    const mobileNotice = mobilePanel?.querySelector<HTMLElement>('.popup-mobile-notice');
    const mobileTabBtn = document.querySelector<HTMLElement>('.popup-tab-btn[data-tab="mobile"]');
    const syncIndicator = mobileTabBtn?.querySelector<HTMLElement>('.popup-tab-sync');

    if (!mobilePanel) return;

    const hasMobileContent = checkMobileHasContent(mobilePanel);

    if (hasMobileContent) {
        mobileNotice?.classList.add('is-hidden');
        if (syncIndicator) syncIndicator.style.display = 'none';
    } else {
        mobileNotice?.classList.remove('is-hidden');
        if (syncIndicator) syncIndicator.style.display = '';
    }
}

function checkMobileHasContent(mobilePanel: HTMLElement): boolean {
    const slides = Array.from(mobilePanel.querySelectorAll('.popup-slide-item')) as HTMLElement[];

    for (const slide of slides) {
        const textarea = slide.querySelector('textarea') as HTMLTextAreaElement | null;
        const editorId = textarea?.id;
        let content = textarea?.value ?? '';

        if (editorId && typeof tinymce !== 'undefined') {
            const editor = tinymce.get(editorId);
            if (editor) {
                content = editor.getContent();
            }
        }

        if (content.trim()) {
            return true;
        }
    }

    return false;
}

function setupExistingEditorChangeHandlers(): void {
    if (typeof tinymce === 'undefined') return;

    for (let i = 0; i < tinymce.editors.length; i++) {
        const editor = tinymce.editors[i];
        if (editor?.id?.startsWith('popup_editor_')) {
            editor.on('change keyup blur', () => {
                editor.save();
                if (editor.id.includes('mobile')) {
                    updateMobileSyncStatus();
                }
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initSlidesRepeater();
    syncTinyMCEOnSubmit();

    setTimeout(updateMobileSyncStatus, DOM_RENDER_DELAY_MS);
    setTimeout(setupExistingEditorChangeHandlers, TINYMCE_READY_DELAY_MS);
});
