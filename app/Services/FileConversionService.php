<?php

namespace App\Services;

use Exception;
use Imagick;

class FileConversionService
{
    protected int $pdfDpi;

    protected int $pdfQuality;

    protected string $imageFormat;

    protected int $maxPages;

    public function __construct()
    {
        $config = config('services.file_conversion', []);
        $this->pdfDpi = $config['pdf_dpi'] ?? 150;
        $this->pdfQuality = $config['pdf_quality'] ?? 90;
        $this->imageFormat = $config['image_format'] ?? 'jpg';
        $this->maxPages = $config['max_pages'] ?? 20;
    }

    public function imageToPdf(string $imagePath, ?string $outputPath = null): string
    {
        if ($outputPath === null) {
            $outputPath = $imagePath.'.pdf';
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($this->imagickCanDecode($imagePath)) {
            return $this->imageToPdfViaImagick($imagePath, $outputPath);
        }

        return $this->imageToPdfViaGd($imagePath, $outputPath);
    }

    /**
     * @return list<string>
     */
    public function pdfToImages(string $pdfPath, ?string $outputDir = null): array
    {
        if (! $this->imagickCanDecode($pdfPath)) {
            throw new Exception('Imagick cannot decode PDF files. Ensure ImageMagick is installed with PDF delegate support.');
        }

        $imagick = new Imagick();
        $imagick->readImage($pdfPath);
        $imagick->setResolution($this->pdfDpi, $this->pdfDpi);

        $pageCount = $imagick->getNumberImages();
        $pageCount = min($pageCount, $this->maxPages);

        if ($outputDir === null) {
            $outputDir = dirname($pdfPath).'/../images';
        }

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $baseName = pathinfo($pdfPath, PATHINFO_FILENAME);
        $results = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $imagick->setIteratorIndex($i);
            $imagick->setImageFormat($this->imageFormat);
            $imagick->setImageCompressionQuality($this->pdfQuality);

            $outputFile = $outputDir.'/'.$baseName.'_page-'.($i + 1).'.'.$this->imageFormat;
            $imagick->writeImage($outputFile);
            $results[] = $outputFile;
        }

        $imagick->clear();
        $imagick->destroy();

        return $results;
    }

    public function getMimeType(string $filePath): string
    {
        $mimeType = mime_content_type($filePath);

        return $mimeType !== false ? $mimeType : 'application/octet-stream';
    }

    public function isPdf(string $filePath): bool
    {
        return $this->getMimeType($filePath) === 'application/pdf';
    }

    public function isImage(string $filePath): bool
    {
        return str_starts_with($this->getMimeType($filePath), 'image/');
    }

    protected function imagickCanDecode(string $filePath): bool
    {
        try {
            $imagick = new Imagick();
            $imagick->readImage($filePath);
            $supported = $imagick->getNumberImages() > 0;
            $imagick->clear();
            $imagick->destroy();

            return $supported;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function imageToPdfViaImagick(string $imagePath, string $outputPath): string
    {
        $imagick = new Imagick();
        $imagick->readImage($imagePath);
        $imagick->setResolution($this->pdfDpi, $this->pdfDpi);
        $imagick->setImageFormat('pdf');
        $imagick->writeImages($outputPath, true);
        $imagick->clear();
        $imagick->destroy();

        return $outputPath;
    }

    protected function imageToPdfViaGd(string $imagePath, string $outputPath): string
    {
        $mimeType = $this->getMimeType($imagePath);

        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($imagePath),
            'image/png' => @imagecreatefrompng($imagePath),
            'image/webp' => @imagecreatefromwebp($imagePath),
            default => throw new Exception("Unsupported image type for GD conversion: {$mimeType}"),
        };

        if ($image === false) {
            throw new Exception("Failed to create GD image from: {$imagePath}");
        }

        $width = imagesx($image);
        $height = imagesy($image);

        ob_start();
        imagejpeg($image, null, $this->pdfQuality);
        $jpegData = (string) ob_get_clean();
        imagedestroy($image);

        if ($jpegData === '') {
            throw new Exception('Failed to encode image as JPEG for PDF conversion');
        }

        return $this->buildPdfFromJpeg($jpegData, $width, $height, $outputPath);
    }

    protected function buildPdfFromJpeg(string $jpegData, int $imageWidth, int $imageHeight, string $outputPath): string
    {
        $scale = 72 / $this->pdfDpi;
        $pageWidth = round($imageWidth * $scale, 2);
        $pageHeight = round($imageHeight * $scale, 2);

        $offsets = [];
        $body = "%PDF-1.4\n";

        $offsets[1] = strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $offsets[2] = strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $offsets[3] = strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Contents 4 0 R /Resources << /XObject << /Img 5 0 R >> >> >>\nendobj\n";

        $content = "q\n{$pageWidth} 0 0 {$pageHeight} 0 0 cm\n/Img Do\nQ\n";
        $offsets[4] = strlen($body);
        $body .= "4 0 obj\n<< /Length ".strlen($content)." >>\nstream\n{$content}endstream\nendobj\n";

        $offsets[5] = strlen($body);
        $body .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imageWidth} /Height {$imageHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpegData)." >>\nstream\n";
        $body .= $jpegData;
        $body .= "\nendstream\nendobj\n";

        $xrefOffset = strlen($body);
        $body .= "xref\n0 6\n";
        $body .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $body .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $body .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        file_put_contents($outputPath, $body);

        return $outputPath;
    }
}
