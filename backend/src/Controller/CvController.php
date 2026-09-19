<?php

namespace App\Controller;

use App\Entity\CV;
use App\Service\FileUploader;
use App\Service\PdfTextExtractor;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CvController extends AbstractController
{
    #[Route('/api/cv/upload', name: 'cv_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        FileUploader $fileUploader,
        PdfTextExtractor $pdfTextExtractor,
        GeminiService $geminiService,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        /*
         * =========================================================
         * 1. GET UPLOADED FILE
         * =========================================================
         *
         * Authentication is no longer required.
         * Anyone can upload a CV.
         */

        $file = $request->files->get('cv');

        if (!$file) {

            return $this->json([
                'error' => 'No CV file uploaded.'
            ], 400);
        }

        /*
         * =========================================================
         * 2. CHECK PDF
         * =========================================================
         */

        if ($file->getMimeType() !== 'application/pdf') {

            return $this->json([
                'error' => 'Only PDF files are allowed.'
            ], 400);
        }

        try {

            /*
             * =====================================================
             * 3. UPLOAD PDF
             * =====================================================
             */

            $filename = $fileUploader->upload($file);

            /*
             * =====================================================
             * 4. BUILD FILE PATH
             * =====================================================
             */

            $filePath =
                $this->getParameter('kernel.project_dir')
                . '/public/uploads/'
                . $filename;

            /*
             * =====================================================
             * 5. EXTRACT TEXT
             * =====================================================
             */

            $cvText =
                $pdfTextExtractor->extract($filePath);

            if (trim($cvText) === '') {

                return $this->json([
                    'error' =>
                        'Could not extract text from this PDF.',

                    'details' =>
                        'The PDF may be scanned or contain no selectable text.'
                ], 400);
            }

            /*
             * =====================================================
             * 6. ANALYZE WITH GEMINI
             * =====================================================
             */

            $analysis =
                $geminiService->analyzeCv($cvText);

            /*
             * =====================================================
             * 7. CREATE CV ENTITY
             * =====================================================
             */

            $cv = new CV();

            $cv->setTitle(
                pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                )
            );

            $cv->setFileName($filename);

            $cv->setUploadAt(
                new \DateTime()
            );

            $cv->setAnalysis(
                json_encode(
                    $analysis,
                    JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_UNICODE
                )
            );

            /*
             * =====================================================
             * 8. SAVE TO DATABASE
             * =====================================================
             *
             * No User association.
             *
             * The CV is now anonymous.
             */

            $entityManager->persist($cv);

            $entityManager->flush();

            /*
             * =====================================================
             * 9. SUCCESS RESPONSE
             * =====================================================
             */

            return $this->json([
                'message' =>
                    'CV uploaded and analyzed successfully',

                'cv' => [
                    'id' =>
                        $cv->getId(),

                    'title' =>
                        $cv->getTitle(),

                    'fileName' =>
                        $cv->getFileName(),

                    'uploadAt' =>
                        $cv->getUploadAt()
                            ->format('Y-m-d H:i:s'),

                    'analysis' =>
                        $analysis
                ]
            ], 201);

        } catch (\RuntimeException $e) {

            /*
             * =====================================================
             * GEMINI / SERVICE ERROR
             * =====================================================
             */

            $message =
                $e->getMessage();

            if (
                str_contains(
                    $message,
                    'Gemini API'
                ) ||
                str_contains(
                    $message,
                    'Gemini is temporarily unavailable'
                ) ||
                str_contains(
                    $message,
                    'Gemini cURL error'
                ) ||
                str_contains(
                    $message,
                    'Gemini returned'
                )
            ) {

                return $this->json([
                    'error' =>
                        'CV analysis service is temporarily unavailable.',

                    'details' =>
                        $message
                ], 503);
            }

            /*
             * Other RuntimeException errors.
             */

            return $this->json([
                'error' =>
                    'CV analysis failed.',

                'details' =>
                    $message
            ], 500);

        } catch (\Throwable $e) {

            /*
             * =====================================================
             * UNEXPECTED APPLICATION ERROR
             * =====================================================
             */

            return $this->json([
                'error' =>
                    'CV analysis failed.',

                'details' =>
                    $e->getMessage()
            ], 500);
        }
    }
}
