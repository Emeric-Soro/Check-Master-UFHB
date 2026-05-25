/**
 * Inline Confirmation Utility
 * Converts modal confirmations to inline confirmation panels
 */
(() => {
	// Configuration
	var INLINE_CONFIRM_CONFIG = {
		message: "Confirmer la suppression ?",
		confirmText: "Confirmer",
		cancelText: "Annuler",
		confirmClass: "cm-btn is-danger is-sm",
		cancelClass: "cm-btn is-light is-sm",
		panelClass: "cm-inline-confirm-panel",
	};

	// Track active panel for window.confirm/CM.confirm interception
	var activePanel = null;
	var activeCallback = null;
	var lastClickedElement = null;

	// Initialize inline confirmations on page load
	document.addEventListener("DOMContentLoaded", () => {
		initInlineConfirmations();
		interceptConfirmationMethods();
	});

	document.addEventListener(
		"mousedown",
		(e) => {
			lastClickedElement =
				e.target.closest(
					'button, a, input[type="submit"], input[type="button"], .cm-btn, .cm-action-bar',
				) || e.target;
		},
		true,
	);

	// Re-initialize when new content is loaded (for AJAX pages)
	// Using debounce + guard to prevent infinite loop from our own DOM mutations
	var observer = null;
	var initTimeout = null;
	var isSelfUpdating = false;
	if (typeof MutationObserver !== "undefined") {
		observer = new MutationObserver((mutations) => {
			// Skip if the mutations were caused by our own DOM updates
			if (isSelfUpdating) return;
			// Only react if at least one mutation adds actual elements (not text nodes)
			var hasRealElements = false;
			for (var i = 0; i < mutations.length; i++) {
				var addedNodes = mutations[i].addedNodes;
				for (var j = 0; j < addedNodes.length; j++) {
					if (addedNodes[j].nodeType === 1) {
						// ELEMENT_NODE
						hasRealElements = true;
						break;
					}
				}
				if (hasRealElements) break;
			}
			if (hasRealElements) {
				clearTimeout(initTimeout);
				initTimeout = setTimeout(() => {
					isSelfUpdating = true;
					initInlineConfirmations();
					isSelfUpdating = false;
				}, 150);
			}
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}

	function initInlineConfirmations() {
		// Check if already initialized to prevent redundant scans
		// Convert delete buttons in data-tables
		convertDeleteButtons(".cm-btn-action.is-delete");

		// Convert bulk delete buttons
		convertBulkDeleteButtons("[data-bulk-delete]");
	}

	/**
	 * Intercept window.CM.confirm and window.confirm to show inline panels
	 */
	function interceptConfirmationMethods() {
		// Override window.CM.confirm
		window.CM = window.CM || {};
		window.CM.confirm = (options) => {
			var message = "";
			var type = "danger";
			var confirmText = "Confirmer";
			var cancelText = "Annuler";

			if (typeof options === "string") {
				message = options;
			} else if (typeof options === "object") {
				message = options.message || options.title || "Êtes-vous sûr ?";
				type = options.type || "danger";
				confirmText = options.confirmText || "Confirmer";
				cancelText = options.cancelText || "Annuler";
			}

			// Get the element that triggered the confirmation
			var trigger = document.activeElement;
			if (!trigger || trigger === document.body) {
				trigger = lastClickedElement;
			}

			// If trigger is still body, find an action bar or header to attach to
			if (!trigger || trigger === document.body) {
				trigger =
					document.querySelector(
						".cm-action-bar, .cm-page-header, .cm-card-header",
					) || document.body.firstElementChild;
			}

			// Show inline confirmation and return Promise
			return new Promise((resolve) => {
				showInlineConfirmPromise(
					message,
					trigger,
					type,
					confirmText,
					cancelText,
					resolve,
				);
			});
		};
	}

	/**
	 * Show inline confirmation with Promise (for window.CM.confirm)
	 */
	function showInlineConfirmPromise(
		message,
		trigger,
		type,
		confirmText,
		cancelText,
		callback,
	) {
		var panel = createConfirmPanel(
			message,
			() => {
				removePanel(panel, trigger);
				callback(true);
			},
			() => {
				removePanel(panel, trigger);
				callback(false);
			},
			type,
			confirmText,
			cancelText,
		);

		// Insert panel after trigger
		trigger.parentNode.insertBefore(panel, trigger.nextSibling);
		showPanel(panel, trigger);
	}

	function convertDeleteButtons(selector) {
		var buttons = document.querySelectorAll(selector);
		buttons.forEach((btn) => {
			if (btn.hasAttribute("data-inline-confirm-initialized")) {
				return;
			}
			btn.setAttribute("data-inline-confirm-initialized", "true");

			// Store original click handler
			var originalHandler = btn.onclick;
			btn.onclick = null;

			// Create wrapper
			var wrapper = document.createElement("div");
			wrapper.className = "cm-inline-confirm";
			btn.parentNode.insertBefore(wrapper, btn);
			wrapper.appendChild(btn);

			// Create confirmation panel
			var panel = createConfirmPanel(
				INLINE_CONFIRM_CONFIG.message,
				() => {
					// Confirm action
					if (originalHandler) {
						originalHandler.call(btn);
					}
					// Trigger click to execute original logic
					var event = new MouseEvent("click", {
						bubbles: true,
						cancelable: true,
						view: window,
					});
					btn.dispatchEvent(event);
					hidePanel(panel);
				},
				() => {
					// Cancel action
					hidePanel(panel);
				},
			);
			wrapper.appendChild(panel);

			// Replace click handler
			btn.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();
				showPanel(panel, btn);
			});
		});
	}

	function convertBulkDeleteButtons(selector) {
		var buttons = document.querySelectorAll(selector);
		buttons.forEach((btn) => {
			if (btn.hasAttribute("data-inline-confirm-initialized")) {
				return;
			}
			btn.setAttribute("data-inline-confirm-initialized", "true");

			// Store original click handler
			var originalClick = btn.onclick;
			btn.onclick = null;

			// Create wrapper
			var wrapper = document.createElement("div");
			wrapper.className = "cm-inline-confirm";
			btn.parentNode.insertBefore(wrapper, btn);
			wrapper.appendChild(btn);

			// Create confirmation panel
			var panel = createConfirmPanel(
				"Confirmer la suppression des éléments sélectionnés ?",
				() => {
					// Confirm action
					if (originalClick) {
						originalClick.call(btn);
					}
					hidePanel(panel);
				},
				() => {
					// Cancel action
					hidePanel(panel);
				},
			);
			wrapper.appendChild(panel);

			// Replace click handler
			btn.addEventListener("click", (e) => {
				e.preventDefault();
				e.stopPropagation();
				showPanel(panel, btn);
			});
		});
	}

	function createConfirmPanel(
		message,
		onConfirm,
		onCancel,
		type,
		confirmText,
		cancelText,
	) {
		type = type || "danger";
		confirmText = confirmText || INLINE_CONFIRM_CONFIG.confirmText;
		cancelText = cancelText || INLINE_CONFIRM_CONFIG.cancelText;

		var confirmClass = "cm-btn is-" + type + " is-sm";
		var panelClass = INLINE_CONFIRM_CONFIG.panelClass + " is-" + type;

		var panel = document.createElement("div");
		panel.className = panelClass;
		panel.style.display = "none";

		var iconClass =
			type === "danger" ? "fa-exclamation-triangle" : "fa-question-circle";

		panel.innerHTML =
			'<div class="cm-inline-confirm__message">' +
			'<i class="fas ' +
			iconClass +
			'" aria-hidden="true"></i>' +
			"<span>" +
			escapeHtml(message) +
			"</span>" +
			"</div>" +
			'<div class="cm-inline-confirm__actions">' +
			'<button type="button" class="cm-inline-confirm__confirm ' +
			confirmClass +
			'">' +
			'<i class="fas fa-check" aria-hidden="true"></i>' +
			"<span>" +
			escapeHtml(confirmText) +
			"</span>" +
			"</button>" +
			'<button type="button" class="cm-inline-confirm__cancel ' +
			INLINE_CONFIRM_CONFIG.cancelClass +
			'">' +
			'<i class="fas fa-times" aria-hidden="true"></i>' +
			"<span>" +
			escapeHtml(cancelText) +
			"</span>" +
			"</button>" +
			"</div>";

		panel
			.querySelector(".cm-inline-confirm__confirm")
			.addEventListener("click", onConfirm);
		panel
			.querySelector(".cm-inline-confirm__cancel")
			.addEventListener("click", onCancel);

		return panel;
	}

	function showPanel(panel, trigger) {
		// Hide trigger button
		trigger.style.display = "none";

		// Show panel
		panel.style.display = "flex";
		panel.style.animation = "cm-inline-confirm-enter 0.2s ease";
	}

	function hidePanel(panel) {
		// Find wrapper
		var wrapper = panel.closest(".cm-inline-confirm");
		if (!wrapper) return;

		// Find trigger button
		var trigger = wrapper.querySelector(
			"button:not(.cm-inline-confirm__confirm):not(.cm-inline-confirm__cancel)",
		);

		// Hide panel
		panel.style.display = "none";

		// Show trigger button
		if (trigger) {
			trigger.style.display = "";
		}
	}

	function removePanel(panel, trigger) {
		// Remove panel from DOM
		if (panel && panel.parentNode) {
			panel.parentNode.removeChild(panel);
		}

		// Show trigger button
		if (trigger) {
			trigger.style.display = "";
		}
	}

	function escapeHtml(text) {
		var div = document.createElement("div");
		div.textContent = text;
		return div.innerHTML;
	}

	// Expose for external use
	window.CMInlineConfirm = {
		init: initInlineConfirmations,
		convertDeleteButtons: convertDeleteButtons,
		convertBulkDeleteButtons: convertBulkDeleteButtons,
	};
})();
