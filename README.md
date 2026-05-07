# Subscription Billing Platform

Sistema de gestión de suscripciones y pagos construido con Laravel/PHP. El objetivo del proyecto no es demostrar un CRUD básico, sino un motor de facturación periódica con lógica de negocio, colas, eventos, generación de facturas PDF, testing y despliegue reproducible con Docker.

## Por qué Laravel

Elegí Laravel porque combina productividad con herramientas maduras para procesos financieros:

- **Queues nativas**: permiten procesar cobros, PDFs y correos fuera del request HTTP.
- **Eventos y listeners**: desacoplan el éxito/fallo del pago de acciones secundarias como correos y generación de PDF.
- **Service Container**: facilita inyección de dependencias y testing unitario/feature.
- **Eloquent y migraciones**: aceleran el modelado de entidades financieras sin perder claridad.
- **Monolog integrado**: permite canales separados para auditoría y troubleshooting.

## Arquitectura

```text
Subscription -> Plan
Subscription -> Invoice
BillingService -> PaymentGatewayFactory -> StripePaymentGateway | PayPalPaymentGateway
BillingService -> PaymentSucceeded | PaymentFailed events
PaymentSucceeded -> GenerateInvoicePdf + SendPaymentSucceededEmail
PaymentFailed -> SendPaymentFailedEmail
Scheduler -> ProcessRecurringBillingJob -> BillingService
```

## Patrones de diseño usados

- **Strategy**: `PaymentGateway` define el contrato común para pasarelas. `StripePaymentGateway` y `PayPalPaymentGateway` encapsulan comportamientos específicos.
- **Factory**: `PaymentGatewayFactory` selecciona la estrategia según la suscripción o configuración.
- **Domain Service**: `BillingService` centraliza la regla de facturación periódica.
- **Event-driven architecture**: eventos `PaymentSucceeded` y `PaymentFailed` desacoplan efectos secundarios.

## Casos de negocio cubiertos

- Planes con distintos precios, moneda, intervalo y días de prueba.
- Suscripciones `trialing`, `active`, `past_due` y `canceled`.
- Cobro periódico cuando `current_period_ends_at` vence.
- Renovación automática del periodo tras pago exitoso.
- Marcado como `past_due` ante pago fallido.
- Simulación de tarjeta expirada mediante tokens que contienen `expired`.
- Simulación de fondos insuficientes mediante tokens que contienen `fail`.
- Facturas con estado `draft`, `paid` y `failed`.
- Generación de PDF para facturas pagadas.

## Ejecución con Docker

Requisitos:

- Docker
- Docker Compose

```bash
cp .env.example .env
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose exec app composer install --no-interaction --prefer-dist
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

La aplicación queda disponible en:

```text
http://localhost:8080
```

Procesos incluidos:

- **app**: PHP-FPM/Laravel.
- **nginx**: servidor web.
- **mysql**: base de datos.
- **redis**: backend de colas.
- **queue**: worker `queue:work`.
- **scheduler**: ejecuta `schedule:run` cada minuto.

El proyecto usa un volumen dedicado `vendor-data` para `/var/www/html/vendor`. Esto evita que el bind mount local `.:/var/www/html` oculte las dependencias instaladas por Composer dentro del contenedor.

Si aparece un error como:

```text
Class "Illuminate\Foundation\Application" not found
Class Illuminate\Foundation\ComposerScripts is not autoloadable
```

significa que `vendor` está vacío o incompleto dentro del contenedor. Ejecuta:

```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
docker compose exec app composer install --no-interaction --prefer-dist
```

## Facturación periódica

El scheduler dispara diariamente `ProcessRecurringBillingJob` a las 02:00:

```php
Schedule::job(new ProcessRecurringBillingJob)->dailyAt('02:00');
```

El job busca suscripciones vencidas y delega en `BillingService`.

Para correrlo manualmente:

```bash
docker compose exec app php artisan queue:work redis
docker compose exec app php artisan schedule:run
```

## Testing

El proyecto usa Pest sobre PHPUnit.

```bash
docker compose exec app php artisan test
```

Casos incluidos:

- **Pago exitoso**: factura pagada, transacción registrada y periodo renovado.
- **Tarjeta expirada**: factura fallida y suscripción `past_due`.
- **PayPal Strategy**: mismo flujo de facturación con otra pasarela.

## CI/CD

GitHub Actions está configurado en `.github/workflows/ci.yml` para ejecutar en cada push/PR:

- Instalación de dependencias.
- Preparación de `.env` y `APP_KEY`.
- Laravel Pint en modo verificación.
- Tests con cobertura Clover.
- SonarCloud si existe `SONAR_TOKEN` en secrets.

Para activar SonarCloud:

1. Crear proyecto en SonarCloud.
2. Configurar `SONAR_TOKEN` como GitHub Secret.
3. Ajustar `sonar.projectKey` en `sonar-project.properties`.

## Monitoreo y logs

Laravel usa Monolog internamente. Se agregó un canal dedicado:

```php
'billing' => [
    'driver' => 'single',
    'path' => storage_path('logs/billing.log'),
]
```

Eventos relevantes se registran con contexto:

- Pago exitoso: `invoice_id`.
- Pago fallido: `invoice_id` y `reason`.
- Correos simulados: log de encolado.

En producción manejaría errores así:

- Enviar logs estructurados a Datadog, ELK, Papertrail o CloudWatch.
- Alertar si sube la tasa de `PaymentFailed`.
- Alertar si crece la tabla `failed_jobs`.
- Configurar reintentos con backoff para pasarelas externas.
- Usar idempotency keys por factura para evitar cobros duplicados.
- Enmascarar datos sensibles y nunca guardar PAN/CVV.

## Seguridad financiera

Este repositorio simula pasarelas, pero la integración real debe cumplir:

- No guardar datos de tarjeta directamente.
- Usar tokens de Stripe/PayPal.
- Usar webhooks firmados.
- Implementar idempotencia por `invoice.number`.
- Auditar cambios de estado financiero.

## Estructura relevante

```text
app/Models
app/Services
app/Payments
app/Events
app/Listeners
app/Jobs
database/migrations
tests/Feature
.github/workflows
```

## Próximas mejoras

- Webhooks reales de Stripe y PayPal.
- Panel administrativo de suscripciones.
- Tabla de intentos de pago.
- Reintentos inteligentes antes de cancelar.
- Emails reales con Mailable.
- Observabilidad con métricas Prometheus.
