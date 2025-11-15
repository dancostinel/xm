<?php

namespace App\Controller;

use App\Contract\FileReaderInterface;
use App\Dto\HistoryQuotesDto;
use App\Service\CsvService;
use App\Service\EmailService;
use App\Service\HistoryQuotesService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
readonly class ApiController
{
    public function __construct(
        private FileReaderInterface $fileReader,
        private HistoryQuotesService $historyQuotesService,
        private CsvService $csvService,
        private EmailService $emailService,
    ) {
    }

    #[Route(path: '/api/history-quotes', name: 'api_history_quotes', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        HistoryQuotesDto $request,
        #[Autowire(param: 'app.input_file_path')] string $inputFilePath,
        #[Autowire(param: 'app.filtered_file_path')] string $filteredFilePath
    ): JsonResponse
    {
        try {
            $items = $this->fileReader->read($inputFilePath);
            $filteredData = $this->historyQuotesService->getFilteredHistoryQuotes($items, $request);
            $this->csvService->generateFile($filteredData, $filteredFilePath);
            $this->emailService->sendWithFileAttachment(
                $request->getEmailFrom(),
                $request->email,
                $request->getEmailSubject(),
                $request->getEmailBody(),
                $filteredFilePath,
                $request->getEmailAttachmentName()
            );
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => [$exception->getMessage()],
                'timestamp' => new \DateTime()->format('Y-m-d H:i:s'),
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'message' => $request,
            'errors' => [],
            'timestamp' => new \DateTime()->format('Y-m-d H:i:s'),
        ]);
    }
}
