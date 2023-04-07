/* prepare element showing/hiding events for special accommodations fields */
document.addEventListener('DigraphDOMReady', (e) => {
    const fields = e.target.getElementsByClassName('regalia-degree-field');
    for (const i in fields) {
        if (Object.hasOwnProperty.call(fields, i)) {
            const field = fields[i];
            field.addEventListener('change', e => update(field));
            update(field);
        }
    }
    function update(wrapper) {
        const type = wrapper.getElementsByClassName('regalia-degree-field__type')[0].getElementsByTagName('select')[0];
        const field = wrapper.getElementsByClassName('regalia-degree-field__field')[0];
        if (type.value.substring(0,8) == '[preset]' || type.value == '0') {
            field.style.display = 'none';
        }else {
            field.style.display = null;
        }
    }
});