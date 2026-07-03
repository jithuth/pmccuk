@extends('layouts.admin')

@section('page_title', 'Master Ledger & Financials')

@section('styles')
<style>
    :root {
        --fin-primary: #1a2845;
        --fin-success: #10b981;
        --fin-danger: #ef4444;
        --fin-accent: #f59e0b;
        --fin-bg: #f8fafc;
    }

    .fin-card { border: none; border-radius: 1.25rem; overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .fin-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
    
    .glass-stat {
        background: linear-gradient(135deg, var(--fin-primary), #2563eb);
        color: white; border: 1px solid rgba(255,255,255,0.1);
    }
    
    .kpi-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,0.15); font-size: 1.25rem;
    }

    .trend-chart-container { background: white; border-radius: 1.25rem; padding: 1.5rem; }

    .table-ledger thead th {
        background: #f1f5f9; color: #64748b; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.7rem;
        padding: 1rem 1.5rem; border: none;
    }
    .table-ledger tbody td { padding: 1.25rem 1.5rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

    .category-tag {
        font-size: 0.75rem; font-weight: 600; padding: 0.35rem 0.85rem;
        border-radius: 50px; background: #f1f5f9; color: #475569;
        display: inline-flex; align-items: center; gap: 0.5rem;
    }

    .amount-in { color: var(--fin-success); font-family: 'Inter', sans-serif; font-weight: 800; }
    .amount-out { color: var(--fin-danger); font-family: 'Inter', sans-serif; font-weight: 800; }

    .filter-dock {
        background: white; border-radius: 50px; padding: 0.5rem 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        display: flex; align-items: center; gap: 1rem;
    }
</style>
@endsection

@section('content')
<!-- Header & Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-black text-dark mb-1">Financial Intelligence</h3>
        <p class="text-muted small mb-0"><i class="fas fa-microchip me-1 text-primary"></i> Real-time ledger synchronization enabled</p>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('admin.accounting.sync') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm" onclick="return confirm('Synchronize all unrecorded event and membership revenue into the master ledger?')">
                <i class="fas fa-sync-alt me-1 text-primary"></i> RECONCILE DATA
            </button>
        </form>
        <form action="{{ route('admin.accounting.revoke') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm" onclick="return confirm('WARNING: This will remove all bulk-reconciled entries from the ledger. Manual entries and real-time payments will be preserved. Proceed?')">
                <i class="fas fa-history me-1"></i> REVOKE
            </button>
        </form>
        <button class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm" data-toggle="modal" data-target="#addTransactionModal">
            <i class="fas fa-plus-circle me-1 text-accent"></i> New Entry
        </button>
        <a href="{{ route('admin.accounting.export', request()->query()) }}" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold">
            <i class="fas fa-file-excel me-1 text-success"></i> Export Excel
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Main KPI -->
    <div class="col-lg-4">
        <div class="fin-card glass-stat p-4 h-100 position-relative animate__animated animate__fadeInLeft">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="kpi-icon"><i class="fas fa-vault"></i></div>
                <span class="badge bg-white text-primary rounded-pill px-3 py-2 fw-bold small">Audit Balance</span>
            </div>
            <p class="text-white-50 small mb-1 text-uppercase fw-bold tracking-wider">Master Net Liquidity</p>
            <h1 class="display-5 fw-black mb-0">£{{ number_format($globalBal, 2) }}</h1>
            <div class="mt-4 pt-3 border-top border-white-10 d-flex justify-content-between align-items-center">
                <div class="small"><i class="fas fa-shield-alt text-success-subtle me-1"></i> <span class="text-white-50">Operational Margin:</span> Healthy</div>
                <div class="avatar-group d-flex">
                    <div class="rounded-circle border border-white bg-warning text-dark d-flex align-items-center justify-content-center" style="width:24px;height:24px;font-size:10px;font-weight:900;">A</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Stats -->
    <div class="col-lg-8">
        <div class="row g-4 h-100">
            <div class="col-md-6">
                <div class="fin-card p-4 bg-white shadow-sm h-100 animate__animated animate__fadeInUp border">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="kpi-icon bg-success-soft text-success"><i class="fas fa-arrow-trend-up"></i></div>
                        <h6 class="mb-0 text-muted fw-bold text-uppercase small">Period Revenue</h6>
                    </div>
                    <h2 class="fw-black text-dark">£{{ number_format($periodInc, 2) }}</h2>
                    <div class="progress mt-3" style="height: 6px; border-radius: 10px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                    <small class="text-muted d-block mt-2">Combined entry streams</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="fin-card p-4 bg-white shadow-sm h-100 animate__animated animate__fadeInUp border" style="animation-delay: 0.1s;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="kpi-icon bg-danger-soft text-danger"><i class="fas fa-receipt"></i></div>
                        <h6 class="mb-0 text-muted fw-bold text-uppercase small">Period Expenses</h6>
                    </div>
                    <h2 class="fw-black text-dark">£{{ number_format($periodExp, 2) }}</h2>
                    <div class="progress mt-3" style="height: 6px; border-radius: 10px;">
                        <div class="progress-bar bg-danger" style="width: 100%"></div>
                    </div>
                    <small class="text-muted d-block mt-2">System wide leakage</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 d-flex align-items-stretch">
    <!-- Trend Chart -->
    <div class="col-lg-6">
        <div class="trend-chart-container shadow-sm border h-100 animate__animated animate__fadeIn">
            <h6 class="fw-black text-dark text-uppercase small mb-4">Cash Flow Intelligence Analysis</h6>
            <div style="height: 300px;">
                <canvas id="accountingChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Category Breakdown Chart -->
    <div class="col-lg-3">
        <div class="trend-chart-container shadow-sm border h-100 bg-white animate__animated animate__fadeIn">
            <h6 class="fw-black text-dark text-uppercase small mb-4">Revenue Sectors</h6>
            <div style="height: 250px;">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="mt-3 small text-center text-muted fw-bold">Income Distribution</div>
        </div>
    </div>

    <!-- Filter Dock -->
    <div class="col-lg-3">
        <div class="fin-card bg-white p-4 shadow-sm border h-100 animate__animated animate__fadeInRight">
            <h6 class="fw-black text-dark text-uppercase small mb-4">Ledger Intelligence</h6>
            <form action="{{ route('admin.accounting') }}" method="GET">
                <div class="form-group mb-3">
                    <label class="small text-muted fw-bold mb-1">Time Range</label>
                    <div class="space-y-2">
                        <input type="date" name="from" class="form-control border-light bg-light py-2" value="{{ $dFrom }}">
                        <input type="date" name="to" class="form-control border-light bg-light py-2" value="{{ $dTo }}">
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="small text-muted fw-bold mb-1">Impact Sector</label>
                    <select name="f_cat" class="form-select border-light bg-light py-2">
                        <option value="">Full Ecosystem</option>
                        @foreach($allCats as $cat)
                            <option value="{{ $cat }}" {{ request('f_cat') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-lg">
                    <i class="fas fa-radar me-1"></i> SYNC DASHBOARD
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Ledger Table -->
<div class="fin-card bg-white shadow-sm border animate__animated animate__fadeInUp">
    <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-black text-dark mb-0">Master Ledger Entries</h5>
        <div class="badge bg-light text-muted border px-3 py-2">{{ $transactions->total() }} Records Found</div>
    </div>
    <div class="table-responsive">
        <table class="table table-ledger mb-0">
            <thead>
                <tr>
                    <th>Entry Date</th>
                    <th>Intelligence category</th>
                    <th>Ledger Type</th>
                    <th>Detailed Description</th>
                    <th class="text-end ps-0">Financial Impact</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                <tr>
                    <td class="fw-bold text-dark">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M, Y') }}</td>
                    <td>
                        <span class="category-tag">
                            @if(str_contains(strtolower($t->category), 'member')) <i class="fas fa-users-crown text-primary"></i> 
                            @elseif(str_contains(strtolower($t->category), 'booking')) <i class="fas fa-ticket-perforated text-warning"></i>
                            @else <i class="fas fa-layer-group"></i> @endif
                            {{ $t->category }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-circle p-1 {{ $t->type == 'income' ? 'bg-success' : 'bg-danger' }}"></span>
                            <span class="small fw-bold text-uppercase {{ $t->type == 'income' ? 'text-success' : 'text-danger' }}">{{ $t->type }}</span>
                        </div>
                    </td>
                    <td class="text-muted small italic" style="max-width: 300px;">{{ $t->description }}</td>
                    <td class="text-end">
                        <span class="{{ $t->type == 'income' ? 'amount-in' : 'amount-out' }} h5 mb-0">
                            {{ $t->type == 'income' ? '+' : '-' }} £{{ number_format($t->amount, 2) }}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <div class="btn-group btn-group-sm rounded-pill overflow-hidden shadow-sm">
                            <button class="btn btn-warning text-white" onclick="editTransaction({{ $t->id }})" title="Edit Entry">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.accounting.delete', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently remove this entry from the ledger?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" title="Delete Entry">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="opacity-25 mb-3"><i class="fas fa-box-open fa-3x"></i></div>
                        <p class="text-muted fw-bold">Zero financial events recorded for this sector.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 bg-light border-top">
        {{ $transactions->appends(request()->query())->links() }}
    </div>
</div>

<style>
    .bg-success-soft { background: rgba(16, 185, 129, 0.1); }
    .bg-danger-soft { background: rgba(239, 68, 68, 0.1); }
    .fw-black { font-weight: 900 !important; }
    .space-y-2 > * + * { margin-top: 0.5rem; }
</style>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header bg-dark text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-black"><i class="fas fa-plus-circle text-accent me-2"></i> Log Financial Event</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('admin.accounting.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4 text-start">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Ledger Type</label>
                            <select name="type" class="form-select border-light bg-light py-3 fw-bold" required>
                                <option value="income">Incoming (Income)</option>
                                <option value="expense">Outgoing (Expense)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Amount (£)</label>
                            <input type="number" step="0.01" name="amount" class="form-control border-light bg-light py-3 fw-bold" placeholder="0.00" required>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Category</label>
                            <input type="text" name="category" class="form-control border-light bg-light py-3" list="categoryHints" placeholder="e.g. Rent, Donation, Supplies..." required>
                            <datalist id="categoryHints">
                                @foreach($allCats as $cat) <option value="{{ $cat }}"> @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Transaction Date</label>
                            <input type="date" name="transaction_date" class="form-control border-light bg-light py-3" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Payment Method</label>
                            <select name="payment_method" class="form-select border-light bg-light py-3">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Detailed Description</label>
                            <textarea name="description" class="form-control border-light bg-light" rows="3" placeholder="Brief notes for the auditor..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark px-5 py-2 fw-black rounded-pill shadow-lg">
                        COMMIT ENTRY <i class="fas fa-check-double ms-2 text-accent"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header bg-warning text-dark border-0 py-4 px-4">
                <h5 class="modal-title fw-black"><i class="fas fa-edit me-2"></i> Update Financial Entry</h5>
                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
            </div>
            <form id="editTransactionForm" method="POST">
                @csrf
                <div class="modal-body p-4 text-start">
                    <div id="editLoading" class="text-center py-4 d-none">
                        <div class="spinner-border text-warning"></div>
                    </div>
                    <div id="editFields" class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Ledger Type</label>
                            <select name="type" id="e_type" class="form-select border-light bg-light py-3 fw-bold" required>
                                <option value="income">Incoming (Income)</option>
                                <option value="expense">Outgoing (Expense)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Amount (£)</label>
                            <input type="number" step="0.01" name="amount" id="e_amount" class="form-control border-light bg-light py-3 fw-bold" required>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Category</label>
                            <input type="text" name="category" id="e_category" class="form-control border-light bg-light py-3" list="categoryHints" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Transaction Date</label>
                            <input type="date" name="transaction_date" id="e_date" class="form-control border-light bg-light py-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Payment Method</label>
                            <select name="payment_method" id="e_method" class="form-select border-light bg-light py-3">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-muted fw-bold mb-1 text-uppercase">Detailed Description</label>
                            <textarea name="description" id="e_description" class="form-control border-light bg-light" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-5 py-2 fw-black rounded-pill shadow-lg text-white">
                        SAVE CHANGES <i class="fas fa-save ms-2 text-white"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function editTransaction(id) {
        const modal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
        const form = document.getElementById('editTransactionForm');
        const fields = document.getElementById('editFields');
        const loading = document.getElementById('editLoading');

        form.action = `/admin/accounting/${id}/update`;
        fields.classList.add('d-none');
        loading.classList.remove('d-none');
        modal.show();

        fetch(`/admin/accounting/${id}/details`)
            .then(res => res.json())
            .then(t => {
                document.getElementById('e_type').value = t.type;
                document.getElementById('e_amount').value = t.amount;
                document.getElementById('e_category').value = t.category;
                document.getElementById('e_date').value = t.transaction_date;
                document.getElementById('e_method').value = t.payment_method || 'Bank Transfer';
                document.getElementById('e_description').value = t.description || '';

                loading.classList.add('d-none');
                fields.classList.remove('d-none');
            });
    }
    const ctx = document.getElementById('accountingChart').getContext('2d');
    
    const gradientInc = ctx.createLinearGradient(0, 0, 0, 400);
    gradientInc.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
    gradientInc.addColorStop(1, 'rgba(16, 185, 129, 0)');

    const gradientExp = ctx.createLinearGradient(0, 0, 0, 400);
    gradientExp.addColorStop(0, 'rgba(239, 68, 68, 0.2)');
    gradientExp.addColorStop(1, 'rgba(239, 68, 68, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [
                {
                    label: 'Income (£)',
                    data: {!! json_encode($chartInc) !!},
                    borderColor: '#10b981',
                    borderWidth: 3,
                    backgroundColor: gradientInc,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointHoverRadius: 6
                },
                {
                    label: 'Expense (£)',
                    data: {!! json_encode($chartExp) !!},
                    borderColor: '#ef4444',
                    borderWidth: 3,
                    backgroundColor: gradientExp,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#ef4444',
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#1a2845',
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    padding: 12,
                    displayColors: false
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false },
                    ticks: { color: '#64748b', font: { size: 11 } }
                },
                x: { 
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { size: 11 } }
                }
            }
        }
    });

    // Category Breakdown Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($catStats->pluck('category')) !!},
            datasets: [{
                data: {!! json_encode($catStats->pluck('total')) !!},
                backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a2845',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            return `${label}: £${value.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });
</script>
@endsection
