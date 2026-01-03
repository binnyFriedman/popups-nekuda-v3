import { Selectors } from './config';

export class TabManager {
    private buttons: HTMLButtonElement[] = [];
    private panels: HTMLElement[] = [];

    init(): void {
        const container = document.querySelector(Selectors.tabs);
        if (!container) return;

        this.buttons = Array.from(container.querySelectorAll(Selectors.tabBtn));
        this.panels = Array.from(container.querySelectorAll(Selectors.tabPanel));

        this.buttons.forEach((btn) => {
            btn.addEventListener('click', () => this.switchTo(btn.dataset.tab ?? ''));
        });

        this.restore();
    }

    private switchTo(tabId: string): void {
        this.buttons.forEach((b) => b.classList.toggle('is-active', b.dataset.tab === tabId));
        this.panels.forEach((p) => p.classList.toggle('is-active', p.dataset.panel === tabId));

        try { sessionStorage.setItem('popup_active_tab', tabId); } catch { /* noop */ }
    }

    private restore(): void {
        try {
            const saved = sessionStorage.getItem('popup_active_tab');
            if (saved) this.switchTo(saved);
        } catch { /* noop */ }
    }
}

