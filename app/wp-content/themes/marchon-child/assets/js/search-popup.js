(() => {
  if (!window.marchonSearchPopup) {
    return;
  }

  const popup = document.querySelector("[data-search-popup]");
  const inline = document.querySelector("[data-search-inline]");
  const openButtons = document.querySelectorAll("[data-search-popup-open]");
  const closeButtons = popup?.querySelectorAll("[data-search-popup-close]") ?? [];
  const nav = document.getElementById("nav-primary");
  const navToggle = document.getElementById("nav-toggle");

  const popupRefs = popup
    ? {
        form: popup.querySelector("[data-search-popup-form]"),
        input: popup.querySelector("[data-search-popup-input]"),
        results: popup.querySelector("[data-search-popup-results]"),
        status: popup.querySelector("[data-search-popup-status]"),
      }
    : null;

  const inlineRefs = inline
    ? {
        form: inline.querySelector("[data-search-inline-form]"),
        input: inline.querySelector("[data-search-inline-input]"),
        results: inline.querySelector("[data-search-inline-results]"),
        status: inline.querySelector("[data-search-inline-status]"),
      }
    : null;

  let controller = null;
  let debounceTimer = null;

  const escapeHtml = (value) => {
    const div = document.createElement("div");
    div.textContent = value ?? "";
    return div.innerHTML;
  };

  const getMode = () => (popup && popupRefs ? "popup" : "inline");

  const getRefs = () => (getMode() === "popup" ? popupRefs : inlineRefs);

  const renderEmptyState = (refs, message) => {
    if (!refs?.results) {
      return;
    }
    refs.results.innerHTML = `<div class="search-popup-empty">${escapeHtml(message)}</div>`;
  };

  const renderResults = (refs, items) => {
    if (!refs?.results) {
      return;
    }

    if (!items.length) {
      renderEmptyState(refs, marchonSearchPopup.i18n.noResults);
      return;
    }

    refs.results.innerHTML = items
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

  const setLoading = (refs, isLoading, message = "") => {
    popup?.classList.toggle("is-loading", getMode() === "popup" && isLoading);
    inline?.classList.toggle("is-loading", getMode() === "inline" && isLoading);
    if (refs?.status) {
      refs.status.textContent = message;
    }
  };

  const closeMobileNav = () => {
    nav?.classList.remove("aberto");
    navToggle?.classList.remove("aberto");
    navToggle?.setAttribute("aria-expanded", "false");
  };

  const openInline = () => {
    if (!inline || !inlineRefs) {
      return;
    }
    closeMobileNav();
    inline.hidden = false;
    inline.scrollIntoView({ behavior: "smooth", block: "start" });
    window.setTimeout(() => inlineRefs.input?.focus(), 30);
  };

  const openPopup = () => {
    if (!popup || !popupRefs) {
      openInline();
      return;
    }

    closeMobileNav();
    popup.hidden = false;
    popup.setAttribute("aria-hidden", "false");
    document.body.classList.add("search-popup-open");
    openButtons.forEach((button) => button.setAttribute("aria-expanded", "true"));
    window.setTimeout(() => popupRefs.input?.focus(), 30);
  };

  const closePopup = () => {
    if (!popup) {
      return;
    }
    popup.hidden = true;
    popup.setAttribute("aria-hidden", "true");
    document.body.classList.remove("search-popup-open");
    openButtons.forEach((button) => button.setAttribute("aria-expanded", "false"));
    if (controller) {
      controller.abort();
      controller = null;
    }
    setLoading(popupRefs, false, "");
  };

  const runSearch = async (refs, term) => {
    const query = term.trim();

    if (query.length < 2) {
      setLoading(refs, false, marchonSearchPopup.i18n.minimumChars);
      renderEmptyState(refs, marchonSearchPopup.i18n.minimumChars);
      return;
    }

    if (controller) {
      controller.abort();
    }

    controller = new AbortController();
    setLoading(refs, true, marchonSearchPopup.i18n.loading);

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

      setLoading(refs, false, `${data.data.results.length} resultado(s) encontrado(s).`);
      renderResults(refs, data.data.results);
    } catch (error) {
      if (error.name === "AbortError") {
        return;
      }
      setLoading(refs, false, marchonSearchPopup.i18n.error);
      renderEmptyState(refs, marchonSearchPopup.i18n.error);
    }
  };

  const bindSearch = (refs) => {
    if (!refs?.form || !refs?.input) {
      return;
    }

    refs.form.addEventListener("submit", (event) => {
      event.preventDefault();
      runSearch(refs, refs.input.value ?? "");
    });

    refs.input.addEventListener("input", () => {
      window.clearTimeout(debounceTimer);
      debounceTimer = window.setTimeout(() => {
        runSearch(refs, refs.input.value);
      }, 220);
    });

    renderEmptyState(refs, marchonSearchPopup.i18n.initialHint);
  };

  openButtons.forEach((button) => {
    button.addEventListener("click", () => {
      if (popup && popupRefs) {
        openPopup();
      } else {
        openInline();
      }
    });
  });

  closeButtons.forEach((button) => {
    button.addEventListener("click", closePopup);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && popup && !popup.hidden) {
      closePopup();
    }
  });

  bindSearch(popupRefs);
  bindSearch(inlineRefs);
})();
