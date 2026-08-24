<?php

namespace App\Http\Responses;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class UserSpaPage
{
    /**
     * Return a framework-neutral page envelope to the User SPA while keeping
     * the original Inertia response available to legacy callers.
     *
     * @param  array<string, mixed>|Arrayable<string, mixed>  $props
     */
    public static function render(
        string $component,
        array|Arrayable $props = [],
    ): JsonResponse|InertiaResponse {
        $props = $props instanceof Arrayable ? $props->toArray() : $props;
        $request = request();

        if ($request->isMethod('GET') && $request->header('X-User-Spa') === '1') {
            return response()->json([
                'component' => $component,
                'props' => array_merge(self::sharedProps($request), $props),
                'url' => self::relativeUrl($request),
                'version' => app(HandleInertiaRequests::class)->version($request),
            ])->withHeaders([
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        return Inertia::render($component, $props);
    }

    /**
     * Keep this contract aligned with HandleInertiaRequests::share().
     *
     * @return array<string, mixed>
     */
    private static function sharedProps(Request $request): array
    {
        return [
            'errors' => self::validationErrors($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'image', 'status']),
            ],
            'flash' => [
                'toast' => $request->session()->get('toast'),
            ],
        ];
    }

    private static function validationErrors(Request $request): array|object
    {
        $errorBag = $request->session()->get('errors');

        if (! $errorBag instanceof ViewErrorBag) {
            return (object) [];
        }

        $bags = collect($errorBag->getBags())->map(function ($bag) {
            return collect($bag->messages())
                ->map(fn (array $messages) => $messages[0] ?? null)
                ->filter(fn ($message) => $message !== null)
                ->all();
        });

        if ($bags->has('default') && $request->header('X-Inertia-Error-Bag')) {
            return [
                $request->header('X-Inertia-Error-Bag') => $bags->get('default'),
            ];
        }

        if ($bags->has('default')) {
            return $bags->get('default');
        }

        return $bags->all();
    }

    private static function relativeUrl(Request $request): string
    {
        $url = Str::start(
            Str::after($request->fullUrl(), $request->getSchemeAndHttpHost()),
            '/',
        );

        $rawPath = Str::before($request->getRequestUri(), '?');

        if (Str::endsWith($rawPath, '/') && ! Str::endsWith(Str::before($url, '?'), '/')) {
            $path = Str::finish(Str::before($url, '?'), '/');

            return str_contains($url, '?')
                ? $path.'?'.Str::after($url, '?')
                : $path;
        }

        return $url;
    }
}
