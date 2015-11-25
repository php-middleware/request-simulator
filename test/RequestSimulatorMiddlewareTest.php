<?php

namespace PhpMiddlewareTest\RequestSimulator;

use PhpMiddleware\RequestSimulator\RequestSimulatorMiddleware;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

class RequestSimulatorMiddlewareTest extends PHPUnit_Framework_TestCase
{
    private $middleware;

    protected function setUp()
    {
        $this->middleware = new RequestSimulatorMiddleware();
    }

    public function testNotSimulateRequest()
    {
        $request = new ServerRequest([], [], new Uri(), 'GET');

        $responseBody = json_encode(['boo' => 'foo']);
        $response = new Response('php://memory', 200, ['content-type' => 'application/json']);
        $response->getBody()->write($responseBody);

        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        };

        /* @var $result ResponseInterface */
        $result = call_user_func($this->middleware, $request, $response, $next);

        $body = (string) $result->getBody();

        $this->assertContains('text/html', $result->getHeaderLine('Content-type'));
        $this->assertContains('{"boo":"foo"}', $body);
        $this->assertContains('<html>', $body);
    }


    public function testSimulateRequest()
    {
        $simulatedRequest = "DELETE / HTTP/1.1\r\nBar: faz\r\nHost: php-middleware.com";
        $postBody = http_build_query([RequestSimulatorMiddleware::PARAM => $simulatedRequest]);

        $stream = new Stream('php://memory', 'wb+');
        $stream->write($postBody);

        $request = new ServerRequest([], [], new Uri(), 'POST', $stream, ['Content-type' => 'application/x-www-form-urlencoded']);

        $responseBody = json_encode(['boo' => 'foo']);
        $response = new Response('php://memory', 200, ['content-type' => 'application/json']);
        $response->getBody()->write($responseBody);



        $next = function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertSame('DELETE', $request->getMethod());
            $this->assertSame('faz', $request->getHeaderLine('Bar'));

            return $response;
        };

        /* @var $result ResponseInterface */
        $result = call_user_func($this->middleware, $request, $response, $next);

        $body = (string) $result->getBody();

        $this->assertContains('text/html', $result->getHeaderLine('Content-type'));
        $this->assertContains('{"boo":"foo"}', $body);
        $this->assertContains('<html>', $body);
        $this->assertContains('DELETE / HTTP/1.1', $body);
    }
}
