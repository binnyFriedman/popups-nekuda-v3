import { setCookie, getCookie, trapFocus } from './utils';
import { PopupQueue } from './popup-queue';

interface PopupSettings {
    devMode: boolean;
}
declare const popupSettings: PopupSettings | undefined;

interface PopupElement extends HTMLElement {
    dataset: {
        popupId: string;
        trigger: string;
        timeout: string;
        cookieKey: string;
        cookieExpiry: string;
    };
}

interface PopupState {
    element: PopupElement;
    currentSlide: number;
    autoAdvanceInterval: number | null;
    cleanupFocusTrap: (() => void) | null;
    previouslyFocusedElement: HTMLElement | null;
    isPaused: boolean;
}

const AUTO_ADVANCE_DELAY = 5000;
const TRANSITION_DURATION = 400;
const CLOSE_DELAY = 300;

const popupStates = new Map<string, PopupState>();
const DEV_MODE = popupSettings?.devMode ?? false;

const popupQueue = new PopupQueue({
    onShow: (queuedPopup) => {
        const state = popupStates.get(queuedPopup.id);
        if (state) {
            showPopupInternal(state.element);
        }
    },
});

function init(): void {
    const popups = document.querySelectorAll<PopupElement>('.popup');

    popups.forEach((popup) => {
        const id = popup.dataset.popupId;
        const cookieKey = popup.dataset.cookieKey;

        if (!DEV_MODE && getCookie(`popup_${cookieKey}`)) {
            return;
        }

        popupStates.set(id, {
            element: popup,
            currentSlide: 0,
            autoAdvanceInterval: null,
            cleanupFocusTrap: null,
            previouslyFocusedElement: null,
            isPaused: false,
        });

        setupTrigger(popup);
        setupCloseHandlers(popup);
        setupSlideNavigation(popup);
        setupPauseButton(popup);
    });
}

function queuePopup(popup: PopupElement): void {
    const popupId = popup.dataset.popupId;
    popupQueue.enqueue({
        id: popupId,
        show: () => showPopupInternal(popup),
    });
}

function setupTrigger(popup: PopupElement): void {
    const trigger = popup.dataset.trigger;
    const timeout = parseInt(popup.dataset.timeout, 10) * 1000;
    const popupId = popup.dataset.popupId;

    if (trigger === 'timeout') {
        setTimeout(() => {
            if (!popupQueue.wasTriggered(popupId)) {
                queuePopup(popup);
            }
        }, timeout);
    } else if (trigger === 'exit_intent') {
        setupExitIntent(popup);
    }
}

function setupExitIntent(popup: PopupElement): void {
    const popupId = popup.dataset.popupId;

    function handleMouseOut(e: MouseEvent): void {
        if (e.clientY < 0 && !popupQueue.wasTriggered(popupId)) {
            queuePopup(popup);
            document.removeEventListener('mouseout', handleMouseOut);
        }
    }

    document.addEventListener('mouseout', handleMouseOut);
}

function showPopupInternal(popup: PopupElement): void {
    const state = popupStates.get(popup.dataset.popupId);
    if (!state) return;

    state.previouslyFocusedElement = document.activeElement as HTMLElement;
    popup.classList.add('is-open');
    document.body.classList.add('popup-open');

    const container = popup.querySelector<HTMLElement>('.popup__container');
    if (container) {
        state.cleanupFocusTrap = trapFocus(container);
    }

    startAutoAdvance(popup);
    setPopupCookie(popup);
}

function setPopupCookie(popup: PopupElement): void {
    if (DEV_MODE) return;

    const cookieKey = popup.dataset.cookieKey;
    const cookieExpiry = parseInt(popup.dataset.cookieExpiry, 10);
    setCookie(`popup_${cookieKey}`, '1', cookieExpiry);
}

function closePopup(popup: PopupElement): void {
    const popupId = popup.dataset.popupId;
    const state = popupStates.get(popupId);
    if (!state) return;

    popup.classList.remove('is-open');
    document.body.classList.remove('popup-open');

    if (state.cleanupFocusTrap) {
        state.cleanupFocusTrap();
        state.cleanupFocusTrap = null;
    }

    stopAutoAdvance(popup);
    restoreFocus(state);

    setTimeout(() => {
        popupQueue.notifyClosed(popupId);
    }, CLOSE_DELAY);
}

