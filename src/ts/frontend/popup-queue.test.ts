import { describe, it, expect, vi, beforeEach } from 'vitest';
import { PopupQueue, QueuedPopup } from './popup-queue';

describe('PopupQueue', () => {
    let queue: PopupQueue;
    let onShow: ReturnType<typeof vi.fn>;
    let onQueueChange: ReturnType<typeof vi.fn>;

    beforeEach(() => {
        onShow = vi.fn();
        onQueueChange = vi.fn();
        queue = new PopupQueue({ onShow, onQueueChange });
    });

    describe('enqueue', () => {
        it('shows first popup immediately when queue is empty', () => {
            const popup: QueuedPopup = { id: 'popup-1', show: vi.fn() };

            queue.enqueue(popup);

            expect(onShow).toHaveBeenCalledWith(popup);
            expect(queue.getActivePopupId()).toBe('popup-1');
        });

        it('queues second popup when first is active', () => {
            const popup1: QueuedPopup = { id: 'popup-1', show: vi.fn() };
            const popup2: QueuedPopup = { id: 'popup-2', show: vi.fn() };

            queue.enqueue(popup1);
            queue.enqueue(popup2);

            expect(onShow).toHaveBeenCalledTimes(1);
            expect(onShow).toHaveBeenCalledWith(popup1);
            expect(queue.getQueueLength()).toBe(1);
        });

        it('prevents duplicate queuing of same popup', () => {
            const popup: QueuedPopup = { id: 'popup-1', show: vi.fn() };

            const firstResult = queue.enqueue(popup);
            const secondResult = queue.enqueue(popup);

            expect(firstResult).toBe(true);
            expect(secondResult).toBe(false);
            expect(onShow).toHaveBeenCalledTimes(1);
        });

        it('queues multiple popups in order', () => {
            const popup1: QueuedPopup = { id: 'popup-1', show: vi.fn() };
            const popup2: QueuedPopup = { id: 'popup-2', show: vi.fn() };
            const popup3: QueuedPopup = { id: 'popup-3', show: vi.fn() };

            queue.enqueue(popup1);
            queue.enqueue(popup2);
            queue.enqueue(popup3);

            expect(queue.getQueueLength()).toBe(2); // popup-2 and popup-3 waiting
            expect(queue.getActivePopupId()).toBe('popup-1');
        });
    });

    describe('notifyClosed', () => {
        it('shows next popup when current closes', () => {
            const popup1: QueuedPopup = { id: 'popup-1', show: vi.fn() };
            const popup2: QueuedPopup = { id: 'popup-2', show: vi.fn() };

            queue.enqueue(popup1);
            queue.enqueue(popup2);

            expect(onShow).toHaveBeenCalledTimes(1);

            queue.notifyClosed('popup-1');

            expect(onShow).toHaveBeenCalledTimes(2);
            expect(onShow).toHaveBeenLastCalledWith(popup2);
            expect(queue.getActivePopupId()).toBe('popup-2');
        });

        it('clears active popup when queue is empty', () => {
            const popup: QueuedPopup = { id: 'popup-1', show: vi.fn() };

            queue.enqueue(popup);
            queue.notifyClosed('popup-1');

            expect(queue.getActivePopupId()).toBe(null);
            expect(queue.isActive()).toBe(false);
        });

        it('ignores close notification for non-active popup', () => {
            const popup1: QueuedPopup = { id: 'popup-1', show: vi.fn() };
            const popup2: QueuedPopup = { id: 'popup-2', show: vi.fn() };

            queue.enqueue(popup1);
            queue.enqueue(popup2);

            queue.notifyClosed('popup-2'); // Wrong popup

            expect(queue.getActivePopupId()).toBe('popup-1');
            expect(queue.getQueueLength()).toBe(1);
        });

        it('processes entire queue sequentially', () => {
            const popup1: QueuedPopup = { id: 'popup-1', show: vi.fn() };
            const popup2: QueuedPopup = { id: 'popup-2', show: vi.fn() };
            const popup3: QueuedPopup = { id: 'popup-3', show: vi.fn() };

            queue.enqueue(popup1);
            queue.enqueue(popup2);
            queue.enqueue(popup3);

            queue.notifyClosed('popup-1');
            expect(queue.getActivePopupId()).toBe('popup-2');

            queue.notifyClosed('popup-2');
            expect(queue.getActivePopupId()).toBe('popup-3');

            queue.notifyClosed('popup-3');
            expect(queue.getActivePopupId()).toBe(null);
            expect(queue.getQueueLength()).toBe(0);
        });
    });

    describe('wasTriggered', () => {
        it('tracks triggered popups', () => {
            const popup: QueuedPopup = { id: 'popup-1', show: vi.fn() };

            expect(queue.wasTriggered('popup-1')).toBe(false);

            queue.enqueue(popup);

            expect(queue.wasTriggered('popup-1')).toBe(true);
        });

        it('remembers triggered state after popup closes', () => {
            const popup: QueuedPopup = { id: 'popup-1', show: vi.fn() };

            queue.enqueue(popup);
            queue.notifyClosed('popup-1');

            expect(queue.wasTriggered('popup-1')).toBe(true);
        });
    });

    describe('callbacks', () => {
        it('calls onQueueChange when queue changes', () => {
            const popup1: QueuedPopup = { id: 'popup-1', show: vi.fn() };
            const popup2: QueuedPopup = { id: 'popup-2', show: vi.fn() };

            queue.enqueue(popup1);
            queue.enqueue(popup2);

            // First call: popup-1 added (then immediately shown, so queue becomes empty after shift)
            // Second call: popup-2 added
            expect(onQueueChange).toHaveBeenCalled();
        });
    });

    describe('reset', () => {
        it('clears all state', () => {
            const popup1: QueuedPopup = { id: 'popup-1', show: vi.fn() };
            const popup2: QueuedPopup = { id: 'popup-2', show: vi.fn() };

            queue.enqueue(popup1);
            queue.enqueue(popup2);

            queue.reset();

            expect(queue.getActivePopupId()).toBe(null);
            expect(queue.getQueueLength()).toBe(0);
            expect(queue.wasTriggered('popup-1')).toBe(false);
        });
    });
});

