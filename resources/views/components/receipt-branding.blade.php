@php
    use App\Support\CurrentBranch;
    $branch = CurrentBranch::get();
    $receiptBranding = $branch
        ? $branch->receiptBrandingPayload()
        : [
            'configured' => false,
            'title' => '',
            'subtitle' => '',
            'phone' => '',
            'mobile1' => '',
            'mobile2' => '',
            'email' => '',
            'address' => '',
        ];
@endphp

<script>
    window.FTReceiptBranding = @json($receiptBranding);
</script>

{{-- One-time receipt setup modal (shown on Print if branch not configured) --}}
<div id="ft-receipt-settings-modal" class="fixed inset-0 z-[9999] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/50" data-ft-receipt-backdrop></div>
    <div class="relative min-h-full flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6" role="dialog" aria-labelledby="ft-receipt-modal-title">
            <h3 id="ft-receipt-modal-title" class="text-lg font-semibold text-gray-900">Set receipt details for this branch</h3>
            <p id="ft-receipt-modal-desc" class="mt-1 text-sm text-gray-600">Required before printing. You can also change these anytime from <strong>Receipt Settings</strong> in the menu.</p>
            <form id="ft-receipt-settings-form" class="mt-4 space-y-3">
                <div>
                    <label for="ft-receipt-title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="ft-receipt-title" name="receipt_title" required maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Business name on receipt">
                </div>
                <div>
                    <label for="ft-receipt-subtitle" class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                    <input type="text" id="ft-receipt-subtitle" name="receipt_subtitle" maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Tagline / deals in…">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="ft-receipt-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" id="ft-receipt-phone" name="receipt_phone" maxlength="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="ft-receipt-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="ft-receipt-email" name="receipt_email" maxlength="255"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="ft-receipt-mobile-1" class="block text-sm font-medium text-gray-700 mb-1">Mobile 1</label>
                        <input type="text" id="ft-receipt-mobile-1" name="receipt_mobile_1" maxlength="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="ft-receipt-mobile-2" class="block text-sm font-medium text-gray-700 mb-1">Mobile 2</label>
                        <input type="text" id="ft-receipt-mobile-2" name="receipt_mobile_2" maxlength="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                <div>
                    <label for="ft-receipt-address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea id="ft-receipt-address" name="receipt_address" rows="2" maxlength="500"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"></textarea>
                </div>
                <p id="ft-receipt-settings-error" class="hidden text-sm text-red-600"></p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="ft-receipt-settings-cancel"
                            class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="submit" id="ft-receipt-settings-save"
                            class="px-4 py-2 rounded-md bg-orange-500 hover:bg-orange-600 text-white font-medium">Save &amp; Print</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function applyBranding(payload) {
        window.FTReceiptBranding = Object.assign({}, window.FTReceiptBranding || {}, payload || {}, {
            configured: !!(payload && String(payload.title || '').trim()),
        });
        return window.FTReceiptBranding;
    }

    function fillFormFromBranding() {
        const b = window.FTReceiptBranding || {};
        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        set('ft-receipt-title', b.title);
        set('ft-receipt-subtitle', b.subtitle);
        set('ft-receipt-phone', b.phone);
        set('ft-receipt-mobile-1', b.mobile1);
        set('ft-receipt-mobile-2', b.mobile2);
        set('ft-receipt-email', b.email);
        set('ft-receipt-address', b.address);
    }

    let pendingResolve = null;
    let pendingReject = null;

    function closeModal(cancelled) {
        const modal = document.getElementById('ft-receipt-settings-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
        const err = document.getElementById('ft-receipt-settings-error');
        if (err) {
            err.classList.add('hidden');
            err.textContent = '';
        }
        if (cancelled && pendingReject) {
            const reject = pendingReject;
            pendingResolve = null;
            pendingReject = null;
            reject(new Error('Receipt settings cancelled'));
        }
    }

    function openModal(options = {}) {
        fillFormFromBranding();
        const desc = document.getElementById('ft-receipt-modal-desc');
        if (desc) {
            desc.innerHTML = options.description
                || 'Required before printing. You can also change these anytime from <strong>Receipt Settings</strong> in the menu.';
        }
        const saveBtn = document.getElementById('ft-receipt-settings-save');
        if (saveBtn) {
            saveBtn.textContent = options.saveLabel || 'Save & Print';
            saveBtn.dataset.defaultLabel = saveBtn.textContent;
        }
        const modal = document.getElementById('ft-receipt-settings-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        }
        document.getElementById('ft-receipt-title')?.focus();
    }

    function headerHtml(docTitle) {
        const b = window.FTReceiptBranding || {};
        const title = escapeHtml(b.title || '');
        const subtitle = escapeHtml(b.subtitle || '');
        const phone = (b.phone || '').trim();
        const mobile1 = (b.mobile1 || '').trim();
        const mobile2 = (b.mobile2 || '').trim();
        const email = (b.email || '').trim();
        const address = (b.address || '').trim();
        const left = [];
        if (phone) left.push(`<div>Ph: ${escapeHtml(phone)}</div>`);
        if (mobile1) left.push(`<div>Mob: ${escapeHtml(mobile1)}</div>`);
        if (mobile2) left.push(`<div>Mob: ${escapeHtml(mobile2)}</div>`);
        const right = [];
        if (email) right.push(`<div>Email: ${escapeHtml(email)}</div>`);
        const label = escapeHtml(docTitle || 'Order Receipt');

        return `
            <div class="header">
                <h2>${title}</h2>
                <div class="business-info">
                    ${subtitle ? `<div class="business-service">${subtitle}</div>` : ''}
                    ${address ? `<div class="business-address" style="margin-top: 4px; color: #9ca3af;">${escapeHtml(address)}</div>` : ''}
                    <div class="business-contact">
                        <div class="business-contact-left">${left.join('')}</div>
                        <div class="business-contact-right">${right.join('')}</div>
                    </div>
                </div>
                <p style="margin-top: 10px;">${label}</p>
            </div>`;
    }

    function displayTitle() {
        const b = window.FTReceiptBranding || {};
        return (b.title && String(b.title).trim()) || 'Receipt';
    }

    async function requireConfigured(options = {}) {
        const b = window.FTReceiptBranding || {};
        if (b.configured && String(b.title || '').trim()) {
            return b;
        }

        return new Promise((resolve, reject) => {
            pendingResolve = resolve;
            pendingReject = reject;
            openModal(options);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('ft-receipt-settings-form');
        const cancelBtn = document.getElementById('ft-receipt-settings-cancel');
        const backdrop = document.querySelector('[data-ft-receipt-backdrop]');

        cancelBtn?.addEventListener('click', () => closeModal(true));
        backdrop?.addEventListener('click', () => closeModal(true));

        form?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const err = document.getElementById('ft-receipt-settings-error');
            const saveBtn = document.getElementById('ft-receipt-settings-save');
            const title = (document.getElementById('ft-receipt-title')?.value || '').trim();
            if (!title) {
                if (err) {
                    err.textContent = 'Title is required.';
                    err.classList.remove('hidden');
                }
                return;
            }

            const payload = {
                receipt_title: title,
                receipt_subtitle: (document.getElementById('ft-receipt-subtitle')?.value || '').trim() || null,
                receipt_phone: (document.getElementById('ft-receipt-phone')?.value || '').trim() || null,
                receipt_mobile_1: (document.getElementById('ft-receipt-mobile-1')?.value || '').trim() || null,
                receipt_mobile_2: (document.getElementById('ft-receipt-mobile-2')?.value || '').trim() || null,
                receipt_email: (document.getElementById('ft-receipt-email')?.value || '').trim() || null,
                receipt_address: (document.getElementById('ft-receipt-address')?.value || '').trim() || null,
            };

            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving…';
            }

            try {
                const res = await fetch('{{ route('branches.receipt-settings.update') }}', {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    const msg = data.message
                        || (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                        || 'Failed to save receipt settings.';
                    throw new Error(msg);
                }
                const branding = applyBranding(data.receipt || {
                    title: payload.receipt_title,
                    subtitle: payload.receipt_subtitle || '',
                    phone: payload.receipt_phone || '',
                    mobile1: payload.receipt_mobile_1 || '',
                    mobile2: payload.receipt_mobile_2 || '',
                    email: payload.receipt_email || '',
                    address: payload.receipt_address || '',
                });
                const resolve = pendingResolve;
                pendingResolve = null;
                pendingReject = null;
                closeModal(false);
                if (resolve) resolve(branding);
            } catch (ex) {
                if (err) {
                    err.textContent = ex.message || 'Failed to save.';
                    err.classList.remove('hidden');
                }
            } finally {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = saveBtn.dataset.defaultLabel || 'Save & Print';
                }
            }
        });
    });

    window.FTReceipt = {
        requireConfigured,
        headerHtml,
        displayTitle,
        applyBranding,
        getBranding: () => window.FTReceiptBranding || {},
    };
})();
</script>
