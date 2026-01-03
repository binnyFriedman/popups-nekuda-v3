import { setCookie, trapFocus } from './utils';
import { Slideshow, SlideshowState } from './slideshow';

interface PopupSettings {
    devMode: boolean;
}
declare const popupSettings: PopupSettings | undefined;

export interface PopupElement extends HTMLElement {
    dataset: {
        popupId: string;
        trigger: string;
        timeout: string;
        cookieKey: string;
        cookieExpiry: string;
    };
}

/** Allows close animation to complete before queue advances */
const CLOSE_ANIMATION_MS = 300;

const DEV_MODE = popupSettings?.devMode ?? false;

export class Popup {
    private readonly element: PopupElement;
    private readonly id: string;
    private readonly abortController = new AbortController();
    private readonly slideshow: Slideshow;
    private readonly onClose: (popupId: string) => void;

    private cleanupFocusTrap: (() => void) | null = null;
    private previouslyFocusedElement: HTMLElement | null = null;

    constructor(element: PopupElement, onClose: (popupId: string) => void) {
        this.element = element;
        this.id = element.dataset.popupId;
        this.onClose = onClose;

        const slideshowState: SlideshowState = {
            currentSlide: 0,
            autoAdvanceInterval: null,
            isPaused: false,
            abortController: this.abortController,
        };

        this.slideshow = new Slideshow(element, slideshowState);
        this.setupCloseHandlers();
    }

    get popupId(): string {
        return this.id;
    }

    show(): void {
        this.previouslyFocusedElement = document.activeElement as HTMLElement;
        this.element.classList.add('is-open');
        document.body.classList.add('popup-open');

        const container = this.element.querySelector<HTMLElement>('.popup__container');
        if (container) {
            this.cleanupFocusTrap = trapFocus(container);
        }

        this.slideshow.start();
        this.setCookie();
    }

    close(): void {
        this.element.classList.remove('is-open');
        document.body.classList.remove('popup-open');

        if (this.cleanupFocusTrap) {
            this.cleanupFocusTrap();
            this.cleanupFocusTrap = null;
        }

        this.slideshow.stop();
        this.restoreFocus();

        setTimeout(() => this.onClose(this.id), CLOSE_ANIMATION_MS);
    }

    destroy(): void {
        this.abortController.abort();
    }

    isOpen(): boolean {
        return this.element.classList.contains('is-open');
    }

    private setupCloseHandlers(): void {
        const { signal } = this.abortController;

        const closeBtn = this.element.querySelector<HTMLButtonElement>('.popup__close');
        closeBtn?.addEventListener('click', () => this.close(), { signal });

        const overlay = this.element.querySelector<HTMLElement>('.popup__overlay');
        overlay?.addEventListener('click', () => this.close(), { signal });
    }

    private setCookie(): void {
        if (DEV_MODE) return;

        const { cookieKey, cookieExpiry } = this.element.dataset;
        setCookie(`popup_${cookieKey}`, '1', parseInt(cookieExpiry, 10));
    }

    private restoreFocus(): void {
        this.previouslyFocusedElement?.focus?.();
    }
}

