import { router } from '@inertiajs/react';

const DELETE_CONFIRMATION_MESSAGE =
    'Da li ste sigurni da želite obrisati ovaj zapis?';

let deleteGuardInitialized = false;

/**
 * Determine whether an HTML form performs a delete action.
 */
function isDeleteForm(form: HTMLFormElement): boolean {
    if (form.method.toLowerCase() === 'delete') {
        return true;
    }

    const methodOverrideInput = form.querySelector<HTMLInputElement>(
        'input[name="_method"]'
    );

    return methodOverrideInput?.value.toLowerCase() === 'delete';
}

/**
 * Enable global confirmation before every delete request in the app.
 */
export function initializeDeleteConfirmationGuard(): void {
    if (deleteGuardInitialized) {
        return;
    }

    deleteGuardInitialized = true;

    // Guard delete calls made directly via Inertia router API.
    const originalRouterDelete = router.delete.bind(router);

    router.delete = ((url, options) => {
        if (!window.confirm(DELETE_CONFIRMATION_MESSAGE)) {
            return;
        }

        return originalRouterDelete(url, options);
    }) as typeof router.delete;

    // Guard delete submissions done through regular <Form method="delete"> flow.
    document.addEventListener(
        'submit',
        (event) => {
            const target = event.target;

            if (!(target instanceof HTMLFormElement)) {
                return;
            }

            if (!isDeleteForm(target)) {
                return;
            }

            if (!window.confirm(DELETE_CONFIRMATION_MESSAGE)) {
                event.preventDefault();
                event.stopPropagation();
            }
        },
        true
    );
}
