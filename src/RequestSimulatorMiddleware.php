<?php

namespace PhpMiddleware\RequestSimulator;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use PhpMiddleware\DoublePassCompatibilityTrait;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request\Serializer as RequestSerializer;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\Serializer as ResponseSerializer;
use Zend\Diactoros\ServerRequest;

final class RequestSimulatorMiddleware implements MiddlewareInterface
{
    use DoublePassCompatibilityTrait;

    const PARAM = 'simulated-request';

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($request->getMethod() === 'POST') {
            $parsedBody = $this->parseBody($request->getBody());

            if (is_array($parsedBody) && isset($parsedBody[self::PARAM])) {
                $requestToSimulate = $parsedBody[self::PARAM];
                $deserializedRequest = RequestSerializer::fromString($requestToSimulate);
                $request = new ServerRequest($request->getServerParams(), $request->getUploadedFiles(), $deserializedRequest->getUri(), $deserializedRequest->getMethod(), $deserializedRequest->getBody(), $deserializedRequest->getHeaders());
            }
        }

        $requestAsString = RequestSerializer::toString($request);

        $response = $delegate->process($request);

        $responseAsString = ResponseSerializer::toString($response);

        $html = sprintf($this->getHtmlTemplate(), self::PARAM, $requestAsString, $responseAsString);

        return new HtmlResponse($html);
    }

    private function parseBody($body)
    {
        $params = [];
        parse_str($body, $params);

        return $params;
    }

    private function getHtmlTemplate()
    {
        return '<html>'
                . '<body>'
                . '<h1>Request simulator</h1>'
                . '<form method="post">'
                . '<h2>Request</h2>'
                . '<textarea name="%s">%s</textarea>'
                . '<input type="submit" />'
                . '</form>'
                . '<h2>Response</h2>'
                . '<code>%s</code>'
                . '</body>'
                . '</html>';
    }

}
