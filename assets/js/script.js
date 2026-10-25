// Hamburger Menu
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger) {
        hamburger.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }

    // Modal helpers
    document.querySelectorAll('[data-open-modal]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.getElementById(btn.getAttribute('data-open-modal'));
            if (target) target.classList.remove('hidden');
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = document.getElementById(btn.getAttribute('data-close-modal'));
            if (target) target.classList.add('hidden');
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.add('hidden');
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(el) {
        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.5s';
            setTimeout(function() {
                el.style.display = 'none';
            }, 500);
        }, 5000);
    });

    // Balance visibility toggle
    const balanceToggle = document.querySelector('[data-toggle-balance]');
    if (balanceToggle) {
        setBalanceVisibility(getStoredBalanceVisibility() === 'hidden');

        balanceToggle.addEventListener('click', function() {
            setBalanceVisibility(balanceToggle.getAttribute('aria-pressed') !== 'true');
        });
    }
});

const BALANCE_STORAGE_KEY = 'pbm_balance_visibility';

function getStoredBalanceVisibility() {
    try {
        return localStorage.getItem(BALANCE_STORAGE_KEY) || 'visible';
    } catch (e) {
        return 'visible';
    }
}

function setBalanceVisibility(hidden) {
    const toggle = document.querySelector('[data-toggle-balance]');
    const icon = document.querySelector('[data-balance-toggle-icon]');
    const label = document.querySelector('[data-balance-toggle-label]');

    document.querySelectorAll('[data-sensitive-amount]').forEach(function(el) {
        if (hidden) {
            if (!el.hasAttribute('data-real-amount')) {
                el.setAttribute('data-real-amount', el.textContent.trim());
            }
            el.textContent = '••••••';
            el.classList.add('is-masked');
        } else {
            el.textContent = el.getAttribute('data-real-amount') || '';
            el.classList.remove('is-masked');
        }
    });

    if (icon) icon.textContent = hidden ? 'visibility' : 'visibility_off';
    if (label) label.textContent = hidden ? 'Show Balance' : 'Hide Balance';
    if (toggle) toggle.setAttribute('aria-pressed', hidden ? 'true' : 'false');

    try {
        localStorage.setItem(BALANCE_STORAGE_KEY, hidden ? 'hidden' : 'visible');
    } catch (e) {
    }
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let valid = true;

    inputs.forEach(function(input) {
        const errorSpan = input.nextElementSibling;
        if (input.value.trim() === '') {
            valid = false;
            input.style.borderColor = '#dc3545';
            if (errorSpan && errorSpan.classList.contains('error-message')) {
                errorSpan.textContent = 'This field is required';
                errorSpan.style.display = 'block';
            }
        } else {
            input.style.borderColor = '#ddd';
            if (errorSpan && errorSpan.classList.contains('error-message')) {
                errorSpan.style.display = 'none';
            }
        }
    });

    return valid;
}

// Validate amount field
function validateAmount(input) {
    const value = parseFloat(input.value);
    if (isNaN(value) || value <= 0) {
        input.style.borderColor = '#dc3545';
        return false;
    } else {
        input.style.borderColor = '#ddd';
        return true;
    }
}

// Validate email field
function validateEmail(input) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(input.value)) {
        input.style.borderColor = '#dc3545';
        return false;
    } else {
        input.style.borderColor = '#ddd';
        return true;
    }
}

// Validate password match
function validatePasswordMatch(passwordId, confirmId) {
    const password = document.getElementById(passwordId);
    const confirm = document.getElementById(confirmId);

    if (password.value !== confirm.value) {
        confirm.style.borderColor = '#dc3545';
        return false;
    } else {
        confirm.style.borderColor = '#ddd';
        return true;
    }
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Toggle password visibility
function togglePassword(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        toggleBtn.textContent = 'Hide';
    } else {
        input.type = 'password';
        toggleBtn.textContent = 'Show';
    }
}

// Filter table rows
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        for (let j = 0; j < cells.length; j++) {
            const cell = cells[j];
            if (cell) {
                const textValue = cell.textContent || cell.innerText;
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        rows[i].style.display = found ? '' : 'none';
    }
}

// Format currency
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Calculate totals dynamically
function calculateTotals() {
    const amountInputs = document.querySelectorAll('.amount-input');
    let total = 0;
    amountInputs.forEach(function(input) {
        const val = parseFloat(input.value) || 0;
        total += val;
    });
    const totalDisplay = document.getElementById('total-amount');
    if (totalDisplay) {
        totalDisplay.textContent = formatCurrency(total);
    }
}

// Date range filter
function filterByDateRange(startId, endId, tableId) {
    const startDate = document.getElementById(startId).value;
    const endDate = document.getElementById(endId).value;
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const dateCell = rows[i].getElementsByTagName('td')[0];
        if (dateCell) {
            const rowDate = dateCell.textContent;
            if (startDate && endDate) {
                if (rowDate >= startDate && rowDate <= endDate) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            } else {
                rows[i].style.display = '';
            }
        }
    }
}

// Category filter
function filterByCategory(selectId, tableId) {
    const category = document.getElementById(selectId).value;
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const categoryCell = rows[i].getElementsByTagName('td')[1];
        if (categoryCell) {
            const rowCategory = categoryCell.textContent;
            if (!category || rowCategory === category) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}

// Type filter (Income/Expense)
function filterByType(selectId, tableId) {
    const type = document.getElementById(selectId).value;
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const typeCell = rows[i].getElementsByTagName('td')[3];
        if (typeCell) {
            const rowType = typeCell.textContent.trim().toLowerCase();
            if (!type || rowType === type) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}
