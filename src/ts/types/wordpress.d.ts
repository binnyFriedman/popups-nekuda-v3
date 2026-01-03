/**
 * WordPress admin and TinyMCE type definitions
 */

// Popup admin localized data
interface PopupAdmin {
    ajaxUrl: string;
    nonce: string;
}

declare const popupAdmin: PopupAdmin;

// TinyMCE types
interface TinyMCEEditor {
    id: string;
    settings: Record<string, unknown>;
    getContent(): string;
    setContent(content: string): void;
    save(): void;
    remove(): void;
    on(event: string, callback: () => void): void;
}

interface TinyMCE {
    editors: TinyMCEEditor[];
    get(id: string): TinyMCEEditor | null;
    init(settings: Record<string, unknown>): void;
    triggerSave(): void;
}

declare const tinymce: TinyMCE;

// WordPress quicktags
interface QuicktagsConfig {
    id: string;
}

declare function quicktags(config: QuicktagsConfig): void;

interface QTagsStatic {
    _buttonsInit(): void;
}

declare const QTags: QTagsStatic;

// WordPress active editor
declare const wpActiveEditor: string;

// WordPress global
interface WPGlobal {
    editor?: {
        getContent(): string;
    };
}

declare const wp: WPGlobal;

// jQuery types (supplement @types/jquery)
interface JQueryStatic {
    post(url: string, data: object, callback?: (response: unknown) => void): JQueryPromise<unknown>;
}

interface JQueryPromise<T> {
    fail(callback: () => void): JQueryPromise<T>;
}






