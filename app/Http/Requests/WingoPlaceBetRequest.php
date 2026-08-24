<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WingoPlaceBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'round_id' => ['required','integer','exists:game_rounds,id'],
            'bet_type' => ['required','in:color,number,bigsmall'],
            'bet_value' => ['required','string','max:10'],
            'amount' => ['required','numeric','min:1'],
            'qty' => ['required','integer','min:1','max:1000'],
        ];
    }

    public function withValidator(Validator $v): void
    {
        $v->after(function (Validator $v) {
            $type = $this->input('bet_type');
            $value = $this->input('bet_value');

            if ($type === 'color' && !in_array($value, ['red','green','violet'], true)) {
                $v->errors()->add('bet_value', 'Invalid color');
            }

            if ($type === 'bigsmall' && !in_array($value, ['big','small'], true)) {
                $v->errors()->add('bet_value', 'Invalid big/small');
            }

            if ($type === 'number') {
                if (!ctype_digit((string)$value) || (int)$value < 0 || (int)$value > 9) {
                    $v->errors()->add('bet_value', 'Invalid number');
                }
            }
        });
    }
}
