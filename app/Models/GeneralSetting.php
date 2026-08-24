<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
        protected $fillable = [
        'site_name',
        'cur_text',
        'cur_sym',
        'maintenance_mode',
        'registration',
        'signup_bonus_amount'
    ];
    
    
        public static function changeStatus($id, $column = 'status')
    {
        $modelName = get_class();
        $query     = $modelName::findOrFail($id);
        if ($query->$column == 1) {
            $query->$column = 0;
        } else {
            $query->$column = 1;
        }
        $message       = keyToTitle($column). ' changed successfully';

        $query->save();
        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }
}
