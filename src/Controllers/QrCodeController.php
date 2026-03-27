<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\QrCodeService;

class QrCodeController
{
    private QrCodeService $qrCodes;

    public function __construct()
    {
        $this->qrCodes = new QrCodeService();
    }

    public function generate(Request $request, string $id): Response
    {
        $qr = $this->qrCodes->getOrGenerate((int) $id);

        return Response::success([
            'qr' => $qr,
            'download_url' => '/api/animals/' . $id . '/qr/download',
        ], 'QR code retrieved successfully.');
    }

    public function download(Request $request, string $id): Response
    {
        $qr = $this->qrCodes->getOrGenerate((int) $id);
        $path = dirname(__DIR__, 2) . '/public/' . $qr['file_path'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $contentType = $extension === 'svg' ? 'image/svg+xml' : 'image/png';

        if (ob_get_level()) ob_clean();

        return new Response(200, (string) file_get_contents($path), [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="animal-' . $id . '-qr.' . $extension . '"',
        ]);
    }

    public function scan(Request $request, string $qrData): Response
    {
        $animal = $this->qrCodes->resolveScan($qrData);
        if ($animal === false) {
            return Response::error(404, 'NOT_FOUND', 'QR code did not resolve to an animal.');
        }

        return Response::success([
            'animal' => [
                'id' => $animal['id'],
                'animal_id' => $animal['animal_id'],
                'name' => $animal['name'],
            ],
            'redirect' => '/animals/' . $animal['id'],
        ], 'QR code resolved successfully.');
    }
}
