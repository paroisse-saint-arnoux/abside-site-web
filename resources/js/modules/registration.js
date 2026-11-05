// Formulaire d'inscription : le nombre de places proposé suit les places restantes de la date choisie.
export function initRegistration() {
    document.querySelectorAll('#inscription form').forEach((form) => {
        const select = form.querySelector('[data-seats]');
        if (!select || !form.querySelector('input[type="radio"][name="event_date_id"]')) return;
        const update = () => {
            const radio = form.querySelector('input[name="event_date_id"]:checked');
            const max = Number(radio?.dataset.max || 10);
            const current = Math.min(Number(select.value) || 1, max);
            select.innerHTML = Array.from({ length: max }, (_, i) => `<option value="${i + 1}"${i + 1 === current ? ' selected' : ''}>${i + 1}</option>`).join('');
        };
        form.querySelectorAll('input[name="event_date_id"]').forEach((r) => r.addEventListener('change', update));
        update();
    });
}
