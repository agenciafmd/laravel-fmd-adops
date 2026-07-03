<?php

declare(strict_types=1);

namespace Agenciafmd\FmdAdops\Jobs;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Throwable;

final class SendConversionsToFmdAdopsWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 60;

    public function __construct(protected array $data = []) {}

    public function handle(): void
    {
        if (! config('laravel-fmd-adops.webhook')) {
            return;
        }

        if (! config('laravel-fmd-adops.secret')) {
            return;
        }

        try {
            $client = $this->getClientRequest();

            $payload = json_encode(
                $this->data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $signature = 'sha256=' . hash_hmac(
                'sha256',
                $payload,
                config('laravel-fmd-adops.secret')
            );

            $response = $client->request('POST', config('laravel-fmd-adops.webhook'), [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                ],
            ]);

            if (($response->getStatusCode() !== 200) && (config('laravel-fmd-adops.error_email'))) {
                Mail::raw($response->getBody(), static function (Message $message): void {
                    $message->to(config('laravel-fmd-adops.error_email'))
                        ->subject('[F&MD Adops][' . config('app.url') . '] - Falha na integração - ' . now()->format('d/m/Y H:i:s'));
                });

                // IMPORTANTE: força falha do job → Laravel faz retry
                throw new Exception('Webhook retornou status: ' . $response->getStatusCode());
            }

        } catch (Throwable $throwable) {
            Log::error('Erro no envio webhook FMD Adops', [
                'error' => $throwable->getMessage(),
                'data' => $this->data,
            ]);

            throw $throwable; // <- ESSENCIAL para retry da fila
        }
    }

    private function getClientRequest(): Client
    {
        $logger = new Logger('FmdAdops');
        $logger->pushHandler(new StreamHandler(storage_path('logs/fmd-adops-' . date('Y-m-d') . '.log')));

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $logger,
                new MessageFormatter('{method} {uri} HTTP/{version} {req_body} | RESPONSE: {code} - {res_body}')
            )
        );

        return new Client([
            'timeout' => 60,
            'connect_timeout' => 60,
            'http_errors' => false,
            'verify' => false,
            'handler' => $stack,
        ]);
    }
}
