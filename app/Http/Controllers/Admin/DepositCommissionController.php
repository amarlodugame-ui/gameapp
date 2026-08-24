<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepositCommission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositCommissionController extends Controller
{
    public function index()
    {
        $pageTitle = 'Deposit Commission Settings';
        $commissions = DepositCommission::query()
            ->orderBy('deposit_amount')
            ->get();

        return view('admin.deposit_commissions.index', compact('pageTitle', 'commissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'deposit_amount' => ['required', 'numeric', 'min:1', 'unique:deposit_commissions,deposit_amount'],
            'commission_percent' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        DepositCommission::create($data);

        $notify[] = ['success', 'Deposit commission created successfully.'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $commission = DepositCommission::findOrFail($id);

        $data = $request->validate([
            'deposit_amount' => [
                'required',
                'numeric',
                'min:1',
                Rule::unique('deposit_commissions', 'deposit_amount')->ignore($commission->id),
            ],
            'commission_percent' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $commission->update($data);

        $notify[] = ['success', 'Deposit commission updated successfully.'];
        return back()->withNotify($notify);
    }

    public function destroy($id)
    {
        $commission = DepositCommission::findOrFail($id);
        $commission->delete();

       $notify[] = ['success', 'Deposit commission deleted successfully.'];
        return back()->withNotify($notify);
    }

    public function toggle($id)
    {
        $commission = DepositCommission::findOrFail($id);
        $commission->is_active = !$commission->is_active;
        $commission->save();

        $notify[] = ['success', 'Deposit commission ' . ($commission->is_active ? 'activated' : 'deactivated') . ' successfully.'];
        return back()->withNotify($notify);
    }
}