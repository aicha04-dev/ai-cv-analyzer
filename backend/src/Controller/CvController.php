<?php

namespace App\Controller;

use App\Entity\CV;
use App\Entity\User;
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
         * 1. CHECK AUTHENTICATED USER
         * =========================================================
         */

        $user = $this->getUser();

        if (!$user instanceof User) {

            return $this->json([
                'error' =>
                    'You must be logged in to upload and analyze a CV.'
            ], 401);
        }

        /*
         * =========================================================
         * 2. GET UPLOADED FILE
         * =========================================================
         */

        $file = $request->files->get('cv');

        if (!$file) {

            return $this->json([
                'error' => 'No CV file uploaded.'
            ], 400);
        }

        /*
         * =========================================================
         * 3. CHECK PDF
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
             * 4. UPLOAD PDF
             * =====================================================
             */

            $filename = $fileUploader->upload($file);

            /*
             * =====================================================
             * 5. BUILD FILE PATH
             * =====================================================
             */

            $filePath =
                $this->getParameter('kernel.project_dir')
                . '/public/uploads/'
                . $filename;

            /*
             * =====================================================
             * 6. EXTRACT TEXT
             * =====================================================
             */

            $cvText = $pdfTextExtractor->extract($filePath);

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
             * 7. ANALYZE WITH GEMINI
             * =====================================================
             */

            $analysis = $geminiService->analyzeCv($cvText);

            /*
             * =====================================================
             * 8. CREATE CV ENTITY
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
             * Associate CV with authenticated user.
             */

            $cv->setUser($user);

            /*
             * =====================================================
             * 9. SAVE TO DATABASE
             * =====================================================
             */

            $entityManager->persist($cv);

            $entityManager->flush();

            /*
             * =====================================================
             * 10. SUCCESS RESPONSE
             * =====================================================
             */

            return $this->json([
                'message' =>
                    'CV uploaded and analyzed successfully',

                'cv' => [
                    'id' => $cv->getId(),

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
             *
             * Return 503 when an external AI service is
             * temporarily unavailable.
             */

            $message = $e->getMessage();

            if (
                str_contains($message, 'Gemini API') ||
                str_contains($message, 'Gemini is temporarily unavailable') ||
                str_contains($message, 'Gemini cURL error') ||
                str_contains($message, 'Gemini returned')
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