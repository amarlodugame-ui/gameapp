<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GiftCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GiftCodeController extends Controller
{

public function index(Request $request)
{
    $pageTitle = "Gift Code";

    $q = GiftCode::with(['redemptions.user']);

    if ($request->filled('code')) {
        $q->where('code','like','%'.$request->code.'%');
    }

    if ($request->filled('is_used')) {
        $q->where('is_used',(int)$request->is_used);
    }

    $giftCodes = $q->latest()
        ->paginate(20)
        ->appends($request->only('code','is_used'));

    return view('admin.gift_code.index', compact('giftCodes','pageTitle'));
}


public function generate(Request $request)
{
    $data = $request->validate([
        'count' => ['required','integer','min:1','max:5000'],
        'max_users' => ['required','integer','min:1'],
        'reward_amount' => ['required','numeric','min:0'],
        'remarks' => ['required'],
    ]);

    $rows = [];
    $codes = [];
    $now = now();

    for ($i=0; $i < $data['count']; $i++) {
        do{
            $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $numbers = '0123456789';
            $code = '';
            
            for ($i = 0; $i < 15; $i++) {
                $code .= $numbers[random_int(0, 9)];
            }
            $all = $letters . $numbers;
            for ($i = 0; $i < 12; $i++) {
                $code .= $all[random_int(0, strlen($all) - 1)];
            }
            $code = str_shuffle($code);
        }while(GiftCode::where('code',$code)->exists());

        $codes[] = $code;

        $rows[] = [
            'code'=>$code,
            'reward_amount'=>$data['reward_amount'],
            'remarks'=>$data['remarks'],
            'max_users'=>$data['max_users'],
            'used_count'=>0,
            'is_used'=>0,
            'last_used_at'=>null,
            'created_at'=>$now,
            'updated_at'=>$now,
        ];
    }

    GiftCode::insert($rows);

    return redirect()->route('admin.gift.index')->with('success','Gift codes generated')->with('generated_codes',$codes);
}


public function update(Request $request, GiftCode $giftCode)
{
    $data = $request->validate([
        'code'=>['required','max:60','unique:gift_codes,code,'.$giftCode->id],
        'reward_amount'=>['required','numeric','min:0'],
        'max_users'=>['required','integer','min:1'],
        'remarks'=>['required']
    ]);

    if($giftCode->is_used == 1){
        return back()->with('error','This code already locked');
    }

    $giftCode->update([
        'code'=>strtoupper($data['code']),
        'reward_amount'=>$data['reward_amount'],
        'max_users'=>$data['max_users'],
        'remarks'=>$data['remarks']
    ]);

    return redirect()->route('admin.gift.index')->with('success','Gift updated');
}

}