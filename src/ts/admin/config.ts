/// <reference path="../types/wordpress.d.ts" />

export type SlideType = 'desktop' | 'mobile';
export type NotifyFn = (message: string, type?: 'error' | 'warning' | 'success') => void;

export const Keys: Record<SlideType, string> = {
    desktop: '_popup_slides_desktop',
    mobile: '_popup_slides_mobile',
};

export const Selectors = {
    repeater: '.popup-slides-repeater',
    slideList: '.popup-slides-list',
    slideItem: '.popup-slide-item',
    slideTitle: '.popup-slide-title',
    addBtn: '.popup-add-slide',
    removeBtn: '.popup-remove-slide',
    tabs: '.popup-content-tabs',
    tabBtn: '.popup-tab-btn',
    tabPanel: '.popup-tab-panel',
} as const;

export const TinyMCEDefaults: Record<string, unknown> = {
    wpautop: true,
    indent: false,
    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,spellchecker,fullscreen,wp_adv',
    toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
    plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview',
    height: 500,
    menubar: false,
    branding: false,
    convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
};

