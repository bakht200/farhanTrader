import { db } from './db';
import { isOnline, checkNow } from './connectivity';
import { supplierWallet } from './outbox';

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function money(n) {
    const v = Number(n) || 0;
    return 'PKR ' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function paddedCode(prefix, id, explicit) {
    if (explicit) {
        return String(explicit);
    }
    const n = String(id ?? '');
    if (/^\d+$/.test(n)) {
        return `${prefix}-${n.padStart(3, '0')}`;
    }
    return n || '—';
}

function setPageHeader(text) {
    const h2 = document.querySelector('nav h2');
    if (h2) {
        h2.textContent = text;
    }
    document.querySelectorAll('#sidebar a, aside a, nav a').forEach((a) => {
        const href = a.getAttribute('href') || '';
        a.classList.remove('bg-orange-50', 'text-orange-700', 'font-medium');
        if (href === window.location.pathname || href.replace(/\/+$/, '') === window.location.pathname.replace(/\/+$/, '')) {
            a.classList.add('bg-orange-50', 'text-orange-700', 'font-medium');
        }
    });
}

function pageKind() {
    const path = (window.location.pathname || '').replace(/\/+$/, '') || '/';
    if (path === '/customers') {
        return 'customers';
    }
    if (path === '/suppliers') {
        return 'suppliers';
    }
    return null;
}

function pageAlreadyCorrect(kind) {
    return !!document.querySelector(`[data-ftpos-page="${kind}-index"]`);
}

async function shouldHydrate() {
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

function statusBadge(unpaid) {
    if (unpaid) {
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

function viewIcon() {
    return `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268 2.943-9.542-7z"></path>
    </svg>`;
}

async function renderCustomers(main) {
    const rows = (await db.customers.toArray()).sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    const sales = await db.sales.toArray().catch(() => []);
    const latestByCustomer = new Map();
    for (const sale of sales) {
        if (!sale.customer_id) {
            continue;
        }
        const key = String(sale.customer_id);
        const prev = latestByCustomer.get(key);
        if (!prev || String(sale.sale_date || sale.id) > String(prev.sale_date || prev.id)) {
            latestByCustomer.set(key, sale);
        }
    }

    let totalPrice = 0;
    let paid = 0;
    let remaining = 0;
    const cards = rows.map((c) => {
        const price = Number(c.total_price ?? c.total ?? 0) || 0;
        const unpaid = Number(c.unpaid_amount ?? c.remaining ?? 0) || 0;
        totalPrice += price;
        remaining += unpaid;
        paid += Math.max(0, price - unpaid);
        const latest = latestByCustomer.get(String(c.id));
        return { ...c, price, unpaid, latest };
    });

    setPageHeader('Customer');
    main.innerHTML = `
      <div data-ftpos-page="customers-index">
        <div class="mb-4">
          <nav class="text-sm text-gray-600">
            <a href="/dashboard" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">&gt;</span>
            <span class="text-gray-900 font-medium">Customer</span>
          </nav>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Total Price</p><p class="text-2xl font-bold text-gray-900 mt-2">${money(totalPrice)}</p></div>
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Paid</p><p class="text-2xl font-bold text-green-600 mt-2">${money(paid)}</p></div>
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Remaining</p><p class="text-2xl font-bold ${remaining > 0 ? 'text-red-600' : 'text-green-600'} mt-2">${money(remaining)}</p></div>
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Balance</p><p class="text-2xl font-bold ${remaining > 0 ? 'text-red-600' : 'text-green-600'} mt-2">${money(remaining)}</p></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
          <input type="search" data-list-filter placeholder="Q Search" class="w-full px-4 py-2 pl-4 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
        </div>
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="p-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold">All Customers</h3>
            <a href="/customers/create" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Add Customer</a>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer Id</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Number</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Price</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                ${cards.length ? cards.map((c) => `
                  <tr class="hover:bg-gray-50" data-row-name="${escapeHtml((c.name || '').toLowerCase())}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(paddedCode('CN', c.id, c.customer_id))}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(c.name)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">${escapeHtml(c.customer_type || '—')}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm ${c.latest ? 'text-gray-900' : 'text-gray-400'}">${escapeHtml(c.latest?.sale_number || 'No sales')}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <div class="font-medium text-gray-900">${money(c.price)}</div>
                      ${c.unpaid > 0 ? `<div class="text-xs text-red-600">Balance: ${money(c.unpaid)}</div>` : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">${statusBadge(c.unpaid > 0)}</td>
                    <td class="px-6 py-4 whitespace-nowrap"><a href="/customers/${encodeURIComponent(c.id)}" class="text-blue-600 hover:text-blue-900" title="View">${viewIcon()}</a></td>
                  </tr>`).join('') : `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No customers found.</td></tr>`}
              </tbody>
            </table>
          </div>
        </div>
      </div>`;
    bindFilter(main);
}

async function renderSuppliers(main) {
    const rows = (await db.suppliers.toArray()).sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    const withWallet = [];
    let totalPaid = 0;
    let totalRemaining = 0;
    for (const s of rows) {
        const w = await supplierWallet(s.id).catch(() => ({ total_paid: Number(s.total_paid) || 0, remaining: Number(s.remaining) || 0 }));
        const paid = Number(w.total_paid ?? s.total_paid) || 0;
        const remaining = Number(w.remaining ?? s.remaining) || 0;
        totalPaid += paid;
        totalRemaining += remaining;
        withWallet.push({ ...s, paid, remaining });
    }

    setPageHeader('Supplier');
    main.innerHTML = `
      <div data-ftpos-page="suppliers-index">
        <div class="mb-4">
          <nav class="text-sm text-gray-600">
            <a href="/dashboard" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">&gt;</span>
            <span class="text-gray-900 font-medium">Suppliers</span>
          </nav>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Total</p><p class="text-2xl font-bold text-gray-900 mt-2">${money(totalPaid + totalRemaining)}</p></div>
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Paid</p><p class="text-2xl font-bold text-green-600 mt-2">${money(totalPaid)}</p></div>
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Remaining</p><p class="text-2xl font-bold ${totalRemaining > 0 ? 'text-red-600' : 'text-green-600'} mt-2">${money(totalRemaining)}</p></div>
          <div class="bg-white rounded-lg shadow-sm p-6"><p class="text-sm font-medium text-gray-600">Total Suppliers</p><p class="text-2xl font-bold text-gray-900 mt-2">${withWallet.length}</p></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex justify-between items-center">
          <span class="text-gray-900 font-medium">Suppliers</span>
          <a href="/suppliers/create" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md">Add Supplier</a>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
          <input type="search" data-list-filter placeholder="Q Search" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
        </div>
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier Id</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Supplier Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Paid</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remaining</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                ${withWallet.length ? withWallet.map((s) => `
                  <tr class="hover:bg-gray-50" data-row-name="${escapeHtml((s.name || '').toLowerCase())}">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(paddedCode('SN', s.id, s.supplier_id))}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(s.name)}${s.is_anonymous ? ' <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide bg-slate-100 text-slate-700">Cash</span>' : ''}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">${money(s.paid)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium ${s.remaining > 0 ? 'text-red-600' : 'text-green-600'}">${money(s.remaining)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${statusBadge(s.remaining > 0)}</td>
                    <td class="px-6 py-4 whitespace-nowrap"><a href="/suppliers/${encodeURIComponent(s.id)}" class="text-blue-600 hover:text-blue-900" title="View">${viewIcon()}</a></td>
                  </tr>`).join('') : `<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No suppliers found.</td></tr>`}
              </tbody>
            </table>
          </div>
        </div>
      </div>`;
    bindFilter(main);
}

function bindFilter(root) {
    const input = root.querySelector('[data-list-filter]');
    if (!input) {
        return;
    }
    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        root.querySelectorAll('[data-row-name]').forEach((row) => {
            row.classList.toggle('hidden', q !== '' && !row.getAttribute('data-row-name').includes(q));
        });
    });
}

export async function mountOfflineListPages() {
    const kind = pageKind();
    if (!kind || pageAlreadyCorrect(kind)) {
        return;
    }
    if (!(await shouldHydrate())) {
        return;
    }
    const main = document.querySelector('main');
    if (!main) {
        return;
    }
    try {
        if (kind === 'customers') {
            await renderCustomers(main);
        } else {
            await renderSuppliers(main);
        }
    } catch (e) {
        console.warn('[offline] list page hydrate failed', e);
    }
}
