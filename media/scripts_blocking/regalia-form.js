(() => {

  document.addEventListener('DigraphDOMReady', (e) => {
    Array.from(e.target.getElementsByClassName('regalia-form'))
      .forEach(setupRegaliForm);
  });

  Array.from(document.getElementsByClassName('regalia-form'))
    .forEach(setupRegaliForm);

  function setupRegaliForm(form) {
    if (form.classList.contains('regalia-form--js')) return;
    form.classList.add('regalia-form--js');
    const parts = form.getElementsByClassName('regalia-form__parts')[0];
    const part_hat = parts.getElementsByTagName('input')[0];
    const part_robe = parts.getElementsByTagName('input')[1];
    const part_hood = parts.getElementsByTagName('input')[2];
    const degree = form.getElementsByClassName('regalia-form__degree')[0];
    const almaMater = form.getElementsByClassName('regalia-form__alma-mater')[0];
    const size = form.getElementsByClassName('regalia-form__size')[0];
    const size_height = size.getElementsByClassName('regalia-form__height')[0];
    const size_weight = size.getElementsByClassName('regalia-form__weight')[0];
    const size_gender = size.getElementsByClassName('regalia-form__gender')[0];
    const size_hat = size.getElementsByClassName('regalia-form__hat')[0];
    const fn_update = () => {
      // set sub-fields to display or hide based on parts that are checked
      degree.style.display = (part_hood.checked || part_robe.checked) ? null : 'none';
      almaMater.style.display = part_hood.checked ? null : 'none';
      size.style.display = (part_hat.checked || part_robe.checked) ? null : 'none';
      size_hat.style.display = part_hat.checked ? null : 'none';
      size_height.style.display =
        size_weight.style.display =
        size_gender.style.display = part_robe.checked ? null : 'none';
    };
    fn_update();
    parts.addEventListener('change', fn_update);
  }

})();