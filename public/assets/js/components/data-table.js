/* CheckMaster UFRMI - Data Table (IIFE) */
((window, document) => {
	window.CM = window.CM || {};
	var module = (() => {
		var bindingsDone = false;
		var rowSelector =
			".cm-clickable-row, .cm-timeline__item[data-href], table.cm-data-table tbody tr, table.cm-table tbody tr";

		function init() {
			bindEvents();
			enhanceRows(document);
		}

		function bindEvents() {
			if (bindingsDone) return;
			bindingsDone = true;

			// Recherche en temps réel
			document.addEventListener("input", (e) => {
				var searchInput = e.target.closest(
					".toolbar-search input, .cm-toolbar-search",
				);
				var term, container, table, rows, countBadge, visibleRows;
				if (!searchInput) return;

				term = searchInput.value.toLowerCase();
				container = searchInput.closest(".cm-crud-wrapper") || document;

				table = container.querySelector(".cm-table-wrapper table");
				if (!table) return;

				rows = table.querySelectorAll("tbody tr");
				rows.forEach((row) => {
					var text = row.textContent.toLowerCase();
					row.style.display = text.indexOf(term) > -1 ? "" : "none";
				});

				// Mise à jour du compteur si présent
				countBadge = container.querySelector(".results-count");
				if (countBadge) {
					visibleRows = Array.prototype.filter.call(
						rows,
						(r) => r.style.display !== "none",
					).length;
					countBadge.textContent = visibleRows + " résultat(s)";
				}
			});

			// Tri par colonne
			document.addEventListener("click", (e) => {
				var header = e.target.closest("th.is-sortable, th.cm-sortable");
				if (!header) return;

				var col = header.dataset.column || "";
				if (!col) {
					var link = header.querySelector("a.cm-sort-link");
					if (link) {
						return;
					}
				}
				if (!col) return;

				var currentUrl = new URL(window.location.href);
				var currentSort = currentUrl.searchParams.get("sort");
				var currentDir = currentUrl.searchParams.get("dir") || "asc";

				var newDir = "asc";
				if (currentSort === col && currentDir === "asc") {
					newDir = "desc";
				}

				currentUrl.searchParams.set("sort", col);
				currentUrl.searchParams.set("dir", newDir);
				window.location.href = currentUrl.toString();
			});

			// Activation globale des lignes de tableaux cliquables
			document.addEventListener("click", (e) => {
				var row = getInteractiveRow(e.target);
				var action;

				if (!row || isInteractiveTarget(e.target, row)) {
					return;
				}

				action = resolveRowAction(row);
				if (!action) {
					return;
				}

				e.preventDefault();
				activateRowAction(action);
			});

			document.addEventListener("keydown", (e) => {
				var row;
				var action;

				if (e.key !== "Enter" && e.key !== " ") {
					return;
				}

				row = e.target.closest(".cm-clickable-row");
				if (!row || isInteractiveTarget(e.target, row)) {
					return;
				}

				action = resolveRowAction(row);
				if (!action) {
					return;
				}

				e.preventDefault();
				activateRowAction(action);
			});
		}

		function enhanceRows(root) {
			var rows = root.querySelectorAll(rowSelector);
			rows.forEach((row) => {
				var action = resolveRowAction(row);

				if (!action) {
					row.removeAttribute("data-cm-row-clickable");
					if (!row.hasAttribute("data-href")) {
						row.classList.remove("cm-clickable-row");
						row.removeAttribute("tabindex");
						row.removeAttribute("role");
					}
					return;
				}

				row.classList.add("cm-clickable-row");
				row.setAttribute("data-cm-row-clickable", "true");
				row.setAttribute("tabindex", "0");
				row.setAttribute("role", action.kind === "href" ? "link" : "button");
			});
		}

		function getInteractiveRow(target) {
			var row = target.closest(rowSelector);
			if (!row) {
				return null;
			}

			if (row.matches("tr")) {
				if (!row.parentElement || row.parentElement.tagName !== "TBODY") {
					return null;
				}
				if (!row.closest("table.cm-data-table, table.cm-table")) {
					return null;
				}
			}

			return row;
		}

		function isInteractiveTarget(target, row) {
			if (!target || !row || target === row) {
				return false;
			}

			return !!target.closest(
				'a, button, input, select, textarea, label, summary, [role="button"], [contenteditable="true"], .cm-btn, .cm-btn-action, .cm-form-control',
			);
		}

		function resolveRowAction(row) {
			var href = (row.getAttribute("data-href") || "").trim();
			var primaryAction;

			if (href && !hasUnresolvedToken(href)) {
				return { kind: "href", href: href };
			}

			primaryAction = findPrimaryAction(row);
			if (!primaryAction) {
				return null;
			}

			return { kind: "element", element: primaryAction };
		}

		function hasUnresolvedToken(value) {
			return /\{[^}]+\}/.test(value || "");
		}

		function findPrimaryAction(row) {
			var explicitAction = row.querySelector('[data-cm-row-primary="true"]');
			var candidates = [];
			var fallbackCandidates = [];
			var bestCandidate = null;
			var bestScore = -1;

			if (getActionScore(explicitAction) >= 0) {
				return explicitAction;
			}

			addCandidates(
				candidates,
				row.querySelectorAll(
					".cm-table-actions a, .cm-table-actions button, .cm-row-actions a, .cm-row-actions button, td.is-actions a, td.is-actions button, td:last-child a, td:last-child button",
				),
			);

			if (candidates.length === 0) {
				addCandidates(
					fallbackCandidates,
					row.querySelectorAll("a[href], button"),
				);
				candidates = fallbackCandidates;
			}

			candidates.forEach((candidate) => {
				var score = getActionScore(candidate);
				if (score > bestScore) {
					bestScore = score;
					bestCandidate = candidate;
				}
			});

			return bestScore > 0 ? bestCandidate : null;
		}

		function addCandidates(store, nodeList) {
			Array.prototype.forEach.call(nodeList || [], (candidate) => {
				if (store.indexOf(candidate) === -1) {
					store.push(candidate);
				}
			});
		}

		function getActionScore(action) {
			var descriptor;
			var score = 0;

			if (!action || isDisabledAction(action) || isHiddenAction(action)) {
				return -1;
			}

			descriptor = getActionDescriptor(action);

			if (/(supprim|delete|trash|remove|retirer)/.test(descriptor)) {
				return -1;
			}

			if (action.getAttribute("data-cm-row-primary") === "true") {
				score += 1000;
			}
			if (action.closest(".cm-table-actions, .cm-row-actions, td.is-actions")) {
				score += 120;
			}
			if (action.classList.contains("is-view")) {
				score += 500;
			}
			if (action.classList.contains("is-edit")) {
				score += 320;
			}
			if (
				/(voir|view|consulter|detail|details|fiche|workflow|ouvrir|open|preview|apercu|examiner)/.test(
					descriptor,
				)
			) {
				score += 420;
			}
			if (
				/(edit|modifier|editer|update|traiter|programmer|evaluer)/.test(
					descriptor,
				)
			) {
				score += 260;
			}
			if (/(download|telecharg|export|imprimer)/.test(descriptor)) {
				score += 120;
			}
			if (action.tagName === "A" && isUsableHref(action.getAttribute("href"))) {
				score += 100;
			}
			if (action.tagName === "BUTTON") {
				score += 80;
			}

			return score;
		}

		function getActionDescriptor(action) {
			return [
				action.className || "",
				action.getAttribute("title") || "",
				action.getAttribute("aria-label") || "",
				action.textContent || "",
				action.getAttribute("href") || "",
			]
				.join(" ")
				.toLowerCase();
		}

		function isDisabledAction(action) {
			return !!(
				action.disabled || action.getAttribute("aria-disabled") === "true"
			);
		}

		function isHiddenAction(action) {
			if (!action) {
				return true;
			}
			if (action.hidden || action.getAttribute("aria-hidden") === "true") {
				return true;
			}
			return window.getComputedStyle(action).display === "none";
		}

		function isUsableHref(href) {
			return !!href && !/^(javascript:|mailto:|tel:)/i.test(href);
		}

		function activateRowAction(action) {
			if (!action) {
				return;
			}

			if (action.kind === "href") {
				navigateToHref(action.href);
				return;
			}

			if (action.element && typeof action.element.click === "function") {
				action.element.click();
			}
		}

		function navigateToHref(href) {
			if (
				shouldUseAjaxNavigation(href) &&
				window.CM &&
				window.CM.ajax &&
				typeof window.CM.ajax.load === "function"
			) {
				window.CM.ajax.load(href);
				return;
			}

			window.location.href = href;
		}

		function shouldUseAjaxNavigation(href) {
			var parsedUrl;
			var action;

			if (!isUsableHref(href) || href.indexOf("#") === 0) {
				return false;
			}

			try {
				parsedUrl = new window.URL(href, window.location.href);
			} catch (error) {
				return false;
			}

			if (parsedUrl.origin !== window.location.origin) {
				return false;
			}

			action = (parsedUrl.searchParams.get("action") || "").toLowerCase();
			if (
				action.indexOf("imprimer") !== -1 ||
				action.indexOf("export") !== -1 ||
				action.indexOf("download") !== -1
			) {
				return false;
			}
			if (action === "export_pdf" || action === "imprimer_pv") {
				return false;
			}

			if (/\.(pdf|csv|xlsx?|zip)$/i.test(parsedUrl.pathname)) {
				return false;
			}

			return true;
		}

		return { init: init };
	})();
	window.CM.dataTable = module;
})(window, document);

document.addEventListener("DOMContentLoaded", () => {
	if (
		window.CM &&
		window.CM.dataTable &&
		typeof window.CM.dataTable.init === "function"
	)
		window.CM.dataTable.init();
});

// Re-init after AJAX navigation
document.addEventListener("cm:ajax:navigation:done", () => {
	if (
		window.CM &&
		window.CM.dataTable &&
		typeof window.CM.dataTable.init === "function"
	)
		window.CM.dataTable.init();
});