function restoreFocus(state: PopupState): void {
    if (state.previouslyFocusedElement?.focus) {
        state.previouslyFocusedElement.focus();
    }
}

function setupCloseHandlers(popup: PopupElement): void {
    const closeBtn = popup.querySelector<HTMLButtonElement>('.popup__close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => closePopup(popup));
    }

    const overlay = popup.querySelector<HTMLElement>('.popup__overlay');
    if (overlay) {
        overlay.addEventListener('click', () => closePopup(popup));
    }

    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape' && popup.classList.contains('is-open')) {
            closePopup(popup);
        }
    });
}

function setupSlideNavigation(popup: PopupElement): void {
    const contents = popup.querySelectorAll<HTMLElement>('.popup__content');

    contents.forEach((content) => {
        const slides = content.querySelectorAll<HTMLElement>('.popup__slide');

        if (slides.length <= 1) return;

        content.addEventListener('mouseenter', () => stopAutoAdvance(popup));
        content.addEventListener('mouseleave', () => startAutoAdvance(popup));
        content.addEventListener('touchstart', () => stopAutoAdvance(popup), { passive: true });
        content.addEventListener('touchend', () => startAutoAdvance(popup), { passive: true });
    });
}

function getCurrentSlideIndex(content: HTMLElement): number {
    const slides = content.querySelectorAll<HTMLElement>('.popup__slide');
    let current = 0;

    slides.forEach((slide, index) => {
        if (slide.classList.contains('is-active')) {
            current = index;
        }
    });

    return current;
}

function goToSlide(popup: PopupElement, content: HTMLElement, index: number): void {
    const slides = content.querySelectorAll<HTMLElement>('.popup__slide');
    const currentIndex = getCurrentSlideIndex(content);

    if (currentIndex === index) return;

    const currentSlide = slides[currentIndex];
    const nextSlide = slides[index];

    currentSlide.classList.remove('is-active');
    currentSlide.classList.add('is-leaving');
    nextSlide.classList.add('is-active');

    setTimeout(() => {
        currentSlide.classList.remove('is-leaving');
    }, TRANSITION_DURATION);

    const state = popupStates.get(popup.dataset.popupId);
    if (state) {
        state.currentSlide = index;
    }
}

function startAutoAdvance(popup: PopupElement): void {
    const state = popupStates.get(popup.dataset.popupId);
    if (!state || state.autoAdvanceInterval || state.isPaused) return;

    const activeContent = getVisibleContent(popup);
    if (!activeContent) return;

    const slides = activeContent.querySelectorAll<HTMLElement>('.popup__slide');
    if (slides.length <= 1) return;

    state.autoAdvanceInterval = window.setInterval(() => {
        const currentIndex = getCurrentSlideIndex(activeContent);
        const nextIndex = (currentIndex + 1) % slides.length;
        goToSlide(popup, activeContent, nextIndex);
    }, AUTO_ADVANCE_DELAY);
}

function getVisibleContent(popup: PopupElement): HTMLElement | undefined {
    const contents = Array.from(popup.querySelectorAll<HTMLElement>('.popup__content'));
    return contents.find((content) => content.offsetParent !== null);
}

function stopAutoAdvance(popup: PopupElement): void {
    const state = popupStates.get(popup.dataset.popupId);
    if (!state?.autoAdvanceInterval) return;

    clearInterval(state.autoAdvanceInterval);
    state.autoAdvanceInterval = null;
}

function setupPauseButton(popup: PopupElement): void {
    const pauseButtons = popup.querySelectorAll<HTMLButtonElement>('.popup__pause');

    pauseButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const state = popupStates.get(popup.dataset.popupId);
            if (!state) return;

            state.isPaused = !state.isPaused;
            updatePauseButtonState(button, state.isPaused);

            if (state.isPaused) {
                stopAutoAdvance(popup);
            } else {
                startAutoAdvance(popup);
            }
        });
    });
}

function updatePauseButtonState(button: HTMLButtonElement, isPaused: boolean): void {
    button.setAttribute('aria-pressed', isPaused.toString());
    button.setAttribute('aria-label', isPaused ? 'Play slideshow' : 'Pause slideshow');
    button.classList.toggle('is-paused', isPaused);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
