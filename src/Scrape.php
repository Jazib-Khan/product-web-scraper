<?php

namespace App;

$autoloadPath = 'vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require $autoloadPath;
} else {
    $autoloadPath = '../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require $autoloadPath;
    } else {
        die('Error: Could not find autoload.php in vendor/ or ../vendor/');
    }
}

class Scrape
{
    private array $products = [];
    private array $seenProductKeys = [];
    private string $baseUrl = 'https://www.magpiehq.com/developer-challenge/smartphones';

    public function run(): void
    {
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $url = $this->baseUrl . '?page=' . $page;
            echo "Scraping page $page: $url\n";

            try {
                $document = ScrapeHelper::fetchDocument($url);
                $productCount = $document->filter('.product')->count();
                echo "Found $productCount products on page $page\n";

                if ($productCount > 0) {
                    $this->scrapeProductsFromPage($document);
                    $page++;
                } else {
                    $hasMorePages = false;
                }
            } catch (\Exception $e) {
                echo "Error scraping page $page: " . $e->getMessage() . "\n";
                $hasMorePages = false;
            }
        }

        // Convert Product objects to arrays and save to JSON
        $outputProducts = array_map(function (Product $product) {
            return $product->toArray();
        }, $this->products);

        file_put_contents('output.json', json_encode($outputProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "Scraped " . count($outputProducts) . " products and saved to output.json\n";
    }

    private function scrapeProductsFromPage(\Symfony\Component\DomCrawler\Crawler $document): void
    {
        $document->filter('.product')->each(function (\Symfony\Component\DomCrawler\Crawler $product) {
            try {
                // Extract title and capacity
                $title = trim($product->filter('h3 .product-name')->text());
                $capacityText = trim($product->filter('h3 .product-capacity')->text());
                $capacityMB = ScrapeHelper::extractCapacityInMB($capacityText);

                // Extract price
                $priceText = trim($product->filter('div.my-8')->text());
                $price = (float) preg_replace('/[^0-9.]/', '', $priceText);

                // Extract image URL
                $imageUrl = $product->filter('img')->attr('src');
                if (strpos($imageUrl, 'http') !== 0) {
                    if (strpos($imageUrl, '../') === 0) {
                        $imageUrl = substr($imageUrl, 3);
                        $imageUrl = 'https://www.magpiehq.com/developer-challenge/' . $imageUrl;
                    } else {
                        $imageUrl = 'https://www.magpiehq.com/developer-challenge/' . ltrim($imageUrl, '/');
                    }
                }

                // Extract availability
                $divs = $product->filter('div.my-4');
                $availabilityText = '';
                $shippingText = '';

                // Loop through all div.my-4 elements to correctly identify which is which
                for ($i = 0; $i < $divs->count(); $i++) {
                    $text = trim($divs->eq($i)->text());

                    // If text contains "Availability:", it's the availability text
                    if (strpos($text, 'Availability:') !== false) {
                        $availabilityText = $text;
                    } else {
                        // Otherwise, it's shipping text
                        if (empty($shippingText)) {
                            $shippingText = $text;
                        }
                    }
                }

                // Determine if product is available (must check for different "In Stock" variations)
                $isAvailable = strpos($availabilityText, 'In Stock') !== false;

                // Extract shipping date from shipping text
                $shippingDate = ScrapeHelper::extractShippingDate($shippingText);

                // Extract colors
                $colors = $product->filter('span[data-colour]')->each(function (\Symfony\Component\DomCrawler\Crawler $color) {
                    return $color->attr('data-colour');
                });

                // Add product for each color
                foreach ($colors as $color) {
                    $this->addProduct(
                        $title,
                        $price,
                        $imageUrl,
                        $capacityMB,
                        $color,
                        $availabilityText,
                        $isAvailable,
                        $shippingText,
                        $shippingDate
                    );
                }
            } catch (\Exception $e) {
                echo "Error processing product: " . $e->getMessage() . "\n";
            }
        });
    }

    private function addProduct(
        string $title,
        float $price,
        string $imageUrl,
        int $capacityMB,
        string $color,
        string $availabilityText,
        bool $isAvailable,
        string $shippingText,
        ?string $shippingDate
    ): void {
        $key = md5($title . $capacityMB . $color);

        if (!isset($this->seenProductKeys[$key])) {
            $this->seenProductKeys[$key] = true;

            $product = new Product(
                $title,
                $price,
                $imageUrl,
                $capacityMB,
                $color,
                $availabilityText,
                $isAvailable,
                $shippingText,
                $shippingDate
            );

            $this->products[] = $product;
            echo "Added product: $title ($color, {$capacityMB}MB)\n";
        } else {
            echo "Skipped duplicate: $title ($color, {$capacityMB}MB)\n";
        }
    }
}

$scrape = new Scrape();
$scrape->run();
