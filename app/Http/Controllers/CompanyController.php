<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CompanyController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display a listing of companies
     */
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 15), 100);
        $companies = $query->orderBy('name')->paginate($perPage);

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new company
     */
    public function create()
    {
        return Inertia::render('Companies/Create');
    }

    /**
     * Store a newly created company
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'commission' => 'nullable|numeric|min:0|max:100',
            'whatsapp_api_key' => 'nullable|string|max:255',
        ]);

        $company = Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company created successfully.');
    }

    /**
     * Display the specified company
     */
    public function show(Company $company)
    {
        $company->load(['parcels', 'margins']);
        
        // Get WhatsApp configuration status
        $whatsappStatus = $this->whatsappService->isCompanyConfigured($company);
        
        return Inertia::render('Companies/Show', [
            'company' => $company,
            'whatsappStatus' => $whatsappStatus,
        ]);
    }

    /**
     * Show the form for editing the specified company
     */
    public function edit(Company $company)
    {
        return Inertia::render('Companies/Edit', [
            'company' => $company,
        ]);
    }

    /**
     * Update the specified company
     */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code,' . $company->id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'commission' => 'nullable|numeric|min:0|max:100',
            'whatsapp_api_key' => 'nullable|string|max:255',
        ]);

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Company updated successfully.');
    }

    /**
     * Test WhatsApp API connection for a company
     */
    public function testWhatsAppConnection(Request $request, Company $company)
    {
        // If API key is provided in request, use it temporarily for testing
        if ($request->has('whatsapp_api_key')) {
            $tempCompany = clone $company;
            $tempCompany->whatsapp_api_key = $request->whatsapp_api_key;
            $result = $this->whatsappService->testConnection($tempCompany);
        } else {
            $result = $this->whatsappService->testConnection($company);
        }

        return response()->json($result);
    }

    /**
     * Update WhatsApp API key for a company
     */
    public function updateWhatsAppApiKey(Request $request, Company $company)
    {
        $validator = Validator::make($request->all(), [
            'whatsapp_api_key' => 'required|string|max:255',
            'whatsapp_desk_pickup_template' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = ['whatsapp_api_key' => $request->whatsapp_api_key];
        
        if ($request->has('whatsapp_desk_pickup_template')) {
            $updateData['whatsapp_desk_pickup_template'] = $request->whatsapp_desk_pickup_template;
        }

        $company->update($updateData);

        // Test the connection
        $testResult = $this->whatsappService->testConnection($company);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp API key updated successfully',
            'test_result' => $testResult
        ]);
    }

    /**
     * Get companies with WhatsApp status
     */
    public function getWhatsAppStatus()
    {
        $companies = Company::active()->get()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'is_configured' => $this->whatsappService->isCompanyConfigured($company),
                'has_api_key' => !empty($company->whatsapp_api_key)
            ];
        });

        return response()->json([
            'success' => true,
            'companies' => $companies
        ]);
    }
}