import { EditorManager } from './editor-manager';
import { SlideList, SlideManager } from './slides';

const INITIAL_CHECK_DELAY_MS = 500;
const EDITOR_READY_DELAY_MS = 1000;

export class MobileSyncIndicator {
    private editors: EditorManager;

    constructor(editors: EditorManager) {
        this.editors = editors;
    }

    watch(slides: SlideManager): void {
        setTimeout(() => this.updateIndicator(slides), INITIAL_CHECK_DELAY_MS);
        setTimeout(() => {
            this.editors.onEditorChange(() => this.updateIndicator(slides));
        }, EDITOR_READY_DELAY_MS);
    }

    private updateIndicator(slides: SlideManager): void {
        const mobileList = slides.getLists().find((l) => l.type === 'mobile');
        const hasContent = mobileList ? this.hasMobileContent(mobileList) : false;

        const notice = document.querySelector<HTMLElement>('.popup-mobile-notice');
        const indicator = document.querySelector<HTMLElement>('.popup-tab-btn[data-tab="mobile"] .popup-tab-sync');

        notice?.classList.toggle('is-hidden', hasContent);
        if (indicator) indicator.style.display = hasContent ? 'none' : '';
    }

    private hasMobileContent(list: SlideList): boolean {
        return list.items().some((slide) => {
            const textarea = slide.querySelector<HTMLTextAreaElement>('textarea');
            if (!textarea) return false;

            const content = this.editors.getEditorContent(textarea.id) || textarea.value;
            return content.trim().length > 0;
        });
    }
}
