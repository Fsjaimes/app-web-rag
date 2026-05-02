/**
 * Enfoque y scroll al primer campo con error de validación en un formulario.
 *
 * Convención del proyecto: marcar contenedores de campo con
 *   data-form-error-anchor="<clave>"
 * donde `clave` coincide con la clave en el objeto de errores (p. ej. clientErrors).
 * El orden lo define el orden en el DOM dentro del formulario (o contenedor raíz).
 *
 * Uso:
 *   const { focusFirstFormError } = useFormErrorFocus();
 *   await nextTick();
 *   focusFirstFormError(formRef.value ?? $refs.myForm, errors);
 */

export const FORM_ERROR_ANCHOR_ATTR = 'data-form-error-anchor';

/**
 * @param {HTMLElement | null | undefined} rootEl - <form> o contenedor que envuelva los anclas
 * @param {Record<string, unknown>} errors - Mapa de errores (valor truthy = hay error en ese campo)
 * @param {{ behavior?: ScrollBehavior, block?: ScrollLogicalPosition }} [scrollOptions]
 */
export function focusFirstFormError(rootEl, errors, scrollOptions = {}) {
    if (!rootEl || !errors || typeof errors !== 'object') return;

    const { behavior = 'smooth', block = 'center' } = scrollOptions;
    const selector = `[${FORM_ERROR_ANCHOR_ATTR}]`;
    const anchors = rootEl.querySelectorAll(selector);

    for (let i = 0; i < anchors.length; i++) {
        const anchor = anchors[i];
        const field = anchor.getAttribute(FORM_ERROR_ANCHOR_ATTR);
        if (!field || !errors[field]) continue;

        anchor.scrollIntoView({ behavior, block });
        requestAnimationFrame(() => focusWithinErrorAnchor(anchor));
        return;
    }
}

function focusWithinErrorAnchor(anchor) {
    const phoneInput = anchor.querySelector(
        'input.flag-input, input.form-control[type="number"].flag-input, .input-group input.form-control[type="number"]',
    );
    if (phoneInput && typeof phoneInput.focus === 'function') {
        phoneInput.focus({ preventScroll: true });
        return;
    }

    const flatpickrInput = anchor.querySelector('input.flatpickr-input');
    if (flatpickrInput && typeof flatpickrInput.focus === 'function') {
        flatpickrInput.focus({ preventScroll: true });
        return;
    }

    const input = anchor.querySelector(
        'input.form-control:not([readonly]), input:not([readonly]):not([type="hidden"]), textarea.form-control:not([readonly])',
    );
    if (input && typeof input.focus === 'function') {
        input.focus({ preventScroll: true });
        return;
    }

    const anyTextInput = anchor.querySelector(
        'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]):not([readonly])',
    );
    if (anyTextInput && typeof anyTextInput.focus === 'function') {
        anyTextInput.focus({ preventScroll: true });
        return;
    }

    const select = anchor.querySelector('select');
    if (!select) return;

    const next = select.nextElementSibling;
    if (next && next.classList && next.classList.contains('select2-container')) {
        const sel = next.querySelector('.select2-selection');
        if (sel && typeof sel.focus === 'function') {
            sel.focus();
            return;
        }
    }
    if (typeof select.focus === 'function') {
        select.focus({ preventScroll: true });
    }
}

/**
 * @returns {{ focusFirstFormError: typeof focusFirstFormError, FORM_ERROR_ANCHOR_ATTR: string }}
 */
export function useFormErrorFocus() {
    return {
        focusFirstFormError,
        FORM_ERROR_ANCHOR_ATTR,
    };
}
