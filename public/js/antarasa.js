document.addEventListener('DOMContentLoaded', () => {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js').catch((error) => {
            console.error('Service worker AntarRasa gagal didaftarkan:', error);
        });
    }

    const deliveryFields = document.querySelector('.delivery-fields');
    const deliveryOptions = document.querySelectorAll('input[name="delivery_method"]');

    const syncDeliveryFields = () => {
        if (!deliveryFields) return;
        const delivery = document.querySelector('input[name="delivery_method"]:checked')?.value === 'delivery';
        deliveryFields.hidden = !delivery;
        deliveryFields.querySelectorAll('input, textarea').forEach((field) => {
            field.required = delivery;
        });
    };

    deliveryOptions.forEach((option) => option.addEventListener('change', syncDeliveryFields));
    syncDeliveryFields();

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    document.querySelectorAll('input[data-image-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const preview = input.parentElement.querySelector('.image-preview');
            if (!file || !preview || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.addEventListener('load', () => {
                preview.replaceChildren();
                const image = document.createElement('img');
                image.src = reader.result;
                image.alt = 'Pratinjau gambar';
                preview.append(image);
            });
            reader.readAsDataURL(file);
        });
    });
});
