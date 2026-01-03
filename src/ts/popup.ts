import { setCookie, getCookie, trapFocus } from './utils';

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
    triggered: boolean;
}

const AUTO_ADVANCE_DELAY = 5000;
const popupStates = new Map<string, PopupState>();

// Dev mode: skip cookie checks for debugging (set to false for production)
const DEV_MODE = true;

/**
 * Initialize all popups on DOM ready
 */
function init(): void {
    const popups = document.querySelectorAll<PopupElement>('.popup');

    popups.forEach((popup) => {
        const id = popup.dataset.popupId;
        const cookieKey = popup.dataset.cookieKey;

        // Check cookie - skip if already shown (disabled in dev mode)
        if (!DEV_MODE && getCookie(`popup_${cookieKey}`)) {
            return;
        }

        // Initialize state
        popupStates.set(id, {
            element: popup,
            currentSlide: 0,
            autoAdvanceInterval: null,
            cleanupFocusTrap: null,
            triggered: false,
        });

        // Set up trigger
        setupTrigger(popup);

        // Set up close handlers
        setupCloseHandlers(popup);

        // Set up slide navigation
        setupSlideNavigation(popup);
    });
}

/**
 * Set up popup trigger based on type
 */
function setupTrigger(popup: PopupElement): void {
    const trigger = popup.dataset.trigger;
    const timeout = parseInt(popup.dataset.timeout, 10) * 1000;
    const state = popupStates.get(popup.dataset.popupId);

    if (!state) return;

    if (trigger === 'timeout') {
        setTimeout(() => {
            if (!state.triggered) {
                showPopup(popup);
            }
        }, timeout);
    } else if (trigger === 'exit_intent') {
        setupExitIntent(popup);
    }
}

/**
 * Set up exit intent detection
 */
function setupExitIntent(popup: PopupElement): void {
    const popupId = popup.dataset.popupId;

    function handleMouseOut(e: MouseEvent): void {
        const state = popupStates.get(popupId);
        if (state && e.clientY < 0 && !state.triggered) {
            showPopup(popup);
            document.removeEventListener('mouseout', handleMouseOut);
        }
    }

    document.addEventListener('mouseout', handleMouseOut);
}

/**
 * Show popup
 */
function showPopup(popup: PopupElement): void {
    const state = popupStates.get(popup.dataset.popupId);
    if (!state || state.triggered) return;

    state.triggered = true;
    popup.classList.add('is-open');

    // Trap focus
    const container = popup.querySelector<HTMLElement>('.popup__container');
    if (container) {
        state.cleanupFocusTrap = trapFocus(container);
    }

    // Start auto-advance for slides
    startAutoAdvance(popup);

    // Set cookie on show (disabled in dev mode)
    if (!DEV_MODE) {
        const cookieKey = popup.dataset.cookieKey;
        const cookieExpiry = parseInt(popup.dataset.cookieExpiry, 10);
        setCookie(`popup_${cookieKey}`, '1', cookieExpiry);
    }
}

/**
 * Close popup
 */
function closePopup(popup: PopupElement): void {
    const state = popupStates.get(popup.dataset.popupId);
    if (!state) return;

    popup.classList.remove('is-open');

    // Clean up focus trap
    if (state.cleanupFocusTrap) {
        state.cleanupFocusTrap();
        state.cleanupFocusTrap = null;
    }

    // Stop auto-advance
    stopAutoAdvance(popup);
}

/**
 * Set up close handlers
 */
function setupCloseHandlers(popup: PopupElement): void {
    // Close button
    const closeBtn = popup.querySelector<HTMLButtonElement>('.popup__close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => closePopup(popup));
    }

    // Overlay click
    const overlay = popup.querySelector<HTMLElement>('.popup__overlay');
    if (overlay) {
        overlay.addEventListener('click', () => closePopup(popup));
    }

    // Escape key
    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape' && popup.classList.contains('is-open')) {
            closePopup(popup);
        }
    });
}

/**
 * Set up slide behavior (pause on hover only, no navigation)
 */
function setupSlideNavigation(popup: PopupElement): void {
    const contents = popup.querySelectorAll<HTMLElement>('.popup__content');

    contents.forEach((content) => {
        const slides = content.querySelectorAll<HTMLElement>('.popup__slide');

        if (slides.length <= 1) return;

        // Pause auto-advance on hover
        content.addEventListener('mouseenter', () => stopAutoAdvance(popup));
        content.addEventListener('mouseleave', () => startAutoAdvance(popup));
    });
}

/**
 * Get current slide index
 */
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

/**
 * Go to specific slide
 */
function goToSlide(popup: PopupElement, content: HTMLElement, index: number): void {
    const slides = content.querySelectorAll<HTMLElement>('.popup__slide');

    slides.forEach((slide, i) => {
        slide.classList.toggle('is-active', i === index);
    });

    const state = popupStates.get(popup.dataset.popupId);
    if (state) {
        state.currentSlide = index;
    }
}

/**
 * Start auto-advance
 */
function startAutoAdvance(popup: PopupElement): void {
    const state = popupStates.get(popup.dataset.popupId);
    if (!state || state.autoAdvanceInterval) return;

    // Get the currently visible content (desktop or mobile)
    const contents = popup.querySelectorAll<HTMLElement>('.popup__content');
    let activeContent: HTMLElement | null = null;

    contents.forEach((content) => {
        if (content.offsetParent !== null) {
            activeContent = content;
        }
    });

    if (!activeContent) return;

    const slides = activeContent.querySelectorAll<HTMLElement>('.popup__slide');
    if (slides.length <= 1) return;

    state.autoAdvanceInterval = window.setInterval(() => {
        const currentIndex = getCurrentSlideIndex(activeContent!);
        const nextIndex = (currentIndex + 1) % slides.length;
        goToSlide(popup, activeContent!, nextIndex);
    }, AUTO_ADVANCE_DELAY);
}

/**
 * Stop auto-advance
 */
function stopAutoAdvance(popup: PopupElement): void {
    const state = popupStates.get(popup.dataset.popupId);
    if (!state || !state.autoAdvanceInterval) return;

    clearInterval(state.autoAdvanceInterval);
    state.autoAdvanceInterval = null;
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
