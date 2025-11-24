// Audit Interface Enhancement Script
document.addEventListener('DOMContentLoaded', function() {
    initializeDatePickers();
    initializeSearch();
    initializeActionButtons();
    initializeTableInteractions();
    initializePrintFunctionality();
});

function initializeDatePickers() {
    console.log('Date pickers initialized');
}

function initializeSearch() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }
}

function initializeActionButtons() {
    const exportBtn = document.querySelector('a[href*="action=export"]');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Export en cours...';
            this.style.pointerEvents = 'none';
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
            }, 5000);
        });
    }
    
    const printBtn = document.querySelector('button[onclick="window.print()"]');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showPrintPreview();
        });
    }
}

function initializeTableInteractions() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f9fafb';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    const copyableCells = document.querySelectorAll('td:nth-child(3), td:nth-child(5)');
    copyableCells.forEach(cell => {
        cell.style.cursor = 'pointer';
        cell.title = 'Cliquer pour copier';
        cell.addEventListener('click', function() {
            copyToClipboard(this.textContent.trim());
            showToast('Texte copié dans le presse-papiers', 'success');
        });
    });
}

function initializePrintFunctionality() {
    const printStyles = document.createElement('style');
    printStyles.textContent = `
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .bg-white { background: white !important; }
            .shadow-lg { box-shadow: none !important; }
            .rounded-xl { border-radius: 0 !important; }
            table { border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; }
        }
    `;
    document.head.appendChild(printStyles);
}

function showPrintPreview() {
    const printWindow = window.open('', '_blank');
    const table = document.querySelector('table');
    const title = document.querySelector('h1').textContent;
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 20px; }
                .filters { margin-bottom: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>${title}</h1>
                <p>Généré le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}</p>
            </div>
            <div class="filters">
                <strong>Filtres appliqués:</strong> ${getActiveFilters()}
            </div>
            ${table.outerHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

function getActiveFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const filters = [];
    
    if (urlParams.get('date_debut')) filters.push(`Date début: ${urlParams.get('date_debut')}`);
    if (urlParams.get('date_fin')) filters.push(`Date fin: ${urlParams.get('date_fin')}`);
    if (urlParams.get('action')) filters.push(`Action: ${urlParams.get('action')}`);
    if (urlParams.get('table')) filters.push(`Table: ${urlParams.get('table')}`);
    if (urlParams.get('statut')) filters.push(`Statut: ${urlParams.get('statut')}`);
    if (urlParams.get('search')) filters.push(`Recherche: ${urlParams.get('search')}`);
    
    return filters.length > 0 ? filters.join(', ') : 'Aucun filtre';
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function exportWithProgress() {
    const exportBtn = document.querySelector('a[href*="action=export"]');
    if (!exportBtn) return;
    
    const originalHref = exportBtn.href;
    const originalText = exportBtn.innerHTML;
    
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Préparation de l\'export...';
    exportBtn.style.pointerEvents = 'none';
    
    setTimeout(() => {
        exportBtn.innerHTML = '<i class="fas fa-download mr-2"></i> Téléchargement...';
        
        const link = document.createElement('a');
        link.href = originalHref;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        setTimeout(() => {
            exportBtn.innerHTML = originalText;
            exportBtn.style.pointerEvents = 'auto';
            showToast('Export terminé avec succès', 'success');
        }, 1000);
    }, 500);
}

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        showPrintPreview();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        const exportBtn = document.querySelector('a[href*="action=export"]');
        if (exportBtn) {
            exportBtn.click();
        }
    }
});

function enableAutoRefresh(interval = 30000) {
    setInterval(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const hasFilters = urlParams.get('date_debut') || urlParams.get('date_fin') || 
                          urlParams.get('action') || urlParams.get('table') || 
                          urlParams.get('statut') || urlParams.get('search');
        
        if (!hasFilters) {
            location.reload();
        }
    }, interval);
} 