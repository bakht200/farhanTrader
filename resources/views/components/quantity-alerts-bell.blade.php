@props([
    'variant' => 'light', // light (dashboard) | dark (POS)
])

@php
    use App\Models\Product;
    use App\Support\CurrentBranch;
    use Illuminate\Support\Facades\DB;

    $branchId = CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID;
    $quantityAlerts = collect();

    if (auth()->check() && $branchId) {
        $quantityAlerts = Product::query()
            ->where('products.is_active', true)
            ->visibleToBranch($branchId)
            ->leftJoin('branch_product_stocks', function ($join) use ($branchId) {
                $join->on('branch_product_stocks.product_id', '=', 'products.id')
                    ->where('branch_product_stocks.branch_id', '=', $branchId);
            })
            ->leftJoin('units', function ($join) {
                $join->on('units.id', '=', DB::raw('COALESCE(products.base_unit_id, products.unit_id)'));
            })
            ->whereRaw('COALESCE(branch_product_stocks.stock_quantity, 0) <= 5')
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(branch_product_stocks.stock_quantity, 0) as alert_stock'),
                DB::raw("COALESCE(units.short_name, 'Pcs') as unit_name")
            )
            ->orderByRaw('COALESCE(branch_product_stocks.stock_quantity, 0) ASC')
            ->orderBy('products.name')
            ->limit(100)
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'remaining' => round((float) $item->alert_stock, 2),
                'unit' => $item->unit_name ?: 'Pcs',
                'level' => (float) $item->alert_stock <= 0 ? 'out' : 'low',
            ])
            ->values();
    }

    $isDark = $variant === 'dark';
    $btnClass = $isDark
        ? 'relative p-2 hover:bg-gray-700 rounded text-white'
        : 'relative inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500';
    $iconActiveClass = $isDark ? 'text-amber-300' : 'text-amber-500';
@endphp

<div class="relative" data-ft-qty-alerts-root>
    <button
        type="button"
        class="{{ $btnClass }}"
        title="Quantity alerts"
        data-ft-qty-alerts-btn
        aria-haspopup="true"
        aria-expanded="false"
    >
        <svg class="w-5 h-5" data-ft-qty-alerts-icon fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        <span
            data-ft-qty-alerts-badge
            class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold leading-[18px] text-center"
        >0</span>
    </button>

    <div
        data-ft-qty-alerts-panel
        class="hidden absolute right-0 mt-2 w-80 max-h-96 overflow-hidden bg-white text-gray-900 rounded-lg shadow-xl border border-gray-200 z-[10060]"
    >
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-2 bg-gray-50">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Quantity alerts</p>
                <p class="text-xs text-gray-500">Stock at 5 or below</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    data-ft-qty-alerts-mark-read
                    class="text-xs font-medium text-gray-600 hover:text-gray-900"
                >Mark all read</button>
                <a href="{{ route('products.low-stocks') }}" class="text-xs font-medium text-orange-600 hover:text-orange-700">View all</a>
            </div>
        </div>
        <div data-ft-qty-alerts-list class="max-h-72 overflow-y-auto divide-y divide-gray-100">
            <p class="px-4 py-6 text-sm text-gray-500 text-center">No quantity alerts</p>
        </div>
    </div>
</div>

