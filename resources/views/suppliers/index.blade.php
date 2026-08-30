<x-app-layout>
    <x-slot name="header">
        Supplier
    </x-slot>

    <!-- Breadcrumb -->
    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Suppliers</span>
        </nav>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">PKR {{ number_format($grandTotalOwed ?? 0, 2) }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">PKR {{ number_format($grandTotalPaid ?? 0, 2) }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Remaining</p>
                    <p class="text-2xl font-bold {{ ($grandTotalRemaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }} mt-2">PKR {{ number_format($grandTotalRemaining ?? 0, 2) }}</p>
                </div>
                <div class="rounded-full p-3 {{ ($grandTotalRemaining ?? 0) > 0 ? 'bg-red-100' : 'bg-green-100' }}">
                    <svg class="w-6 h-6 {{ ($grandTotalRemaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Suppliers</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $suppliers->total() }}</p>
                </div>
                <div class="bg-orange-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Header with Add Button -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <span class="text-gray-900 font-medium">Suppliers</span>
            </div>
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="flex items-center gap-2">
                    <div class="flex flex-col">
                        <label for="supplier-from-date" class="text-xs text-gray-600">From Date</label>
                        <input id="supplier-from-date" type="date" class="px-2 py-1 border border-gray-300 rounded-md text-sm">
                    </div>
                    <div class="flex flex-col">
                        <label for="supplier-to-date" class="text-xs text-gray-600">To Date</label>
                        <input id="supplier-to-date" type="date" class="px-2 py-1 border border-gray-300 rounded-md text-sm">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="printAllSuppliersReport()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Detailed Report
                    </button>
                    <form method="POST" action="{{ route('suppliers.anonymous') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Anonymous
                        </button>
                    </form>
                    <a href="{{ route('suppliers.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Supplier
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex items-center gap-4" id="search-form">
            <div class="flex-1 relative">
                <input type="text" 
                       id="search-input"
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Q Search" 
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                       oninput="handleSearchInput()">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            @if(request('search'))
                <a href="{{ route('suppliers.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Supplier Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" id="select-all">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier Id</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Paid</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($suppliers as $supplier)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="supplier-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="{{ $supplier->id }}">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $supplier->supplier_id ?? 'SN-' . str_pad($supplier->id, 3, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ $supplier->name }}</span>
                            @if($supplier->is_anonymous)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-slate-100 text-slate-700">Cash</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-green-600">PKR {{ number_format($supplier->total_paid ?? 0, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium {{ ($supplier->remaining ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                                PKR {{ number_format($supplier->remaining ?? 0, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($supplier->hasUnpaid ?? false)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                    Unpaid
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                    Paid
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('suppliers.show', $supplier) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <button onclick="printSupplierReport({{ $supplier->id }})" class="text-green-600 hover:text-green-900" title="Print Complete Report">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </button>
                                @unless($supplier->is_anonymous)
                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No suppliers found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm text-gray-700">Row Per Page</span>
                <form method="GET" action="{{ route('suppliers.index') }}" class="ml-2">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <select name="per_page" onchange="this.form.submit()" class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-orange-500 focus:border-orange-500">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 Entries</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 Entries</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 Entries</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 Entries</option>
                    </select>
                </form>
            </div>
            <div class="flex items-center space-x-2">
                @if($suppliers->hasPages())
                    <div class="flex items-center space-x-1">
                        @if($suppliers->onFirstPage())
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&lt;</span>
                        @else
                            <a href="{{ $suppliers->previousPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&lt;</a>
                        @endif
                        
                        @foreach($suppliers->getUrlRange(1, min(5, $suppliers->lastPage())) as $page => $url)
                            @if($page == $suppliers->currentPage())
                                <span class="px-3 py-1 bg-orange-500 text-white rounded">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $page }}</a>
                            @endif
                        @endforeach
                        
                        @if($suppliers->hasMorePages())
                            <span class="px-2 py-1 text-gray-500">...</span>
                            <a href="{{ $suppliers->url($suppliers->lastPage()) }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">{{ $suppliers->lastPage() }}</a>
                        @endif
                        
                        @if($suppliers->hasMorePages())
                            <a href="{{ $suppliers->nextPageUrl() }}" class="px-3 py-1 text-gray-700 hover:text-gray-900">&gt;</a>
                        @else
                            <span class="px-3 py-1 text-gray-400 cursor-not-allowed">&gt;</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        // Auto-search on keypress with debounce
        let searchTimeout;
        function handleSearchInput() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const form = document.getElementById('search-form');
                if (form) {
                    // Offline: filter local table instead of submitting to server
                    const offline = window.FTOffline && window.FTOffline.isOnline && !window.FTOffline.isOnline();
                    if (offline) {
                        if (tableHasServerSupplierRows()) {
                            filterServerSupplierRows();
                        } else {
                            hydrateSuppliersFromOffline();
                        }
                        return;
                    }
                    form.submit();
                }
            }, 500); // Wait 500ms after user stops typing
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function formatMoney(n) {
            const v = Number(n) || 0;
            return 'PKR ' + v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function supplierTableBody() {
            return document.querySelector('table.min-w-full tbody');
        }

        function tableHasServerSupplierRows() {
            const tbody = supplierTableBody();
            if (!tbody) {
                return false;
            }
            return [...tbody.querySelectorAll('a[href]')].some((a) => {
                const href = a.getAttribute('href') || '';
                return /\/suppliers\/\d+/.test(href);
            });
        }

        function filterServerSupplierRows() {
            const tbody = supplierTableBody();
            if (!tbody) {
                return;
            }
            const search = (document.getElementById('search-input')?.value || '').trim().toLowerCase();
            tbody.querySelectorAll('tr').forEach((tr) => {
                const hay = (tr.innerText || '').toLowerCase();
                tr.style.display = !search || hay.includes(search) ? '' : 'none';
            });
        }

        async function hydrateSuppliersFromOffline() {
            // Cached supplier names do not include paid/remaining. Never replace
            // a live Laravel table with those zeros.
            if (tableHasServerSupplierRows()) {
                filterServerSupplierRows();
                return;
            }
            if (!window.FTOffline?.db?.suppliers) {
                return;
            }

            const tbody = document.querySelector('table.min-w-full tbody');
            if (!tbody) {
                return;
            }

            const search = (document.getElementById('search-input')?.value || '').trim().toLowerCase();
            const branchMeta = await window.FTOffline.db.meta.get('active_branch_id');
            const branchId = branchMeta?.value != null ? Number(branchMeta.value) : null;

            let rows = await window.FTOffline.db.suppliers.toArray();
            if (branchId) {
                rows = rows.filter((s) => !s.branch_id || Number(s.branch_id) === branchId);
            }
            if (search) {
                rows = rows.filter((s) => {
                    const hay = `${s.name || ''} ${s.supplier_id || ''} ${s.email || ''} ${s.phone || ''} ${s.company_name || ''}`.toLowerCase();
                    return hay.includes(search);
                });
            }

            rows.sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));

            if (!rows.length) {
                tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No suppliers found offline. Sync once while online.</td></tr>`;
                return;
            }

            tbody.innerHTML = await Promise.all(rows.map(async (s) => {
                let paid = Number(s.total_paid || 0);
                let remaining = Number(s.remaining || 0);
                if (window.FTOffline.supplierWallet) {
                    const w = await window.FTOffline.supplierWallet(s.id);
                    paid = w.total_paid;
                    remaining = w.remaining;
                } else if (window.FTOffline.db.supplierTransactions) {
                    const txs = (await window.FTOffline.db.supplierTransactions.toArray()).filter((t) => String(t.supplier_id) === String(s.id));
                    paid = txs.filter((t) => t.type === 'debit').reduce((n, t) => n + Number(t.amount || 0), 0);
                    const credit = txs.filter((t) => t.type === 'credit').reduce((n, t) => n + Number(t.amount || 0), 0);
                    remaining = credit - paid;
                }
                const unpaid = remaining > 0.009;
                const isLocal = String(s.id).startsWith('local-') || s.sync_status === 'pending';
                const viewHref = `/suppliers/${s.id}`;
                const statusBadge = unpaid
                    ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>Unpaid</span>`
                    : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>Paid</span>`;
                const idLabel = escapeHtml(s.supplier_id || (typeof s.id === 'number' ? ('SN-' + String(s.id).padStart(3, '0')) : 'Pending sync'));

                return `<tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="supplier-checkbox rounded border-gray-300 text-orange-600 focus:ring-orange-500" value="${escapeHtml(s.id)}" ${isLocal ? 'disabled' : ''}>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-gray-900">${idLabel}${isLocal ? ' <span class="text-amber-600 text-xs">(offline)</span>' : ''}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm text-gray-900">${escapeHtml(s.name || '')}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium text-green-600">${formatMoney(paid)}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-medium ${remaining > 0 ? 'text-red-600' : 'text-green-600'}">${formatMoney(remaining)}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-3">
                            <a href="${viewHref}" class="text-blue-600 hover:text-blue-900" title="View bills and payments">View</a>
                        </div>
                    </td>
                </tr>`;
            })).then((html) => html.join(''));

            // Totals offline are limited (transactions not cached) — show counts at least
            const totalEl = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-4 .text-2xl.font-bold.text-gray-900.mt-2');
            // Update "Total Suppliers" card (4th card)
            const cards = document.querySelectorAll('.grid.grid-cols-1.md\\:grid-cols-4 > div p.text-2xl');
            if (cards.length >= 4) {
                cards[3].textContent = String(rows.length);
            }
        }

        async function maybeEnableOfflineSuppliers() {
            const offline = window.FTOffline && window.FTOffline.isOnline && !window.FTOffline.isOnline();
            if (!offline) {
                return;
            }

            document.querySelectorAll('button[onclick*="printAllSuppliersReport"], button[onclick*="printSupplierReport"]').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Supplier PDF reports need an internet connection. Open View to see bills and payments on this device.');
                }, true);
            });

            await hydrateSuppliersFromOffline();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', maybeEnableOfflineSuppliers);
        } else {
            maybeEnableOfflineSuppliers();
        }

        // Select all checkbox functionality
        document.getElementById('select-all')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.supplier-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        async function printAllSuppliersReport() {
            try {
                if (window.FTReceipt?.requireConfigured) {
                    await window.FTReceipt.requireConfigured();
                }
            } catch (e) {
                return;
            }

            // Read optional From/To dates from inputs above the button
            const fromDateInput = document.getElementById('supplier-from-date')?.value || '';
            const toDateInput = document.getElementById('supplier-to-date')?.value || '';

            const printWindow = window.open('', '_blank');
            
            // Show loading message
            printWindow.document.write('<html><head><title>Loading...</title></head><body><h1>Loading report...</h1></body></html>');
            printWindow.document.close();
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                             document.querySelector('input[name="_token"]')?.value || '';
            
            // Build query string with optional dates
            const params = new URLSearchParams();
            if (fromDateInput && fromDateInput.trim()) {
                params.append('from_date', fromDateInput.trim());
            }
            if (toDateInput && toDateInput.trim()) {
                params.append('to_date', toDateInput.trim());
            }

            const url = `/suppliers/print-all-report` + (params.toString() ? `?${params.toString()}` : '');

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                }
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Error response:', text);
                    throw new Error(`HTTP ${response.status}: ${text.substring(0, 200)}`);
                }
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                } else {
                    const text = await response.text();
                    throw new Error('Expected JSON response but got: ' + contentType);
                }
            })
            .then(data => {
                if (!data) {
                    throw new Error('No data received');
                }
                if (data.success) {
                    const suppliers = data.suppliers || [];
                    const fromDate = data.from_date || null;
                    const toDate = data.to_date || null;
                    const grandTotals = data.grand_totals || { total_owed: 0, total_paid: 0, remaining: 0 };
                    const totalSuppliers = data.total_suppliers || 0;
                    
                    let printContent = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>All Suppliers Detailed Report</title>
                            <style>
                                @media print {
                                    @page { margin: 10mm; }
                                    .page-break { page-break-after: always; }
                                    .supplier-section { page-break-inside: avoid; }
                                }
                                * {
                                    color: #000 !important;
                                }
                                body { 
                                    font-family: 'Arial', sans-serif; 
                                    padding: 20px; 
                                    font-size: 11px;
                                    color: #000 !important;
                                }
                                .header { 
                                    text-align: center; 
                                    margin-bottom: 25px; 
                                    border-bottom: 3px solid #000; 
                                    padding-bottom: 15px; 
                                }
                                .header h1 { 
                                    margin: 0; 
                                    font-size: 24px; 
                                    font-weight: bold;
                                }
                                .header p {
                                    margin: 5px 0 0 0;
                                    font-size: 11px;
                                }
                                .business-info {
                                    padding-top: 10px;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                                .business-service {
                                    font-weight: bold;
                                    margin-bottom: 6px;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                                .business-contact {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: flex-start;
                                    margin-top: 6px;
                                    font-size: 7px;
                                    color: #000 !important;
                                }
                                .supplier-section {
                                    margin-bottom: 30px;
                                    border: 1px solid #000;
                                    padding: 15px;
                                    page-break-inside: avoid;
                                }
                                .supplier-header {
                                    background-color: #f0f0f0;
                                    padding: 15px;
                                    margin-bottom: 15px;
                                    border-bottom: 2px solid #000;
                                }
                                .supplier-name {
                                    font-weight: bold;
                                    font-size: 18px;
                                    margin-bottom: 10px;
                                    color: #000 !important;
                                    text-transform: uppercase;
                                }
                                .supplier-summary {
                                    background-color: #f9f9f9;
                                    padding: 15px;
                                    margin-bottom: 15px;
                                    border: 1px solid #000;
                                }
                                .summary-grid {
                                    display: grid;
                                    grid-template-columns: repeat(2, 1fr);
                                    gap: 10px;
                                    font-size: 11px;
                                }
                                .summary-item {
                                    display: flex;
                                    justify-content: space-between;
                                    padding: 5px 0;
                                    border-bottom: 1px dotted #ccc;
                                }
                                .summary-total {
                                    font-weight: bold;
                                    font-size: 14px;
                                    margin-top: 10px;
                                    padding-top: 10px;
                                    border-top: 2px solid #000;
                                }
                            </style>
                        </head>
                        <body>
                            ${(window.FTReceipt && window.FTReceipt.headerHtml) ? window.FTReceipt.headerHtml('All Suppliers Detailed Report') : ''}
                            <p style="text-align:center;font-size:10px;margin:4px 0 12px;">
                                <strong>From Date:</strong> ${fromDate ? new Date(fromDate).toLocaleDateString('en-US') : 'All Time'}
                                &nbsp; | &nbsp;
                                <strong>To Date:</strong> ${toDate ? new Date(toDate).toLocaleDateString('en-US') : 'All Time'}
                                <br>Generated on: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                            </p>
                            
                            <!-- Grand Totals Section -->
                            <div style="border: 3px solid #000; padding: 20px; margin-bottom: 30px; background-color: #f9f9f9;">
                                <div style="font-weight: bold; font-size: 18px; margin-bottom: 15px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px;">
                                    ALL SUPPLIERS SUMMARY
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 15px;">
                                    <div style="border: 2px solid #000; padding: 15px; text-align: center; background-color: #fff;">
                                        <div style="font-size: 12px; color: #000 !important; margin-bottom: 8px; font-weight: bold;">Total</div>
                                        <div style="font-size: 20px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(grandTotals.total_owed || 0).toFixed(2)}</div>
                                    </div>
                                    <div style="border: 2px solid #000; padding: 15px; text-align: center; background-color: #fff;">
                                        <div style="font-size: 12px; color: #000 !important; margin-bottom: 8px; font-weight: bold;">Paid</div>
                                        <div style="font-size: 20px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(grandTotals.total_paid || 0).toFixed(2)}</div>
                                    </div>
                                    <div style="border: 2px solid #000; padding: 15px; text-align: center; background-color: #fff;">
                                        <div style="font-size: 12px; color: #000 !important; margin-bottom: 8px; font-weight: bold;">Remaining</div>
                                        <div style="font-size: 20px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(grandTotals.remaining || 0).toFixed(2)}</div>
                                    </div>
                                    <div style="border: 2px solid #000; padding: 15px; text-align: center; background-color: #fff;">
                                        <div style="font-size: 12px; color: #000 !important; margin-bottom: 8px; font-weight: bold;">Total Suppliers</div>
                                        <div style="font-size: 20px; font-weight: bold; color: #000 !important;">${totalSuppliers}</div>
                                    </div>
                                </div>
                            </div>
                    `;
                    
                    suppliers.forEach((supplierData, index) => {
                        const supplier = supplierData.supplier;
                        const summary = supplierData.summary;
                        const transactions = supplierData.transactions || [];
                        
                        printContent += `
                            <div class="supplier-section">
                                <div class="supplier-header">
                                    <div class="supplier-name">${supplier.name}${supplier.company_name ? ' - ' + supplier.company_name : ''}</div>
                                    <div style="font-size: 11px; line-height: 1.6;">
                                        ${supplier.supplier_id ? `<div><strong>Supplier ID:</strong> ${supplier.supplier_id}</div>` : ''}
                                        ${supplier.email ? `<div><strong>Email:</strong> ${supplier.email}</div>` : ''}
                                        ${supplier.phone ? `<div><strong>Phone:</strong> ${supplier.phone}</div>` : ''}
                                        ${supplier.address ? `<div><strong>Address:</strong> ${supplier.address}${supplier.city ? ', ' + supplier.city : ''}${supplier.state ? ', ' + supplier.state : ''}${supplier.country ? ', ' + supplier.country : ''}</div>` : ''}
                                    </div>
                                </div>
                                
                                <div class="supplier-summary">
                                    <div style="font-weight: bold; font-size: 14px; margin-bottom: 15px; text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px;">
                                        ACCOUNT SUMMARY
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 15px;">
                                        <div style="border: 2px solid #000; padding: 12px; text-align: center; background-color: #f9f9f9;">
                                            <div style="font-size: 11px; color: #000 !important; margin-bottom: 5px; font-weight: bold;">Total</div>
                                            <div style="font-size: 18px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(summary.total_owed || 0).toFixed(2)}</div>
                                        </div>
                                        <div style="border: 2px solid #000; padding: 12px; text-align: center; background-color: #f9f9f9;">
                                            <div style="font-size: 11px; color: #000 !important; margin-bottom: 5px; font-weight: bold;">Paid</div>
                                            <div style="font-size: 18px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(summary.total_paid || 0).toFixed(2)}</div>
                                        </div>
                                        <div style="border: 2px solid #000; padding: 12px; text-align: center; background-color: #f9f9f9;">
                                            <div style="font-size: 11px; color: #000 !important; margin-bottom: 5px; font-weight: bold;">Remaining</div>
                                            <div style="font-size: 18px; font-weight: bold; color: #000 !important;">PKR ${parseFloat(summary.remaining || 0).toFixed(2)}</div>
                                        </div>
                                    </div>
                                    <div style="border-top: 2px solid #000; padding-top: 10px; margin-top: 10px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: bold; font-size: 12px;">Total Bills:</span>
                                            <span style="font-weight: bold; font-size: 14px;">${summary.total_bills || 0}</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                                            <span style="font-weight: bold; font-size: 12px;">Total Transactions:</span>
                                            <span style="font-weight: bold; font-size: 14px;">${summary.total_transactions || 0}</span>
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top: 15px;">
                                    <div style="font-weight: bold; font-size: 13px; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px;">
                                        SUPPLIER TRANSACTIONS (Within Selected Date Range)
                                    </div>`;

                        if (transactions.length === 0) {
                            printContent += `
                                    <p style="font-size: 11px; font-style: italic;">No transactions found in this date range.</p>
                                </div>
                            </div>
                            `;
                        } else {
                            printContent += `
                                    <table style="width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 5px;">
                                        <thead>
                                            <tr>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: left;">Date</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: center;">Type</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: right;">Amount</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: left;">Description</th>
                                                <th style="border: 1px solid #000; padding: 4px; text-align: left;">Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

                            transactions.forEach(tx => {
                                printContent += `
                                            <tr>
                                                <td style="border: 1px solid #ccc; padding: 3px;">${tx.transaction_date || ''}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px; text-align: center;">${(tx.type || '').toUpperCase()}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px; text-align: right;">PKR ${parseFloat(tx.amount || 0).toFixed(2)}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px;">${tx.description || ''}</td>
                                                <td style="border: 1px solid #ccc; padding: 3px;">${tx.reference_number || ''}</td>
                                            </tr>`;
                            });

                            printContent += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            `;
                        }
                    });
                    
                    printContent += `
                            <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #000; font-size: 10px; color: #000 !important;">
                                <p>This is a computer-generated comprehensive report.</p>
                                <p>End of Report</p>
                            </div>
                        </body>
                        </html>
                    `;
                    
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    printWindow.document.write(`
                        <html>
                            <head><title>Error</title></head>
                            <body>
                                <h1 style="color: red;">Error Loading Report</h1>
                                <p>${data.message || 'Failed to load report'}</p>
                            </body>
                        </html>
                    `);
                    printWindow.document.close();
                    alert('Error: ' + (data.message || 'Failed to load report'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMessage = error.message || 'Unknown error occurred';
                if (printWindow && !printWindow.closed) {
                    printWindow.document.write(`
                        <html>
                            <head><title>Error</title></head>
                            <body>
                                <h1 style="color: red;">Error Loading Report</h1>
                                <p>${errorMessage}</p>
                                <p>Please check the browser console for more details.</p>
                            </body>
                        </html>
                    `);
                    printWindow.document.close();
                }
                alert('Error loading report: ' + errorMessage);
            });
        }

        async function printSupplierReport(supplierId) {
            try {
                if (window.FTReceipt?.requireConfigured) {
                    await window.FTReceipt.requireConfigured();
                }
            } catch (e) {
                return;
            }

            const printWindow = window.open('', '_blank');
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                             document.querySelector('input[name="_token"]')?.value || '';
            
            fetch(`/suppliers/${supplierId}/print-report`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                }
            })
            .then(async response => {
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(text || 'Failed to load report');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const supplierData = data.supplier;
                    const supplier = supplierData.supplier;
                    const summary = supplierData.summary;
                    const bills = supplierData.bills || [];
                    const transactions = supplierData.transactions || [];
                    
                    let printContent = `
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Complete Supplier Report - ${supplier.name}</title>
                            <style>
                                @media print {
                                    @page { margin: 10mm; }
                                    .page-break { page-break-after: always; }
                                    .bill-section { page-break-inside: avoid; }
                                }
                                * {
                                    color: #000 !important;
                                }
                                body { 
                                    font-family: 'Arial', sans-serif; 
                                    padding: 20px; 
                                    font-size: 11px;
                                    color: #000 !important;
                                }
                                .header { 
                                    text-align: center; 
                                    margin-bottom: 25px; 
                                    border-bottom: 3px solid #000; 
                                    padding-bottom: 15px; 
                                }
                                .header h1 { 
                                    margin: 0; 
                                    font-size: 24px; 
                                    font-weight: bold;
                                }
                                .header p {
                                    margin: 5px 0 0 0;
                                    font-size: 11px;
                                }
                                .business-info {
                                    padding-top: 10px;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                                .business-service {
                                    font-weight: bold;
                                    margin-bottom: 6px;
                                    font-size: 10px;
                                    color: #000 !important;
                                }
                                .business-service i {
                                    font-style: italic;
                                }
                                .business-contact {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: flex-start;
                                    margin-top: 6px;
                                    font-size: 7px;
                                    color: #000 !important;
                                }
                                .business-contact-left {
                                    text-align: left;
                                }
                                .business-contact-right {
                                    text-align: right;
                                }
                                .supplier-header {
                                    background-color: #f0f0f0;
                                    padding: 15px;
                                    margin-bottom: 20px;
                                    border: 1px solid #000;
                                }
                                .supplier-name {
                                    font-weight: bold;
                                    font-size: 18px;
                                    margin-bottom: 10px;
                                    color: #000 !important;
                                    text-transform: uppercase;
                                }
                                .supplier-info {
                                    font-size: 11px;
                                    line-height: 1.6;
                                }
                                .supplier-summary {
                                    background-color: #f9f9f9;
                                    padding: 15px;
                                    margin-bottom: 20px;
                                    border: 1px solid #000;
                                }
                                .summary-grid {
                                    display: grid;
                                    grid-template-columns: repeat(2, 1fr);
                                    gap: 10px;
                                    font-size: 11px;
                                }
                                .summary-item {
                                    display: flex;
                                    justify-content: space-between;
                                    padding: 5px 0;
                                    border-bottom: 1px dotted #ccc;
                                }
                                .summary-total {
                                    font-weight: bold;
                                    font-size: 14px;
                                    margin-top: 10px;
                                    padding-top: 10px;
                                    border-top: 2px solid #000;
                                }
                                .bill-section {
                                    margin-bottom: 25px;
                                    border: 1px solid #ddd;
                                    padding: 15px;
                                    page-break-inside: avoid;
                                }
                                .bill-header {
                                    background-color: #e8e8e8;
                                    padding: 10px;
                                    margin-bottom: 10px;
                                    border-bottom: 2px solid #000;
                                    font-weight: bold;
                                }
                                .bill-info { 
                                    margin-bottom: 15px; 
                                    font-size: 10px;
                                }
                                .bill-info div { 
                                    display: flex; 
                                    justify-content: space-between; 
                                    margin-bottom: 3px;
                                }
                                .amount-section {
                                    margin-top: 15px;
                                    padding-top: 10px;
                                    border-top: 2px solid #000;
                                }
                                .amount-row {
                                    display: flex;
                                    justify-content: space-between;
                                    margin-bottom: 5px;
                                    font-size: 11px;
                                }
                                .total-row {
                                    display: flex;
                                    justify-content: space-between;
                                    font-weight: bold;
                                    font-size: 13px;
                                    margin-top: 10px;
                                    padding-top: 10px;
                                    border-top: 1px solid #ddd;
                                }
                                .payment-history {
                                    margin-top: 15px;
                                    padding-top: 10px;
                                    border-top: 1px solid #ddd;
                                }
                                .payment-history-title {
                                    font-weight: bold;
                                    font-size: 11px;
                                    margin-bottom: 8px;
                                }
                                .payment-item {
                                    display: flex;
                                    justify-content: space-between;
                                    margin-bottom: 5px;
                                    font-size: 10px;
                                    padding: 3px 0;
                                    border-bottom: 1px dotted #ddd;
                                }
                                .products-section {
                                    margin-top: 15px;
                                    padding-top: 10px;
                                    border-top: 1px solid #000;
                                }
                                .products-title {
                                    font-weight: bold;
                                    font-size: 12px;
                                    margin-bottom: 8px;
                                    text-align: center;
                                    color: #000 !important;
                                }
                                .products-table {
                                    width: 100%;
                                    font-size: 9px;
                                    border-collapse: collapse;
                                    margin-bottom: 10px;
                                }
                                .products-table th {
                                    border-bottom: 1px solid #000;
                                    padding: 5px 3px;
                                    text-align: left;
                                    font-weight: bold;
                                    background-color: #f0f0f0;
                                    color: #000 !important;
                                }
                                .products-table td {
                                    padding: 4px 3px;
                                    border-bottom: 1px dotted #ddd;
                                    color: #000 !important;
                                }
                                .products-table .text-right {
                                    text-align: right;
                                }
                                .product-name {
                                    font-weight: bold;
                                    color: #000 !important;
                                }
                                .product-sku {
                                    font-size: 8px;
                                    color: #666 !important;
                                }
                                .transactions-section {
                                    margin-top: 20px;
                                    padding-top: 15px;
                                    border-top: 2px solid #000;
                                }
                                .transactions-title {
                                    font-weight: bold;
                                    font-size: 14px;
                                    margin-bottom: 10px;
                                    text-align: center;
                                }
                                .transactions-table {
                                    width: 100%;
                                    font-size: 9px;
                                    border-collapse: collapse;
                                }
                                .transactions-table th {
                                    border: 1px solid #000;
                                    padding: 6px 4px;
                                    text-align: left;
                                    font-weight: bold;
                                    background-color: #f0f0f0;
                                    color: #000 !important;
                                }
                                .transactions-table td {
                                    border: 1px solid #ddd;
                                    padding: 5px 4px;
                                    color: #000 !important;
                                }
                                .transaction-credit {
                                    color: #d32f2f !important;
                                    font-weight: bold;
                                }
                                .transaction-debit {
                                    color: #388e3c !important;
                                    font-weight: bold;
                                }
                            </style>
                        </head>
                        <body>
                            ${(window.FTReceipt && window.FTReceipt.headerHtml) ? window.FTReceipt.headerHtml('Complete Supplier Report') : ''}
                            <p style="text-align:center;font-size:10px;margin:4px 0 12px;">Generated on: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                            
                            <div class="supplier-header">
                                <div class="supplier-name">${supplier.name}${supplier.company_name ? ' - ' + supplier.company_name : ''}</div>
                                <div class="supplier-info">
                                    ${supplier.supplier_id ? `<div><strong>Supplier ID:</strong> ${supplier.supplier_id}</div>` : ''}
                                    ${supplier.email ? `<div><strong>Email:</strong> ${supplier.email}</div>` : ''}
                                    ${supplier.phone ? `<div><strong>Phone:</strong> ${supplier.phone}</div>` : ''}
                                    ${supplier.address ? `<div><strong>Address:</strong> ${supplier.address}${supplier.city ? ', ' + supplier.city : ''}${supplier.state ? ', ' + supplier.state : ''}${supplier.country ? ', ' + supplier.country : ''}</div>` : ''}
                                </div>
                            </div>
                            
                            <div class="supplier-summary">
                                <div style="font-weight: bold; font-size: 14px; margin-bottom: 10px; text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px;">
                                    ACCOUNT SUMMARY
                                </div>
                                <div class="summary-grid">
                                    <div class="summary-item">
                                        <span>Total Bills:</span>
                                        <span><strong>${bills.length}</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Total Transactions:</span>
                                        <span><strong>${transactions.length}</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Total Amount Owed:</span>
                                        <span><strong>PKR ${parseFloat(summary.total_owed || 0).toFixed(2)}</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Total Amount Paid:</span>
                                        <span><strong>PKR ${parseFloat(summary.total_paid || 0).toFixed(2)}</strong></span>
                                    </div>
                                </div>
                                <div class="summary-total">
                                    <div class="summary-item">
                                        <span>Remaining Balance:</span>
                                        <span style="color: ${(summary.remaining || 0) > 0 ? '#d32f2f' : '#388e3c'} !important; font-size: 16px;">
                                            PKR ${parseFloat(summary.remaining || 0).toFixed(2)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                    `;
                    
                    // Add all bills
                    if (bills.length > 0) {
                        printContent += `
                            <div style="font-weight: bold; font-size: 16px; margin: 20px 0 15px 0; text-align: center; border-bottom: 2px solid #000; padding-bottom: 5px;">
                                ALL BILLS (${bills.length})
                            </div>
                        `;
                        
                        bills.forEach((bill, billIndex) => {
                            const billItems = bill.bill_items || [];
                            const paymentHistory = bill.payment_history || [];
                            
                            printContent += `
                                <div class="bill-section">
                                    <div class="bill-header">
                                        Bill #${bill.bill_number || bill.id} - ${new Date(bill.bill_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                    </div>
                                    <div class="bill-info">
                                        ${bill.reference_number ? `
                                        <div>
                                            <span>Reference Number:</span>
                                            <span>${bill.reference_number}</span>
                                        </div>
                                        ` : ''}
                                        ${bill.description ? `
                                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                                            <div style="font-weight: bold; margin-bottom: 5px; color: #000;">Description:</div>
                                            <div style="color: #000;">${bill.description}</div>
                                        </div>
                                        ` : ''}
                                    </div>
                                    ${billItems.length > 0 ? `
                                    <div class="products-section">
                                        <div class="products-title">Products in this Bill</div>
                                        <table class="products-table">
                                            <thead>
                                                <tr>
                                                    <th>Product Name</th>
                                                    <th>SKU</th>
                                                    <th class="text-right">Quantity</th>
                                                    <th class="text-right">Unit Price</th>
                                                    <th class="text-right">Discount</th>
                                                    <th class="text-right">Tax</th>
                                                    <th class="text-right">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${billItems.map(item => `
                                                    <tr>
                                                        <td><div class="product-name">${item.product_name}</div></td>
                                                        <td><div class="product-sku">${item.product_sku || 'N/A'}</div></td>
                                                        <td class="text-right">${parseFloat(item.quantity).toFixed(2)}</td>
                                                        <td class="text-right">PKR ${parseFloat(item.unit_price).toFixed(2)}</td>
                                                        <td class="text-right">${parseFloat(item.discount || 0).toFixed(2)}%</td>
                                                        <td class="text-right">PKR ${parseFloat(item.tax || 0).toFixed(2)}</td>
                                                        <td class="text-right"><strong>PKR ${parseFloat(item.total).toFixed(2)}</strong></td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                    ` : '<div style="padding: 10px; text-align: center; color: #666;">No products listed</div>'}
                                    <div class="amount-section">
                                        <div class="amount-row">
                                            <span>Bill Amount:</span>
                                            <span style="color: #000 !important; font-weight: bold;">PKR ${parseFloat(bill.bill_amount).toFixed(2)}</span>
                                        </div>
                                        <div class="amount-row">
                                            <span>Paid Amount:</span>
                                            <span style="color: #388e3c !important;">PKR ${parseFloat(bill.paid_amount || 0).toFixed(2)}</span>
                                        </div>
                                        <div class="amount-row">
                                            <span>Remaining:</span>
                                            <span style="color: ${(bill.remaining || 0) > 0 ? '#d32f2f' : '#388e3c'} !important; font-weight: bold;">
                                                PKR ${parseFloat(bill.remaining || 0).toFixed(2)}
                                            </span>
                                        </div>
                                        <div class="total-row">
                                            <span>Status:</span>
                                            <span style="color: #000 !important;">
                                                ${(bill.remaining || 0) > 0 ? '<span style="color: #d32f2f !important;">UNPAID</span>' : '<span style="color: #388e3c !important;">PAID</span>'}
                                            </span>
                                        </div>
                                    </div>
                                    ${paymentHistory.length > 0 ? `
                                    <div class="payment-history">
                                        <div class="payment-history-title">Payment History for this Bill</div>
                                        ${paymentHistory.map(payment => `
                                            <div class="payment-item">
                                                <div>
                                                    <span>${new Date(payment.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                                                    ${payment.reference_number ? `<span style="margin-left: 10px; font-size: 9px;">Ref: ${payment.reference_number}</span>` : ''}
                                                    ${payment.description ? `<div style="font-size: 9px; color: #666;">${payment.description}</div>` : ''}
                                                </div>
                                                <span style="font-weight: bold; color: #388e3c !important;">PKR ${parseFloat(payment.amount).toFixed(2)}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                    ` : ''}
                                </div>
                            `;
                        });
                    }
                    
                    // Add complete transaction history
                    if (transactions.length > 0) {
                        printContent += `
                            <div class="transactions-section">
                                <div class="transactions-title">COMPLETE TRANSACTION HISTORY</div>
                                <table class="transactions-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Description</th>
                                            <th>Reference</th>
                                            <th>Bill #</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${transactions.map(trans => `
                                            <tr>
                                                <td>${new Date(trans.transaction_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                                                <td class="${trans.type === 'credit' ? 'transaction-credit' : 'transaction-debit'}">
                                                    ${trans.type === 'credit' ? 'CREDIT (Owed)' : 'DEBIT (Paid)'}
                                                </td>
                                                <td class="${trans.type === 'credit' ? 'transaction-credit' : 'transaction-debit'}">
                                                    PKR ${parseFloat(trans.amount).toFixed(2)}
                                                </td>
                                                <td>${trans.description || 'N/A'}</td>
                                                <td>${trans.reference_number || 'N/A'}</td>
                                                <td>${trans.supplier_bill_id ? '#' + trans.supplier_bill_id : 'N/A'}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }
                    
                    printContent += `
                            <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #000; font-size: 10px; color: #000 !important;">
                                <p>This is a computer-generated comprehensive report.</p>
                                <p>End of Report</p>
                            </div>
                        </body>
                        </html>
                    `;
                    
                    printWindow.document.write(printContent);
                    printWindow.document.close();
                    printWindow.print();
                } else {
                    alert('Error: ' + (data.message || 'Failed to load report'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading report. Please try again.');
            });
        }
    </script>
</x-app-layout>

