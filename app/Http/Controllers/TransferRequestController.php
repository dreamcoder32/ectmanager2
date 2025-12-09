<?php

namespace App\Http\Controllers;

use App\Models\TransferRequest;
use App\Models\Recolte;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TransferRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = TransferRequest::with(['supervisor', 'admin', 'recoltes.expenses', 'recoltes.collections'])
            ->orderBy('created_at', 'desc');

        $query->where(function ($q) use ($user) {
            $q->where('supervisor_id', $user->id)
                ->orWhere('admin_id', $user->id);
        });

        $transfers = $query->paginate(20);

        $transfers->getCollection()->transform(function ($transfer) use ($user) {
            if ($user->id === $transfer->admin_id) {
                $transfer->makeVisible('verification_code');
            }
            return $transfer;
        });

        return Inertia::render('TransferRequest/Index', [
            'transfers' => $transfers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
            'recolte_ids' => 'required|array',
            'recolte_ids.*' => 'exists:recoltes,id',
        ]);

        try {
            DB::beginTransaction();

            $recoltes = Recolte::whereIn('id', $request->recolte_ids)
                ->whereNull('transfer_request_id') // Ensure not already transferred
                ->with(['expenses', 'collections'])
                ->get();

            if ($recoltes->isEmpty()) {
                return back()->withErrors(['message' => 'No valid recoltes selected.']);
            }

            // Generate 6 digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $transfer = TransferRequest::create([
                'supervisor_id' => Auth::id(),
                'admin_id' => $request->admin_id,
                'status' => 'pending',
                'verification_code' => $code,
            ]);

            Recolte::whereIn('id', $recoltes->pluck('id'))->update([
                'transfer_request_id' => $transfer->id
            ]);

            DB::commit();

            return back()->with([
                'success' => 'Transfer request created successfully.',
                'new_transfer_id' => $transfer->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['message' => 'Error creating transfer request: ' . $e->getMessage()]);
        }
    }

    public function show(TransferRequest $transferRequest)
    {
        $user = Auth::user();

        // Authorization check
        if ($user->id !== $transferRequest->supervisor_id && $user->id !== $transferRequest->admin_id && !$user->isAdmin()) {
            abort(403);
        }

        $transferRequest->load(['recoltes.expenses', 'recoltes.collections', 'supervisor', 'admin']);

        // Only Admin (receiver) can see the code initially? 
        // Or maybe we hide it until they click "Approve"?
        // For now, let's pass it. The frontend can handle visibility/masking.
        // Actually, user said "supervisor cant see it". So we should NOT send it to supervisor.

        $canSeeCode = $user->id === $transferRequest->admin_id;

        return Inertia::render('TransferRequest/Show', [
            'transfer' => $transferRequest,
            'canSeeCode' => $canSeeCode,
            'verificationCode' => $canSeeCode ? $transferRequest->verification_code : null
        ]);
    }

    public function verify(Request $request, TransferRequest $transferRequest)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        if ($transferRequest->status !== 'pending') {
            return back()->withErrors(['message' => 'Transfer is not pending.']);
        }

        if ($request->code === $transferRequest->verification_code) {
            $transferRequest->update(['status' => 'success']);
            return back()->with('success', 'Transfer verified successfully.');
        }

        return back()->withErrors(['code' => 'Invalid verification code.']);
    }
}
