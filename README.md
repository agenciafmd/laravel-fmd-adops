## Laravel - F&MD Adops Webhook

[![Downloads](https://img.shields.io/packagist/dt/agenciafmd/laravel-fmd-adops-webhook.svg?style=flat-square)](https://packagist.org/packages/agenciafmd/laravel-fmd-adops)
[![Licença](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

- Envia as conversões para o Webhook da F&MD Adops

## Instalação

```bash
sail composer require agenciafmd/laravel-fmd-adops:dev-master
```

## Configuração

Primeiro, vamos solicitar a url do Webhook ao time de Adops.

Colocamos esta url no nosso .env

```dotenv
FMD_DIGITAL_WEBHOOK=https://adops.fmd.digital/api/webhooks/generic/xxxxxxx
```

## Uso

Envie os campos no formato de array para o SendConversionsToFmdAdopsWebhook.

Para que o processo funcione pelos **jobs**, é preciso passar os valores dos cookies conforme mostrado abaixo.

```php
use Agenciafmd\FmdAdops\Jobs\SendConversionsToFmdAdopsWebhook;
use Illuminate\Support\Facades\Cookie;

$data['email'] = 'irineu@fmd.ag';
$data['nome'] = 'Irineu Junior';

SendConversionsToFmdAdopsWebhook::dispatch($data + [
        'identificador' => 'seja-um-parceiro',
        'utm_campaign' => Cookie::get('utm_campaign', ''),
        'utm_content' => Cookie::get('utm_content', ''),
        'utm_medium' => Cookie::get('utm_medium', ''),
        'utm_source' => Cookie::get('utm_source', ''),
        'utm_term' => Cookie::get('utm_term', ''),
        'gclid_' => Cookie::get('gclid', ''),
        'cid' => Cookie::get('cid', ''),
    ])
    ->delay(5)
    ->onQueue('low');
```

Note que no nosso exemplo, enviamos o job para a fila **low**.

Certifique-se de estar rodando no seu queue:work esteja semelhante ao abaixo.

```shell
php artisan queue:work --tries=3 --delay=5 --timeout=60 --queue=high,default,low
```
