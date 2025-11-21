/**
 * JavaScript pour la gestion de la liste des étudiants encadrés
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Toggle filter section
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    const filterSection = document.getElementById('filterSection');
    
    if (toggleFiltersBtn && filterSection) {
        toggleFiltersBtn.addEventListener('click', function() {
            filterSection.classList.toggle('collapsed');
            
            // Change button text and icon
            const icon = this.querySelector('i');
            if (filterSection.classList.contains('collapsed')) {
                this.innerHTML = '<i class="fas fa-filter mr-2"></i> Afficher Filtres';
            } else {
                this.innerHTML = '<i class="fas fa-filter mr-2"></i> Masquer Filtres';
            }
        });
    }

    // Auto-submit form when filters change
    const filterSelects = document.querySelectorAll('select[name="filter_ue"], select[name="filter_ecue"]');
    filterSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            // Add loading state
            const form = this.closest('form');
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Recherche...';
                submitBtn.disabled = true;
            }
            
            // Submit form
            form.submit();
        });
    });

    // Add loading state to search button
    const searchForm = document.querySelector('form[method="GET"]');
    if (searchForm) {
        const submitBtn = searchForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Recherche...';
                this.disabled = true;
            });
        }
    }

    // Real-time search (optional - can be enabled for better UX)
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Debounce search to avoid too many requests
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    this.closest('form').submit();
                }
            }, 500);
        });
    }

    // Pagination links enhancement
    const paginationLinks = document.querySelectorAll('.pagination-link');
    paginationLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Add loading state to the clicked link
            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.style.pointerEvents = 'none';
            
            // Reset after a short delay (in case of navigation issues)
            setTimeout(() => {
                this.innerHTML = originalContent;
                this.style.pointerEvents = 'auto';
            }, 2000);
        });
    });

    // Table row hover effects
    const tableRows = document.querySelectorAll('.table-row-hover');
    tableRows.forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });

    // Action buttons tooltips
    const actionButtons = document.querySelectorAll('[title]');
    actionButtons.forEach(function(button) {
        button.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title');
            if (title) {
                // Create tooltip
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute z-10 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg';
                tooltip.textContent = title;
                tooltip.style.top = '-30px';
                tooltip.style.left = '50%';
                tooltip.style.transform = 'translateX(-50%)';
                
                this.style.position = 'relative';
                this.appendChild(tooltip);
            }
        });
        
        button.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('div');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });

    // Export functionality (if needed)
    const exportBtn = document.querySelector('#exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Add export functionality here
            alert('Fonctionnalité d\'export à implémenter');
        });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to clear search
        if (e.key === 'Escape') {
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                searchInput.closest('form').submit();
            }
        }
    });

    // Responsive table handling
    function handleResponsiveTable() {
        const table = document.querySelector('table');
        if (table && window.innerWidth < 768) {
            // Add horizontal scroll indicator
            if (!table.querySelector('.scroll-indicator')) {
                const indicator = document.createElement('div');
                indicator.className = 'scroll-indicator text-xs text-gray-500 text-center py-2';
                indicator.innerHTML = '<i class="fas fa-arrows-alt-h mr-1"></i> Faites défiler horizontalement';
                table.parentNode.insertBefore(indicator, table);
            }
        }
    }

    // Call on load and resize
    handleResponsiveTable();
    window.addEventListener('resize', handleResponsiveTable);

    // Initialize any additional features
    console.log('Liste des étudiants enseignant initialisée');
}); 