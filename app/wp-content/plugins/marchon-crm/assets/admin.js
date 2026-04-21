document.addEventListener('DOMContentLoaded', function () {
  var interestSelect = document.getElementById('_mcrm_interest_type');
  var terrainBox = document.querySelector('[data-mcrm-terrain-fields]');
  var cpfInput = document.querySelector('[data-mask="cpf"]');
  var phoneInput = document.querySelector('[data-mask="phone"]');

  function digitsOnly(value) {
    return (value || '').replace(/\D+/g, '');
  }

  function formatCpf(value) {
    var digits = digitsOnly(value).slice(0, 11);

    if (digits.length <= 3) return digits;
    if (digits.length <= 6) return digits.replace(/(\d{3})(\d+)/, '$1.$2');
    if (digits.length <= 9) return digits.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
    return digits.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '$1.$2.$3-$4');
  }

  function formatPhone(value) {
    var digits = digitsOnly(value).slice(0, 11);

    if (digits.length <= 2) return digits;
    if (digits.length <= 6) return digits.replace(/(\d{2})(\d+)/, '($1) $2');
    if (digits.length <= 10) return digits.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3');
    return digits.replace(/(\d{2})(\d{5})(\d+)/, '($1) $2-$3');
  }

  function bindMask(input, formatter) {
    if (!input) return;
    var apply = function () {
      input.value = formatter(input.value);
    };
    input.addEventListener('input', apply);
    input.addEventListener('blur', apply);
    apply();
  }

  bindMask(cpfInput, formatCpf);
  bindMask(phoneInput, formatPhone);

  if (interestSelect && terrainBox) {
    function syncTerrainFields() {
      var isTerrain = interestSelect.value === 'terreno';
      terrainBox.style.display = isTerrain ? 'block' : 'none';
    }

    interestSelect.addEventListener('change', syncTerrainFields);
    syncTerrainFields();
  }
});
