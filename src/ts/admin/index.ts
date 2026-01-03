import { EditorManager } from './editor-manager';
import { MobileSyncIndicator } from './mobile-sync';
import { SlideManager } from './slides';
import { TabManager } from './tab-manager';
import { showNotice } from './utils';

document.addEventListener('DOMContentLoaded', () => {
    const editors = new EditorManager();
    const slides = new SlideManager(editors, showNotice);
    const tabs = new TabManager();
    const mobileSync = new MobileSyncIndicator(editors);

    tabs.init();
    slides.init();
    editors.bindFormSubmitSync();
    mobileSync.watch(slides);
});
