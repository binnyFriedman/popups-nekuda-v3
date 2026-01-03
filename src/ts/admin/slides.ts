/// <reference path="../types/wordpress.d.ts" />

import { Keys, NotifyFn, Selectors, SlideType } from './config';
import { EditorManager } from './editor-manager';
import { executeScripts, htmlToElement } from './utils';

const SlideApi = {
    async fetch(type: SlideType, index: number): Promise<{ html: string; scripts?: string } | null> {
        const body = new FormData();
        body.append('action', 'popup_get_editor');
        body.append('nonce', popupAdmin.nonce);
        body.append('key', Keys[type]);
        body.append('index', String(index));

        const res = await fetch(popupAdmin.ajaxUrl, { method: 'POST', body });
        const json = await res.json();

        return json.success ? json.data : null;
    },
};

export class SlideList {
    readonly type: SlideType;
    private container: HTMLElement;
    private addBtn: HTMLButtonElement;

    private constructor(container: HTMLElement, addBtn: HTMLButtonElement, type: SlideType) {
        this.container = container;
        this.addBtn = addBtn;
        this.type = type;
    }

    static from(repeater: HTMLElement): SlideList | null {
        const container = repeater.querySelector<HTMLElement>(Selectors.slideList);
        const addBtn = repeater.querySelector<HTMLButtonElement>(Selectors.addBtn);
        const type = repeater.dataset.type as SlideType;

        if (!container || !addBtn || !type) return null;

        return new SlideList(container, addBtn, type);
    }

    count(): number {
        return this.items().length;
    }

    items(): HTMLElement[] {
        return Array.from(this.container.querySelectorAll(Selectors.slideItem));
    }

    append(html: string, scripts?: string): HTMLElement {
        const slide = htmlToElement(html);
        this.container.appendChild(slide);

        if (scripts) executeScripts(scripts);

        this.reindex();
        return slide;
    }

    remove(slide: HTMLElement): void {
        slide.remove();
        this.reindex();
    }

    onAdd(handler: () => void): void {
        this.addBtn.addEventListener('click', handler);
    }

    onRemove(handler: (slide: HTMLElement) => void): void {
        this.items().forEach((slide) => {
            slide.querySelector(Selectors.removeBtn)?.addEventListener('click', () => handler(slide));
        });
    }

    setAddingState(adding: boolean): void {
        this.addBtn.disabled = adding;
        this.addBtn.textContent = adding ? 'Adding...' : 'Add Slide';
    }

    private reindex(): void {
        const key = Keys[this.type];

        this.items().forEach((item, index) => {
            item.dataset.index = String(index);

            const title = item.querySelector(Selectors.slideTitle);
            if (title) title.textContent = `Slide ${index + 1}`;

            const textarea = item.querySelector<HTMLTextAreaElement>('textarea');
            if (textarea) textarea.name = `${key}[${index}]`;
        });
    }
}

export class SlideManager {
    private editors: EditorManager;
    private notify: NotifyFn;
    private lists: SlideList[] = [];

    constructor(editors: EditorManager, notify: NotifyFn) {
        this.editors = editors;
        this.notify = notify;
    }

    init(): void {
        document.querySelectorAll<HTMLElement>(Selectors.repeater).forEach((el) => {
            const list = SlideList.from(el);
            if (list) {
                this.lists.push(list);
                this.bind(list);
            }
        });
    }

    getLists(): SlideList[] {
        return this.lists;
    }

    private bind(list: SlideList): void {
        list.onAdd(() => this.addSlide(list));
        list.onRemove((slide) => this.removeSlide(list, slide));
    }

    private async addSlide(list: SlideList): Promise<void> {
        const index = list.count();

        list.setAddingState(true);

        try {
            const result = await SlideApi.fetch(list.type, index);
            if (!result) return;

            const slide = list.append(result.html, result.scripts);
            this.editors.initEditorForSlide(list.type, index);
            this.bindSlideRemove(list, slide);
        } catch {
            this.notify('Failed to add slide. Please try again.');
        } finally {
            list.setAddingState(false);
        }
    }

    private removeSlide(list: SlideList, slide: HTMLElement): void {
        if (list.count() <= 1) {
            this.notify('You must have at least one slide.', 'warning');
            return;
        }

        this.editors.destroyEditorForSlide(slide);
        list.remove(slide);
    }

    private bindSlideRemove(list: SlideList, slide: HTMLElement): void {
        const btn = slide.querySelector(Selectors.removeBtn);
        btn?.addEventListener('click', () => this.removeSlide(list, slide));
    }
}

