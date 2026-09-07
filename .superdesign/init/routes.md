# Routes

Routing is configured in `routes/web.php` and rendered by Laravel controllers/Blade.

| URL | Handler | View / UI |
|---|---|---|
| `/` | closure | `resources/views/index.blade.php` |
| `/ordenes` | `OrderController@index` | `resources/views/orders/index.blade.php` |
| `/orden_servicio` | `OrderFormController@create` | `resources/views/orders/orden_page.blade.php` + `orden_form.blade.php` |
| `/orden_servicio/{id}` | `OrderFormController@edit` | Existing order form and delivery modals |
| `/historial-ordenes` | `HistorialOrdenesController@index` | PDF history |
| `/pdf/orden/{id}` | `OrderPdfController@show` | Order PDF |
| `/api/ordenes/registrar` | `OrderController@registrar` | JSON save endpoint used by the delivery signature modal |

Relevant route source:

```php
Route::middleware('auth')->group(function () {
    Route::get('/ordenes', [OrderController::class, 'index'])->name('ordenes.index');
    Route::post('/api/ordenes/registrar', [OrderController::class, 'registrar'])->name('orders.registrar');
    Route::get('/orden_servicio', [OrderFormController::class, 'create'])->name('orden_servicio.create');
    Route::get('/orden_servicio/{id}', [OrderFormController::class, 'edit'])
        ->whereNumber('id')
        ->name('orden_servicio.edit');
    Route::get('/pdf/orden/{id}', [OrderPdfController::class, 'show'])
        ->whereNumber('id')
        ->name('pdf.orden');
});
```

