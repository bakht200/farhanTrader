import { db } from './db';
import { isOnline, checkNow } from './connectivity';
import {
    supplierWallet,
    queueOfflineSupplierBill,
    queueOfflineSupplierPayment,
} from './outbox';
import { showToast } from './banner';

function money1(n) {
    const v = Number(n) || 0;
    return 'PKR ' + v.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
}

function money2(n) {
    const v = Number(n) || 0;
    return 'PKR ' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtDateLong(value) {
    if (!value) {
        return '—';
    }
    const raw = String(value).slice(0, 10);
    const d = new Date(`${raw}T00:00:00`);
    if (Number.isNaN(d.getTime())) {
        return raw;
    }
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtDateSlash(value) {
    if (!value) {
        return '—';
    }
    const [y, m, day] = String(value).slice(0, 10).split('-');
    if (!y || !m || !day) {
        return String(value).slice(0, 10);
    }
    return `${day}/${m}/${y}`;
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function displaySupplierCode(supplier) {
    if (supplier.supplier_id) {
        return String(supplier.supplier_id);
    }
    const n = String(supplier.id ?? '');
    if (/^\d+$/.test(n)) {
        return `SN-${n.padStart(3, '0')}`;
    }
    return n || 'N/A';
}

function parseSupplierPath(href = window.location.href) {
    try {
        const path = new URL(href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
        const parts = path.split('/').filter(Boolean);
        if (parts[0] !== 'suppliers' || !parts[1]) {
            return { kind: null, id: null };
        }
        const id = decodeURIComponent(parts[1]);
        if (id === 'create' || id === 'print-all-report' || id === 'anonymous-purchase') {
            return { kind: null, id: null };
        }
        if (parts[2] === 'transactions' && parts[3] === 'create') {
            return { kind: 'create', id };
        }
        if (parts[2]) {
            return { kind: 'nested', id };
        }
        return { kind: 'show', id };
    } catch {
        return { kind: null, id: null };
    }
}

async function shouldUseLocalSuppliers() {
    if (typeof navigator !== 'undefined' && navigator.onLine === true && isOnline()) {
        return false;
    }
    if (typeof navigator !== 'undefined' && navigator.onLine === true) {
        const ok = await checkNow().catch(() => false);
        if (ok) {
            return false;
        }
    }
    return true;
}

function pageShowsSupplier(kind, supplierId) {
    const el = document.querySelector(`[data-ftpos-page="${kind}"][data-ftpos-supplier-id]`);
    return !!(el && String(el.getAttribute('data-ftpos-supplier-id')) === String(supplierId));
}

async function loadDetail(supplierId) {
    let supplier = await db.suppliers.get(Number(supplierId)).catch(() => null);
    if (!supplier) {
        supplier = await db.suppliers.get(supplierId);
    }
    if (!supplier) {
        supplier = (await db.suppliers.toArray()).find((s) => String(s.id) === String(supplierId));
    }
    const bills = (await db.supplierBills.toArray()).filter((b) => String(b.supplier_id) === String(supplierId));
    const txs = (await db.supplierTransactions.toArray()).filter((t) => String(t.supplier_id) === String(supplierId));
    const items = await db.supplierBillItems.toArray();
    const wallet = await supplierWallet(supplierId);
    const products = (await db.products.toArray()).filter((p) => (
        String(p.supplier_id || '') === String(supplierId)
        || (supplier?.name && String(p.supplier_name || '') === String(supplier.name))
    ));
    const billsWithPay = bills.map((bill) => {
        const paid = txs
            .filter((t) => t.type === 'debit' && String(t.supplier_bill_id) === String(bill.id))
            .reduce((s, t) => s + (Number(t.amount) || 0), 0);
        return {
            ...bill,
            paid_amount: paid,
            remaining: (Number(bill.bill_amount) || 0) - paid,
            items: items.filter((i) => String(i.supplier_bill_id) === String(bill.id)),
        };
    });
    billsWithPay.sort((a, b) => {
        const dateCmp = String(a.bill_date || '').localeCompare(String(b.bill_date || ''));
        if (dateCmp !== 0) {
            return dateCmp;
        }
        return String(a.id).localeCompare(String(b.id), undefined, { numeric: true });
    });
    const txsDesc = [...txs].sort((a, b) => {
        const dateCmp = String(b.transaction_date || '').localeCompare(String(a.transaction_date || ''));
        if (dateCmp !== 0) {
            return dateCmp;
        }
        return String(b.id || '').localeCompare(String(a.id || ''), undefined, { numeric: true });
    });
    const txsAsc = [...txs].sort((a, b) => {
        const dateCmp = String(a.transaction_date || '').localeCompare(String(b.transaction_date || ''));
        if (dateCmp !== 0) {
            return dateCmp;
        }
        return String(a.id || '').localeCompare(String(b.id || ''), undefined, { numeric: true });
    });
    return { supplier, bills: billsWithPay, txs: txsDesc, txsAsc, wallet, products };
}

function buildLedger(txsAsc, currentBalance) {
    const rows = txsAsc.map((tx) => {
        const isCredit = tx.type === 'credit';
        return {
            date: tx.transaction_date,
            type: isCredit ? 'Credit' : 'Payment',
            ref: tx.reference_number || (tx.supplier_bill_id ? `#${tx.supplier_bill_id}` : '-'),
            narration: tx.description || (isCredit ? 'Credit' : 'Payment'),
            debit: isCredit ? null : Number(tx.amount) || 0,
            credit: isCredit ? Number(tx.amount) || 0 : null,
        };
    });
    let totalCredit = rows.reduce((s, r) => s + (r.credit || 0), 0);
    let totalDebit = rows.reduce((s, r) => s + (r.debit || 0), 0);
    const opening = Math.round(((Number(currentBalance) || 0) - (totalCredit - totalDebit)) * 100) / 100;
    if (Math.abs(opening) >= 0.01) {
        rows.unshift({
            date: null,
            type: 'Opening',
            ref: '-',
            narration: 'Opening balance',
            debit: opening < 0 ? Math.abs(opening) : null,
            credit: opening > 0 ? opening : null,
        });
        if (opening > 0) {
            totalCredit += opening;
        } else {
            totalDebit += Math.abs(opening);
        }
    }
    let running = 0;
    const withBalance = rows.map((row) => {
        running += (row.credit || 0) - (row.debit || 0);
        return { ...row, balance: Math.round(running * 100) / 100 };
    });
    return {
        rows: withBalance,
        total_debit: Math.round(totalDebit * 100) / 100,
        total_credit: Math.round(totalCredit * 100) / 100,
        final_balance: Math.round(running * 100) / 100,
    };
}

function setPageHeader(text) {
    const h2 = document.querySelector('nav h2');
    if (h2) {
        h2.textContent = text;
    }
    const name = document.title.includes(' - ')
        ? document.title.slice(document.title.indexOf(' - ') + 3)
        : document.title;
    document.title = `${text} - ${name}`;
}

function unpaidBadge(remaining) {
    if (remaining > 0) {
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
            <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
            Unpaid
        </span>`;
    }
    return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
        Paid
    </span>`;
}

function renderShowHtml(data) {
    const { supplier, bills, txs, wallet, products } = data;
    const ledger = buildLedger(data.txsAsc, wallet.remaining);
    const supplierId = encodeURIComponent(supplier.id);
    const remainingClass = wallet.remaining > 0 ? 'text-red-600' : 'text-green-600';
    let runningBalance = wallet.remaining;

    const info = (label, value) => `
        <div>
            <dt class="text-sm font-medium text-gray-500">${label}</dt>
            <dd class="mt-1 text-sm text-gray-900">${escapeHtml(value ?? 'N/A')}</dd>
        </div>`;

    const billsSection = bills.length ? `
        <div class="mt-6">
            <button type="button" data-ftpos-collapse-btn="bills" class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-left shadow-sm hover:bg-gray-50">
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-semibold text-gray-900">Bills</h3>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">${bills.length}</span>
                </div>
                <svg data-ftpos-collapse-icon="bills" class="h-5 w-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div data-ftpos-collapse-panel="bills" class="hidden mt-3 overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="divide-x divide-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remaining</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${bills.map((bill) => `
                        <tr class="hover:bg-gray-50 divide-x divide-gray-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#${escapeHtml(bill.bill_number || bill.id)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${fmtDateLong(bill.bill_date)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">${money1(bill.bill_amount)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">${money1(bill.paid_amount)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold ${bill.remaining > 0 ? 'text-red-600' : 'text-green-600'}">${money1(bill.remaining)}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${bill.remaining > 0
                                ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Unpaid</span>'
                                : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Paid</span>'}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${bill.bill_image
                                ? `<button type="button" data-bill-image="/storage/${escapeHtml(bill.bill_image)}" class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    View Image
                                </button>`
                                : '<span class="text-gray-400 text-sm">No image</span>'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="/suppliers/${supplierId}/bills/${encodeURIComponent(bill.id)}/edit" class="text-green-600 hover:text-green-900" title="Edit Bill">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button type="button" data-print-bill="${escapeHtml(bill.id)}" class="text-blue-600 hover:text-blue-900 inline-flex items-center" title="Print Receipt">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>` : '';

    const txRows = txs.map((transaction) => {
        const shown = runningBalance;
        if (transaction.type === 'credit') {
            runningBalance -= Number(transaction.amount) || 0;
        } else {
            runningBalance += Number(transaction.amount) || 0;
        }
        return `
            <tr class="hover:bg-gray-50 divide-x divide-gray-200">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${fmtDateLong(transaction.transaction_date)}</td>
                <td class="px-6 py-4 whitespace-nowrap">${transaction.type === 'credit'
                    ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Credit (Owed)</span>'
                    : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Debit (Paid)</span>'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold ${transaction.type === 'credit' ? 'text-red-600' : 'text-green-600'}">${money1(transaction.amount)}</td>
                <td class="px-6 py-4 text-sm text-gray-900">${escapeHtml(transaction.description || 'N/A')}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(transaction.reference_number || 'N/A')}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold ${shown > 0 ? 'text-red-600' : 'text-green-600'}">${money1(shown)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="/suppliers/${supplierId}/transactions/${encodeURIComponent(transaction.id)}/edit" class="text-blue-600 hover:text-blue-900" title="Edit">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                </td>
            </tr>`;
    }).join('');

    const ledgerRows = ledger.rows.map((row) => `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 border border-gray-300">${fmtDateSlash(row.date)}</td>
            <td class="px-4 py-3 whitespace-nowrap border border-gray-300">${row.type === 'Credit'
                ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Credit</span>'
                : row.type === 'Payment'
                    ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Payment</span>'
                    : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">${escapeHtml(row.type)}</span>`}</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 border border-gray-300">${escapeHtml(row.ref)}</td>
            <td class="px-4 py-3 text-sm text-gray-900 max-w-md border border-gray-300">${escapeHtml(row.narration)}</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-green-600 border border-gray-300">${row.debit !== null ? money2(row.debit) : ''}</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 border border-gray-300">${row.credit !== null ? money2(row.credit) : ''}</td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold border border-gray-300 ${row.balance > 0 ? 'text-red-600' : 'text-green-600'}">${money2(row.balance)}</td>
        </tr>`).join('');

    const productsSection = products.length ? `
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Products Supplied</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchase Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${products.map((product) => `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(product.name)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(product.sku || 'N/A')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${money1(product.purchase_price)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${(Number(product.stock_quantity) || 0).toLocaleString('en-US')}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${money1((Number(product.purchase_price) || 0) * (Number(product.stock_quantity) || 0))}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>` : '';

    return `
      <style>[x-cloak] { display: none !important; }</style>
      <div data-ftpos-page="supplier-show" data-ftpos-supplier-id="${escapeHtml(supplier.id)}">
        <div class="mb-4">
          <nav class="text-sm text-gray-600">
            <a href="/dashboard" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">&gt;</span>
            <a href="/suppliers" class="hover:text-gray-900">Suppliers</a>
            <span class="mx-2">&gt;</span>
            <span class="text-gray-900 font-medium">${escapeHtml(supplier.name || 'Supplier')}</span>
          </nav>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">${escapeHtml(supplier.name || 'Supplier')}</h2>
              <p class="text-sm text-gray-500">Supplier ID: ${escapeHtml(displaySupplierCode(supplier))}</p>
              ${supplier.is_anonymous ? '<p class="mt-2 text-sm text-slate-600">Cash purchases from people who are not a saved supplier.</p>' : ''}
            </div>
            <div class="flex space-x-2">
              ${supplier.is_anonymous ? '' : `<a href="/suppliers/${supplierId}/edit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Edit Supplier</a>`}
              <a href="/suppliers" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Back to List</a>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-semibold mb-4">Supplier Information</h3>
              <dl class="space-y-3">
                ${info('Name', supplier.name)}
                ${info('Company Name', supplier.company_name)}
                ${info('Email', supplier.email)}
                ${info('Phone', supplier.phone)}
                ${info('Address', supplier.address)}
                ${info('City', supplier.city)}
                ${info('State', supplier.state)}
                ${info('Country', supplier.country)}
              </dl>
            </div>
            <div>
              <h3 class="text-lg font-semibold mb-4">Supplier Wallet</h3>
              <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 space-y-4">
                <div class="border-b border-orange-200 pb-3">
                  <p class="text-sm text-gray-600 mb-1">Total Paid</p>
                  <p class="text-2xl font-bold text-green-600">${money1(wallet.total_paid)}</p>
                </div>
                <div class="border-b border-orange-200 pb-3">
                  <p class="text-sm text-gray-600 mb-1">Total</p>
                  <p class="text-2xl font-bold text-gray-700">${money1(wallet.credit)}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600 mb-1">Remaining</p>
                  <p class="text-2xl font-bold ${remainingClass}">${money1(wallet.remaining)}</p>
                </div>
                <div class="pt-2">${unpaidBadge(wallet.remaining)}</div>
              </div>
            </div>
          </div>
          ${billsSection}
          <div class="mt-6">
            <div class="flex items-center gap-3">
              <button type="button" data-ftpos-collapse-btn="tx" class="flex-1 flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-left shadow-sm hover:bg-gray-50">
                <div class="flex items-center gap-2">
                  <h3 class="text-lg font-semibold text-gray-900">Transactions</h3>
                  <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">${txs.length}</span>
                </div>
                <svg data-ftpos-collapse-icon="tx" class="h-5 w-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <a href="/suppliers/${supplierId}/transactions/create" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center whitespace-nowrap">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Transaction
              </a>
            </div>
            <div data-ftpos-collapse-panel="tx" class="hidden mt-3">
              ${txs.length ? `<div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr class="divide-x divide-gray-200">
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase font-bold text-gray-900">Remaining</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">${txRows}</tbody>
                </table>
              </div>` : `<div class="text-center py-8 text-gray-500 border border-gray-200 rounded-lg">
                No transactions found. Add a transaction to get started.
              </div>`}
            </div>
          </div>
          <div class="mt-6">
            <h3 class="text-lg font-semibold mb-4">Ledger</h3>
            ${ledger.rows.length ? `<div class="overflow-x-auto border border-gray-300 rounded-lg">
              <table class="min-w-full border-collapse">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Ref #</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase border border-gray-300">Narration</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Debit</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase whitespace-nowrap border border-gray-300">Credit</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-900 uppercase whitespace-nowrap border border-gray-300">Balance</th>
                  </tr>
                </thead>
                <tbody class="bg-white">${ledgerRows}</tbody>
                <tfoot class="bg-gray-50">
                  <tr>
                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900 text-right border border-gray-300">Total</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-green-600 border border-gray-300">${money2(ledger.total_debit)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold text-gray-900 border border-gray-300">${money2(ledger.total_credit)}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-bold border border-gray-300 ${ledger.final_balance > 0 ? 'text-red-600' : 'text-green-600'}">${money2(ledger.final_balance)}</td>
                  </tr>
                </tfoot>
              </table>
            </div>` : `<div class="text-center py-8 text-gray-500 border border-gray-200 rounded-lg">
              No ledger entries found.
            </div>`}
          </div>
          ${productsSection}
        </div>
      </div>`;
}

function bindShowInteractions(root, supplierId) {
    root.querySelectorAll('[data-ftpos-collapse-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.getAttribute('data-ftpos-collapse-btn');
            const panel = root.querySelector(`[data-ftpos-collapse-panel="${key}"]`);
            const icon = root.querySelector(`[data-ftpos-collapse-icon="${key}"]`);
            if (!panel) {
                return;
            }
            panel.classList.toggle('hidden');
            icon?.classList.toggle('rotate-180');
        });
    });

    root.querySelectorAll('[data-bill-image]').forEach((btn) => {
        btn.addEventListener('click', () => viewBillImage(btn.getAttribute('data-bill-image')));
    });

    root.querySelectorAll('[data-print-bill]').forEach((btn) => {
        btn.addEventListener('click', () => printSupplierBillReceipt(supplierId, btn.getAttribute('data-print-bill')));
    });
}

function viewBillImage(imageUrl) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50';
    modal.onclick = function (e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
    modal.innerHTML = `
        <div class="relative max-w-4xl max-h-full p-4">
            <button type="button" class="absolute top-2 right-2 text-white bg-red-600 hover:bg-red-700 rounded-full p-2 z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <img src="${escapeHtml(imageUrl)}" alt="Bill Image" class="max-w-full max-h-screen rounded-lg shadow-lg">
        </div>`;
    modal.querySelector('button')?.addEventListener('click', () => modal.remove());
    document.body.appendChild(modal);
}

async function printSupplierBillReceipt(supplierId, billId) {
    try {
        if (window.FTReceipt?.requireConfigured) {
            await window.FTReceipt.requireConfigured();
        }
    } catch {
        return;
    }
    if (typeof window.printSupplierBillReceipt === 'function' && window.printSupplierBillReceipt !== printSupplierBillReceipt) {
        window.printSupplierBillReceipt(billId);
        return;
    }
    showToast('Receipt printing needs the live page. Connect and open this supplier once.');
}

function ensureMain() {
    return document.querySelector('main');
}

export async function hydrateSupplierShow(supplierId) {
    const data = await loadDetail(supplierId);
    if (!data.supplier) {
        showToast('Supplier not cached on this device. Connect once to sync.');
        return;
    }
    const main = ensureMain();
    if (!main) {
        return;
    }
    setPageHeader('Supplier Details');
    main.innerHTML = renderShowHtml(data);
    bindShowInteractions(main, data.supplier.id);
}

function bindOfflineCreateForm(supplierId) {
    const form = document.querySelector('form[action*="/transactions"]');
    if (!form || form.dataset.ftposOfflineBound === '1') {
        return;
    }
    form.dataset.ftposOfflineBound = '1';
    form.addEventListener('submit', async (e) => {
        if (!(await shouldUseLocalSuppliers())) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        const fd = new FormData(form);
        const type = String(fd.get('type') || '');
        try {
            if (type === 'debit') {
                await queueOfflineSupplierPayment({
                    supplier_id: supplierId,
                    amount: fd.get('amount'),
                    transaction_date: fd.get('transaction_date'),
                    description: fd.get('description'),
                    reference_number: fd.get('reference_number'),
                    supplier_bill_id: fd.get('supplier_bill_id') || null,
                });
            } else {
                await queueOfflineSupplierBill({
                    supplier_id: supplierId,
                    bill_number: fd.get('bill_number'),
                    bill_date: fd.get('bill_date') || fd.get('transaction_date'),
                    bill_amount: fd.get('amount') || fd.get('calculated_amount'),
                    paid_amount: fd.get('paid_amount'),
                    description: fd.get('description'),
                    reference_number: fd.get('reference_number'),
                });
            }
            showToast('Saved on this device. Use Upload to cloud when internet returns.');
            window.location.assign(`/suppliers/${encodeURIComponent(supplierId)}`);
        } catch (err) {
            showToast(err.message || 'Could not save on this device');
        }
    }, true);
}

export function mountOfflineSupplierPanel() {
    if (window.__ftposSupplierPanelBound) {
        return;
    }
    window.__ftposSupplierPanelBound = true;
    hydrateFromLocation();
}

function hydrateFromLocation() {
    const { kind, id } = parseSupplierPath(window.location.href);
    if (!id) {
        return;
    }
    if (kind === 'create') {
        if (pageShowsSupplier('supplier-transaction-create', id)) {
            bindOfflineCreateForm(id);
            return;
        }
        shouldUseLocalSuppliers().then((useLocal) => {
            if (useLocal) {
                bindOfflineCreateForm(id);
            }
        });
        return;
    }
    if (kind !== 'show') {
        return;
    }
    if (pageShowsSupplier('supplier-show', id)) {
        return;
    }
    shouldUseLocalSuppliers().then((useLocal) => {
        if (!useLocal) {
            return;
        }
        hydrateSupplierShow(id).catch((err) => {
            showToast(err.message || 'Could not open supplier');
        });
    });
}

export const openOfflineSupplier = hydrateSupplierShow;
