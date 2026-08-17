<x-app-layout>
    <x-slot name="header">
        Receipt Settings
    </x-slot>

    <div class="mb-4">
        <nav class="text-sm text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-gray-900">Dashboard</a>
            <span class="mx-2">></span>
            <span class="text-gray-900 font-medium">Receipt Settings</span>
        </nav>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if(! $branch)
        <div class="bg-white rounded-lg shadow-sm p-6 text-sm text-gray-700">
            Select a branch from the switcher before editing receipt settings.
        </div>
    @else
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-1">Receipt / Print Header</h3>
            <p class="text-sm text-gray-600 mb-4">
                These details appear on printed receipts for
                <strong>{{ $branch->name }}</strong> only. Branch users and Admin can update them anytime.
            </p>

            <form method="POST" action="{{ route('branches.receipt-settings.update') }}" class="space-y-4" id="receipt-settings-form">
                @csrf
                @method('PUT')

                <div>
                    <label for="receipt_title" class="block text-sm font-medium text-gray-700 mb-2">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="receipt_title" name="receipt_title" required maxlength="255"
                           value="{{ old('receipt_title', $branch->receipt_title) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Business name on receipt"
                           data-receipt-preview>
                    @error('receipt_title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="receipt_subtitle" class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                    <input type="text" id="receipt_subtitle" name="receipt_subtitle" maxlength="255"
                           value="{{ old('receipt_subtitle', $branch->receipt_subtitle) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                           placeholder="Tagline / deals in…"
                           data-receipt-preview>
                    @error('receipt_subtitle')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="receipt_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="text" id="receipt_phone" name="receipt_phone" maxlength="50"
                               value="{{ old('receipt_phone', $branch->receipt_phone) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="Landline"
                               data-receipt-preview>
                        @error('receipt_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="receipt_email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="receipt_email" name="receipt_email" maxlength="255"
                               value="{{ old('receipt_email', $branch->receipt_email) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               placeholder="email@example.com"
                               data-receipt-preview>
                        @error('receipt_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="receipt_mobile_1" class="block text-sm font-medium text-gray-700 mb-2">Mobile 1</label>
                        <input type="text" id="receipt_mobile_1" name="receipt_mobile_1" maxlength="50"
                               value="{{ old('receipt_mobile_1', $branch->receipt_mobile_1) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               data-receipt-preview>
                        @error('receipt_mobile_1')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="receipt_mobile_2" class="block text-sm font-medium text-gray-700 mb-2">Mobile 2</label>
                        <input type="text" id="receipt_mobile_2" name="receipt_mobile_2" maxlength="50"
                               value="{{ old('receipt_mobile_2', $branch->receipt_mobile_2) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                               data-receipt-preview>
                        @error('receipt_mobile_2')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="receipt_address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea id="receipt_address" name="receipt_address" rows="2" maxlength="500"
                              class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
                              placeholder="Branch address for receipts"
                              data-receipt-preview>{{ old('receipt_address', $branch->receipt_address) }}</textarea>
                    @error('receipt_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md font-medium">
                        Save Receipt Settings
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 xl:sticky xl:top-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">E-Receipt preview</h3>
                    <p class="text-sm text-gray-500">Updates live as you type — sample order only</p>
                </div>
                <span class="text-[10px] font-semibold uppercase tracking-wide px-2 py-1 rounded bg-orange-50 text-orange-700">Live</span>
            </div>

            <div class="bg-gray-100 rounded-lg p-4 flex justify-center">
                <div id="ereceipt-preview" class="ereceipt-preview bg-white shadow-md w-full max-w-[320px] px-3 py-3 text-gray-900">
                    {{-- Filled by JS --}}
                </div>
            </div>
        </div>
    </div>

    <style>
        .ereceipt-preview {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }
        .ereceipt-preview .header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .ereceipt-preview .header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            word-break: break-word;
        }
        .ereceipt-preview .business-info {
            padding-top: 6px;
            font-size: 10px;
        }
        .ereceipt-preview .business-service {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .ereceipt-preview .business-address {
            margin: 0 0 4px 0;
            line-height: 1.3;
            white-space: pre-wrap;
            word-break: break-word;
            color: #9ca3af;
        }
        .ereceipt-preview .business-contact {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-top: 4px;
            font-size: 9px;
            text-align: left;
        }
        .ereceipt-preview .business-contact-left {
            text-align: left;
        }
        .ereceipt-preview .business-contact-right {
            text-align: right;
        }
        .ereceipt-preview .doc-label {
            margin-top: 8px;
            font-size: 10px;
        }
        .ereceipt-preview .meta {
            margin-bottom: 8px;
            font-size: 10px;
        }
        .ereceipt-preview .meta div {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 2px;
        }
        .ereceipt-preview table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 10px;
        }
        .ereceipt-preview th,
        .ereceipt-preview td {
            border-bottom: 1px solid #ddd;
            padding: 4px 2px;
            text-align: left;
        }
        .ereceipt-preview th {
            font-weight: 700;
            border-bottom: 1px solid #111;
        }
        .ereceipt-preview td.num,
        .ereceipt-preview th.num {
            text-align: right;
        }
        .ereceipt-preview .totals {
            margin-top: 8px;
            font-size: 10px;
        }
        .ereceipt-preview .totals div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .ereceipt-preview .totals .grand {
            font-weight: 700;
            font-size: 12px;
            border-top: 1px solid #111;
            padding-top: 4px;
            margin-top: 4px;
        }
        .ereceipt-preview .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 9px;
            color: #555;
            border-top: 1px dashed #ccc;
            padding-top: 8px;
        }
        .ereceipt-preview .placeholder {
            color: #9ca3af;
            font-style: italic;
        }
    </style>

    <script>
    (function () {
        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function val(id) {
            return (document.getElementById(id)?.value || '').trim();
        }

        function renderPreview() {
            const root = document.getElementById('ereceipt-preview');
            if (!root) return;

            const title = val('receipt_title');
            const subtitle = val('receipt_subtitle');
            const phone = val('receipt_phone');
            const mobile1 = val('receipt_mobile_1');
            const mobile2 = val('receipt_mobile_2');
            const email = val('receipt_email');
            const address = val('receipt_address');

            const left = [];
            if (phone) left.push(`<div>Ph: ${escapeHtml(phone)}</div>`);
            if (mobile1) left.push(`<div>Mob: ${escapeHtml(mobile1)}</div>`);
            if (mobile2) left.push(`<div>Mob: ${escapeHtml(mobile2)}</div>`);

            const right = [];
            if (email) right.push(`<div>Email: ${escapeHtml(email)}</div>`);

            const now = new Date();
            const dateStr = now.toLocaleString();

            root.innerHTML = `
                <div class="header">
                    <h2>${title ? escapeHtml(title) : '<span class="placeholder">Business title</span>'}</h2>
                    <div class="business-info">
                        ${subtitle ? `<div class="business-service">${escapeHtml(subtitle)}</div>` : '<div class="business-service placeholder">Subtitle</div>'}
                        ${address ? `<div class="business-address">${escapeHtml(address)}</div>` : '<div class="business-address placeholder">Address</div>'}
                        <div class="business-contact">
                            <div class="business-contact-left">${left.join('') || '<div class="placeholder">Phone / Mobile</div>'}</div>
                            <div class="business-contact-right">${right.join('') || '<div class="placeholder">Email</div>'}</div>
                        </div>
                    </div>
                    <p class="doc-label">Order Receipt</p>
                </div>
                <div class="meta">
                    <div><span>Sale Number:</span><span>SALE-PREVIEW</span></div>
                    <div><span>Date:</span><span>${escapeHtml(dateStr)}</span></div>
                    <div><span>Customer:</span><span>Walk-in Customer</span></div>
                    <div><span>Payment:</span><span>Cash</span></div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="num">Qty</th>
                            <th class="num">Price</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Sample Product A</td>
                            <td class="num">2</td>
                            <td class="num">150.00</td>
                            <td class="num">300.00</td>
                        </tr>
                        <tr>
                            <td>Sample Product B</td>
                            <td class="num">1</td>
                            <td class="num">50.00</td>
                            <td class="num">50.00</td>
                        </tr>
                    </tbody>
                </table>
                <div class="totals">
                    <div><span>Subtotal</span><span>PKR 350.00</span></div>
                    <div><span>Paid</span><span>PKR 350.00</span></div>
                    <div class="grand"><span>Grand Total</span><span>PKR 350.00</span></div>
                </div>
                <div class="footer">Thank you for your business!</div>
            `;
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-receipt-preview]').forEach((el) => {
                el.addEventListener('input', renderPreview);
                el.addEventListener('change', renderPreview);
            });
            renderPreview();
        });
    })();
    </script>
    @endif
</x-app-layout>
