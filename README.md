# WebPulse

Monitor de sitios web: analiza una URL y detecta enlaces rotos, errores de carga, problemas de SEO e imágenes pesadas. Da puntuaciones por categoría, guarda el historial de auditorías y genera reportes técnicos.

> 🚧 En construcción. Demo pública próximamente.

## Stack

- Laravel 13 (PHP 8.3)
- Livewire + Tailwind CSS
- MySQL en producción, SQLite en local
- Colas y Scheduler de Laravel para auditorías y monitoreo

## Modelo de datos

| Tabla | Qué guarda |
|-------|------------|
| `sites` | Sitios registrados, si se monitorean y cada cuánto |
| `audits` | Cada auditoría: estado, código HTTP, tiempo de carga y puntuaciones (rendimiento, SEO, enlaces, total) |
| `audit_issues` | Problemas encontrados, con categoría, severidad, URL afectada y detalles |

## Ejecutar en local

```bash
git clone https://github.com/ingsivan/webpulse.git
cd webpulse
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Pruebas:

```bash
php artisan test
```

## Hoja de ruta

- [x] Proyecto base y modelos `Site`, `Audit`, `AuditIssue`
- [ ] Servicio `SiteAuditor` (estado, carga, SEO, imágenes, enlaces) con protección contra SSRF
- [ ] Auditorías en cola con progreso en vivo (Livewire)
- [ ] Dashboard con puntuaciones e historial
- [ ] Monitoreo programado con alertas por correo
- [ ] Reporte PDF
- [ ] CI con GitHub Actions y demo pública

## Créditos

Construido con ayuda de Claude; el diseño, las decisiones y las pruebas son míos.
