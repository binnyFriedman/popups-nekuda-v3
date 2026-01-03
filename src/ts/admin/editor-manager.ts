/// <reference path="../types/wordpress.d.ts" />

import { Keys, SlideType, TinyMCEDefaults } from './config';

const DOM_RENDER_DELAY_MS = 100;
const SYNC_EVENTS = 'change keyup blur';

/** Manages TinyMCE editors for popup slides. */
export class EditorManager {
    private abortController: AbortController | null = null;

    initEditorForSlide(type: SlideType, index: number): void {
        const id = `popup_editor_${type}_${index}`;
        setTimeout(() => this.initOrReuseEditor(id), DOM_RENDER_DELAY_MS);
    }

    destroyEditorForSlide(slide: HTMLElement): void {
        const id = slide.querySelector<HTMLTextAreaElement>('textarea')?.id;
        if (id) tinymce?.get(id)?.remove();
    }

    bindFormSubmitSync(): void {
        this.abortController?.abort();
        this.abortController = new AbortController();
        const { signal } = this.abortController;

        document.addEventListener('mousedown', this.syncOnSubmitClick, { signal });
        document.querySelector('form#post')?.addEventListener('submit', this.syncEditorsToTextareas, { signal });
        document.addEventListener('heartbeat-send', this.syncEditorsToTextareas, { signal });
    }

    unbindFormSync(): void {
        this.abortController?.abort();
        this.abortController = null;
    }

    getEditorContent(editorId: string): string {
        return tinymce?.get(editorId)?.getContent() ?? '';
    }

    onEditorChange(onMobileChange?: () => void): void {
        if (typeof tinymce === 'undefined') return;

        tinymce.editors.forEach((editor) => {
            if (!editor?.id?.startsWith('popup_editor_')) return;

            editor.on(SYNC_EVENTS, () => {
                editor.save();
                if (onMobileChange && editor.id.includes('mobile')) {
                    onMobileChange();
                }
            });
        });
    }

    private syncOnSubmitClick = (e: Event): void => {
        const target = e.target as HTMLElement;
        const isSubmit = target.id === 'publish' 
            || target.id === 'save-post' 
            || target.matches('input[type="submit"]');
        
        if (isSubmit) this.syncEditorsToTextareas();
    };

    private initOrReuseEditor(id: string): void {
        const existing = tinymce?.get(id);
        if (existing) {
            this.autoSaveOnChange(existing);
        } else {
            this.initTinyMCE(id);
        }
    }

    private initTinyMCE(id: string): void {
        if (typeof tinymce === 'undefined') return;

        tinymce.get(id)?.remove();
        tinymce.init(this.buildTinyMCESettings(id));

        if (typeof quicktags !== 'undefined') {
            quicktags({ id });
            QTags?._buttonsInit();
        }
    }

    private buildTinyMCESettings(id: string): Record<string, unknown> {
        const base = tinymce.editors[0]?.settings ?? TinyMCEDefaults;

        return {
            ...base,
            selector: `#${id}`,
            body_class: id,
            setup: (editor: TinyMCEEditor) => {
                (base.setup as ((e: TinyMCEEditor) => void) | undefined)?.(editor);
                this.autoSaveOnChange(editor);
            },
        };
    }

    private autoSaveOnChange(editor: TinyMCEEditor): void {
        editor.on(SYNC_EVENTS, () => editor.save());
    }

    private syncEditorsToTextareas = (): void => {
        tinymce?.triggerSave();

        tinymce?.editors.forEach((editor) => {
            if (!editor?.id?.startsWith('popup_editor_')) return;

            const textarea = document.getElementById(editor.id) as HTMLTextAreaElement | null;
            if (!textarea?.name) {
                const match = editor.id.match(/popup_editor_(desktop|mobile)_(\d+)/);
                if (match) {
                    textarea!.name = `${Keys[match[1] as SlideType]}[${match[2]}]`;
                }
            }
        });
    };
}
