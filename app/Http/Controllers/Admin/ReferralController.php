<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{


    public function depindex()
    {
         $pageTitle = 'Deposit Method';
        $commissions = DB::table('deposit_method_commissions')->orderBy('id', 'asc')->get();

        return view('admin.refferal.depositcom', compact('commissions','pageTitle'));
    }
public function depupdate(Request $request)
{
    $request->validate([
        'id' => ['required', 'integer', 'exists:deposit_method_commissions,id'],
        'is_active' => ['required', 'in:0,1'],
    ]);

    DB::table('deposit_method_commissions')
        ->where('id', $request->id)
        ->update([
            'is_active' => $request->is_active,
            'updated_at' => now(),
        ]);

        
       $notify[] = ['success', 'Method status updated successfully.'];
        return back()->withNotify($notify);
        
}
    // public function depupdate(Request $request)
    // {
    //     $request->validate([
    //         'id' => ['required', 'integer', 'exists:deposit_method_commissions,id'],
    //         'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
    //         'is_active' => ['required', 'in:0,1'],
    //     ]);

    //     DB::table('deposit_method_commissions')
    //         ->where('id', $request->id)
    //         ->update([
    //             'commission_percent' => $request->commission_percent,
    //             'is_active' => $request->is_active,
    //             'updated_at' => now(),
    //         ]);

    //     return redirect()
    //         ->back()
    //         ->with('success', 'Commission updated successfully.');
    // }






public function index()
{
    $pageTitle = 'Manage Referral';
    $referrals = Referral::get();

    $commissionTypes = [
        'deposit_commission' => 'Deposit Commission',
    ];

    $depositCommissions = DB::table('deposit_method_commissions')->get();

    return view('admin.refferal.index', compact(
        'pageTitle',
        'referrals',
        'commissionTypes',
        'depositCommissions'
    ));
}


    public function status($type)
    {
        return GeneralSetting::changeStatus(1, $type);
    }

    public function update(Request $request)
    {
        $request->validate([
            'percent'         => 'required',
            'percent*'        => 'required|numeric',
            'commission_type' => 'required|in:deposit_commission',
        ]);
        $type = $request->commission_type;

        Referral::where('commission_type', $type)->delete();

        for ($i = 0; $i < count($request->percent); $i++) {
            $referral                  = new Referral();
            $referral->level           = $i + 1;
            $referral->percent         = $request->percent[$i];
            $referral->commission_type = $request->commission_type;
            $referral->save();
        }
        return back()->with('success', 'Referral commission setting updated successfully');
    }
}
