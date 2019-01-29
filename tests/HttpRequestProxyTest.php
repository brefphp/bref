<?php declare(strict_types=1);

namespace Bref\Test;

/**
 * This interface standardizes tests for objects that proxy HTTP requests from one format to another.
 *
 * For example from API Gateway to PSR-7, API Gateway to FastCGI.
 */
interface HttpRequestProxyTest
{
    public function test simple request();

    public function test request with query string();

    public function test request with arrays in query string();

    public function test request with custom header();

    public function test POST request with raw body();

    public function test POST request with form data();

    public function test the content type header is not case sensitive();

    public function test POST request with multipart form data();

    public function test POST request with multipart form data containing arrays();

    public function test request with cookies();

    public function test POST request with multipart file uploads();

    public function test POST request with base64 encoded body();

    public function test HTTP_HOST header();

    public function test PUT request();

    public function test PATCH request();

    public function test DELETE request();

    public function test OPTIONS request();
}
