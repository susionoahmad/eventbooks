<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OcrService
{
    /**
     * Extract coordinates and maps URL from an invitation image.
     *
     * @param string $imagePath Absolute path to the image file
     * @return array Array containing maps_btn_top, maps_btn_left, maps_btn_width, and maps_url
     */
    public static function extractAddressCoordinates(string $imagePath): array
    {
        // Default fallback values
        $result = [
            'maps_btn_top' => 72.00,
            'maps_btn_left' => 15.00,
            'maps_btn_width' => 70.00,
            'maps_btn_height' => 6.00,
            'maps_url' => null,
        ];

        // 1. Check if the image exists
        if (!file_exists($imagePath)) {
            Log::warning("OCR Service: Image file not found at {$imagePath}");
            return $result;
        }

        // 2. Check if Google Vision client library and key are configured
        $keyPath = storage_path('app/google-vision-key.json');
        if (!file_exists($keyPath)) {
            Log::info("OCR Service: GCP Vision credentials not found at {$keyPath}. Using default coordinates.");
            return $result;
        }

        if (!class_exists('\Google\Cloud\Vision\V1\ImageAnnotatorClient')) {
            Log::info("OCR Service: Google Cloud Vision PHP library is not installed. Using default coordinates.");
            return $result;
        }

        try {
            // Get image dimensions for pixel-to-percentage conversion
            $imageInfo = @getimagesize($imagePath);
            if (!$imageInfo) {
                Log::warning("OCR Service: Failed to read image dimensions for {$imagePath}");
                return $result;
            }

            $imgWidth = $imageInfo[0];
            $imgHeight = $imageInfo[1];

            // Initialize Vision Client
            $imageAnnotator = new \Google\Cloud\Vision\V1\ImageAnnotatorClient([
                'keyFile' => json_decode(file_get_contents($keyPath), true)
            ]);

            $imageContent = file_get_contents($imagePath);
            $response = $imageAnnotator->documentTextDetection($imageContent);
            $annotation = $response->getFullTextAnnotation();

            if ($annotation) {
                foreach ($annotation->getPages() as $page) {
                    foreach ($page->getBlocks() as $block) {
                        $blockText = '';
                        foreach ($block->getParagraphs() as $paragraph) {
                            foreach ($paragraph->getWords() as $word) {
                                foreach ($word->getSymbols() as $symbol) {
                                    $blockText .= $symbol->getText();
                                }
                                $blockText .= ' ';
                            }
                        }

                        // Search for address indicators or maps URL
                        if (stripos($blockText, 'Jl.') !== false || 
                            stripos($blockText, 'Jalan') !== false || 
                            stripos($blockText, 'maps.app.goo.gl') !== false ||
                            stripos($blockText, 'maps.google.com') !== false) {

                            $vertices = $block->getBoundingBox()->getVertices();
                            if (count($vertices) >= 4) {
                                // Get bounding box coordinates in pixels
                                $xMin = $vertices[0]->getX();
                                $yMin = $vertices[0]->getY();
                                $yMax = $vertices[2]->getY(); // bottom-most Y coordinate of the block
                                $xMax = $vertices[1]->getX();

                                $blockWidth = $xMax - $xMin;
                                $blockHeight = $yMax - $yMin;

                                // Convert to percentages
                                // Position the button exactly below the address (bottom-most Y + vertical margin)
                                $verticalMargin = 25; // 25 pixels
                                $topPercent = (($yMax + $verticalMargin) / $imgHeight) * 100;
                                $leftPercent = ($xMin / $imgWidth) * 100;
                                $widthPercent = ($blockWidth / $imgWidth) * 100;
                                $heightPercent = ($blockHeight / $imgHeight) * 100;

                                // Bound the percentages to ensure visibility
                                $result['maps_btn_top'] = round(min(92.00, max(5.00, $topPercent)), 2);
                                $result['maps_btn_left'] = round(min(80.00, max(5.00, $leftPercent)), 2);
                                $result['maps_btn_width'] = round(min(90.00, max(10.00, $widthPercent)), 2);
                                $result['maps_btn_height'] = round(min(20.00, max(2.00, $heightPercent)), 2);

                                // Try to extract maps URL if present in the text block
                                $regex = '/https?:\/\/(?:maps\.)?google\.[a-z\.]+\/\S+|https?:\/\/maps\.app\.goo\.gl\/\S+/i';
                                if (preg_match($regex, $blockText, $matches)) {
                                    $result['maps_url'] = trim($matches[0]);
                                }

                                Log::info("OCR Service: Successfully extracted coordinates. Top: {$result['maps_btn_top']}%, Left: {$result['maps_btn_left']}%, Width: {$result['maps_btn_width']}%");
                                break 2;
                            }
                        }
                    }
                }
            }
            $imageAnnotator->close();
        } catch (\Exception $e) {
            Log::error("OCR Service Exception: " . $e->getMessage());
        }

        return $result;
    }
}
