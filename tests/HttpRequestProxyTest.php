<?php declare(strict_types=1);

namespace Bref\Test;

/**
 * This interface standardizes tests for objects that proxy HTTP requests from one format to another.
 *
 * For example from API Gateway to PSR-7, API Gateway to FastCGI.
 */
interface HttpRequestProxyTest
{
    public function test simple request(int $version);

    public function test request with query string(int $version);

    public function test request with multivalues query string have basic support(int $version);

    public function test request with arrays in query string(int $version);

    public function test request with custom header(int $version);

    public function test request with custom multi header(int $version);

    public function test POST request with raw body(int $version);

    public function test POST request with form data(int $version);

    /**
     * @see https://github.com/brefphp/bref/issues/162
     */
    public function test request with body and no content length(int $version, string $method);

    public function test request supports utf8 characters in body(int $version);

    public function test the content type header is not case sensitive(int $version);

    public function test POST request with multipart form data(int $version);

    public function test POST request with multipart form data containing arrays(int $version);

    public function test POST request with multipart file uploads(int $version);

    public function test request with cookies(int $version);

    public function test POST request with base64 encoded body(int $version);

    public function test PUT request(int $version);

    public function test PATCH request(int $version);

    public function test DELETE request(int $version);

    public function test OPTIONS request(int $version);
}
