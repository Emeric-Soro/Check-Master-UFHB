/**
 * CheckMaster Premium - Interactions JavaScript
 * 
 * Ce fichier centralise TOUTES les interactions JavaScript de l'application.
 * Système modulaire inspiré de Shadcn/UI.
 */

(function() {
    'use strict';

    // ==========================================================================
    // 1. MODAL SYSTEM
    // ==========================================================================
    
    const Modal = {
        /**
         * Affiche une modale par son ID
         * @param {string} id - ID de la modale
         */
        show(id) {
            const overlay = document.getElementById(id);
            if (!overlay) {
                console.warn(`Modal "${id}" not found`);
                return;
            }
            
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Focus trap
            const focusable = overlay.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusable.length > 0) {
                focusable[0].focus();
            }
            
            // Close on escape
            overlay.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    Modal.hide(id);
                }
            });
            
            // Close on overlay click
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    Modal.hide(id);
                }
            });
        },
        
        /**
         * Cache une modale par son ID
         * @param {string} id - ID de la modale
         */
        hide(id) {
            const overlay = document.getElementById(id);
            if (!overlay) return;
            
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        },
        
        /**
         * Toggle l'état d'une modale
         * @param {string} id - ID de la modale
         */
        toggle(id) {
            const overlay = document.getElementById(id);
            if (!overlay) return;
            
            if (overlay.classList.contains('active')) {
                this.hide(id);
            } else {
                this.show(id);
            }
        },
        
        /**
         * Modale de confirmation
         * @param {Object} options - Options de la modale
         * @returns {Promise<boolean>}
         */
        confirm(options = {}) {
            const {
                title = 'Confirmation',
                message = 'Êtes-vous sûr de vouloir continuer ?',
                confirmText = 'Confirmer',
                cancelText = 'Annuler',
                type = 'warning' // 'warning', 'danger', 'info'
            } = options;
            
            return new Promise((resolve) => {
                // Create modal dynamically
                const modalId = 'confirm-modal-' + Date.now();
                const iconMap = {
                    warning: 'fa-exclamation-triangle',
                    danger: 'fa-trash-alt',
                    info: 'fa-info-circle'
                };
                const colorMap = {
                    warning: 'warning',
                    danger: 'danger',
                    info: 'info'
                };
                
                const modalHTML = `
                    <div id="${modalId}" class="modal-overlay">
                        <div class="modal modal-sm">
                            <div class="modal-header">
                                <h3 class="modal-title">${title}</h3>
                                <button type="button" class="modal-close" data-action="cancel">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="flex items-center gap-md">
                                    <div class="stat-card-icon ${colorMap[type]}">
                                        <i class="fas ${iconMap[type]}"></i>
                                    </div>
                                    <p>${message}</p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-action="cancel">${cancelText}</button>
                                <button type="button" class="btn btn-${type === 'danger' ? 'danger' : 'primary'}" data-action="confirm">${confirmText}</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                const modal = document.getElementById(modalId);
                
                // Show modal
                setTimeout(() => Modal.show(modalId), 10);
                
                // Handle actions
                modal.addEventListener('click', function(e) {
                    const action = e.target.closest('[data-action]')?.dataset.action;
                    if (action) {
                        Modal.hide(modalId);
                        setTimeout(() => modal.remove(), 200);
                        resolve(action === 'confirm');
                    }
                });
            });
        }
    };

    // ==========================================================================
    // 2. TOAST / NOTIFICATION SYSTEM
    // ==========================================================================
    
    const Toast = {
        container: null,
        
        /**
         * Initialize toast container
         */
        init() {
            if (!document.getElementById('toast-container')) {
                const container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
            this.container = document.getElementById('toast-container');
        },
        
        /**
         * Affiche une notification toast
         * @param {string} message - Message à afficher
         * @param {Object} options - Options de la notification
         */
        show(message, options = {}) {
            if (!this.container) this.init();
            
            const {
                type = 'success', // 'success', 'warning', 'danger', 'info'
                title = null,
                duration = 5000,
                closable = true
            } = options;
            
            const iconMap = {
                success: 'fa-check-circle',
                warning: 'fa-exclamation-triangle',
                danger: 'fa-times-circle',
                info: 'fa-info-circle'
            };
            
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} toast`;
            toast.innerHTML = `
                <div class="alert-icon">
                    <i class="fas ${iconMap[type]}"></i>
                </div>
                <div class="alert-content">
                    ${title ? `<div class="alert-title">${title}</div>` : ''}
                    <div class="alert-description">${message}</div>
                </div>
                ${closable ? '<button type="button" class="modal-close" onclick="this.closest(\'.toast\').remove()"><i class="fas fa-times"></i></button>' : ''}
            `;
            
            this.container.appendChild(toast);
            
            // Auto-remove
            if (duration > 0) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }
            
            return toast;
        },
        
        success(message, options = {}) {
            return this.show(message, { ...options, type: 'success' });
        },
        
        warning(message, options = {}) {
            return this.show(message, { ...options, type: 'warning' });
        },
        
        danger(message, options = {}) {
            return this.show(message, { ...options, type: 'danger' });
        },
        
        error(message, options = {}) {
            return this.danger(message, options);
        },
        
        info(message, options = {}) {
            return this.show(message, { ...options, type: 'info' });
        }
    };

    // ==========================================================================
    // 3. DROPDOWN SYSTEM
    // ==========================================================================
    
    const Dropdown = {
        /**
         * Initialize all dropdowns
         */
        init() {
            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-dropdown]');
                
                if (trigger) {
                    e.preventDefault();
                    const dropdownId = trigger.dataset.dropdown;
                    this.toggle(dropdownId);
                } else {
                    // Close all dropdowns when clicking outside
                    this.closeAll();
                }
            });
        },
        
        toggle(id) {
            const dropdown = document.querySelector(`[data-dropdown-menu="${id}"]`)?.closest('.dropdown') 
                || document.getElementById(id);
            
            if (!dropdown) return;
            
            const isOpen = dropdown.classList.contains('open');
            this.closeAll();
            
            if (!isOpen) {
                dropdown.classList.add('open');
            }
        },
        
        closeAll() {
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        }
    };

    // ==========================================================================
    // 4. TABS SYSTEM
    // ==========================================================================
    
    const Tabs = {
        /**
         * Initialize all tabs
         */
        init() {
            document.addEventListener('click', (e) => {
                const tab = e.target.closest('[data-tab]');
                if (!tab) return;
                
                const tabGroup = tab.closest('[data-tabs]');
                const targetPanel = tab.dataset.tab;
                
                if (tabGroup && targetPanel) {
                    this.switchTo(tabGroup, targetPanel);
                }
            });
        },
        
        switchTo(tabGroup, targetPanel) {
            // Update tab states
            tabGroup.querySelectorAll('[data-tab]').forEach(t => {
                t.classList.toggle('active', t.dataset.tab === targetPanel);
            });
            
            // Update panel states
            const container = tabGroup.closest('[data-tab-container]') || tabGroup.parentElement;
            container.querySelectorAll('[data-tab-panel]').forEach(p => {
                p.classList.toggle('active', p.dataset.tabPanel === targetPanel);
            });
        }
    };

    // ==========================================================================
    // 5. SIDEBAR NAVIGATION
    // ==========================================================================
    
    const Sidebar = {
        /**
         * Toggle category collapse
         * @param {string} collapseId - ID of the collapse element
         */
        toggleCategory(collapseId) {
            const content = document.getElementById(collapseId);
            const icon = document.getElementById('icon-' + collapseId);
            
            if (!content) return;
            
            const isHidden = content.style.display === 'none' || content.style.display === '';
            
            content.style.display = isHidden ? 'block' : 'none';
            
            if (icon) {
                icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        },
        
        /**
         * Toggle submenu
         * @param {string} subId - ID of the submenu element
         */
        toggleSubMenu(subId) {
            const content = document.getElementById(subId);
            const icon = document.getElementById('icon-' + subId);
            
            if (!content) return;
            
            const isHidden = content.style.display === 'none' || content.style.display === '';
            
            content.style.display = isHidden ? 'block' : 'none';
            
            if (icon) {
                icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        },
        
        /**
         * Mobile sidebar toggle
         */
        toggleMobile() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('open');
            }
        },
        
        /**
         * Auto-expand active category on load
         */
        initActiveState() {
            document.querySelectorAll('.sidebar-link.active').forEach(link => {
                const category = link.closest('[id^="collapse-"]');
                if (category) {
                    category.style.display = 'block';
                    const icon = document.getElementById('icon-' + category.id);
                    if (icon) {
                        icon.style.transform = 'rotate(180deg)';
                    }
                }
            });
        }
    };

    // ==========================================================================
    // 6. DATA TABLE HELPERS
    // ==========================================================================
    
    const DataTable = {
        /**
         * Select/Deselect all checkboxes
         * @param {HTMLInputElement} masterCheckbox - The master checkbox element
         * @param {string} className - Class name of row checkboxes
         */
        toggleSelectAll(masterCheckbox, className = 'row-checkbox') {
            const checkboxes = document.querySelectorAll('.' + className);
            checkboxes.forEach(cb => {
                cb.checked = masterCheckbox.checked;
            });
            this.updateBulkActions();
        },
        
        /**
         * Update bulk actions visibility
         */
        updateBulkActions() {
            const selected = document.querySelectorAll('.row-checkbox:checked').length;
            const bulkActions = document.querySelector('.bulk-actions');
            const selectedCount = document.querySelector('.selected-count');
            
            if (bulkActions) {
                bulkActions.style.display = selected > 0 ? 'flex' : 'none';
            }
            
            if (selectedCount) {
                selectedCount.textContent = selected;
            }
        },
        
        /**
         * Get selected row IDs
         * @returns {Array<string>}
         */
        getSelectedIds() {
            return Array.from(document.querySelectorAll('.row-checkbox:checked'))
                .map(cb => cb.value);
        },
        
        /**
         * Sort table by column
         * @param {HTMLTableElement} table - Table element
         * @param {number} columnIndex - Column index to sort by
         * @param {boolean} ascending - Sort direction
         */
        sortBy(table, columnIndex, ascending = true) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aVal = a.cells[columnIndex].textContent.trim();
                const bVal = b.cells[columnIndex].textContent.trim();
                
                // Try numeric comparison
                const aNum = parseFloat(aVal);
                const bNum = parseFloat(bVal);
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return ascending ? aNum - bNum : bNum - aNum;
                }
                
                // String comparison
                return ascending 
                    ? aVal.localeCompare(bVal, 'fr')
                    : bVal.localeCompare(aVal, 'fr');
            });
            
            rows.forEach(row => tbody.appendChild(row));
        },
        
        /**
         * Filter table rows
         * @param {HTMLTableElement} table - Table element
         * @param {string} query - Search query
         */
        filter(table, query) {
            const rows = table.querySelectorAll('tbody tr');
            const lowerQuery = query.toLowerCase();
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(lowerQuery) ? '' : 'none';
            });
        }
    };

    // ==========================================================================
    // 7. FORM HELPERS
    // ==========================================================================
    
    const Form = {
        /**
         * Serialize form data to object
         * @param {HTMLFormElement} form - Form element
         * @returns {Object}
         */
        serialize(form) {
            const formData = new FormData(form);
            const data = {};
            
            formData.forEach((value, key) => {
                if (data[key] !== undefined) {
                    if (!Array.isArray(data[key])) {
                        data[key] = [data[key]];
                    }
                    data[key].push(value);
                } else {
                    data[key] = value;
                }
            });
            
            return data;
        },
        
        /**
         * Validate form
         * @param {HTMLFormElement} form - Form element
         * @returns {boolean}
         */
        validate(form) {
            let isValid = true;
            
            // Clear previous errors
            form.querySelectorAll('.form-error').forEach(e => e.remove());
            form.querySelectorAll('.error').forEach(e => e.classList.remove('error'));
            
            // Check required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    this.showError(field, 'Ce champ est requis');
                }
            });
            
            // Check email fields
            form.querySelectorAll('[type="email"]').forEach(field => {
                if (field.value && !this.isValidEmail(field.value)) {
                    isValid = false;
                    this.showError(field, 'Adresse email invalide');
                }
            });
            
            return isValid;
        },
        
        /**
         * Show field error
         * @param {HTMLElement} field - Input field
         * @param {string} message - Error message
         */
        showError(field, message) {
            field.classList.add('error');
            const error = document.createElement('span');
            error.className = 'form-error';
            error.textContent = message;
            field.parentNode.appendChild(error);
        },
        
        /**
         * Validate email format
         * @param {string} email - Email to validate
         * @returns {boolean}
         */
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        
        /**
         * Reset form
         * @param {HTMLFormElement} form - Form element
         */
        reset(form) {
            form.reset();
            form.querySelectorAll('.form-error').forEach(e => e.remove());
            form.querySelectorAll('.error').forEach(e => e.classList.remove('error'));
        }
    };

    // ==========================================================================
    // 8. AJAX HELPERS
    // ==========================================================================
    
    const Ajax = {
        /**
         * Make AJAX request
         * @param {string} url - Request URL
         * @param {Object} options - Request options
         * @returns {Promise}
         */
        async request(url, options = {}) {
            const {
                method = 'GET',
                data = null,
                headers = {},
                responseType = 'json'
            } = options;
            
            const fetchOptions = {
                method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...headers
                }
            };
            
            if (data) {
                if (data instanceof FormData) {
                    fetchOptions.body = data;
                } else {
                    fetchOptions.headers['Content-Type'] = 'application/json';
                    fetchOptions.body = JSON.stringify(data);
                }
            }
            
            try {
                const response = await fetch(url, fetchOptions);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                if (responseType === 'json') {
                    return await response.json();
                }
                
                return await response.text();
            } catch (error) {
                console.error('Ajax error:', error);
                throw error;
            }
        },
        
        get(url, options = {}) {
            return this.request(url, { ...options, method: 'GET' });
        },
        
        post(url, data, options = {}) {
            return this.request(url, { ...options, method: 'POST', data });
        },
        
        put(url, data, options = {}) {
            return this.request(url, { ...options, method: 'PUT', data });
        },
        
        delete(url, options = {}) {
            return this.request(url, { ...options, method: 'DELETE' });
        }
    };

    // ==========================================================================
    // 9. UTILITY FUNCTIONS
    // ==========================================================================
    
    const Utils = {
        /**
         * Format date to French locale
         * @param {string|Date} date - Date to format
         * @param {Object} options - Intl.DateTimeFormat options
         * @returns {string}
         */
        formatDate(date, options = {}) {
            const d = date instanceof Date ? date : new Date(date);
            const defaults = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            };
            return d.toLocaleDateString('fr-FR', { ...defaults, ...options });
        },
        
        /**
         * Format number with French locale
         * @param {number} number - Number to format
         * @param {Object} options - Intl.NumberFormat options
         * @returns {string}
         */
        formatNumber(number, options = {}) {
            return new Intl.NumberFormat('fr-FR', options).format(number);
        },
        
        /**
         * Format currency
         * @param {number} amount - Amount to format
         * @param {string} currency - Currency code
         * @returns {string}
         */
        formatCurrency(amount, currency = 'XOF') {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency,
                minimumFractionDigits: 0
            }).format(amount);
        },
        
        /**
         * Generate initials from name
         * @param {string} name - Full name
         * @returns {string}
         */
        getInitials(name) {
            return name
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase()
                .slice(0, 2);
        },
        
        /**
         * Truncate text
         * @param {string} text - Text to truncate
         * @param {number} length - Max length
         * @returns {string}
         */
        truncate(text, length = 50) {
            if (text.length <= length) return text;
            return text.slice(0, length) + '...';
        },
        
        /**
         * Debounce function
         * @param {Function} func - Function to debounce
         * @param {number} wait - Wait time in ms
         * @returns {Function}
         */
        debounce(func, wait = 300) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Copy text to clipboard
         * @param {string} text - Text to copy
         * @returns {Promise<boolean>}
         */
        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                Toast.success('Copié dans le presse-papier');
                return true;
            } catch (err) {
                console.error('Failed to copy:', err);
                return false;
            }
        },
        
        /**
         * Download file
         * @param {string} url - File URL
         * @param {string} filename - Filename
         */
        download(url, filename) {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        /**
         * Escape HTML
         * @param {string} text - Text to escape
         * @returns {string}
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // ==========================================================================
    // 10. LOADING STATES
    // ==========================================================================
    
    const Loading = {
        /**
         * Show loading overlay
         * @param {HTMLElement} container - Container element
         */
        show(container) {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            `;
            
            container.style.position = 'relative';
            container.appendChild(overlay);
        },
        
        /**
         * Hide loading overlay
         * @param {HTMLElement} container - Container element
         */
        hide(container) {
            const overlay = container.querySelector('.loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        },
        
        /**
         * Button loading state
         * @param {HTMLButtonElement} button - Button element
         * @param {boolean} loading - Loading state
         */
        button(button, loading) {
            if (loading) {
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
                button.disabled = true;
            } else {
                button.innerHTML = button.dataset.originalText || button.innerHTML;
                button.disabled = false;
            }
        }
    };

    // ==========================================================================
    // INITIALIZATION
    // ==========================================================================
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all systems
        Toast.init();
        Dropdown.init();
        Tabs.init();
        Sidebar.initActiveState();
        
        // Initialize row checkbox listeners
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', () => DataTable.updateBulkActions());
        });
        
        // Initialize search inputs with debounce
        document.querySelectorAll('[data-table-search]').forEach(input => {
            const tableId = input.dataset.tableSearch;
            const table = document.getElementById(tableId);
            
            if (table) {
                input.addEventListener('input', Utils.debounce((e) => {
                    DataTable.filter(table, e.target.value);
                }, 300));
            }
        });
        
        // Auto-hide flash messages
        document.querySelectorAll('.alert[data-auto-hide]').forEach(alert => {
            const duration = parseInt(alert.dataset.autoHide) || 5000;
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, duration);
        });
    });

    // ==========================================================================
    // GLOBAL EXPORTS
    // ==========================================================================
    
    // Expose to global scope for use in inline handlers
    window.CM = {
        Modal,
        Toast,
        Dropdown,
        Tabs,
        Sidebar,
        DataTable,
        Form,
        Ajax,
        Utils,
        Loading
    };
    
    // Legacy compatibility - expose direct functions
    window.openModal = (id) => Modal.show(id);
    window.closeModal = (id) => Modal.hide(id);
    window.toggleCategory = (id) => Sidebar.toggleCategory(id);
    window.toggleSubMenu = (id) => Sidebar.toggleSubMenu(id);
    window.notify = (message, type) => Toast.show(message, { type });

})();
