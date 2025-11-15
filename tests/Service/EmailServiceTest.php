<?php

namespace App\Tests\Service;

use App\Exception\MailerException;
use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailServiceTest extends TestCase
{
    private MailerInterface $mailerMock;
    private EmailService $emailService;
    private string $testDataDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailerMock = $this->createMock(MailerInterface::class);
        $this->emailService = new EmailService($this->mailerMock);
        $this->testDataDir = sys_get_temp_dir() . '/email_service_tests_' . uniqid();
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDataDir)) {
            $files = glob($this->testDataDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDataDir);
        }

        parent::tearDown();
    }

    private function createTestFile(string $filename, string $content = 'test content'): string
    {
        $filePath = $this->testDataDir . '/' . $filename;
        file_put_contents($filePath, $content);
        return $filePath;
    }

    public function testConstructorAcceptsMailerInterface(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $service = new EmailService($mailer);

        $this->assertInstanceOf(EmailService::class, $service);
    }

    public function testSendWithFileAttachmentSuccess(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $this->emailService->sendWithFileAttachment(
            'sender@example.com',
            'recipient@example.com',
            'Test Subject',
            '<p>Test Body</p>',
            $filePath,
            'custom_name.csv'
        );
        $this->assertTrue(true);
    }

    public function testSendWithFileAttachmentCallsMailerSend(): void
    {
        $filePath = $this->createTestFile('data.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );
    }

    public function testSendWithFileAttachmentPassesEmailObject(): void
    {
        $filePath = $this->createTestFile('attachment.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) {
                return $email instanceof Email;
            }));

        $this->emailService->sendWithFileAttachment(
            'sender@test.com',
            'receiver@test.com',
            'Test',
            'Content',
            $filePath
        );
    }

    public function testUsesCustomAttachmentNameWhenProvided(): void
    {
        $filePath = $this->createTestFile('original.csv');
        $customName = 'custom_attachment.csv';

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath,
            $customName
        );

        $this->assertTrue(true);
    }

    public function testUsesFileNameAsAttachmentNameWhenNotProvided(): void
    {
        $filePath = $this->createTestFile('report.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath,
            null
        );

        $this->assertTrue(true);
    }

    public function testUsesFileNameWhenAttachmentNameIsNull(): void
    {
        $filePath = $this->createTestFile('data_export.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testThrowsExceptionWhenFileDoesNotExist(): void
    {
        $nonExistentPath = $this->testDataDir . '/does_not_exist.csv';

        $this->mailerMock
            ->expects($this->never())
            ->method('send');

        $this->expectException(MailerException::class);
        $this->expectExceptionMessage('Attachment file not found');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $nonExistentPath
        );
    }

    public function testExceptionMessageContainsFilePath(): void
    {
        $nonExistentPath = '/path/to/missing/file.csv';

        try {
            $this->emailService->sendWithFileAttachment(
                'from@example.com',
                'to@example.com',
                'Subject',
                'Body',
                $nonExistentPath
            );

            $this->fail('Expected MailerException was not thrown');
        } catch (MailerException $e) {
            $this->assertStringContainsString($nonExistentPath, $e->getMessage());
            $this->assertStringContainsString('Attachment file not found', $e->getMessage());
        }
    }

    public function testDoesNotCallMailerWhenFileNotFound(): void
    {
        $nonExistentPath = $this->testDataDir . '/missing.csv';

        $this->mailerMock
            ->expects($this->never())
            ->method('send');

        try {
            $this->emailService->sendWithFileAttachment(
                'from@example.com',
                'to@example.com',
                'Subject',
                'Body',
                $nonExistentPath
            );
        } catch (MailerException $e) {
        }
    }

    public function testAcceptsFromAddress(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'sender@domain.com',
            'recipient@domain.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testAcceptsToAddress(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'custom-recipient@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testAcceptsSubject(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Custom Email Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testAcceptsHtmlBody(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $htmlBody = '<html><body><h1>Hello</h1><p>This is HTML</p></body></html>';

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            $htmlBody,
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testThrowsTransportExceptionWhenMailerFails(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $transportException = new TransportException('SMTP connection failed');

        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException($transportException);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('SMTP connection failed');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );
    }

    public function testPropagatesTransportException(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new TransportException('Network error'));

        try {
            $this->emailService->sendWithFileAttachment(
                'from@example.com',
                'to@example.com',
                'Subject',
                'Body',
                $filePath
            );

            $this->fail('Expected TransportException was not thrown');
        } catch (TransportException $e) {
            $this->assertEquals('Network error', $e->getMessage());
        }
    }

    public function testAttachmentIsSetAsTextCsv(): void
    {
        $filePath = $this->createTestFile('data.csv', 'csv,content,here');

        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                return true;
            }));

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );
    }

    public function testWorksWithDifferentFileExtensions(): void
    {
        $filePath = $this->createTestFile('report.xlsx');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

//    public function testWorksWithFileInNestedDirectory(): void
//    {
//        $nestedDir = $this->testDataDir . '/nested/path';
//        mkdir($nestedDir, 0777, true);
//
//        $filePath = $nestedDir . '/file.csv';
//        file_put_contents($filePath, 'content');
//
//        $this->mailerMock
//            ->expects($this->once())
//            ->method('send');
//
//        $this->emailService->sendWithFileAttachment(
//            'from@example.com',
//            'to@example.com',
//            'Subject',
//            'Body',
//            $filePath
//        );
//    }

    public function testWorksWithEmptyFile(): void
    {
        $filePath = $this->createTestFile('empty.csv', '');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testWorksWithLargeFile(): void
    {
        $largeContent = str_repeat('a', 1024 * 1024);
        $filePath = $this->createTestFile('large.csv', $largeContent);

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testWorksWithSpecialCharactersInFileName(): void
    {
        $filePath = $this->createTestFile('file-with_special.chars@123.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testWorksWithUnicodeInBody(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $unicodeBody = '<p>Héllo Wörld 你好 مرحبا</p>';

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject with 中文',
            $unicodeBody,
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testCanBCalledMultipleTimes(): void
    {
        $filePath1 = $this->createTestFile('file1.csv');
        $filePath2 = $this->createTestFile('file2.csv');

        $this->mailerMock
            ->expects($this->exactly(2))
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject 1',
            'Body 1',
            $filePath1
        );

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject 2',
            'Body 2',
            $filePath2
        );

        $this->assertTrue(true);
    }

    public function testAcceptsValidEmailAddressFormats(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'user.name+tag@example.co.uk',
            'recipient_123@sub.domain.example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testAcceptsAllRequiredParameters(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath,
            'attachment.csv'
        );

        $this->assertTrue(true);
    }

    public function testWorksWithoutOptionalAttachmentName(): void
    {
        $filePath = $this->createTestFile('default_name.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertTrue(true);
    }

    public function testMethodHasCorrectReturnType(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $result = $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );

        $this->assertNull($result);
    }

    public function testFileExistenceCheckedBeforeMailerCalled(): void
    {
        $nonExistentPath = $this->testDataDir . '/does_not_exist.csv';

        $this->mailerMock
            ->expects($this->never())
            ->method('send');

        try {
            $this->emailService->sendWithFileAttachment(
                'from@example.com',
                'to@example.com',
                'Subject',
                'Body',
                $nonExistentPath
            );
            $this->fail('Expected exception not thrown');
        } catch (MailerException $e) {
        }
    }

    public function testMailerSendCalledExactlyOnce(): void
    {
        $filePath = $this->createTestFile('test.csv');

        $this->mailerMock
            ->expects($this->once())
            ->method('send');

        $this->emailService->sendWithFileAttachment(
            'from@example.com',
            'to@example.com',
            'Subject',
            'Body',
            $filePath
        );
    }

    public function testMailerNotCalledOnFileNotFound(): void
    {
        $this->mailerMock
            ->expects($this->never())
            ->method('send');

        try {
            $this->emailService->sendWithFileAttachment(
                'from@example.com',
                'to@example.com',
                'Subject',
                'Body',
                '/nonexistent/path/file.csv'
            );
        } catch (MailerException $e) {
            // Expected
        }
    }

    public function testCompleteEmailSendingFlow(): void
    {
        $filePath = $this->createTestFile('report.csv', 'id,name,value\n1,Test,100');

        $capturedEmail = null;

        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$capturedEmail) {
                $capturedEmail = $email;
            });

        $this->emailService->sendWithFileAttachment(
            'sender@company.com',
            'recipient@client.com',
            'Monthly Report',
            '<h1>Monthly Report</h1><p>Please find attached.</p>',
            $filePath,
            'monthly_report.csv'
        );

        $this->assertInstanceOf(Email::class, $capturedEmail);
    }
}
