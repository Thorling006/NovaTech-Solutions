<?php
require __DIR__ . '/vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;

$content = file_get_contents('C:\Users\anton\.gemini\antigravity-ide\brain\27f1b403-eaec-461a-85b6-d73111f35a9d\.system_generated\steps\666\content.md');
$crawler = new Crawler($content);

$nodes = $crawler->filter('.product-item');
echo "Found .product-item: " . $nodes->count() . "\n";
if ($nodes->count() == 0) {
    $nodes = $crawler->filter('.item.product');
    echo "Found .item.product: " . $nodes->count() . "\n";
}
if ($nodes->count() == 0) {
    $nodes = $crawler->filter('.product-layout');
    echo "Found .product-layout: " . $nodes->count() . "\n";
}

if ($nodes->count() > 0) {
    echo "First product HTML:\n";
    echo $nodes->first()->html() . "\n";
}
