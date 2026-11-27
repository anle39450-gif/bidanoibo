import sweetAlertUrl from './sweetalert-2.1.2.js?url';
import './match-detail-dialog.js';

function loadSweetAlert() {
    if (typeof window.swal === 'function') {
        return Promise.resolve(window.swal);
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = sweetAlertUrl;
        script.onload = () => typeof window.swal === 'function' ? resolve(window.swal) : reject();
        script.onerror = () => {
            script.remove();
            reject();
        };
        document.head.append(script);
    });
}

let confirmationPending = false;

window.confirmSiteAction = async function ({ title, text, buttons, inputPlaceholder }) {
    if (confirmationPending) return inputPlaceholder ? null : false;

    const returnFocus = document.activeElement;
    confirmationPending = true;
    try {
        const swal = await loadSweetAlert();
        const confirmed = await swal({
            title,
            text,
            icon: 'warning',
            buttons,
            dangerMode: true,
            className: 'action-confirmation',
            ...(inputPlaceholder && {
                content: { element: 'input', attributes: { placeholder: inputPlaceholder, ariaLabel: inputPlaceholder } },
            }),
        });
        return inputPlaceholder ? (typeof confirmed === 'string' ? confirmed : null) : confirmed === true;
    } catch {
        return inputPlaceholder ? window.prompt(text) : window.confirm(text);
    } finally {
        confirmationPending = false;
        if (returnFocus?.isConnected) returnFocus.focus({ preventScroll: true });
    }
};

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-confirm-logout], [data-confirm-payment]');
    if (!button?.form) return;

    const isLogout = button.hasAttribute('data-confirm-logout');
    const confirmed = await window.confirmSiteAction({
        title: isLogout ? 'Đăng xuất?' : (button.dataset.confirmPaymentTitle || 'Cập nhật trả kèo?'),
        text: isLogout ? 'Bạn có chắc muốn đăng xuất khỏi Bida Nội Bộ?' : button.dataset.confirmPayment,
        buttons: isLogout ? ['Ở lại', 'Đăng xuất'] : ['Hủy', 'Xác nhận'],
    });

    if (confirmed) button.form.requestSubmit();
});
