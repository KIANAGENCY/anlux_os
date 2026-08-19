<?php

declare(strict_types=1);

namespace App\Services;

final class EmailSmtpProbeService
{
    /**
     * @return array{status: string, email: string, message: string}
     */
    public function probe(?string $email): array
    {
        $email = mb_strtolower(trim((string) $email), 'UTF-8');
        if ($email === '') {
            return [
                'status' => 'empty',
                'email' => '',
                'message' => 'No se registró correo electrónico.',
            ];
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'invalid',
                'email' => $email,
                'message' => 'El correo electrónico no tiene un formato válido.',
            ];
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return [
                'status' => 'invalid',
                'email' => $email,
                'message' => 'El correo electrónico no tiene un formato válido.',
            ];
        }

        $mxHosts = $this->mxHosts($domain);
        if ($mxHosts === []) {
            return [
                'status' => 'rejected',
                'email' => $email,
                'message' => 'El dominio del correo no tiene registros MX o no acepta correo.',
            ];
        }

        $fallback = [
            'status' => 'unverifiable',
            'email' => $email,
            'message' => 'No fue posible verificar el correo por SMTP.',
        ];
        foreach ($mxHosts as $host) {
            $result = $this->probeHost($host, $email);
            if (in_array($result['status'], ['verified', 'rejected'], true)) {
                return $result;
            }
            $fallback = $result;
        }

        return $fallback;
    }

    /**
     * @return list<string>
     */
    private function mxHosts(string $domain): array
    {
        $hosts = [];

        if (function_exists('dns_get_record')) {
            try {
                $records = @dns_get_record($domain, DNS_MX);
                if (is_array($records)) {
                    foreach ($records as $record) {
                        $target = trim((string) ($record['target'] ?? ''));
                        if ($target === '') {
                            continue;
                        }
                        $hosts[] = [
                            'host' => rtrim($target, '.'),
                            'priority' => (int) ($record['pri'] ?? 0),
                        ];
                    }
                }
            } catch (\Throwable) {
                // ignore and keep probing fallbacks
            }
        }

        if ($hosts === [] && function_exists('getmxrr')) {
            $mx = [];
            $weight = [];
            if (@getmxrr($domain, $mx, $weight)) {
                foreach ($mx as $index => $host) {
                    $host = trim((string) $host);
                    if ($host === '') {
                        continue;
                    }
                    $hosts[] = [
                        'host' => rtrim($host, '.'),
                        'priority' => (int) ($weight[$index] ?? 0),
                    ];
                }
            }
        }

        if ($hosts === []) {
            $hasDns = false;
            foreach (['MX', 'A', 'AAAA'] as $type) {
                if (function_exists('checkdnsrr') && @checkdnsrr($domain, $type)) {
                    $hasDns = true;
                    break;
                }
            }
            if ($hasDns) {
                $hosts[] = ['host' => $domain, 'priority' => 0];
            }
        }

        usort($hosts, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);
        $unique = [];
        foreach ($hosts as $item) {
            $host = trim((string) ($item['host'] ?? ''));
            if ($host === '' || in_array($host, $unique, true)) {
                continue;
            }
            $unique[] = $host;
        }

        return $unique;
    }

    /**
     * @return array{status: string, email: string, message: string}
     */
    private function probeHost(string $host, string $email): array
    {
        $timeout = 6;
        $socket = @stream_socket_client(
            'tcp://'.$host.':25',
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (! is_resource($socket)) {
            return [
                'status' => 'unverifiable',
                'email' => $email,
                'message' => 'No fue posible abrir conexión SMTP con el dominio del correo.',
            ];
        }

        stream_set_timeout($socket, $timeout);

        try {
            $greeting = $this->readResponse($socket);
            if (! $this->isPositiveReply($greeting['code'])) {
                return [
                    'status' => 'unverifiable',
                    'email' => $email,
                    'message' => 'El servidor SMTP no respondió de forma utilizable.',
                ];
            }

            $heloHost = $this->heloHost();
            $hello = $this->sendCommand($socket, 'EHLO '.$heloHost);
            if (! $this->isPositiveReply($hello['code'])) {
                $hello = $this->sendCommand($socket, 'HELO '.$heloHost);
                if (! $this->isPositiveReply($hello['code'])) {
                    return [
                        'status' => 'unverifiable',
                        'email' => $email,
                        'message' => 'El servidor SMTP rechazó el saludo de verificación.',
                    ];
                }
            }

            $mailFrom = $this->probeSenderAddress($heloHost);
            $mailFromReply = $this->sendCommand($socket, 'MAIL FROM:<'.$mailFrom.'>');
            if (! $this->isPositiveReply($mailFromReply['code'])) {
                return [
                    'status' => 'unverifiable',
                    'email' => $email,
                    'message' => 'El servidor SMTP no permitió iniciar la validación del correo.',
                ];
            }

            $rcptReply = $this->sendCommand($socket, 'RCPT TO:<'.$email.'>');
            $code = $rcptReply['code'];

            if (in_array($code, [250, 251], true)) {
                return [
                    'status' => 'verified',
                    'email' => $email,
                    'message' => 'El servidor SMTP aceptó el correo de destino.',
                ];
            }
            if ($code === 252) {
                return [
                    'status' => 'unverifiable',
                    'email' => $email,
                    'message' => 'El servidor SMTP aceptó el dominio, pero no confirmó si el buzón existe.',
                ];
            }
            if ($code >= 550 && $code <= 559) {
                return [
                    'status' => 'rejected',
                    'email' => $email,
                    'message' => 'El servidor SMTP rechazó el correo de destino como inexistente o no válido.',
                ];
            }

            return [
                'status' => 'unverifiable',
                'email' => $email,
                'message' => 'El servidor SMTP no confirmó el buzón y pidió intentar después o usar otro método.',
            ];
        } finally {
            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
        }
    }

    private function heloHost(): string
    {
        $appHost = (string) parse_url((string) config('app.url', ''), PHP_URL_HOST);
        if ($appHost !== '') {
            return $appHost;
        }

        $host = trim((string) gethostname());

        return $host !== '' ? $host : 'localhost';
    }

    private function probeSenderAddress(string $heloHost): string
    {
        $from = trim((string) config('mail.from.address', ''));
        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return $from;
        }

        $domain = str_contains($heloHost, '.') ? $heloHost : 'localhost.localdomain';

        return 'noreply@'.$domain;
    }

    /**
     * @return array{code: int, text: string}
     */
    private function sendCommand($socket, string $command): array
    {
        @fwrite($socket, $command."\r\n");

        return $this->readResponse($socket);
    }

    /**
     * @return array{code: int, text: string}
     */
    private function readResponse($socket): array
    {
        $lines = [];
        $code = 0;

        while (! feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $line = rtrim($line, "\r\n");
            $lines[] = $line;
            if (preg_match('/^(\d{3})([ -])/', $line, $matches) === 1) {
                $code = (int) $matches[1];
                if ($matches[2] === ' ') {
                    break;
                }
            } else {
                break;
            }
        }

        return [
            'code' => $code,
            'text' => trim(implode(' ', $lines)),
        ];
    }

    private function isPositiveReply(int $code): bool
    {
        return $code >= 200 && $code < 400;
    }
}
