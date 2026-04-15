@extends('layouts.admin')

@section('page_title', 'Accounting & Financials')

@section('styles')
<style>
    .info-card { border-radius: 15px; border: none; transition: transform 0.2s; }
    .bg-indigo { background-color: #6610f2 !important; }
    .text-indigo { color: #6610f2 !important; }
    .table td, .table th { vertical-align: middle !important; font-size: 13px; }
</style>
@endsection

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-0">Financial Overview</h2>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-plus me-1"></i> Add Transaction</button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title fw-bold mb-0">Financial Trend (Last 12 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="accountingChart" style="height: 250px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-indigo text-white shadow-sm border-0 mb-3 rounded-4">
            <div class="card-body p-4">
                <h6 class="text-uppercase small fw-bold opacity-75">Global Net Balance</h6>
                <h1 class="fw-bold mb-0">£{{ number_format($globalBal, 2) }}</h1>
                <p class="small mb-0 mt-2 opacity-50 text-uppercase tracking-wider">All-time Master Ledger</p>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-6">
                <div class="card border-0 shadow-sm text-center py-3 rounded-3 h-100">
                    <h6 class="text-success small fw-bold text-uppercase mb-1">Period In</h6>
                    <h4 class="fw-bold m-0 text-dark">£{{ number_format($periodInc, 2) }}</h4>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm text-center py-3 rounded-3 h-100">
                    <h6 class="text-danger small fw-bold text-uppercase mb-1">Period Out</h6>
                    <h4 class="fw-bold m-0 text-dark">£{{ number_format($periodExp, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-3 rounded-3 overflow-hidden">
            <div class="card-body p-3 text-center">
                <h6 class="text-xs fw-bold text-uppercase text-muted opacity-50 mb-1">Net Period Profit</h6>
                <h2 class="fw-bold m-0 {{ ($periodInc - $periodExp >= 0) ? 'text-success' : 'text-danger' }}">£{{ number_format($periodInc - $periodExp, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <form action="{{ route('admin.accounting') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="small fw-bold text-muted text-uppercase mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $dFrom }}">
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted text-uppercase mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $dTo }}">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted text-uppercase mb-1">Category</label>
                <select name="f_cat" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($allCats as $cat)
                        <option value="{{ $cat }}" {{ request('f_cat') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="fas fa-filter me-1"></i> Apply</button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light text-uppercase text-secondary small">
                    <tr>
                        <th class="ps-3">Date</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end pe-3">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                    <tr>
                        <td class="ps-3">{{ \Carbon\Carbon::parse($t->transaction_date)->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-indigo border rounded-pill px-3">{{ $t->category }}</span></td>
                        <td>
                            <span class="text-{{ $t->type == 'income' ? 'success' : 'danger' }} fw-bold text-uppercase small">
                                <i class="fas fa-{{ $t->type == 'income' ? 'plus-circle' : 'minus-circle' }} me-1"></i> {{ $t->type }}
                            </span>
                        </td>
                        <td class="text-muted small italic">{{ $t->description }}</td>
                        <td class="text-end pe-3 fw-bold {{ $t->type == 'income' ? 'text-success' : 'text-danger' }}">
                            {{ $t->type == 'income' ? '+' : '-' }} £{{ number_format($t->amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $transactions->appends(request()->query())->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('accountingChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [
                {
                    label: 'Income',
                    data: {!! json_encode($chartInc) !!},
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Expense',
                    data: {!! json_encode($chartExp) !!},
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endsection
