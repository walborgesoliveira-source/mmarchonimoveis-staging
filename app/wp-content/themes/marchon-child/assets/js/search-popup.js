(() => {
  const popup = document.querySelector("[data-search-popup]");
  if (!popup || !window.marchonSearchPopup) {
    return;
  }

  const openButtons = document.querySelectorAll("[data-search-popup-open]");
  const closeButtons = popup.querySelectorAll("[data-search-popup-close]");
  const form = popup.querySelector("[data-search-popup-form]");
  const input = popup.querySelector("[data-search-popup-input]");
  const results = popup.querySelector("[data-search-popup-results]");
  const status = popup.querySelector("[data-search-popup-status]");
  const nav = document.getElementById("nav-primary");
  const navToggle = document.getElementById("nav-toggle");

  let controller = null;
  let debounceTimer = null;

  const escapeHtml = (value) => {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
  };

  const renderEmptyState = (message) => {
    results.innerHTML = `<div class="search-popup-empty">${escapeHtml(message)}</div>`;
  };

  const renderResults = (items) => {
    if (!items.length) {
      renderEmptyState(marchonSearchPopup.i18n.noResults);
      return;
    }

    results.innerHTML = items
      .map((item) => {
        const meta = [item.typeLabel, item.meta].filter(Boolean).join(" • ");
        return `
          <a class="search-popup-result" href="${escapeHtml(item.url)}">
            <div class="search-popup-result-body">
              <div class="search-popup-result-title">${escapeHtml(item.title)}</div>
              ${meta ? `<div class="search-popup-result-meta">${escapeHtml(meta)}</div>` : ""}
              ${item.description ? `<div class="search-popup-result-description">${escapeHtml(item.description)}</div>` : ""}
            </div>
            <span class="search-popup-result-arrow" aria-hidden="true">→</span>
          </a>
        `;
      })
      .join("");
  };

  const setLoading = (isLoading, message = "") => {
    popup.classList.toggle("is-loading", isLoading);
    status.textContent = message;
  };

  const openPopup = () => {
    nav?.classList.remove("aberto");
    navToggle?.classList.remove("aberto");
    navToggle?.setAttribute("aria-expanded", "false");
    popup.hidden = false;
    popup.setAttribute("aria-hidden", "false");
    document.body.classList.add("search-popup-open");
    openButtons.forEach((button) => button.setAttribute("aria-expanded", "true"));
    window.setTimeout(() => input?.focus(), 30);
  };

  const closePopup = () => {
    popup.hidden = true;
    popup.setAttribute("aria-hidden", "true");
    document.body.classList.remove("search-popup-open");
    openButtons.forEach((button) => button.setAttribute("aria-expanded", "false"));
    if (controller) {
      controller.abort();
      controller = null;
    }
    setLoading(false, "");
  };

  const runSearch = async (term) => {
    const query = term.trim();

    if (query.length < 2) {
      status.textContent = marchonSearchPopup.i18n.minimumChars;
      renderEmptyState(marchonSearchPopup.i18n.minimumChars);
      return;
    }

    if (controller) {
      controller.abort();
    }

    controller = new AbortController();
    setLoading(true, marchonSearchPopup.i18n.loading);

    try {
      const payload = new URLSearchParams({
        action: "marchon_search_popup",
        nonce: marchonSearchPopup.nonce,
        term: query,
      });

      const response = await fetch(marchonSearchPopup.ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: payload.toString(),
        signal: controller.signal,
      });

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data?.data?.message || marchonSearchPopup.i18n.error);
      }

      setLoading(false, `${data.data.results.length} resultado(s) encontrado(s).`);
      renderResults(data.data.results);
    } catch (error) {
      if (error.name === "AbortError") {
        return;
      }
      setLoading(false, marchonSearchPopup.i18n.error);
      renderEmptyState(marchonSearchPopup.i18n.error);
    }
  };

  openButtons.forEach((button) => {
    button.addEventListener("click", openPopup);
  });

  closeButtons.forEach((button) => {
    button.addEventListener("click", closePopup);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !popup.hidden) {
      closePopup();
    }
  });

  form?.addEventListener("submit", (event) => {
    event.preventDefault();
    runSearch(input?.value ?? "");
  });

  input?.addEventListener("input", () => {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(() => {
      runSearch(input.value);
    }, 220);
  });

  renderEmptyState(marchonSearchPopup.i18n.initialHint);
})();
