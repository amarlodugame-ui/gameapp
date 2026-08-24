<?php

namespace App\Lib;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use Illuminate\Http\Client\PendingRequest;

class StargoHelper
{

     function host(): string
    {
        return rtrim((string)  env('STARPAGO_HOST'), '/');
    }

     function appId(): string
    {
        return (string) env('STARPAGO_APP_ID');
    }

     function appSecret(): string
    {
        return (string) env('STARPAGO_APP_SECRET');
    }

     function notifyUrl(): string
    {
        return (string) env('STARPAGO_NOTIFY_URL');
    }


function http(): PendingRequest
{
    return Http::timeout(15)
        ->acceptJson()
        ->withHeaders([
            'Content-Type' => 'application/json;charset=utf-8',
        ])
        ->asJson()
        ->retry(2, 200);
}

     function newMerOrderNo(): string
    {
        return (string) 'RC' . now()->format('YmdHis') . random_int(100000, 999999) . Str::lower(Str::random(1));
    }

     function normalize(array $data): array
    {
        $clean = [];
        foreach ($data as $k => $v) {
            if ($v === null || $v === '') continue;
            if (is_array($v)) {
                if ($k === 'extra') $clean[$k] = $this->normalize($v);
                else $clean[$k] = $v;
            } else {
                $clean[$k] = (string) $v;
            }
        }
        return $clean;
    }

     function buildPairs(array $data): array
    {
        ksort($data, SORT_STRING);

        $pairs = [];
        foreach ($data as $k => $v) {
            if (is_array($v) && $k === 'extra') {
                ksort($v, SORT_STRING);
                $extraParts = [];
                foreach ($v as $ek => $ev) {
                    if ($ev === null || $ev === '') continue;
                    $extraParts[] = $ek . '=' . (string) $ev;
                }
                $pairs[] = 'extra=' . implode('&', $extraParts);
            } elseif (!is_array($v)) {
                $pairs[] = $k . '=' . $v;
            }
        }

        return $pairs;
    }

     function sign(array $data): string
    {
        $data = $this->normalize($data);
        $pairs = $this->buildPairs($data);
        $pairs[] = 'key=' . $this->appSecret();
        return hash('sha256', implode('&', $pairs));
    }

     function verify(array $data): bool
    {
        $sign = (string) ($data['sign'] ?? '');
        unset($data['sign']);
        $expected = $this->sign($data);
        return hash_equals($expected, $sign);
    }

     function payMethodFromUi(string $method): string
    {
        $m = mb_strtolower(trim($method));
        if ($m === 'nagad') return 'BD_NAGAD';
        if ($m === 'bkash') return 'BD_BKASH';
        return '';
    }

}