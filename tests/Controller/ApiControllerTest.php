<?php

namespace App\Tests\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerTest extends WebTestCase
{
    private string $testInputFile;
    private string $testOutputFile;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $testDataDir = dirname(__DIR__) . '/fixtures';
        $this->testInputFile = $testDataDir . '/test_input_data.json';
        $this->testOutputFile = $testDataDir . '/test_output_data.csv';

        // Create fixtures directory if it doesn't exist
        if (!is_dir($testDataDir)) {
            mkdir($testDataDir, 0755, true);
        }

        // Create test input file
        $this->createTestInputFile();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testOutputFile)) {
            unlink($this->testOutputFile);
        }

        parent::tearDown();
    }

    private function createTestInputFile(): void
    {
        $testData = [
            [
                'start_date' => '2023-07-28',
                'end_date' => '2023-07-29',
                'open' => 1.24,
                'high' => 1.25,
                'low' => 1.26,
                'close' => 1.27,
                'volume' => 1443,
                'symbol' => 'AAPL'
            ],
            [
                'start_date' => '2023-07-29',
                'end_date' => '2023-07-30',
                'open' => 1.28,
                'high' => 1.29,
                'low' => 1.30,
                'close' => 1.31,
                'volume' => 1444,
                'symbol' => 'AAPL'
            ],
            [
                'start_date' => '2023-07-30',
                'end_date' => '2023-07-31',
                'open' => 1.32,
                'high' => 1.33,
                'low' => 1.34,
                'close' => 1.35,
                'volume' => 1445,
                'symbol' => 'GOOGL'
            ],
            [
                'start_date' => '2023-08-01',
                'end_date' => '2023-08-02',
                'open' => 1.36,
                'high' => 1.37,
                'low' => 1.38,
                'close' => 1.39,
                'volume' => 1446,
                'symbol' => 'AAPL'
            ],
        ];

        file_put_contents($this->testInputFile, json_encode($testData, JSON_PRETTY_PRINT));
    }

    private function setMailerMockInContainer(): void
    {
        $emailServiceMock = $this->createMock(EmailService::class);
        $emailServiceMock->expects($this->once())
            ->method('sendWithFileAttachment')
            ->with(
                $this->isType('string'),
                $this->equalTo('test@example.com'),
                $this->isType('string'),
                $this->isType('string'),
                $this->stringContains('test_output_data.csv'),
                $this->isType('string')
            );
        $container = static::getContainer();
        $container->set(EmailService::class, $emailServiceMock);
    }

    public function testSuccessfulRequest(): void
    {
        $this->setMailerMockInContainer();

        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('timestamp', $responseData);
        $this->assertEmpty($responseData['errors']);
    }

    public function testMissingCompanySymbol(): void
    {
        $requestData = [
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
    }

    public function testMissingStartDate(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function testMissingEndDate(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
    }

    public function testMissingEmail(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
    }

    public function testInvalidEmailFormat(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30',
            'email' => 'invalid-email'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('errors', $responseData);
    }

    public function testInvalidDateFormat(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '28-07-2023',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
    }

    public function testStartDateAfterEndDate(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-30',
            'endDate' => '2023-07-28',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
    }

    public function testFutureDateValidation(): void
    {
        $futureDate = new \DateTime('+1 year')->format('Y-m-d');

        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'endDate' => $futureDate,
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
    }

    public function testInvalidJsonPayload(): void
    {
        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json {'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMissingContentTypeHeader(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            [], // No Content-Type header
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    public function testEmptyRequestBody(): void
    {
        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            ''
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetMethodNotAllowed(): void
    {
        $this->client->request('GET', '/api/history-quotes');

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testPutMethodNotAllowed(): void
    {
        $this->client->request('PUT', '/api/history-quotes');

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testDeleteMethodNotAllowed(): void
    {
        $this->client->request('DELETE', '/api/history-quotes');

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testResponseStructure(): void
    {
        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        // Check all required fields are present
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('timestamp', $responseData);

        // Check types
        $this->assertIsBool($responseData['success']);
        $this->assertIsArray($responseData['errors']);
        $this->assertIsString($responseData['timestamp']);

        // Validate timestamp format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $responseData['timestamp']
        );
    }

    public function testCompanySymbolCaseSensitive(): void
    {
        $requestData = [
            'companySymbol' => 'aapl',
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('timestamp', $responseData);
        $this->assertEquals(['companySymbol' => 'The companySymbol field must contain only uppercase letters and numbers.'], $responseData['errors']);
    }

    public function testMultipleValidationErrors(): void
    {
        $requestData = [
            'companySymbol' => '', // Empty
            'startDate' => 'invalid-date',
            'endDate' => 'invalid-date',
            'email' => 'not-an-email'
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
        $this->assertGreaterThan(1, count($responseData['errors']));
    }

    public function testExtraFieldsAreIgnored(): void
    {
        $this->setMailerMockInContainer();

        $requestData = [
            'companySymbol' => 'AAPL',
            'startDate' => '2023-07-28',
            'endDate' => '2023-07-30',
            'email' => 'test@example.com',
            'extraField' => 'should be ignored',
            'anotherExtra' => 123
        ];

        $this->client->request(
            'POST',
            '/api/history-quotes',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['success']);
        $this->assertEmpty($responseData['errors']);
    }
}