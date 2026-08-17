<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'is_active',
        'receipt_title',
        'receipt_subtitle',
        'receipt_phone',
        'receipt_mobile_1',
        'receipt_mobile_2',
        'receipt_email',
        'receipt_address',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function hasReceiptConfigured(): bool
    {
        return filled(trim((string) $this->receipt_title));
    }

    /**
     * Two-letter code from branch name for sale numbers (e.g. "Lahori Gate" → "LA").
     */
    public function saleNumberCode(): string
    {
        $letters = preg_replace('/[^A-Za-z]/', '', (string) $this->name) ?? '';
        $code = strtoupper(substr($letters, 0, 2));

        return str_pad($code !== '' ? $code : 'BR', 2, 'X');
    }

    /**
     * @return array{
     *     configured: bool,
     *     title: string,
     *     subtitle: string,
     *     phone: string,
     *     mobile1: string,
     *     mobile2: string,
     *     email: string,
     *     address: string
     * }
     */
    public function receiptBrandingPayload(): array
    {
        return [
            'configured' => $this->hasReceiptConfigured(),
            'title' => (string) ($this->receipt_title ?? ''),
            'subtitle' => (string) ($this->receipt_subtitle ?? ''),
            'phone' => (string) ($this->receipt_phone ?? ''),
            'mobile1' => (string) ($this->receipt_mobile_1 ?? ''),
            'mobile2' => (string) ($this->receipt_mobile_2 ?? ''),
            'email' => (string) ($this->receipt_email ?? ''),
            'address' => (string) ($this->receipt_address ?? ''),
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(BranchProductStock::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(BranchShare::class);
    }
}
