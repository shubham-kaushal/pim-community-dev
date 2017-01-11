<?php

namespace Pim\Bundle\ApiBundle\tests\integration\EventSubscriber;

use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CheckHeadersRequestSubscriberIntegration extends TestCase
{
    protected $purgeDatabaseForEachTest = false;

    public function testErrorIfAcceptHeaderIsXml()
    {
        $client = static::createClient();

        $client->request('GET', 'api/rest/v1/categories/master', [], [], ['HTTP_ACCEPT' => 'application/xml']);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode(), 'Header is not acceptable');
        $content = json_decode($response->getContent(), true);
        $this->assertCount(2, $content, 'Error response contains 2 items');
        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $content['code']);
        $this->assertSame('"application/xml" in "Accept" header is not valid. Only "application/json" is allowed.', $content['message']);
    }

    public function testSuccessIfAcceptHeaderIsJson()
    {
        $client = static::createClient();

        $client->request('GET', 'api/rest/v1/categories/master', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), 'Header is acceptable');
    }

    public function testSuccessIfAcceptHeaderIsEmpty()
    {
        $client = static::createClient();

        $client->request('GET', 'api/rest/v1/categories/master');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), 'Header is acceptable');
    }

    public function testErrorIfContentTypeHeaderIsXml()
    {
        $client = static::createClient();

        $client->request('POST', 'api/rest/v1/categories', [], [], [
            'CONTENT_TYPE' => 'application/xml',
        ], '{"code": "my_category"}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode(), 'Header is not acceptable');
        $content = json_decode($response->getContent(), true);
        $this->assertCount(2, $content, 'Error response contains 2 items');
        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $content['code']);
        $this->assertSame('"application/xml" in "Content-Type" header is not valid. Only "application/json" is allowed.', $content['message']);
    }

    public function testSuccessIfContentTypeHeaderIsJson()
    {
        $client = static::createClient();

        $client->request('POST', 'api/rest/v1/categories', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"code": "my_category"}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), 'Header is acceptable');
    }

    public function testSuccessIfContentTypeHeaderIsEmpty()
    {
        $client = static::createClient();

        $client->request('POST', 'api/rest/v1/categories', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], '{"code": "my_category_1"}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), 'Header is acceptable');
    }

    public function testSuccessWhenRouteIsOutsideTheAPI()
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), 'Page is accessible without error');
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfiguration()
    {
        return new Configuration(
            [Configuration::getTechnicalCatalogPath()],
            false
        );
    }
}
