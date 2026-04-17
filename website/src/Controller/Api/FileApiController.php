<?php

namespace App\Controller\Api;

use App\Controller\BaseAbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\Exception\IniSizeFileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FileApiController extends BaseAbstractController
{
    public function __construct(
        private readonly string $quizUploadPath,
    ) {
    }

    #[Route('/api/file/upload', methods: ['POST'])]
    public function uploadFile(Request $request): Response
    {
        try {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('file');

            if (!$uploadedFile) {
                return $this->failed(['file' => 'File is required.']);
            }

            $slugger = new AsciiSlugger();
            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = strtolower((string) $slugger->slug($originalFilename));
            $extension = strtolower(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_EXTENSION) ?: 'bin');

            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true)) {
                return $this->failed(['file' => 'Unsupported image format.']);
            }

            $filename = sprintf('%s-%s.%s', $safeFilename ?: 'quiz', uniqid(), $extension);

            $uploadedFile->move($this->quizUploadPath, $filename);

            return $this->success([
                'image' => [[
                    'id' => $filename,
                    'source' => '/uploads/quiz/' . $filename,
                ]],
            ]);
        } catch (IniSizeFileException|FileException $exception) {
            return $this->failed([
                'file' => 'Файл слишком большой для текущей конфигурации PHP upload_max_filesize/post_max_size.',
            ], code: 413);
        }
    }
}
