export interface QueuedPopup {
    id: string;
    show: () => void;
}

export interface PopupQueueCallbacks {
    onShow: (popup: QueuedPopup) => void;
    onQueueChange?: (queue: QueuedPopup[]) => void;
}

export class PopupQueue {
    private queue: QueuedPopup[] = [];
    private activePopupId: string | null = null;
    private triggeredIds = new Set<string>();
    private callbacks: PopupQueueCallbacks;

    constructor(callbacks: PopupQueueCallbacks) {
        this.callbacks = callbacks;
    }

    enqueue(popup: QueuedPopup): boolean {
        if (this.triggeredIds.has(popup.id)) {
            return false;
        }

        this.triggeredIds.add(popup.id);
        this.queue.push(popup);
        this.callbacks.onQueueChange?.(this.queue);

        if (this.activePopupId === null) {
            this.showNext();
        }

        return true;
    }

    notifyClosed(popupId: string): void {
        if (this.activePopupId !== popupId) {
            return;
        }

        this.activePopupId = null;
        this.showNext();
    }

    private showNext(): void {
        if (this.queue.length === 0) {
            this.activePopupId = null;
            return;
        }

        const popup = this.queue.shift()!;
        this.activePopupId = popup.id;
        this.callbacks.onQueueChange?.(this.queue);
        this.callbacks.onShow(popup);
    }

    wasTriggered(popupId: string): boolean {
        return this.triggeredIds.has(popupId);
    }

    getActivePopupId(): string | null {
        return this.activePopupId;
    }

    getQueueLength(): number {
        return this.queue.length;
    }

    isActive(): boolean {
        return this.activePopupId !== null;
    }

    reset(): void {
        this.queue = [];
        this.activePopupId = null;
        this.triggeredIds.clear();
    }
}
