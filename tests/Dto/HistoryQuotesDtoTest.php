<?php

namespace App\Tests\Dto;

use App\Dto\HistoryQuotesDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class HistoryQuotesDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a real validator for constraint testing
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    // ========== Property Tests ==========

    public function testCompanySymbolPropertyCanBeSet(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';

        $this->assertEquals('AAPL', $dto->companySymbol);
    }

    public function testStartDatePropertyCanBeSet(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->startDate = '2023-01-01';

        $this->assertEquals('2023-01-01', $dto->startDate);
    }

    public function testEndDatePropertyCanBeSet(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->endDate = '2023-12-31';

        $this->assertEquals('2023-12-31', $dto->endDate);
    }

    public function testEmailPropertyCanBeSet(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->email = 'test@example.com';

        $this->assertEquals('test@example.com', $dto->email);
    }

    // ========== Validation Tests - companySymbol ==========

    public function testValidCompanySymbol(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';
        $dto->email = 'test@example.com';

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertCount(0, $violations);
    }

    public function testCompanySymbolCannotBeBlank(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = '';

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertCount(2, $violations);
        $this->assertEquals('The companySymbol field is required.', $violations[0]->getMessage());
        $this->assertEquals('companySymbol field must be at least 1 characters long.', $violations[1]->getMessage());
    }

    public function testCompanySymbolCannotBeNull(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = null;

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testCompanySymbolMinLength(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = '';

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testCompanySymbolMaxLength(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'TOOLONG'; // More than 4 characters

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('cannot be longer than 4 characters', $violations[0]->getMessage());
    }

    public function testCompanySymbolMustBeUppercase(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'aapl'; // lowercase

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertCount(1, $violations);
        $this->assertEquals('The companySymbol field must contain only uppercase letters and numbers.', $violations[0]->getMessage());
    }

    public function testCompanySymbolCanContainNumbers(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'A1B2';

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertCount(0, $violations);
    }

    public function testCompanySymbolCannotContainSpecialCharacters(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AA-PL';

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertCount(2, $violations);
        $this->assertEquals('companySymbol field cannot be longer than 4 characters.', $violations[0]->getMessage());
        $this->assertEquals('The companySymbol field must contain only uppercase letters and numbers.', $violations[1]->getMessage());
    }

    // ========== Validation Tests - startDate ==========

    public function testValidStartDate(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->startDate = '2023-01-01';

        $violations = $this->validator->validateProperty($dto, 'startDate');

        $this->assertCount(0, $violations);
    }

    public function testStartDateCannotBeBlank(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->startDate = '';

        $violations = $this->validator->validateProperty($dto, 'startDate');

        $this->assertCount(1, $violations);
        $this->assertEquals('The startDate field is required.', $violations[0]->getMessage());
    }

    public function testStartDateMustBeValidFormat(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->startDate = '01-01-2023'; // Invalid format

        $violations = $this->validator->validateProperty($dto, 'startDate');

        $this->assertCount(1, $violations);
        $this->assertEquals('The startDate field must be in the YYYY-mm-dd format.', $violations[0]->getMessage());
    }

    public function testStartDateCannotBeInFuture(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = (new \DateTime('+1 year'))->format('Y-m-d');
        $dto->endDate = (new \DateTime('+2 years'))->format('Y-m-d');
        $dto->email = 'test@example.com';

        $violations = $this->validator->validate($dto);

        $hasStartDateViolation = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'startDate' &&
                str_contains($violation->getMessage(), 'cannot be later than the current date')) {
                $hasStartDateViolation = true;
                break;
            }
        }

        $this->assertTrue($hasStartDateViolation);
    }

    // ========== Validation Tests - endDate ==========

    public function testValidEndDate(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->endDate = '2023-12-31';

        $violations = $this->validator->validateProperty($dto, 'endDate');

        $this->assertCount(0, $violations);
    }

    public function testEndDateCannotBeBlank(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->endDate = '';

        $violations = $this->validator->validateProperty($dto, 'endDate');

        $this->assertCount(1, $violations);
        $this->assertEquals('The endDate field is required.', $violations[0]->getMessage());
    }

    public function testEndDateMustBeValidFormat(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->endDate = '31/12/2023'; // Invalid format

        $violations = $this->validator->validateProperty($dto, 'endDate');

        $this->assertCount(1, $violations);
        $this->assertEquals('The endDate field must be in the YYYY-mm-dd format.', $violations[0]->getMessage());
    }

    public function testEndDateCannotBeInFuture(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = (new \DateTime('+1 year'))->format('Y-m-d');
        $dto->email = 'test@example.com';

        $violations = $this->validator->validate($dto);

        $hasEndDateViolation = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'endDate' &&
                str_contains($violation->getMessage(), 'cannot be later than the current date')) {
                $hasEndDateViolation = true;
                break;
            }
        }

        $this->assertTrue($hasEndDateViolation);
    }

    // ========== Validation Tests - email ==========

    public function testValidEmail(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->email = 'test@example.com';

        $violations = $this->validator->validateProperty($dto, 'email');

        $this->assertCount(0, $violations);
    }

    public function testEmailCannotBeBlank(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->email = '';

        $violations = $this->validator->validateProperty($dto, 'email');

        $this->assertCount(1, $violations);
        $this->assertEquals('The email field is required.', $violations[0]->getMessage());
    }

    public function testEmailMustBeValid(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->email = 'invalid-email';

        $violations = $this->validator->validateProperty($dto, 'email');

        $this->assertCount(1, $violations);
        $this->assertEquals('The email field address is not valid.', $violations[0]->getMessage());
    }

    // ========== Date Range Validation Tests ==========

    public function testStartDateCannotBeAfterEndDate(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-12-31';
        $dto->endDate = '2023-01-01';
        $dto->email = 'test@example.com';

        $violations = $this->validator->validate($dto);

        $hasDateRangeViolation = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'startDate' &&
                str_contains($violation->getMessage(), 'must be less than or equal to endDate')) {
                $hasDateRangeViolation = true;
                break;
            }
        }

        $this->assertTrue($hasDateRangeViolation);
    }

    public function testStartDateCanEqualEndDate(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-01-01';
        $dto->email = 'test@example.com';

        $violations = $this->validator->validate($dto);

        // Should have no date range violations
        $hasDateRangeViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation->getMessage(), 'must be less than or equal to endDate')) {
                $hasDateRangeViolation = true;
                break;
            }
        }

        $this->assertFalse($hasDateRangeViolation);
    }

    // ========== Full DTO Validation Tests ==========

    public function testValidDto(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';
        $dto->email = 'test@example.com';

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testInvalidDtoWithMultipleErrors(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'toolong'; // Too long and lowercase
        $dto->startDate = 'invalid';
        $dto->endDate = 'invalid';
        $dto->email = 'invalid';

        $violations = $this->validator->validate($dto);

        $this->assertGreaterThan(3, $violations->count());
    }

    // ========== applyFilterConditions Tests ==========

    public function testApplyFilterConditionsMatchesSymbolAndDateRange(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'symbol' => 'AAPL',
            'start_date' => '2023-06-01',
            'end_date' => '2023-06-30',
            'company_name' => 'Apple Inc.'
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertTrue($result);
    }

    public function testApplyFilterConditionsDoesNotMatchDifferentSymbol(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'symbol' => 'GOOGL',
            'start_date' => '2023-06-01',
            'end_date' => '2023-06-30'
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertFalse($result);
    }

    public function testApplyFilterConditionsSymbolIsCaseInsensitive(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'symbol' => 'aapl', // lowercase
            'start_date' => '2023-06-01',
            'end_date' => '2023-06-30'
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertTrue($result);
    }

    public function testApplyFilterConditionsDoesNotMatchOutsideDateRange(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-06-30';

        $item = [
            'symbol' => 'AAPL',
            'start_date' => '2023-07-01', // After endDate
            'end_date' => '2023-07-31'
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertFalse($result);
    }

    public function testApplyFilterConditionsReturnsFalseWhenSymbolMissing(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'start_date' => '2023-06-01',
            'end_date' => '2023-06-30'
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertFalse($result);
    }

    public function testApplyFilterConditionsReturnsFalseWhenDatesMissing(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'symbol' => 'AAPL'
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertFalse($result);
    }

    public function testApplyFilterConditionsSetsCompanyName(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'symbol' => 'AAPL',
            'start_date' => '2023-06-01',
            'end_date' => '2023-06-30',
            'company_name' => 'Apple Inc.'
        ];

        $dto->applyFilterConditions($item);

        $this->assertEquals('Apple Inc.', $dto->getEmailSubject());
    }

    // ========== Email Getter Tests ==========

    public function testGetEmailFrom(): void
    {
        $dto = new HistoryQuotesDto();

        $this->assertEquals('reports@our-company.com', $dto->getEmailFrom());
    }

    public function testGetEmailSubject(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'symbol' => 'AAPL',
            'start_date' => '2023-06-01',
            'end_date' => '2023-06-30',
            'company_name' => 'Apple Inc.'
        ];

        $dto->applyFilterConditions($item);

        $this->assertEquals('Apple Inc.', $dto->getEmailSubject());
    }

    public function testGetEmailSubjectWhenCompanyNameNotSet(): void
    {
        $dto = new HistoryQuotesDto();

        $this->assertEquals('', $dto->getEmailSubject());
    }

    public function testGetEmailBody(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        $expected = 'From 2023-01-01 to 2023-12-31';

        $this->assertEquals($expected, $dto->getEmailBody());
    }

    public function testGetEmailAttachmentName(): void
    {
        $dto = new HistoryQuotesDto();

        $this->assertEquals('history_quotes.csv', $dto->getEmailAttachmentName());
    }

    // ========== Edge Cases ==========

    public function testApplyFilterConditionsWithArrayAccess(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-12-31';

        // Using ArrayObject to test iterable
        $item = new \ArrayObject([
            'symbol' => 'AAPL',
            'start_date' => '2023-06-01',
            'end_date' => '2023-06-30',
            'company_name' => 'Apple Inc.'
        ]);

        $result = $dto->applyFilterConditions($item);

        $this->assertTrue($result);
    }

    public function testValidationWithPastDates(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2020-01-01';
        $dto->endDate = '2020-12-31';
        $dto->email = 'test@example.com';

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testValidationWithTodayAsDate(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = (new \DateTime())->format('Y-m-d');
        $dto->endDate = (new \DateTime())->format('Y-m-d');
        $dto->email = 'test@example.com';

        $violations = $this->validator->validate($dto);

        $this->assertCount(0, $violations);
    }

    public function testCompanySymbolWithOnlyNumbers(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = '1234';

        $violations = $this->validator->validateProperty($dto, 'companySymbol');

        $this->assertCount(0, $violations);
    }

    public function testApplyFilterConditionsDateRangeBoundaryStart(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-06-01';
        $dto->endDate = '2023-12-31';

        $item = [
            'symbol' => 'AAPL',
            'start_date' => '2023-06-01', // Exactly on boundary
            'end_date' => '2023-06-15'
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertTrue($result);
    }

    public function testApplyFilterConditionsDateRangeBoundaryEnd(): void
    {
        $dto = new HistoryQuotesDto();
        $dto->companySymbol = 'AAPL';
        $dto->startDate = '2023-01-01';
        $dto->endDate = '2023-06-30';

        $item = [
            'symbol' => 'AAPL',
            'start_date' => '2023-06-15',
            'end_date' => '2023-06-30' // Exactly on boundary
        ];

        $result = $dto->applyFilterConditions($item);

        $this->assertTrue($result);
    }
}