<script>
(function () {
    const STOCK_LOW_THRESHOLD = 5;
    const ICON_ACTIVE = @json($iconActiveClass);
    let items = @json($quantityAlerts);

    function dayKey() {
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    function readStorageKey() {
        return `ftpos-qty-alerts-read:${dayKey()}`;
    }

    function getReadIds() {
        try {
            const raw = localStorage.getItem(readStorageKey());
            const parsed = raw ? JSON.parse(raw) : [];
            return new Set(Array.isArray(parsed) ? parsed.map(Number) : []);
        } catch (e) {
            return new Set();
        }
    }

    function setReadIds(ids) {
        try {
            localStorage.setItem(readStorageKey(), JSON.stringify([...ids]));
        } catch (e) {
            // ignore
        }
    }

    function unreadItems() {
        const read = getReadIds();
        return (items || []).filter((item) => !read.has(Number(item.id)));
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function roots() {
        return Array.from(document.querySelectorAll('[data-ft-qty-alerts-root]'));
    }

    function renderRoot(root) {
        const unread = unreadItems();
        const badge = root.querySelector('[data-ft-qty-alerts-badge]');
        const icon = root.querySelector('[data-ft-qty-alerts-icon]');
        const list = root.querySelector('[data-ft-qty-alerts-list]');
        const markBtn = root.querySelector('[data-ft-qty-alerts-mark-read]');

        if (badge) {
            if (unread.length > 0) {
                badge.textContent = unread.length > 99 ? '99+' : String(unread.length);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        if (icon) {
            ICON_ACTIVE.split(/\s+/).filter(Boolean).forEach((cls) => {
                icon.classList.toggle(cls, unread.length > 0);
            });
        }

        if (markBtn) {
            markBtn.disabled = unread.length === 0;
            markBtn.classList.toggle('opacity-40', unread.length === 0);
            markBtn.classList.toggle('pointer-events-none', unread.length === 0);
        }

        if (!list) return;

        if (unread.length === 0) {
            list.innerHTML = '<p class="px-4 py-6 text-sm text-gray-500 text-center">No unread quantity alerts</p>';
            return;
        }

        list.innerHTML = unread.map((item) => {
            const isOut = item.level === 'out' || Number(item.remaining) <= 0;
            const badgeClass = isOut ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800';
            const label = isOut ? 'Out of stock' : 'Low stock';
            const qtyText = isOut ? '0' : String(item.remaining);
            return `
                <div class="px-4 py-3 hover:bg-gray-50">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${escapeHtml(item.name)}</p>
                            <p class="text-xs text-gray-500 mt-0.5">${escapeHtml(qtyText)} ${escapeHtml(item.unit || 'Pcs')} left</p>
                        </div>
                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide ${badgeClass}">${label}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderAll() {
        roots().forEach(renderRoot);
    }

    function markAllRead() {
        const read = getReadIds();
        (items || []).forEach((item) => read.add(Number(item.id)));
        setReadIds(read);
        renderAll();
    }

    function closePanel(root) {
        const panel = root.querySelector('[data-ft-qty-alerts-panel]');
        const btn = root.querySelector('[data-ft-qty-alerts-btn]');
        if (panel) panel.classList.add('hidden');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    function togglePanel(root, event) {
        if (event) event.stopPropagation();
        const panel = root.querySelector('[data-ft-qty-alerts-panel]');
        const btn = root.querySelector('[data-ft-qty-alerts-btn]');
        if (!panel) return;
        const willOpen = panel.classList.contains('hidden');
        roots().forEach((r) => {
            if (r !== root) closePanel(r);
        });
        panel.classList.toggle('hidden', !willOpen);
        if (btn) btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        if (willOpen) renderRoot(root);
    }

    function bindRoot(root) {
        if (root.dataset.ftQtyBound === '1') return;
        root.dataset.ftQtyBound = '1';
        root.querySelector('[data-ft-qty-alerts-btn]')?.addEventListener('click', (e) => togglePanel(root, e));
        root.querySelector('[data-ft-qty-alerts-mark-read]')?.addEventListener('click', (e) => {
            e.stopPropagation();
            markAllRead();
        });
    }

    function syncFromProducts(productList) {
        if (!Array.isArray(productList)) return;
        items = productList
            .map((p) => {
                const qty = parseFloat(p.stock_quantity);
                if (Number.isNaN(qty) || qty > STOCK_LOW_THRESHOLD) return null;
                return {
                    id: Number(p.id),
                    name: p.name || 'Product',
                    remaining: parseFloat(qty.toFixed(2)),
                    unit: p.unit_name || 'Pcs',
                    level: qty <= 0 ? 'out' : 'low',
                };
            })
            .filter(Boolean)
            .sort((a, b) => {
                if (a.level !== b.level) return a.level === 'out' ? -1 : 1;
                return a.remaining - b.remaining;
            });
        renderAll();
    }

    function updateProductRemaining(productId, remaining, meta = {}) {
        const id = Number(productId);
        const qty = parseFloat(remaining);
        if (!id || Number.isNaN(qty)) return;
        const idx = items.findIndex((i) => Number(i.id) === id);
        if (qty > STOCK_LOW_THRESHOLD) {
            if (idx >= 0) items.splice(idx, 1);
        } else {
            const next = {
                id,
                name: meta.name || (idx >= 0 ? items[idx].name : null) || 'Product',
                remaining: parseFloat(Math.max(0, qty).toFixed(2)),
                unit: meta.unit || (idx >= 0 ? items[idx].unit : null) || 'Pcs',
                level: qty <= 0 ? 'out' : 'low',
            };
            if (idx >= 0) {
                items[idx] = { ...items[idx], ...next };
            } else {
                items.push(next);
                // Newly entered low/out zone becomes unread
                const read = getReadIds();
                if (read.delete(id)) {
                    setReadIds(read);
                }
            }
            items.sort((a, b) => {
                if (a.level !== b.level) return a.level === 'out' ? -1 : 1;
                return a.remaining - b.remaining;
            });
        }
        renderAll();
    }

    window.FTQuantityAlerts = {
        render: renderAll,
        markAllRead,
        syncFromProducts,
        updateProductRemaining,
        getItems: () => items.slice(),
        setItems: (next) => {
            items = Array.isArray(next) ? next : [];
            renderAll();
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        roots().forEach(bindRoot);
        renderAll();
        document.addEventListener('click', (e) => {
            roots().forEach((root) => {
                if (!root.contains(e.target)) closePanel(root);
            });
        });
    });

    // If script runs after DOMContentLoaded (cached include), bind immediately.
    if (document.readyState !== 'loading') {
        roots().forEach(bindRoot);
        renderAll();
    }
})();
</script>
