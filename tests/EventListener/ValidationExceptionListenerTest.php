<?php

namespace App\Tests\EventListener;

use App\EventListener\ValidationExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ValidationExceptionListenerTest extends TestCase
{
    private ValidationExceptionListener $listener;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new ValidationExceptionListener();
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        $request = new Request();

        return new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    private function createViolation(string $propertyPath, string $message): ConstraintViolation
    {
        return new ConstraintViolation(
            $message,
            null,
            [],
            null,
            $propertyPath,
            null
        );
    }

    public function testHandlesUnprocessableEntityHttpExceptionWithValidationFailedException(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('email', 'This value is not a valid email address.'),
            $this->createViolation('password', 'This value is too short.')
        ]);

        $validationException = new ValidationFailedException(null, $violations);

        $httpException = new UnprocessableEntityHttpException(
            'Validation failed',
            $validationException
        );

        // Create event
        $event = $this->createExceptionEvent($httpException);

        // Invoke listener
        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('timestamp', $content);
        $this->assertEquals('This value is not a valid email address.', $content['errors']['email']);
        $this->assertEquals('This value is too short.', $content['errors']['password']);
    }

    public function testHandlesBadRequestHttpExceptionWithValidationFailedException(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('username', 'This value should not be blank.')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Bad request', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['success']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertArrayHasKey('username', $content['errors']);
        $this->assertEquals('This value should not be blank.', $content['errors']['username']);
    }

    public function testHandlesMultipleViolations(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('email', 'Invalid email'),
            $this->createViolation('password', 'Too short'),
            $this->createViolation('age', 'Must be a number'),
            $this->createViolation('name', 'Required field')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new UnprocessableEntityHttpException('Validation failed', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertCount(4, $content['errors']);
        $this->assertArrayHasKey('email', $content['errors']);
        $this->assertArrayHasKey('password', $content['errors']);
        $this->assertArrayHasKey('age', $content['errors']);
        $this->assertArrayHasKey('name', $content['errors']);
    }

    public function testHandlesEmptyViolationList(): void
    {
        $violations = new ConstraintViolationList([]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Bad request', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertEmpty($content['errors']);
        $this->assertIsArray($content['errors']);
    }

    public function testIgnoresUnprocessableEntityHttpExceptionWithoutValidationFailedException(): void
    {
        $httpException = new UnprocessableEntityHttpException('Some other error');

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresBadRequestHttpExceptionWithoutValidationFailedException(): void
    {
        $httpException = new BadRequestHttpException('Some other error');

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresOtherHttpExceptions(): void
    {
        $httpException = new NotFoundHttpException('Not found');

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresGenericException(): void
    {
        $exception = new \RuntimeException('Some runtime error');

        $event = $this->createExceptionEvent($exception);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresExceptionWithDifferentPreviousException(): void
    {
        $previousException = new \InvalidArgumentException('Invalid argument');
        $httpException = new BadRequestHttpException('Bad request', $previousException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testResponseHasCorrectStructure(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('field1', 'Error message 1')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Bad request', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('timestamp', $content);

        $this->assertIsBool($content['success']);
        $this->assertIsString($content['message']);
        $this->assertIsArray($content['errors']);
        $this->assertIsString($content['timestamp']);
    }

    public function testResponseHasCorrectStatusCode(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('field', 'Error')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new UnprocessableEntityHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testResponseIsJsonResponse(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('field', 'Error')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function testTimestampIsValidFormat(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('field', 'Error')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $content['timestamp']
        );

        // Verify timestamp is parseable
        $timestamp = \DateTime::createFromFormat('Y-m-d H:i:s', $content['timestamp']);
        $this->assertInstanceOf(\DateTime::class, $timestamp);
    }

    public function testViolationsAreMappedByPropertyPath(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('user.email', 'Invalid email'),
            $this->createViolation('user.password', 'Too weak'),
            $this->createViolation('profile.age', 'Invalid age')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('user.email', $content['errors']);
        $this->assertArrayHasKey('user.password', $content['errors']);
        $this->assertArrayHasKey('profile.age', $content['errors']);
        $this->assertEquals('Invalid email', $content['errors']['user.email']);
        $this->assertEquals('Too weak', $content['errors']['user.password']);
        $this->assertEquals('Invalid age', $content['errors']['profile.age']);
    }

    public function testEmptyPropertyPathIsHandled(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('', 'General error')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('', $content['errors']);
        $this->assertEquals('General error', $content['errors']['']);
    }

    public function testHandlesSamePropertyPathMultipleTimes(): void
    {
        // When the same property has multiple violations, the last one should win
        $violations = new ConstraintViolationList([
            $this->createViolation('email', 'First error'),
            $this->createViolation('email', 'Second error'),
            $this->createViolation('email', 'Third error')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        // Should contain the last error for the same property path
        $this->assertArrayHasKey('email', $content['errors']);
        $this->assertEquals('Third error', $content['errors']['email']);
    }

    public function testHandlesSpecialCharactersInMessages(): void
    {
        $violations = new ConstraintViolationList([
            $this->createViolation('field', 'Error with "quotes" and \'apostrophes\''),
            $this->createViolation('field2', 'Error with <html> tags'),
            $this->createViolation('field3', 'Error with unicode: é, ñ, 中文')
        ]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertStringContainsString('quotes', $content['errors']['field']);
        $this->assertStringContainsString('<html>', $content['errors']['field2']);
        $this->assertStringContainsString('中文', $content['errors']['field3']);
    }

    public function testListenerIsInvokable(): void
    {
        $this->assertTrue(is_callable($this->listener));
        $this->assertTrue(method_exists($this->listener, '__invoke'));
    }

    public function testWithMockedViolationList(): void
    {
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getPropertyPath')->willReturn('testField');
        $violation->method('getMessage')->willReturn('Test error message');

        $violations = new ConstraintViolationList([$violation]);

        $validationException = new ValidationFailedException(null, $violations);
        $httpException = new BadRequestHttpException('Error', $validationException);

        $event = $this->createExceptionEvent($httpException);

        ($this->listener)($event);

        $response = $event->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals('Test error message', $content['errors']['testField']);
    }

    public function testEventIsNotModifiedWhenExceptionIsNotHandled(): void
    {
        $exception = new \RuntimeException('Some error');
        $event = $this->createExceptionEvent($exception);

        $initialResponse = $event->getResponse();

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
        $this->assertEquals($initialResponse, $event->getResponse());
    }
}
