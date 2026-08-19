<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class OrderStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const LOGO_CID = 'exacto-logo@exacto.local';

    /**
     * @param  array<string, mixed>  $orden
     */
    public function __construct(
        public readonly array $orden,
        public readonly string $estatus,
        public readonly ?string $pdfContent = null,
        public readonly ?string $pdfFilename = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectForStatus(),
            using: [
                function (Email $message): void {
                    $logoPath = $this->logoPath();
                    if (! is_file($logoPath)) {
                        return;
                    }

                    $message->addPart(
                        DataPart::fromPath($logoPath, 'logo.jpeg', 'image/jpeg')
                            ->asInline()
                            ->setContentId(self::LOGO_CID)
                    );
                },
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.status',
            with: [
                'orden' => $this->orden,
                'estatus' => $this->estatus,
                'logoSrc' => $this->hasLogo() ? 'cid:'.self::LOGO_CID : null,
            ]
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->pdfContent === null || $this->pdfContent === '') {
            return [];
        }

        $filename = $this->pdfFilename ?: 'orden-servicio.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfContent, $filename)
                ->withMime('application/pdf'),
        ];
    }

    private function subjectForStatus(): string
    {
        $folio = trim((string) ($this->orden['folio'] ?? ''));
        $suffix = $folio !== '' ? ' - '.$folio : '';

        return match ($this->estatus) {
            'Terminado' => 'Su equipo está listo'.$suffix,
            'Entregado' => 'Equipo entregado'.$suffix,
            default => 'Orden recibida en Exacto'.$suffix,
        };
    }

    private function hasLogo(): bool
    {
        return is_file($this->logoPath());
    }

    private function logoPath(): string
    {
        return public_path('legacy/public/img/logo.jpeg');
    }
}
