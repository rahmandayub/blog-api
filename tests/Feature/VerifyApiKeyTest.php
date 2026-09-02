<?php

use App\Http\Middleware\VerifyApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('blog.api_key', 'test-api-key-value');
    Config::set('blog.webhook_secret', 'test-webhook-secret-value');
    Http::fake();
});

test('middleware rejects missing authorization header', function () {
    $this->getJson('/api/posts')->assertUnauthorized()->assertJson(['message' => 'Missing API key']);
    $this->getJson('/api/categories')->assertUnauthorized()->assertJson(['message' => 'Missing API key']);
});

test('middleware rejects empty bearer token', function () {
    $this->getJson('/api/posts', ['Authorization' => 'Bearer '])->assertUnauthorized();
});

test('middleware rejects header without Bearer prefix', function () {
    $this->getJson('/api/posts', ['Authorization' => 'test-api-key-value'])->assertUnauthorized();
});

test('middleware rejects invalid key', function () {
    $this->getJson('/api/posts', ['Authorization' => 'Bearer invalid'])->assertUnauthorized()->assertJson(['message' => 'Invalid API key']);
});

test('middleware rejects webhook secret on api-key route', function () {
    $this->getJson('/api/posts', ['Authorization' => 'Bearer test-webhook-secret-value'])->assertUnauthorized();
    $this->getJson('/api/categories', ['Authorization' => 'Bearer test-webhook-secret-value'])->assertUnauthorized();
});

test('middleware accepts valid api key', function () {
    $this->getJson('/api/posts', ['Authorization' => 'Bearer test-api-key-value'])->assertSuccessful();
    $this->getJson('/api/categories', ['Authorization' => 'Bearer test-api-key-value'])->assertSuccessful();
});

test('middleware rejects when config api_key is null', function () {
    Config::set('blog.api_key', null);
    $this->getJson('/api/posts', ['Authorization' => 'Bearer test-api-key-value'])->assertUnauthorized();
});

test('middleware handles case sensitive token', function () {
    $this->getJson('/api/posts', ['Authorization' => 'Bearer TEST-API-KEY-VALUE'])->assertUnauthorized();
});

test('middleware rejects api key on webhook route type (logic)', function () {
    // Directly test middleware with webhook keyType
    $middleware = new VerifyApiKey;
    $request = Request::create('/fake', 'GET');
    $request->headers->set('Authorization', 'Bearer test-api-key-value');

    $response = $middleware->handle($request, fn () => response()->json(['ok' => true]), 'webhook');
    expect($response->getStatusCode())->toBe(401);

    $request2 = Request::create('/fake', 'GET');
    $request2->headers->set('Authorization', 'Bearer test-webhook-secret-value');
    $response2 = $middleware->handle($request2, fn () => response()->json(['ok' => true]), 'webhook');
    expect($response2->getStatusCode())->toBe(200);
});
