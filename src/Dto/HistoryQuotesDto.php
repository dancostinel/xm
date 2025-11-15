<?php

namespace App\Dto;

use App\Contract\FilterConditionInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class HistoryQuotesDto implements FilterConditionInterface
{
    private string $companyName;

    #[Assert\NotBlank(message: 'The companySymbol field is required.')]
    #[Assert\Type('string', message: 'The companySymbol field must be a string.')]
    #[Assert\Length(
        min: 1,
        max: 4,
        minMessage: 'companySymbol field must be at least {{ limit }} characters long.',
        maxMessage: 'companySymbol field cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(pattern: '/^[A-Z0-9]+$/', message: 'The companySymbol field must contain only uppercase letters and numbers.')]
    public ?string $companySymbol = null {
        get {
            return $this->companySymbol;
        }
    }

    #[Assert\NotBlank(message: 'The startDate field is required.')]
    #[Assert\Date(message: 'The startDate field must be in the YYYY-mm-dd format.')]
    public ?string $startDate = null {
        get {
            return $this->startDate;
        }
    }

    #[Assert\NotBlank(message: 'The endDate field is required.')]
    #[Assert\Date(message: 'The endDate field must be in the YYYY-mm-dd format.')]
    public ?string $endDate = null {
        get {
            return $this->endDate;
        }
    }

    #[Assert\NotBlank(message: 'The email field is required.')]
    #[Assert\Email(message: 'The email field address is not valid.')]
    public ?string $email = null {
        get {
            return $this->email;
        }
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        $today = new \DateTime();

        if (!empty($this->startDate)) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $this->startDate);
            if ($startDate && $startDate > $today) {
                $context->buildViolation('The startDate field cannot be later than the current date.')
                    ->atPath('startDate')
                    ->addViolation();
            }
        }

        if (!empty($this->endDate)) {
            $endDate = \DateTime::createFromFormat('Y-m-d', $this->endDate);
            if ($endDate && $endDate > $today) {
                $context->buildViolation('The endDate field cannot be later than the current date.')
                    ->atPath('endDate')
                    ->addViolation();
            }
        }

        if (!empty($this->startDate) && !empty($this->endDate) && $this->startDate > $this->endDate) {
            $context->buildViolation('The startDate field must be less than or equal to endDate field.')
                ->atPath('startDate')
                ->addViolation();
        }
    }

    public function applyFilterConditions(iterable $item): bool
    {
        $isMatched = $this->matchesSymbol($item) && $this->matchesDateRange($item);
        if ($isMatched) {
            $this->companyName = $item['company_name'] ?? '';
        }
        return $isMatched;
    }

    public function getEmailFrom(): string
    {
        return 'reports@our-company.com';
    }

    public function getEmailSubject(): string
    {
        return $this->companyName ?? '';
    }

    public function getEmailBody(): string
    {
        return 'From ' . $this->startDate . ' to ' . $this->endDate;
    }

    public function getEmailAttachmentName(): string
    {
        return 'history_quotes.csv';
    }

    private function matchesSymbol(iterable $item): bool
    {
        if (!isset($item['symbol'])) {
            return false;
        }

        return strcasecmp($item['symbol'], $this->companySymbol) === 0;
    }

    private function matchesDateRange(iterable $item): bool
    {
        if (!isset($item['start_date']) || !isset($item['end_date'])) {
            return false;
        }

        $itemStartDate = $item['start_date'];
        $itemEndDate = $item['end_date'];

        return $itemStartDate >= $this->startDate
            && $itemEndDate <= $this->endDate;
    }
}
