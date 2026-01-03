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

    const focusableEls = Array.from(
        container.querySelectorAll<HTMLElement>(focusableSelectors.join(','))
    );

    if (focusableEls.length === 0) return () => {};

    const firstEl = focusableEls[0];
    const lastEl = focusableEls[focusableEls.length - 1];

    function handleKeydown(e: KeyboardEvent): void {
        if (e.key !== 'Tab') return;

        if (e.shiftKey) {
            if (document.activeElement === firstEl) {
                e.preventDefault();
                lastEl.focus();
            }
        } else {
            if (document.activeElement === lastEl) {
                e.preventDefault();
                firstEl.focus();
            }
        }
    }

    container.addEventListener('keydown', handleKeydown);
    firstEl.focus();

    return () => {
        container.removeEventListener('keydown', handleKeydown);
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
