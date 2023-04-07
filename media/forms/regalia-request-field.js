/* prepare element showing/hiding events for special accommodations fields */
document.addEventListener('DigraphDOMReady', (e) => {
    const fields = e.target.getElementsByClassName('regalia-request-field');
    for (const i in fields) {
        if (Object.hasOwnProperty.call(fields, i)) {
            const field = fields[i];
            field.addEventListener('change', e => update(field));
            update(field);
        }
    }
    function update(field) {
        const optOut = field.getElementsByClassName('regalia-request-field__needs-regalia')[0].getElementsByTagName('input')[0];
        const infoForm = field.getElementsByClassName('regalia-request-field__info-form')[0];
        if (optOut.checked) {
            infoForm.style.display = null;
        } else {
            infoForm.style.display = 'none';
        }
    }
});