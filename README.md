# Stock modul

Termékek készlet kezelése

## Előfeltételek

Telepíteni kell a következő modult (composer `require`):
- `istvanmolitor/product`

## Telepítés

### Provider regisztrálása
config/app.php
```php
'providers' => ServiceProvider::defaultProviders()->merge([
    /*
    * Package Service Providers...
    */
    \Molitor\Stock\Providers\StockServiceProvider::class,
])->toArray(),
```

### Seeder regisztrálása

database/seeders/DatabaseSeeder.php
```php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StockSeeder::class,
        ]);
    }
}
```

