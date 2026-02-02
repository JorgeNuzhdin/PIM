# Configuración de Email para Recuperación de Contraseña

Para que funcione la recuperación de contraseña, necesitas configurar el envío de emails en tu archivo `.env`:

## Configuración SMTP

Añade o modifica estas líneas en tu archivo `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=tu_servidor_smtp
MAIL_PORT=587
MAIL_USERNAME=tu_usuario_smtp
MAIL_PASSWORD=tu_contraseña_smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@matematicamente.es
MAIL_FROM_NAME="PIM - Matemáticamente"
```

## Ejemplo de configuración para diferentes proveedores

### Gmail
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_contraseña_de_aplicacion
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@matematicamente.es
MAIL_FROM_NAME="PIM - Matemáticamente"
```

### Mailgun
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=tu_dominio.mailgun.org
MAILGUN_SECRET=tu_clave_api_mailgun
MAIL_FROM_ADDRESS=info@matematicamente.es
MAIL_FROM_NAME="PIM - Matemáticamente"
```

### SendGrid
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=tu_api_key_sendgrid
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@matematicamente.es
MAIL_FROM_NAME="PIM - Matemáticamente"
```

## Modo de prueba (para desarrollo)

Si quieres probar sin enviar emails reales, puedes usar:

```env
MAIL_MAILER=log
```

Esto escribirá los emails en `storage/logs/laravel.log` en lugar de enviarlos.

## Verificar configuración

Después de configurar, ejecuta:

```bash
php artisan config:cache
```

Para verificar que el email funciona, puedes usar:

```bash
php artisan tinker
```

Y ejecutar:

```php
Mail::raw('Test email', function ($message) {
    $message->to('tu_email@example.com')->subject('Test');
});
```
