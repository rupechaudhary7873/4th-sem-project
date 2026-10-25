<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Manager - Income Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
        <nav class="navbar">
        <div class="nav-container">
            <div class="nav-left">
                <div class="nav-brand">
                    <a href="/dashboard">Budget Manager</a>
                </div>
                <ul class="nav-menu" id="navMenu">
                                        <li><a class="" href="/dashboard">Dashboard</a></li>
                    <li><a class="active" href="/income">Income</a></li>
                    <li><a class="" href="/expenses">Expenses</a></li>
                    <li><a class="" href="/budget">Budgets</a></li>
                    <li><a class="" href="/reports">Reports</a></li>
                                    </ul>
            </div>
            <div class="nav-actions">
                                <a href="/profile" class="nav-action ">
                    <span class="material-symbols-outlined">person</span>
                    <span class="nav-action-text">Profile</span>
                </a>
                                <a href="/logout" class="nav-action">
                    <span class="material-symbols-outlined">logout</span>
                    <span class="nav-action-text">Logout</span>
                </a>
            </div>
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
        <div class="container">

<div class="page-header">
    <div>
        <h1 class="page-title">Income Management</h1>
        <p class="page-subtitle">Track and manage all your revenue streams.</p>
    </div>
    <button class="btn btn-primary" data-open-modal="addModal">
        <span class="material-symbols-outlined">add</span>
        Add Income
    </button>
</div>

    <div class="alert alert-danger">Please fill all required fields correctly</div>

<div class="card">
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                                <tr>
                    <td>01 Aug 2026</td>
                    <td>
                        <span class="badge badge-primary">Salary</span>
                    </td>
                    <td>Salary</td>
                    <td class="text-right amount-income">+ ₹30,000.00</td>
                    <td class="text-center">
                        <div class="flex justify-center gap-sm">
                            <button class="btn btn-icon btn-outline" title="Edit" onclick="editIncome(5, 'Salary', 31, 30000.00, 'Salary', '2026-08-01')">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <form method="POST" onsubmit="return confirmDelete('Delete this income?')">
                                <input type="hidden" name="id" value="5">
                                <button type="submit" name="delete_income" class="btn btn-icon btn-danger" title="Delete">
                                    <span class="material-symbols-outlined">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                            </tbody>
        </table>
    </div>
    <div class="table-footer">
        <span>Showing 1 income record(s)</span>
    </div>
    </div>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h2 class="modal-title">Add New Income</h2>
            <button type="button" class="modal-close" data-close-modal="addModal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" onsubmit="return validateForm('addIncomeForm')" id="addIncomeForm">
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_category">Category *</label>
                    <select id="add_category" name="category_id" required>
                        <option value="">Select Category</option>
                                                    <option value="34">Business</option>
                                                    <option value="32">Freelance</option>
                                                    <option value="33">Investment</option>
                                                    <option value="35">Other Income</option>
                                                    <option value="31">Salary</option>
                                            </select>
                </div>
                <div class="form-group">
                    <label for="add_amount">Amount (₹) *</label>
                    <input type="number" id="add_amount" name="amount" step="0.01" min="0.01" placeholder="0.00" required onblur="validateAmount(this)">
                </div>
                <div class="form-group">
                    <label for="add_description">Description</label>
                    <textarea id="add_description" name="description" rows="3" placeholder="e.g. October Salary"></textarea>
                </div>
                <div class="form-group">
                    <label for="add_date">Date *</label>
                    <input type="date" id="add_date" name="date" value="2026-08-13" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close-modal="addModal">Cancel</button>
                <button type="submit" name="add_income" class="btn btn-primary">Save Income</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay hidden">
    <div class="modal-card">
        <div class="modal-header">
            <h2 class="modal-title">Edit Income</h2>
            <button type="button" class="modal-close" data-close-modal="editModal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" id="editIncomeForm" onsubmit="return validateForm('editIncomeForm')">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_category">Category *</label>
                    <select id="edit_category" name="category_id" required>
                        <option value="">Select Category</option>
                                                    <option value="34">Business</option>
                                                    <option value="32">Freelance</option>
                                                    <option value="33">Investment</option>
                                                    <option value="35">Other Income</option>
                                                    <option value="31">Salary</option>
                                            </select>
                </div>
                <div class="form-group">
                    <label for="edit_amount">Amount (₹) *</label>
                    <input type="number" id="edit_amount" name="amount" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_date">Date *</label>
                    <input type="date" id="edit_date" name="date" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close-modal="editModal">Cancel</button>
                <button type="submit" name="edit_income" class="btn btn-primary">Update Income</button>
            </div>
        </form>
    </div>
</div>

<script>
function editIncome(id, categoryName, categoryId, amount, description, date) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_category').value = categoryId;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_date').value = date;
    document.getElementById('editModal').classList.remove('hidden');
}
</script>

</div>
    <script src="/assets/js/script.js"></script>
</body>
</html>