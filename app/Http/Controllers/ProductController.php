<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\ProductUnit;
use App\Models\UnitConversion;
use App\Services\BranchStockService;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category', 'unit', 'baseUnit', 'productUnits.unit', 'createdBy', 'currentBranchStock');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'sku') {
            $query->orderBy('sku', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 10))
            ->appends($request->query());

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $units = Unit::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        return view('products.create', compact('categories', 'units', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'brand' => 'nullable|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'base_unit_id' => 'nullable|exists:units,id',
            'selling_type' => 'required|in:retail,wholesale,both',
            'total_units' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:255',
            'product_type' => 'required|in:single,variant',
            'quantity_alert' => 'nullable|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|numeric|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'manufactured_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:manufactured_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'units' => 'nullable|array',
            'units.*.unit_id' => 'nullable|exists:units,id',
            'units.*.is_base_unit' => 'nullable|boolean',
            'units.*.selling_price' => 'nullable|numeric|min:0',
            'units.*.retail_price' => 'nullable|numeric|min:0',
            'units.*.wholesale_price' => 'nullable|numeric|min:0',
            'conversions' => 'nullable|array',
            'conversions.*.from_unit_id' => 'required_with:conversions.*.to_unit_id,conversions.*.factor|exists:units,id',
            'conversions.*.to_unit_id' => 'required_with:conversions.*.from_unit_id,conversions.*.factor|exists:units,id|different:conversions.*.from_unit_id',
            'conversions.*.factor' => 'required_with:conversions.*.from_unit_id,conversions.*.to_unit_id|numeric|min:0.01|max:999999.99|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        // Validate and set prices based on selling_type
        if ($validated['selling_type'] === 'retail') {
            if (empty($validated['retail_price'])) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['retail_price' => ['Retail price is required when selling type is retail.']]], 422);
                }
                return back()->withErrors(['retail_price' => 'Retail price is required when selling type is retail.'])->withInput();
            }
            // Validate retail price >= purchase price
            if ($validated['retail_price'] < $validated['purchase_price']) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['retail_price' => ['Retail price cannot be less than purchase price.']]], 422);
                }
                return back()->withErrors(['retail_price' => 'Retail price cannot be less than purchase price.'])->withInput();
            }
            $validated['selling_price'] = $validated['retail_price'];
        } elseif ($validated['selling_type'] === 'wholesale') {
            if (empty($validated['wholesale_price'])) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['wholesale_price' => ['Wholesale price is required when selling type is wholesale.']]], 422);
                }
                return back()->withErrors(['wholesale_price' => 'Wholesale price is required when selling type is wholesale.'])->withInput();
            }
            // Validate wholesale price >= purchase price
            if ($validated['wholesale_price'] < $validated['purchase_price']) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['wholesale_price' => ['Wholesale price cannot be less than purchase price.']]], 422);
                }
                return back()->withErrors(['wholesale_price' => 'Wholesale price cannot be less than purchase price.'])->withInput();
            }
            $validated['selling_price'] = $validated['wholesale_price'];
        } elseif ($validated['selling_type'] === 'both') {
            if (empty($validated['retail_price'])) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['retail_price' => ['Retail price is required when selling type is both.']]], 422);
                }
                return back()->withErrors(['retail_price' => 'Retail price is required when selling type is both.'])->withInput();
            }
            if (empty($validated['wholesale_price'])) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['wholesale_price' => ['Wholesale price is required when selling type is both.']]], 422);
                }
                return back()->withErrors(['wholesale_price' => 'Wholesale price is required when selling type is both.'])->withInput();
            }
            // Validate retail price >= purchase price
            if ($validated['retail_price'] < $validated['purchase_price']) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['retail_price' => ['Retail price cannot be less than purchase price.']]], 422);
                }
                return back()->withErrors(['retail_price' => 'Retail price cannot be less than purchase price.'])->withInput();
            }
            // Validate wholesale price >= purchase price
            if ($validated['wholesale_price'] < $validated['purchase_price']) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => ['wholesale_price' => ['Wholesale price cannot be less than purchase price.']]], 422);
                }
                return back()->withErrors(['wholesale_price' => 'Wholesale price cannot be less than purchase price.'])->withInput();
            }
            // For "both", set selling_price to retail_price as default
            $validated['selling_price'] = $validated['retail_price'];
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Generate SKU if not provided
        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateSku($validated['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['user_id'] = auth()->id();
        
        // Set base_unit_id if provided, otherwise use unit_id
        if ($request->has('base_unit_id') && $request->base_unit_id) {
            $validated['base_unit_id'] = $request->base_unit_id;
        } else {
            $validated['base_unit_id'] = $validated['unit_id'];
        }
        
        DB::beginTransaction();
        try {
            $initialStock = (float) ($validated['stock_quantity'] ?? 0);
            $validated['stock_quantity'] = 0; // legacy column; live stock is per-branch

            $product = Product::create($validated);
            app(BranchStockService::class)->initializeProduct(
                $product,
                $initialStock,
                null,
                $validated['selling_type'] ?? 'both'
            );
            
            // Create ProductUnit records
            // First, always create base unit ProductUnit
            $baseUnitId = $validated['base_unit_id'] ?? $validated['unit_id'];
            
            // Determine base unit price based on selling_type
            $baseUnitPrice = null;
            if ($product->selling_type === 'retail' && $product->retail_price) {
                $baseUnitPrice = $product->retail_price;
            } elseif ($product->selling_type === 'wholesale' && $product->wholesale_price) {
                $baseUnitPrice = $product->wholesale_price;
            } elseif ($product->selling_type === 'both' && $product->retail_price) {
                $baseUnitPrice = $product->retail_price; // Default to retail
            } else {
                $baseUnitPrice = $product->selling_price ?? 0;
            }
            
            // Check if base unit price is in units array
            if ($request->has('units') && is_array($request->units)) {
                foreach ($request->input('units') as $unitData) {
                    if (!empty($unitData['unit_id']) && ($unitData['is_base_unit'] ?? false)) {
                        $baseUnitPrice = $unitData['retail_price'] ?? $unitData['wholesale_price'] ?? $baseUnitPrice;
                        break;
                    }
                }
            }
            
            // Create base unit ProductUnit
            ProductUnit::create([
                'product_id' => $product->id,
                'unit_id' => $baseUnitId,
                'is_base_unit' => true,
                'selling_price' => $baseUnitPrice ? round((float)$baseUnitPrice, 2) : null,
                'is_active' => true,
            ]);
            
            // Create other selling units if provided
            if ($request->has('units') && is_array($request->units)) {
                foreach ($request->input('units') as $unitData) {
                    if (!empty($unitData['unit_id']) && !($unitData['is_base_unit'] ?? false)) {
                        // Skip if it's the base unit (already created)
                        if ($unitData['unit_id'] == $baseUnitId) {
                            continue;
                        }
                        
                        ProductUnit::create([
                            'product_id' => $product->id,
                            'unit_id' => $unitData['unit_id'],
                            'is_base_unit' => false,
                            'selling_price' => !empty($unitData['selling_price']) && $unitData['selling_price'] > 0 
                                ? round((float)$unitData['selling_price'], 2) 
                                : null, // Will be calculated from base unit if null
                            'is_active' => true,
                        ]);
                    }
                }
            }
            
            // Create UnitConversion records (product-specific)
            $conversionErrors = [];
            $conversionsCreated = 0;
            
            // Log what we received
            \Log::info('Creating conversions for product', [
                'product_id' => $product->id,
                'has_conversions' => $request->has('conversions'),
                'conversions_count' => $request->has('conversions') ? count($request->input('conversions', [])) : 0,
                'conversions_data' => $request->input('conversions', [])
            ]);
            
            $normalizedConversions = $this->extractConversionsFromRequest($request);
            if (!empty($normalizedConversions)) {
                foreach ($normalizedConversions as $index => $conv) {
                    // Skip if any required field is empty or invalid
                    if (empty($conv['from_unit_id']) || empty($conv['to_unit_id']) || empty($conv['factor'])) {
                        \Log::warning('Skipping incomplete conversion', [
                            'index' => $index,
                            'data' => $conv
                        ]);
                        continue;
                    }
                    
                    // Validate factor is numeric and positive, round to 2 decimal places
                    $factor = is_numeric($conv['factor']) ? (float)$conv['factor'] : null;
                    if (!$factor || $factor <= 0) {
                        \Log::warning('Skipping conversion with invalid factor', [
                            'index' => $index,
                            'factor' => $conv['factor']
                        ]);
                        continue;
                    }
                    // Round to 2 decimal places
                    $factor = round($factor, 2);
                    if ($factor < 0.01) {
                        \Log::warning('Skipping conversion with factor less than 0.01', [
                            'index' => $index,
                            'factor' => $factor
                        ]);
                        continue;
                    }
                    
                    try {
                        // Check if conversion already exists for this product
                        $existing = UnitConversion::where('product_id', $product->id)
                            ->where('from_unit_id', $conv['from_unit_id'])
                            ->where('to_unit_id', $conv['to_unit_id'])
                            ->first();
                        
                        if (!$existing) {
                            $newConversion = UnitConversion::create([
                                'product_id' => $product->id,
                                'from_unit_id' => $conv['from_unit_id'],
                                'to_unit_id' => $conv['to_unit_id'],
                                'conversion_factor' => $factor,
                                'is_active' => true,
                            ]);
                            $conversionsCreated++;
                            \Log::info('Created conversion', [
                                'conversion_id' => $newConversion->id,
                                'product_id' => $product->id,
                                'from_unit_id' => $conv['from_unit_id'],
                                'to_unit_id' => $conv['to_unit_id'],
                                'factor' => $factor
                            ]);
                        } else {
                            // Update existing conversion
                            $existing->update([
                                'conversion_factor' => $factor,
                                'is_active' => true,
                            ]);
                            $conversionsCreated++;
                            \Log::info('Updated conversion', [
                                'conversion_id' => $existing->id,
                                'product_id' => $product->id,
                                'factor' => $factor
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Log error but don't fail the entire product creation
                        $errorMsg = "Error creating conversion #{$index}: " . $e->getMessage();
                        \Log::error($errorMsg, [
                            'conversion_data' => $conv,
                            'index' => $index,
                            'product_id' => $product->id,
                            'exception' => $e->getTraceAsString()
                        ]);
                        $conversionErrors[] = $errorMsg;
                        // Continue with other conversions
                    }
                }
            }
            
            \Log::info('Conversion creation completed', [
                'product_id' => $product->id,
                'conversions_created' => $conversionsCreated,
                'errors' => count($conversionErrors)
            ]);
            
            DB::commit();
            
            // Reload conversions to verify they were saved
            $savedConversions = UnitConversion::where('product_id', $product->id)
                ->active()
                ->with(['fromUnit', 'toUnit'])
                ->get();
            
            \Log::info('Conversions after save', [
                'product_id' => $product->id,
                'saved_count' => $savedConversions->count(),
                'conversions' => $savedConversions->toArray()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating product: ' . $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['error' => 'Error creating product: ' . $e->getMessage()])->withInput();
        }
        
        if ($request->expectsJson()) {
            $message = 'Product created successfully.';
            if (!empty($conversionErrors)) {
                $message .= ' However, some conversion factors could not be saved: ' . implode('; ', $conversionErrors);
            } else if ($conversionsCreated > 0) {
                $message .= " {$conversionsCreated} conversion(s) saved successfully.";
            }
            return response()->json([
                'success' => true,
                'message' => $message,
                'product' => $product->load('category', 'unit', 'productUnits.unit'),
                'conversion_errors' => $conversionErrors ?? [],
                'conversions_created' => $conversionsCreated ?? 0,
                'saved_conversions' => $savedConversions ?? []
            ]);
        }
        
        $redirectMessage = 'Product created successfully.';
        if (isset($conversionsCreated) && $conversionsCreated > 0) {
            $redirectMessage .= " {$conversionsCreated} conversion(s) saved successfully.";
        }

        // Keep user on the edit page if any conversion failed so they can fix
        // the offending row instead of silently landing on the index list.
        if (!empty($conversionErrors)) {
            return redirect()->route('products.edit', $product)
                ->with('warning', 'Product created, but some conversion factors could not be saved.')
                ->with('conversion_errors', $conversionErrors)
                ->with('conversions_created', $conversionsCreated ?? 0);
        }

        return redirect()->route('products.index')->with('success', $redirectMessage)
            ->with('conversion_errors', [])
            ->with('conversions_created', $conversionsCreated ?? 0);
    }

    public function show(Product $product)
    {
        $product->load('category', 'unit', 'createdBy', 'currentBranchStock');

        $branchStocks = \App\Models\Branch::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->with(['productStocks' => function ($query) use ($product) {
                $query->where('product_id', $product->id);
            }])
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'stock_quantity' => (float) ($branch->productStocks->first()->stock_quantity ?? 0),
                    'is_current' => (int) $branch->id === (int) (CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID),
                ];
            });

        return view('products.show', compact('product', 'branchStocks'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        $units = Unit::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        
        // Load product units and conversions
        $product->load(['productUnits.unit', 'currentBranchStock']);
        $productUnits = $product->productUnits()->with('unit')->get(); // Load all, not just active
        
        // Get conversions involving product units (check both active and inactive ProductUnits)
        $unitIds = $productUnits->pluck('unit_id')->toArray();
        // Also check conversions that might reference the base unit
        if ($product->base_unit_id) {
            $unitIds[] = $product->base_unit_id;
        }
        $unitIds = array_unique($unitIds);
        
        // Get conversions for this specific product
        $conversions = \App\Models\UnitConversion::where('product_id', $product->id)
            ->active()
            ->with(['fromUnit', 'toUnit'])
            ->get();
        
        // Filter productUnits to show only active ones for display
        $productUnits = $productUnits->where('is_active', true);
        
        // Load product history with supplier details
        $productHistory = $product->history()->with('supplier', 'supplierBill')->get();
        
        return view('products.edit', compact('product', 'categories', 'units', 'suppliers', 'productHistory', 'productUnits', 'conversions'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'brand' => 'nullable|string|max:255',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'base_unit_id' => 'nullable|exists:units,id',
            'selling_type' => 'required|in:retail,wholesale,both',
            'total_units' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:255',
            'product_type' => 'required|in:single,variant',
            'quantity_alert' => 'nullable|string|max:255',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'retail_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'low_stock_threshold' => 'required|numeric|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'manufactured_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:manufactured_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'units' => 'nullable|array',
            'units.*.unit_id' => 'required_with:units|exists:units,id',
            'units.*.is_base_unit' => 'nullable|boolean',
            'units.*.selling_price' => 'nullable|numeric|min:0',
            'units.*.retail_price' => 'nullable|numeric|min:0',
            'units.*.wholesale_price' => 'nullable|numeric|min:0',
            'units.*.id' => 'nullable|exists:product_units,id',
            'conversions' => 'nullable|array',
            'conversions.*.from_unit_id' => 'required_with:conversions.*.to_unit_id,conversions.*.factor|exists:units,id',
            'conversions.*.to_unit_id' => 'required_with:conversions.*.from_unit_id,conversions.*.factor|exists:units,id|different:conversions.*.from_unit_id',
            'conversions.*.factor' => 'required_with:conversions.*.from_unit_id,conversions.*.to_unit_id|numeric|min:0.01|max:999999.99|regex:/^\d+(\.\d{1,2})?$/',
            'conversions.*.id' => 'nullable|exists:unit_conversions,id',
        ]);

        // Validate and set prices based on selling_type
        if ($validated['selling_type'] === 'retail') {
            if (empty($validated['retail_price'])) {
                return back()->withErrors(['retail_price' => 'Retail price is required when selling type is retail.'])->withInput();
            }
            // Validate retail price >= purchase price
            if ($validated['retail_price'] < $validated['purchase_price']) {
                return back()->withErrors(['retail_price' => 'Retail price cannot be less than purchase price.'])->withInput();
            }
            $validated['selling_price'] = $validated['retail_price'];
        } elseif ($validated['selling_type'] === 'wholesale') {
            if (empty($validated['wholesale_price'])) {
                return back()->withErrors(['wholesale_price' => 'Wholesale price is required when selling type is wholesale.'])->withInput();
            }
            // Validate wholesale price >= purchase price
            if ($validated['wholesale_price'] < $validated['purchase_price']) {
                return back()->withErrors(['wholesale_price' => 'Wholesale price cannot be less than purchase price.'])->withInput();
            }
            $validated['selling_price'] = $validated['wholesale_price'];
        } elseif ($validated['selling_type'] === 'both') {
            if (empty($validated['retail_price'])) {
                return back()->withErrors(['retail_price' => 'Retail price is required when selling type is both.'])->withInput();
            }
            if (empty($validated['wholesale_price'])) {
                return back()->withErrors(['wholesale_price' => 'Wholesale price is required when selling type is both.'])->withInput();
            }
            // Validate retail price >= purchase price
            if ($validated['retail_price'] < $validated['purchase_price']) {
                return back()->withErrors(['retail_price' => 'Retail price cannot be less than purchase price.'])->withInput();
            }
            // Validate wholesale price >= purchase price
            if ($validated['wholesale_price'] < $validated['purchase_price']) {
                return back()->withErrors(['wholesale_price' => 'Wholesale price cannot be less than purchase price.'])->withInput();
            }
            // For "both", set selling_price to retail_price as default
            $validated['selling_price'] = $validated['retail_price'];
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }
        
        // Set base_unit_id if provided, otherwise use unit_id
        if ($request->has('base_unit_id') && $request->base_unit_id) {
            $validated['base_unit_id'] = $request->base_unit_id;
        } else {
            $validated['base_unit_id'] = $validated['unit_id'];
        }

        DB::beginTransaction();
        try {
            $branchStockQty = (float) ($validated['stock_quantity'] ?? 0);
            unset($validated['stock_quantity']); // do not write shared legacy column from form

            $product->update($validated);
            $product->setBranchStock($branchStockQty);
            $product->setBranchSellingType($validated['selling_type'] ?? 'both');
            
            // Update ProductUnit records
            // First, update or create base unit ProductUnit
            $baseUnitId = $validated['base_unit_id'] ?? $validated['unit_id'];
            
            // Determine base unit price
            $baseUnitPrice = null;
            $sellingTypeForPrice = $validated['selling_type'] ?? 'retail';
            if ($sellingTypeForPrice === 'retail' && !empty($validated['retail_price'])) {
                $baseUnitPrice = $validated['retail_price'];
            } elseif ($sellingTypeForPrice === 'wholesale' && !empty($validated['wholesale_price'])) {
                $baseUnitPrice = $validated['wholesale_price'];
            } elseif ($sellingTypeForPrice === 'both' && !empty($validated['retail_price'])) {
                $baseUnitPrice = $validated['retail_price'];
            } else {
                $baseUnitPrice = $validated['selling_price'] ?? 0;
            }
            
            // Check if base unit price is in units array
            if ($request->has('units') && is_array($request->units)) {
                foreach ($request->input('units') as $unitData) {
                    // Check if this is the base unit (can be boolean true, string "1", or integer 1)
                    $isBaseUnit = !empty($unitData['is_base_unit']) && 
                                 ($unitData['is_base_unit'] === true || 
                                  $unitData['is_base_unit'] === '1' || 
                                  $unitData['is_base_unit'] === 1 ||
                                  $unitData['is_base_unit'] === 'true');
                    
                    if (!empty($unitData['unit_id']) && $isBaseUnit) {
                        // Get price from hidden fields or use product prices
                        $baseUnitPrice = !empty($unitData['retail_price']) ? $unitData['retail_price'] : 
                                       (!empty($unitData['wholesale_price']) ? $unitData['wholesale_price'] : $baseUnitPrice);
                        break;
                    }
                }
            }
            
            // Update or create base unit ProductUnit
            // First, check if there's an existing base unit
            $oldBaseUnit = ProductUnit::where('product_id', $product->id)
                ->where('is_base_unit', true)
                ->first();
            
            // Check if the new base unit already exists as a non-base ProductUnit
            $existingProductUnit = ProductUnit::where('product_id', $product->id)
                ->where('unit_id', $baseUnitId)
                ->first();
            
            if ($existingProductUnit) {
                // If the new base unit already exists, convert it to base unit
                if ($existingProductUnit->is_base_unit) {
                    // It's already the base unit, just update the price
                    $existingProductUnit->update([
                        'selling_price' => $baseUnitPrice ? round((float)$baseUnitPrice, 2) : null,
                    ]);
                } else {
                    // Convert existing non-base unit to base unit
                    // First, if there's an old base unit and it's different, convert it to non-base
                    if ($oldBaseUnit && $oldBaseUnit->id != $existingProductUnit->id) {
                        $oldBaseUnit->update([
                            'is_base_unit' => false,
                        ]);
                    }
                    // Now convert the existing unit to base unit
                    $existingProductUnit->update([
                        'is_base_unit' => true,
                        'selling_price' => $baseUnitPrice ? round((float)$baseUnitPrice, 2) : null,
                        'is_active' => true,
                    ]);
                }
            } else {
                // New base unit doesn't exist, create or update
                if ($oldBaseUnit) {
                    // Update existing base unit to new unit
                    // If old base unit's unit_id is different, we need to handle it
                    if ($oldBaseUnit->unit_id != $baseUnitId) {
                        // Convert old base unit to non-base
                        $oldBaseUnit->update([
                            'is_base_unit' => false,
                        ]);
                        // Create new base unit
                        ProductUnit::create([
                            'product_id' => $product->id,
                            'unit_id' => $baseUnitId,
                            'is_base_unit' => true,
                            'selling_price' => $baseUnitPrice ? round((float)$baseUnitPrice, 2) : null,
                            'is_active' => true,
                        ]);
                    } else {
                        // Same unit, just update price
                        $oldBaseUnit->update([
                            'selling_price' => $baseUnitPrice ? round((float)$baseUnitPrice, 2) : null,
                        ]);
                    }
                } else {
                    // No base unit exists, create one
                    ProductUnit::create([
                        'product_id' => $product->id,
                        'unit_id' => $baseUnitId,
                        'is_base_unit' => true,
                        'selling_price' => $baseUnitPrice ? round((float)$baseUnitPrice, 2) : null,
                        'is_active' => true,
                    ]);
                }
            }
            
            // Handle other selling units
            // Track processed units (always include base unit and old base unit if it was converted)
            $processedUnitIds = [$baseUnitId];
            if ($oldBaseUnit && $oldBaseUnit->unit_id != $baseUnitId) {
                // Old base unit was converted to selling unit, add it to processed list
                $processedUnitIds[] = $oldBaseUnit->unit_id;
            }
            
            if ($request->has('units') && is_array($request->units)) {
                
                foreach ($request->input('units') as $unitData) {
                    // Skip if empty unit_id
                    if (empty($unitData['unit_id'])) {
                        continue;
                    }
                    
                    // Check if this is the base unit (can be boolean true, string "1", or integer 1)
                    $isBaseUnit = !empty($unitData['is_base_unit']) && 
                                 ($unitData['is_base_unit'] === true || 
                                  $unitData['is_base_unit'] === '1' || 
                                  $unitData['is_base_unit'] === 1 ||
                                  $unitData['is_base_unit'] === 'true');
                    
                    // Skip base unit (already handled) or if unit_id matches base unit
                    if ($isBaseUnit || $unitData['unit_id'] == $baseUnitId) {
                        continue;
                    }
                    
                    $unitId = $unitData['unit_id'];
                    $processedUnitIds[] = $unitId;
                    
                    // Check if ProductUnit exists
                    $productUnit = ProductUnit::where('product_id', $product->id)
                        ->where('unit_id', $unitId)
                        ->first();
                    
                    if ($productUnit) {
                        // Update existing
                        $productUnit->update([
                            'selling_price' => !empty($unitData['selling_price']) && $unitData['selling_price'] > 0 
                                ? round((float)$unitData['selling_price'], 2) 
                                : null,
                            'is_active' => true,
                        ]);
                    } else {
                        // Create new
                        ProductUnit::create([
                            'product_id' => $product->id,
                            'unit_id' => $unitId,
                            'is_base_unit' => false,
                            'selling_price' => !empty($unitData['selling_price']) && $unitData['selling_price'] > 0 
                                ? round((float)$unitData['selling_price'], 2) 
                                : null,
                            'is_active' => true,
                        ]);
                    }
                }
                
                // Deactivate units that were removed
                ProductUnit::where('product_id', $product->id)
                    ->where('is_base_unit', false)
                    ->whereNotIn('unit_id', $processedUnitIds)
                    ->update(['is_active' => false]);
            }
            
            // Update UnitConversion records
            $processedConversionIds = [];
            $conversionErrors = [];
            $conversionsSaved = 0;
            $normalizedConversions = $this->extractConversionsFromRequest($request);

            \Log::info('Updating conversions for product', [
                'product_id' => $product->id,
                'has_conversions' => $request->has('conversions'),
                'conversions_count' => count($normalizedConversions),
                'conversions_data' => $normalizedConversions,
            ]);

            if (!empty($normalizedConversions)) {
                foreach ($normalizedConversions as $index => $conv) {
                    // Skip if any required field is empty
                    if (empty($conv['from_unit_id']) || empty($conv['to_unit_id']) || empty($conv['factor'])) {
                        \Log::warning('Skipping incomplete conversion (update)', ['index' => $index, 'data' => $conv]);
                        continue;
                    }

                    // Sanity-check + round factor to 2 decimal places
                    $factor = is_numeric($conv['factor']) ? (float) $conv['factor'] : null;
                    if (!$factor || $factor <= 0) {
                        \Log::warning('Skipping conversion with invalid factor (update)', ['index' => $index, 'factor' => $conv['factor']]);
                        continue;
                    }
                    $factor = round($factor, 2);
                    if ($factor < 0.01) {
                        \Log::warning('Skipping conversion with factor < 0.01 (update)', ['index' => $index, 'factor' => $factor]);
                        continue;
                    }

                    // Reject from == to (defense in depth even if validator already covers it)
                    if ((int) $conv['from_unit_id'] === (int) $conv['to_unit_id']) {
                        \Log::warning('Skipping conversion with same from/to unit (update)', ['index' => $index, 'data' => $conv]);
                        continue;
                    }

                    try {
                        if (isset($conv['id']) && !empty($conv['id'])) {
                            // Update existing conversion
                            $existing = UnitConversion::find($conv['id']);
                            if ($existing && $existing->product_id == $product->id) {
                                // If from/to changed, ensure no other active conversion of this
                                // product already uses the new pair (would violate the
                                // (product_id, from_unit_id, to_unit_id) unique index).
                                $pairChanged = (int) $existing->from_unit_id !== (int) $conv['from_unit_id']
                                    || (int) $existing->to_unit_id !== (int) $conv['to_unit_id'];

                                if ($pairChanged) {
                                    $clash = UnitConversion::where('product_id', $product->id)
                                        ->where('from_unit_id', $conv['from_unit_id'])
                                        ->where('to_unit_id', $conv['to_unit_id'])
                                        ->where('id', '!=', $existing->id)
                                        ->first();
                                    if ($clash) {
                                        $msg = "Conversion #{$index}: another conversion already exists for from={$conv['from_unit_id']} -> to={$conv['to_unit_id']} on this product.";
                                        \Log::warning($msg, ['existing_id' => $existing->id, 'clash_id' => $clash->id]);
                                        $conversionErrors[] = $msg;
                                        // Keep the original record untouched and skip this row
                                        $processedConversionIds[] = $existing->id;
                                        continue;
                                    }
                                }

                                $existing->update([
                                    'from_unit_id' => $conv['from_unit_id'],
                                    'to_unit_id' => $conv['to_unit_id'],
                                    'conversion_factor' => $factor,
                                    'is_active' => true,
                                ]);
                                $processedConversionIds[] = $existing->id;
                                $conversionsSaved++;
                            }
                        } else {
                            // Create new conversion (product-specific)
                            $existing = UnitConversion::where('product_id', $product->id)
                                ->where('from_unit_id', $conv['from_unit_id'])
                                ->where('to_unit_id', $conv['to_unit_id'])
                                ->first();

                            if (!$existing) {
                                $newConversion = UnitConversion::create([
                                    'product_id' => $product->id,
                                    'from_unit_id' => $conv['from_unit_id'],
                                    'to_unit_id' => $conv['to_unit_id'],
                                    'conversion_factor' => $factor,
                                    'is_active' => true,
                                ]);
                                $processedConversionIds[] = $newConversion->id;
                                $conversionsSaved++;
                            } else {
                                $existing->update([
                                    'conversion_factor' => $factor,
                                    'is_active' => true,
                                ]);
                                $processedConversionIds[] = $existing->id;
                                $conversionsSaved++;
                            }
                        }
                        
                        // Ensure ProductUnits exist for both units in the conversion
                        // Check if from_unit ProductUnit exists
                        $fromUnitProductUnit = ProductUnit::where('product_id', $product->id)
                            ->where('unit_id', $conv['from_unit_id'])
                            ->first();
                        if (!$fromUnitProductUnit && $conv['from_unit_id'] != $baseUnitId) {
                            ProductUnit::create([
                                'product_id' => $product->id,
                                'unit_id' => $conv['from_unit_id'],
                                'is_base_unit' => false,
                                'selling_price' => null, // Will be calculated from base unit
                                'is_active' => true,
                            ]);
                            // Add to processed units so it doesn't get deactivated
                            if (!in_array($conv['from_unit_id'], $processedUnitIds)) {
                                $processedUnitIds[] = $conv['from_unit_id'];
                            }
                        } elseif ($fromUnitProductUnit && !in_array($conv['from_unit_id'], $processedUnitIds)) {
                            // Reactivate if it was deactivated
                            $fromUnitProductUnit->update(['is_active' => true]);
                            $processedUnitIds[] = $conv['from_unit_id'];
                        }
                        
                        // Check if to_unit ProductUnit exists
                        $toUnitProductUnit = ProductUnit::where('product_id', $product->id)
                            ->where('unit_id', $conv['to_unit_id'])
                            ->first();
                        if (!$toUnitProductUnit && $conv['to_unit_id'] != $baseUnitId) {
                            ProductUnit::create([
                                'product_id' => $product->id,
                                'unit_id' => $conv['to_unit_id'],
                                'is_base_unit' => false,
                                'selling_price' => null, // Will be calculated from base unit
                                'is_active' => true,
                            ]);
                            // Add to processed units so it doesn't get deactivated
                            if (!in_array($conv['to_unit_id'], $processedUnitIds)) {
                                $processedUnitIds[] = $conv['to_unit_id'];
                            }
                        } elseif ($toUnitProductUnit && !in_array($conv['to_unit_id'], $processedUnitIds)) {
                            // Reactivate if it was deactivated
                            $toUnitProductUnit->update(['is_active' => true]);
                            $processedUnitIds[] = $conv['to_unit_id'];
                        }
                    } catch (\Exception $e) {
                        // Log error but don't fail the entire product update
                        $errorMsg = "Error updating conversion #{$index}: " . $e->getMessage();
                        \Log::error($errorMsg, [
                            'conversion_data' => $conv,
                            'index' => $index,
                            'product_id' => $product->id,
                            'trace' => $e->getTraceAsString()
                        ]);
                        $conversionErrors[] = $errorMsg;
                        // Continue with other conversions
                    }
                }
            }
            
            // Build the explicit "keep" set: every conversion id the user kept in the
            // submitted form, plus every id we just created/updated. Any other
            // conversion belonging to this product is something the user removed.
            //
            // CAUTION: Eloquent's whereNotIn('id', []) compiles to "1 = 1" (matches
            // every row). If we passed an empty array here we'd silently deactivate
            // every conversion for this product whenever an error blocked the save -
            // that was the source of "kabhi save hota ha kabhi nhi" complaints.
            $submittedConversionIds = collect($request->input('conversions', []))
                ->filter(fn ($c) => is_array($c) && !empty($c['id']))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $keepConversionIds = array_values(array_unique(array_merge(
                $submittedConversionIds,
                $processedConversionIds
            )));

            if (!empty($keepConversionIds)) {
                UnitConversion::where('product_id', $product->id)
                    ->whereNotIn('id', $keepConversionIds)
                    ->update(['is_active' => false]);
            }

            \Log::info('Conversion update completed', [
                'product_id' => $product->id,
                'conversions_saved' => $conversionsSaved,
                'errors' => count($conversionErrors),
                'kept_ids' => $keepConversionIds,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Error updating product: ' . $e->getMessage()])->withInput();
        }

        $redirectMessage = 'Product updated successfully.';
        if (isset($conversionsSaved) && $conversionsSaved > 0) {
            $redirectMessage .= " {$conversionsSaved} conversion(s) saved successfully.";
        }

        // If any conversion failed we keep the user on the edit page so they
        // can actually see and fix the row that did not save. Sending them to
        // the index with a tiny appended sentence was easy to miss and was the
        // reason changes seemed to "kabhi save hota ha kabhi nhi".
        if (!empty($conversionErrors)) {
            return redirect()->route('products.edit', $product)
                ->with('warning', 'Product saved, but some conversion factors could not be saved.')
                ->with('conversion_errors', $conversionErrors)
                ->with('conversions_created', $conversionsSaved ?? 0);
        }

        return redirect()->route('products.index')
            ->with('success', $redirectMessage)
            ->with('conversion_errors', [])
            ->with('conversions_created', $conversionsSaved ?? 0);
    }

    public function destroy(Product $product)
    {
        // Delete image if exists
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function lowStocks(Request $request)
    {
        $tab = $request->get('tab', 'low-stocks'); // 'low-stocks' or 'out-of-stocks'
        $branchId = CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID;

        $query = Product::with('category', 'unit', 'createdBy', 'currentBranchStock')
            ->leftJoin('branch_product_stocks', function ($join) use ($branchId) {
                $join->on('branch_product_stocks.product_id', '=', 'products.id')
                    ->where('branch_product_stocks.branch_id', '=', $branchId);
            })
            ->select('products.*', DB::raw('COALESCE(branch_product_stocks.stock_quantity, 0) as branch_stock_quantity'));

        // Apply tab filter against per-branch stock
        if ($tab === 'out-of-stocks') {
            $query->whereRaw('COALESCE(branch_product_stocks.stock_quantity, 0) = 0');
        } else {
            $query->whereRaw('COALESCE(branch_product_stocks.stock_quantity, 0) <= products.low_stock_threshold')
                  ->whereRaw('COALESCE(branch_product_stocks.stock_quantity, 0) > 0');
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%")
                  ->orWhere('products.brand', 'like', "%{$search}%");
            });
        }

        // Brand filter
        if ($request->filled('brand') && $request->brand !== 'all') {
            $query->where('products.brand', $request->brand);
        }

        // Category filter
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('products.category_id', $request->category_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'stock_quantity');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'sku') {
            $query->orderBy('products.sku', $sortOrder);
        } elseif ($sortBy === 'stock_quantity') {
            $query->orderBy('branch_stock_quantity', $sortOrder);
        } else {
            $query->orderBy('products.' . $sortBy, $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 10))
            ->appends($request->query());

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand')
            ->sort()
            ->values();

        return view('products.low-stocks', compact('products', 'categories', 'brands', 'tab'));
    }

    /**
     * Get all products for API (used in POS popup)
     */
    public function getAllProducts(Request $request)
    {
        $query = Product::where('is_active', true)->with('category', 'unit', 'currentBranchStock');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->get()->map(function($p) {
            // Get base unit
            $baseUnit = $p->base_unit_id ?? $p->unit_id;
            $baseUnitName = $p->baseUnit ? $p->baseUnit->short_name : ($p->unit ? $p->unit->short_name : '');
            
            // Get all selling units with prices
            $sellingUnits = $p->productUnits()->active()->with('unit')->get()->map(function($pu) {
                return [
                    'unit_id' => $pu->unit_id,
                    'unit_name' => $pu->unit->short_name ?? '',
                    'is_base_unit' => $pu->is_base_unit,
                    'selling_price' => $pu->selling_price ?? 0,
                ];
            })->toArray();
            
            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'brand' => $p->brand ?? '',
                'purchase_price' => $p->purchase_price,
                'selling_price' => $p->selling_price,
                'retail_price' => $p->retail_price ?? $p->selling_price,
                'wholesale_price' => $p->wholesale_price ?? $p->selling_price,
                'selling_type' => $p->selling_type ?? 'retail',
                'stock_quantity' => $p->stock_quantity,
                'unit_id' => $baseUnit,
                'unit_name' => $baseUnitName,
                'base_unit_id' => $baseUnit,
                'selling_units' => $sellingUnits, // Array of units with prices
                'category_id' => $p->category_id,
                'category_name' => $p->category ? $p->category->name : '',
                'image' => $p->image ? asset('storage/' . $p->image) : null,
            ];
        });

        return response()->json(['success' => true, 'products' => $products]);
    }

    /**
     * Get product units with prices
     */
    public function getProductUnits(Product $product)
    {
        $productUnits = $product->productUnits()->active()->with('unit')->get()->map(function($pu) {
            return [
                'unit_id' => $pu->unit_id,
                'unit_name' => $pu->unit->name ?? '',
                'unit_short_name' => $pu->unit->short_name ?? '',
                'is_base_unit' => $pu->is_base_unit,
                'selling_price' => $pu->selling_price ?? 0,
            ];
        });
        
        // If no ProductUnit records, return default from product
        if ($productUnits->isEmpty() && $product->unit) {
            $productUnits = collect([[
                'unit_id' => $product->unit_id,
                'unit_name' => $product->unit->name ?? '',
                'unit_short_name' => $product->unit->short_name ?? '',
                'is_base_unit' => true,
                'selling_price' => $product->selling_price ?? 0,
            ]]);
        }
        
        return response()->json([
            'success' => true,
            'units' => $productUnits,
            'base_unit_id' => $product->base_unit_id ?? $product->unit_id,
        ]);
    }
    
    /**
     * Get conversion factor between two units
     */
    public function getConversion(Product $product, $fromUnit, $toUnit)
    {
        $service = app(\App\Services\UnitConversionService::class);
        
        try {
            $factor = $service->convert(1, (int) $fromUnit, (int) $toUnit, (int) $product->id);
            return response()->json([
                'success' => true,
                'conversion_factor' => $factor,
                'from_unit_id' => $fromUnit,
                'to_unit_id' => $toUnit,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Generate a unique SKU for the product
     */
    private function generateSku($productName = null)
    {
        // Get initials from product name if provided
        $prefix = 'PRD';
        if ($productName) {
            $words = explode(' ', strtoupper($productName));
            $initials = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= substr($word, 0, 1);
                }
            }
            if (strlen($initials) >= 2) {
                $prefix = substr($initials, 0, 3);
            }
        }

        // Generate date part (YYYYMMDD)
        $datePart = date('Ymd');

        // Generate random number part (4 digits)
        $randomPart = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Combine: PREFIX-YYYYMMDD-XXXX
        $sku = $prefix . '-' . $datePart . '-' . $randomPart;

        // Check if SKU already exists, if so, regenerate
        $counter = 1;
        while (Product::where('sku', $sku)->exists()) {
            $randomPart = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $sku = $prefix . '-' . $datePart . '-' . $randomPart;
            $counter++;
            // Prevent infinite loop
            if ($counter > 100) {
                $sku = $prefix . '-' . $datePart . '-' . time();
                break;
            }
        }

        return $sku;
    }

    /**
     * Normalize conversions payload from nested arrays or flat form keys.
     */
    private function extractConversionsFromRequest(Request $request): array
    {
        $normalized = [];
        $payload = $request->input('conversions', []);

        if (is_array($payload)) {
            foreach ($payload as $index => $conv) {
                if (!is_array($conv)) {
                    continue;
                }
                $normalized[$index] = [
                    'id' => $conv['id'] ?? null,
                    'from_unit_id' => $conv['from_unit_id'] ?? null,
                    'to_unit_id' => $conv['to_unit_id'] ?? null,
                    'factor' => $conv['factor'] ?? ($conv['conversion_factor'] ?? null),
                ];
            }
        }

        // Fallback for flat keys like conversions[0][factor]
        foreach ($request->all() as $key => $value) {
            if (!is_string($key) || !preg_match('/^conversions\[(\d+)\]\[(from_unit_id|to_unit_id|factor|conversion_factor|id)\]$/', $key, $m)) {
                continue;
            }
            $idx = (int) $m[1];
            $field = $m[2] === 'conversion_factor' ? 'factor' : $m[2];
            if (!isset($normalized[$idx])) {
                $normalized[$idx] = [
                    'id' => null,
                    'from_unit_id' => null,
                    'to_unit_id' => null,
                    'factor' => null,
                ];
            }
            $normalized[$idx][$field] = $value;
        }

        ksort($normalized);

        // Final cleanup and casting
        return collect($normalized)
            ->map(function (array $conv) {
                $from = isset($conv['from_unit_id']) ? (int) $conv['from_unit_id'] : null;
                $to = isset($conv['to_unit_id']) ? (int) $conv['to_unit_id'] : null;
                $factor = isset($conv['factor']) && $conv['factor'] !== '' ? (float) $conv['factor'] : null;
                $id = isset($conv['id']) && $conv['id'] !== '' ? (int) $conv['id'] : null;

                return [
                    'id' => $id,
                    'from_unit_id' => $from,
                    'to_unit_id' => $to,
                    'factor' => $factor,
                ];
            })
            ->filter(function (array $conv) {
                return !empty($conv['from_unit_id']) && !empty($conv['to_unit_id']) && !empty($conv['factor']);
            })
            ->values()
            ->all();
    }
}
