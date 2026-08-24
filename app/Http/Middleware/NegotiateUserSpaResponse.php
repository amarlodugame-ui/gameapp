<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class NegotiateUserSpaResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->seedPreviousPageUrl($request);

        $response = $next($request);

        if ($request->header('X-User-Spa') !== '1'
            || ! $response instanceof RedirectResponse) {
            return $response;
        }

        if ($request->isMethod('GET')) {
            return $this->pageRedirectResponse($request, $response);
        }

        if ($request->isMethod('HEAD')) {
            return $response;
        }

        $errors = $this->validationErrors($request);

        if ($errors !== []) {
            return response()->json([
                'message' => collect($errors)->flatten()->first() ?? 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        $toast = $request->session()->get('toast');
        $message = is_array($toast) ? ($toast['message'] ?? null) : null;
        $message ??= $request->session()->get('success');
        $message ??= $request->session()->get('error');

        return response()->json([
            'message' => is_string($message) && $message !== ''
                ? $message
                : 'Request completed successfully.',
            'toast' => is_array($toast) ? $toast : null,
            'data' => (object) [],
            'redirect' => $this->spaLocation($request, $response->getTargetUrl()),
        ]);
    }

    private function pageRedirectResponse(Request $request, RedirectResponse $response): Response
    {
        $redirect = $this->spaLocation($request, $response->getTargetUrl());

        if ($redirect === null) {
            return $response;
        }

        return response()->json([
            'message' => 'Page redirect required.',
            'redirect' => $redirect,
        ], 409)->header('X-User-Spa-Location', $redirect);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validationErrors(Request $request): array
    {
        $errorBag = $request->session()->get('errors');

        if (! $errorBag instanceof ViewErrorBag) {
            return [];
        }

        $messages = $errorBag->getBag('default');

        return $messages->any() ? $messages->messages() : [];
    }

    private function seedPreviousPageUrl(Request $request): void
    {
        if ($request->header('X-User-Spa') !== '1'
            || $request->isMethod('GET')
            || $request->isMethod('HEAD')) {
            return;
        }

        $location = $request->header('X-User-Spa-Page-Url');

        if (! is_string($location) || trim($location) === '') {
            return;
        }

        $parts = parse_url(trim($location));

        if ($parts === false) {
            return;
        }

        $path = $parts['path'] ?? null;

        if (! is_string($path) || ! str_starts_with($path, '/user/')) {
            return;
        }

        if (isset($parts['host']) && strcasecmp($parts['host'], $request->getHost()) !== 0) {
            return;
        }

        if (isset($parts['scheme']) && strcasecmp($parts['scheme'], $request->getScheme()) !== 0) {
            return;
        }

        if (isset($parts['port']) && (int) $parts['port'] !== $request->getPort()) {
            return;
        }

        $previousUrl = $request->getSchemeAndHttpHost().$path;

        if (isset($parts['query']) && $parts['query'] !== '') {
            $previousUrl .= '?'.$parts['query'];
        }

        $request->session()->setPreviousUrl($previousUrl);
    }

    private function spaLocation(Request $request, string $location): ?string
    {
        $parts = parse_url($location);

        if ($parts === false) {
            return null;
        }

        if (isset($parts['host'])) {
            $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $allowedHosts = array_filter([
                strtolower($request->getHost()),
                is_string($configuredHost) ? strtolower($configuredHost) : null,
            ]);

            if (! in_array(strtolower($parts['host']), $allowedHosts, true)) {
                return null;
            }
        }

        return RedirectLegacyUserPage::hashPathForLocation($location);
    }
}
