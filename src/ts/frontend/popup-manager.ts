import { getCookie } from './utils';
import { PopupQueue } from './popup-queue';
import { Popup, PopupElement } from './popup';

interface PopupSettings {
    devMode: boolean;
}
declare const popupSettings: PopupSettings | undefined;

const SECONDS_TO_MS = 1000;
const EXIT_INTENT_Y_THRESHOLD = 0;
const DEV_MODE = popupSettings?.devMode ?? false;

export class PopupManager {
    private readonly popups = new Map<string, Popup>();
    private readonly queue: PopupQueue;

    constructor() {
        this.queue = new PopupQueue({
            onShow: (queuedPopup) => {
                const popup = this.popups.get(queuedPopup.id);
                popup?.show();
            },
        });
    }

    init(): void {
        document.addEventListener('keydown', this.handleEscapeKey);

        const elements = document.querySelectorAll<PopupElement>('.popup');

        elements.forEach((element) => {
            const { popupId, cookieKey } = element.dataset;

            if (!DEV_MODE && getCookie(`popup_${cookieKey}`)) {
                return;
            }

            const popup = new Popup(element, (id) => this.queue.notifyClosed(id));
            this.popups.set(popupId, popup);
            this.setupTrigger(element);
        });
    }

    closePopup(popupId: string): void {
        this.popups.get(popupId)?.close();
    }

    destroyPopup(popupId: string): void {
        this.popups.get(popupId)?.destroy();
        this.popups.delete(popupId);
    }

    private setupTrigger(element: PopupElement): void {
        const { trigger, timeout, popupId } = element.dataset;
        const timeoutMs = parseInt(timeout, 10) * SECONDS_TO_MS;

        if (trigger === 'timeout') {
            setTimeout(() => {
                if (!this.queue.wasTriggered(popupId)) {
                    this.queuePopup(popupId);
                }
            }, timeoutMs);
        } else if (trigger === 'exit_intent') {
            this.setupExitIntent(element);
        }
    }

    private setupExitIntent(element: PopupElement): void {
        const { popupId } = element.dataset;
        const controller = new AbortController();

        document.addEventListener(
            'mouseout',
            (e: MouseEvent) => {
                if (e.clientY < EXIT_INTENT_Y_THRESHOLD && !this.queue.wasTriggered(popupId)) {
                    this.queuePopup(popupId);
                    controller.abort();
                }
            },
            { signal: controller.signal }
        );
    }

    private queuePopup(popupId: string): void {
        const popup = this.popups.get(popupId);
        if (!popup) return;

        this.queue.enqueue({
            id: popupId,
            show: () => popup.show(),
        });
    }

    private handleEscapeKey = (e: KeyboardEvent): void => {
        if (e.key !== 'Escape') return;

        for (const [, popup] of this.popups) {
            if (popup.isOpen()) {
                popup.close();
                break;
            }
        }
    };
}

