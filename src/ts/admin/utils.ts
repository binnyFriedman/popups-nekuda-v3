const NOTICE_DISPLAY_MS = 5000;
const FADE_DURATION_MS = 300;

export function showNotice(message: string, type: 'error' | 'warning' | 'success' = 'error'): void {
    const notice = document.createElement('div');
    notice.className = `notice notice-${type} is-dismissible`;
    notice.innerHTML = `<p>${message}</p>`;

    const anchor = document.querySelector('.wrap h1') ?? document.querySelector('.wrap');
    anchor?.insertAdjacentElement('afterend', notice);

    setTimeout(() => {
        notice.style.transition = `opacity ${FADE_DURATION_MS}ms`;
        notice.style.opacity = '0';
        setTimeout(() => notice.remove(), FADE_DURATION_MS);
    }, NOTICE_DISPLAY_MS);
}

export function htmlToElement(html: string): HTMLElement {
    const template = document.createElement('template');
    template.innerHTML = html.trim();
    return template.content.firstElementChild as HTMLElement;
}

export function executeScripts(html: string): void {
    const parser = document.createElement('div');
    parser.innerHTML = html;

    parser.querySelectorAll('script').forEach((scriptTag) => {
        const executable = document.createElement('script');
        executable.textContent = scriptTag.textContent;
        document.body.appendChild(executable);
        executable.remove();
    });
}
