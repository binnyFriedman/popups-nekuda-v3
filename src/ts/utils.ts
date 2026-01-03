/**
 * Cookie utilities
 */
export function setCookie(name: string, value: string, days: number): void {
    const expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
}

export function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? match[2] : null;
}

/**
 * Focus trap for accessibility
 */
export function trapFocus(container: HTMLElement): () => void {
    const focusableSelectors = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ];

    const getFocusableElements = (): HTMLElement[] => {
        return Array.from(
            container.querySelectorAll<HTMLElement>(focusableSelectors.join(','))
        ).filter(el => el.offsetParent !== null); // Only visible elements
    };

    const focusableEls = getFocusableElements();
    if (focusableEls.length === 0) return () => {};

    const previouslyFocused = document.activeElement as HTMLElement | null;

    function handleKeydown(e: KeyboardEvent): void {
        if (e.key !== 'Tab') return;

        const currentFocusable = getFocusableElements();
        if (currentFocusable.length === 0) return;

        const firstEl = currentFocusable[0];
        const lastEl = currentFocusable[currentFocusable.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === firstEl || !container.contains(document.activeElement)) {
                e.preventDefault();
                lastEl.focus();
            }
        } else {
            if (document.activeElement === lastEl || !container.contains(document.activeElement)) {
                e.preventDefault();
                firstEl.focus();
            }
        }
    }

    function handleFocusIn(e: FocusEvent): void {
        const target = e.target as HTMLElement;
        if (!container.contains(target)) {
            e.preventDefault();
            e.stopPropagation();
            const currentFocusable = getFocusableElements();
            if (currentFocusable.length > 0) {
                currentFocusable[0].focus();
            }
        }
    }

    document.addEventListener('keydown', handleKeydown);
    document.addEventListener('focusin', handleFocusIn);
    
    // Initial focus
    focusableEls[0].focus();

    return () => {
        document.removeEventListener('keydown', handleKeydown);
        document.removeEventListener('focusin', handleFocusIn);
        // Restore focus to previously focused element
        if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
            previouslyFocused.focus();
        }
    };
}

/**
 * Swipe detection for mobile
 */
export function detectSwipe(
    element: HTMLElement,
    onSwipe: (direction: 'left' | 'right') => void
): void {
    let touchStartX = 0;
    let touchEndX = 0;
    const minSwipeDistance = 50;

    element.addEventListener('touchstart', (e: TouchEvent) => {
        touchStartX = e.touches[0].clientX;
    }, { passive: true });

    element.addEventListener('touchend', (e: TouchEvent) => {
        touchEndX = e.changedTouches[0].clientX;
        const distance = touchEndX - touchStartX;

        if (Math.abs(distance) >= minSwipeDistance) {
            onSwipe(distance > 0 ? 'right' : 'left');
        }
    }, { passive: true });
}
