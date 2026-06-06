<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Categoria;
use App\Models\Producto;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeKayfaStore extends Command
{
    protected $signature = 'scrape:kayfa';
    protected $description = 'Scrape categories and products from Kayfa Store';

    public function handle()
    {
        $this->info('Iniciando el proceso de scraping de Kayfa Store...');

        $this->info('Vaciando tablas...');
        DB::statement('TRUNCATE TABLE productos CASCADE');
        DB::statement('TRUNCATE TABLE categorias CASCADE');

        $baseUrl = 'https://www.kayfa-store.com';
        $homeUrl = $baseUrl . '/home';

        $this->info("Obteniendo página principal: {$homeUrl}");
        $response = Http::get($homeUrl);

        if (!$response->successful()) {
            $this->error('No se pudo acceder a la página principal.');
            return;
        }

        $crawler = new Crawler($response->body());

        // Extracting categories from #mobile-menu ul li a
        $categoryLinks = $crawler->filter('#mobile-menu ul li a')->extract(['href', '_text']);
        
        $categoriesToScrape = [];

        foreach ($categoryLinks as $linkData) {
            $url = $linkData[0];
            $name = trim($linkData[1]);

            if (str_contains($url, '/product/family/')) {
                // Remove weird formatting
                $name = preg_replace('/\s+/', ' ', $name);
                $categoriesToScrape[$url] = $name;
            }
        }

        $this->info('Se encontraron ' . count($categoriesToScrape) . ' categorías. Procesando...');

        foreach ($categoriesToScrape as $url => $categoryName) {
            $this->info("Procesando categoría: {$categoryName}");
            
            $categoria = Categoria::create([
                'nombre' => $categoryName,
                'estado' => true
            ]);

            $catResponse = Http::get($url);
            if (!$catResponse->successful()) {
                $this->error("Error al obtener la categoría: {$url}");
                continue;
            }

            $catCrawler = new Crawler($catResponse->body());

            $productsNode = $catCrawler->filter('.product-item');
            
            if ($productsNode->count() === 0) {
                $this->warn("No se encontraron productos en la categoría {$categoryName}.");
                continue;
            }

            $productsNode->each(function (Crawler $node, $i) use ($categoria) {
                try {
                    $nameNode = $node->filter('.item-title a');
                    $name = $nameNode->count() > 0 ? trim($nameNode->text()) : 'Producto sin nombre';

                    $priceNode = $node->filter('.item-price .price');
                    $priceText = $priceNode->count() > 0 ? $priceNode->first()->text() : '0';
                    $price = floatval(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $priceText)));

                    $imgNode = $node->filter('.pr-img-area img');
                    $imgUrl = $imgNode->count() > 0 ? $imgNode->attr('src') : null;

                    $localImagePath = null;
                    if ($imgUrl) {
                        if (!str_starts_with($imgUrl, 'http')) {
                            if (str_starts_with($imgUrl, '//')) {
                                $imgUrl = 'https:' . $imgUrl;
                            } else {
                                $imgUrl = rtrim('https://www.kayfa-store.com', '/') . '/' . ltrim($imgUrl, '/');
                            }
                        }

                        $imageContent = Http::get($imgUrl)->body();
                        $ext = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if (!$ext) $ext = 'jpg';
                        $imageName = 'productos/scraped_' . Str::random(10) . '.' . $ext;
                        Storage::disk('public')->put($imageName, $imageContent);
                        $localImagePath = $imageName;
                    }

                    Producto::create([
                        'codigo' => 'KAYFA-' . Str::upper(Str::random(6)),
                        'nombre' => Str::limit($name, 250),
                        'descripcion' => 'Importado de Kayfa Store',
                        'categoria_id' => $categoria->id,
                        'precio' => $price > 0 ? $price : 0.01,
                        'stock_actual' => 10,
                        'stock_minimo' => 5,
                        'estado' => 'disponible',
                        'imagen' => $localImagePath
                    ]);
                    $this->line(" - Guardado: {$name} ($price)");

                } catch (\Exception $e) {
                    $this->error("Error procesando un producto: " . $e->getMessage());
                }
            });
        }

        $this->info('Proceso completado.');
    }
}
