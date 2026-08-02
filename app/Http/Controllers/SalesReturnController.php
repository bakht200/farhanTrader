<?php

namespace App\Http\Controllers;

use App\Models\SalesReturn;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesReturn::with('customer', 'sale');
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $returns = $query->latest()->paginate(15);
        return view('sales-returns.index', compact('returns'));
    }

    public function create()
    {
        return view('sales-returns.create');
    }

    public function store(Request $request)
    {
        // Implementation
        return redirect()->route('sales.returns.index')->with('success', 'Sales return created successfully.');
    }

    public function show(SalesReturn $return)
    {
        return view('sales-returns.show', compact('return'));
    }

    public function edit(SalesReturn $return)
    {
        return view('sales-returns.edit', compact('return'));
    }

    public function update(Request $request, SalesReturn $return)
    {
        // Implementation
        return redirect()->route('sales.returns.index')->with('success', 'Sales return updated successfully.');
    }

    public function destroy(SalesReturn $return)
    {
        $return->delete();
        return redirect()->route('sales.returns.index')->with('success', 'Sales return deleted successfully.');
    }
}
