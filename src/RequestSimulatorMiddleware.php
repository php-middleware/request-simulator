<?php

namespace PhpMiddleware\RequestSimulator;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Request\Serializer as RequestSerializer;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\Serializer as ResponseSerializer;

class RequestSimulatorMiddleware
{
    const PARAM = 'simulated-request';

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $parsedBody = $request->getParsedBody();

        if ($request->getMethod() === 'POST' && is_array($parsedBody) && isset($parsedBody[self::PARAM])) {
            $requestToSimulate = $parsedBody[self::PARAM];
            $request = RequestSerializer::fromString($requestToSimulate);
        }

        $requestAsString = RequestSerializer::toString($request);

        $responseResult = $next($request, $response);

        $responseAsString = ResponseSerializer::toString($responseResult);

        $html = sprintf($this->getHtmlTemplate(), self::PARAM, $requestAsString, $responseAsString);

        return new HtmlResponse($html);
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