document.addEventListener('DOMContentLoaded', function () {
  var interestSelect = document.querySelector('[data-mcrm-interest]');
  var terrainBox = document.querySelector('[data-mcrm-terrain]');
  var cpfInputs = document.querySelectorAll('[data-mask="cpf"]');
  var phoneInputs = document.querySelectorAll('[data-mask="phone"]');
  var navLinks = Array.prototype.slice.call(document.querySelectorAll('.mcrm-sidebar-nav a[href^="#"]'));

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

  function bindMask(nodes, formatter) {
    nodes.forEach(function (node) {
      var apply = function () {
        node.value = formatter(node.value);
      };
      node.addEventListener('input', apply);
      node.addEventListener('blur', apply);
      apply();
    });
  }

  function syncTerrain() {
    if (!interestSelect || !terrainBox) return;
    terrainBox.style.display = interestSelect.value === 'terreno' ? 'block' : 'none';
  }

  function getSectionFromLink(link) {
    if (!link) return null;
    var href = link.getAttribute('href') || '';
    if (!href || href.charAt(0) !== '#') return null;
    return document.querySelector(href);
  }

  function setActiveNav(link) {
    navLinks.forEach(function (item) {
      item.classList.toggle('is-active', item === link);
    });
  }

  function bindSectionNavigation() {
    if (!navLinks.length) return;

    navLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        var section = getSectionFromLink(link);
        if (!section) return;

        event.preventDefault();
        var topOffset = 110;
        var top = section.getBoundingClientRect().top + window.scrollY - topOffset;
        window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
        history.replaceState(null, '', link.getAttribute('href'));
        setActiveNav(link);
      });
    });

    if ('IntersectionObserver' in window) {
      var sectionMap = navLinks
        .map(function (link) {
          return { link: link, section: getSectionFromLink(link) };
        })
        .filter(function (item) {
          return !!item.section;
        });

      var observer = new IntersectionObserver(
        function (entries) {
          var visible = entries
            .filter(function (entry) { return entry.isIntersecting; })
            .sort(function (a, b) { return b.intersectionRatio - a.intersectionRatio; })[0];

          if (!visible) return;

          var match = sectionMap.find(function (item) {
            return item.section === visible.target;
          });

          if (match) {
            setActiveNav(match.link);
          }
        },
        {
          rootMargin: '-20% 0px -55% 0px',
          threshold: [0.2, 0.35, 0.5, 0.75]
        }
      );

      sectionMap.forEach(function (item) {
        observer.observe(item.section);
      });
    }

    var hashLink = navLinks.find(function (link) {
      return link.getAttribute('href') === window.location.hash;
    });

    if (hashLink) {
      setActiveNav(hashLink);
    }
  }

  function escHtml(str) {
    return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function bindQuickSearch() {
    var input = document.querySelector('[data-mcrm-quick-search]');
    if (!input || !window.mcrmData) return;

    var form    = input.closest('.mcrm-topbar-search');
    var dropdown = null;
    var timer   = null;

    function showDropdown(results) {
      if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.className = 'mcrm-quick-results';
        form.appendChild(dropdown);
      }

      dropdown.innerHTML = '';

      if (!results.length) {
        dropdown.innerHTML = '<p class="mcrm-quick-results-empty">Nenhum resultado encontrado.</p>';
        return;
      }

      results.forEach(function (item) {
        var a = document.createElement('a');
        a.className = 'mcrm-quick-result-item';
        a.href = item.link;
        a.innerHTML =
          '<div class="mcrm-quick-result-info">' +
            '<span class="mcrm-quick-result-name">' + escHtml(item.name) + '</span>' +
            '<span class="mcrm-quick-result-meta">' + escHtml(item.phone) + (item.status ? ' &middot; ' + escHtml(item.status) : '') + '</span>' +
          '</div>' +
          '<span class="mcrm-quick-result-arrow">&rarr;</span>';
        dropdown.appendChild(a);
      });
    }

    function hideDropdown() {
      if (dropdown) {
        dropdown.remove();
        dropdown = null;
      }
    }

    function doSearch(term) {
      var body = new FormData();
      body.append('action', 'mcrm_quick_search');
      body.append('nonce', window.mcrmData.nonce);
      body.append('term', term);

      fetch(window.mcrmData.ajaxUrl, { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
          if (resp && resp.success) showDropdown(resp.data.results);
        })
        .catch(function () {});
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var term = input.value.trim();
      if (term.length < 2) { hideDropdown(); return; }
      timer = setTimeout(function () { doSearch(term); }, 320);
    });

    input.addEventListener('focus', function () {
      var term = input.value.trim();
      if (term.length >= 2) doSearch(term);
    });

    document.addEventListener('click', function (e) {
      if (dropdown && !form.contains(e.target)) hideDropdown();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') hideDropdown();
    });
  }

  bindMask(cpfInputs, formatCpf);
  bindMask(phoneInputs, formatPhone);
  bindSectionNavigation();
  bindQuickSearch();

  var quickInput = document.querySelector('[data-mcrm-quick-search]');
  if (quickInput && !quickInput.value) {
    quickInput.focus();
  }

  if (interestSelect && terrainBox) {
    interestSelect.addEventListener('change', syncTerrain);
    syncTerrain();
  }
});
