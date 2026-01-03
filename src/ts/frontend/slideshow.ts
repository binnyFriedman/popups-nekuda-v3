/** Long enough to read content but keeps engagement */
const AUTO_ADVANCE_DELAY_MS = 5000;

/** Must match .popup__slide transition in SCSS */
const SLIDE_TRANSITION_MS = 400;

const MIN_SLIDES_FOR_AUTO_ADVANCE = 2;

export interface SlideshowState {
    currentSlide: number;
    autoAdvanceInterval: number | null;
    isPaused: boolean;
    abortController: AbortController;
}

export class Slideshow {
    private readonly container: HTMLElement;
    private readonly state: SlideshowState;

    constructor(container: HTMLElement, state: SlideshowState) {
        this.container = container;
        this.state = state;

        this.setupNavigation();
        this.setupPauseButton();
    }

    start(): void {
        if (this.state.autoAdvanceInterval || this.state.isPaused) return;

        const content = this.getVisibleContent();
        if (!content) return;

        const slides = content.querySelectorAll<HTMLElement>('.popup__slide');
        if (slides.length < MIN_SLIDES_FOR_AUTO_ADVANCE) return;

        this.state.autoAdvanceInterval = window.setInterval(() => {
            const currentIndex = this.getCurrentSlideIndex(content);
            const nextIndex = (currentIndex + 1) % slides.length;
            this.goToSlide(content, nextIndex);
        }, AUTO_ADVANCE_DELAY_MS);
    }

    stop(): void {
        if (!this.state.autoAdvanceInterval) return;

        clearInterval(this.state.autoAdvanceInterval);
        this.state.autoAdvanceInterval = null;
    }

    private setupNavigation(): void {
        const { signal } = this.state.abortController;
        const contentContainers = this.container.querySelectorAll<HTMLElement>('.popup__content');

        contentContainers.forEach((content) => {
            const slides = content.querySelectorAll<HTMLElement>('.popup__slide');
            if (slides.length < MIN_SLIDES_FOR_AUTO_ADVANCE) return;

            content.addEventListener('mouseenter', () => this.stop(), { signal });
            content.addEventListener('mouseleave', () => this.start(), { signal });
            content.addEventListener('touchstart', () => this.stop(), { passive: true, signal });
            content.addEventListener('touchend', () => this.start(), { passive: true, signal });
        });
    }

    private setupPauseButton(): void {
        const { signal } = this.state.abortController;
        const pauseButtons = this.container.querySelectorAll<HTMLButtonElement>('.popup__pause');

        pauseButtons.forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    this.state.isPaused = !this.state.isPaused;
                    this.updatePauseButtonState(button);

                    if (this.state.isPaused) {
                        this.stop();
                    } else {
                        this.start();
                    }
                },
                { signal }
            );
        });
    }

    private updatePauseButtonState(button: HTMLButtonElement): void {
        const { isPaused } = this.state;
        button.setAttribute('aria-pressed', isPaused.toString());
        button.setAttribute('aria-label', isPaused ? 'Play slideshow' : 'Pause slideshow');
        button.classList.toggle('is-paused', isPaused);
    }

    private getCurrentSlideIndex(content: HTMLElement): number {
        const slides = Array.from(content.querySelectorAll<HTMLElement>('.popup__slide'));
        const activeIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
        return activeIndex >= 0 ? activeIndex : 0;
    }

    private goToSlide(content: HTMLElement, targetIndex: number): void {
        const slides = content.querySelectorAll<HTMLElement>('.popup__slide');
        const currentIndex = this.getCurrentSlideIndex(content);

        if (currentIndex === targetIndex) return;

        const exitingSlide = slides[currentIndex];
        const enteringSlide = slides[targetIndex];

        exitingSlide.classList.remove('is-active');
        exitingSlide.classList.add('is-leaving');
        enteringSlide.classList.add('is-active');

        setTimeout(() => {
            exitingSlide.classList.remove('is-leaving');
        }, SLIDE_TRANSITION_MS);

        this.state.currentSlide = targetIndex;
    }

    private getVisibleContent(): HTMLElement | undefined {
        const contentContainers = Array.from(
            this.container.querySelectorAll<HTMLElement>('.popup__content')
        );
        return contentContainers.find((content) => content.offsetParent !== null);
    }
}

