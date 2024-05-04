<?php

namespace App;

require 'vendor/autoload.php';
use Symfony\Component\DomCrawler\Crawler;

class Scrape
{
    private array $products = [];

    public function run(): void
    {
        $document = ScrapeHelper::fetchDocument('https://www.magpiehq.com/developer-challenge/smartphones');

        // Extracting all different page links from the page and running same script on each
        $pageLinks = $document->filter('a')->extract(['href']);
        foreach ($pageLinks as $pageLink) {
            $crawler = ScrapeHelper::fetchDocument($this->convertToFullUrl($pageLink));
            
            // select each product and handle separately
            $productNodes = $crawler->filter('.product');
            foreach ($productNodes as $productNode) {
                $this->processProduct($productNode);
            }
        }

        file_put_contents('output.json', json_encode($this->products, JSON_PRETTY_PRINT));
    }

    
    // Extracting properties of a product and error handling if they don't exist.
    private function processProduct($productNode) {
        $productCrawler = new Crawler($productNode);

        $title = $productCrawler->filter('.product-name')->text('N/A');

        $capacityGB = $productCrawler->filter('.product-capacity')->text('N/A');
        $capacityMB = $this->convertCapacityToMB($capacityGB);

        $relativeImageUrlNode = $productCrawler->filter('img');
        $fullImageUrl = $relativeImageUrlNode->count() ? 
            $this->convertToFullUrl($relativeImageUrlNode->attr('src')) : 'N/A';
        
        $price = $productCrawler->filter('.text-lg')->text('N/A');
        $price = $this->cleanPrice($price);  // remove currency signs

        // shipping text is second in the array of elements matched by '.text-sm'
        $availabilityText = $productCrawler->filter('.text-sm')->text('N/A');
        $availabilityText = str_replace('Availability: ', '', $availabilityText);
        $isAvailable = $this->isAvailable($availabilityText);

        $shippingText = $productCrawler->filter('.text-sm')->eq(1)->text('N/A');
        $shippingDate = $this->getShippingDate($shippingText);

        $coloursNode = $productCrawler->filter('span[data-colour]');
        $colours = $this->getColours($coloursNode);

        // create a new product initialized with all the extracted data for each colour
        foreach ($colours as $colour) {
            $product = new Product(
                $title,
                $price,
                $fullImageUrl,
                $capacityMB,
                $colour,
                $availabilityText,
                $isAvailable,
                $shippingText,
                $shippingDate
            );

            // Do not add if it is a duplicate.
            if ($this->isDuplicate($product)) {
                return;
            } else {
                $this->printProductData($product);            
                $this->products[] = $product;
            }
        }
    }

    // check if another exact same product object is in the products array.
    // (No exact duplicates in this example task).
    private function isDuplicate($productToCheck) {
        foreach ($this->products as $product) {
            if ($productToCheck == $product) {
                return true;
            }
        }
        return false;
    }

    // For debugging
    private function printProductData($product) {
        echo "Title: " . $product->title . PHP_EOL;
        echo "Price: " . $product->price . PHP_EOL;
        echo "Url: " . $product->imageUrl . PHP_EOL;
        echo "CapacityMB: " . $product->capacityMB . PHP_EOL;
        echo "Colour: " . $product->colour . PHP_EOL;
        echo "Availability Text: " . $product->availabilityText . PHP_EOL;
        echo "Is Available: " . $product->isAvailable . PHP_EOL;
        echo "Shipping Text: " . $product->shippingText . PHP_EOL;
        echo "Shipping Date: " . $product->shippingDate . PHP_EOL;
        echo PHP_EOL;
    }

    // Get colours from colour node.
    private function getColours($coloursNode) {
        $colours = [];
        foreach ($coloursNode as $colourNode) {
            $colour = $colourNode->getAttribute('data-colour');
            $colours[] = $colour;
        }
        return $colours;
    }

    // Get shipping date from shipping text. 
    private function getShippingDate($shippingText) {
        // Extract the date portion from the text
        if (preg_match("/(\d{1,2}(st|nd|rd|th)? [A-Za-z]+ \d{4})/", $shippingText, $matches)) {
            $dateString = $matches[1]; // Extracted date string
            // Convert the extracted date string into a timestamp
            $time = strtotime($dateString);
        
            return date("Y-m-d", $time);
        } elseif (preg_match("/(\d{4}-\d{2}-\d{2})/", $shippingText, $matches)) {
            // If the date is in the format yyyy-mm-dd
            return $matches[1];
        } elseif (stripos($shippingText, 'tomorrow') !== false) {
            // If the input is 'tomorrow'
            $tomorrow = strtotime('+1 day');
            return date("Y-m-d", $tomorrow);
        } else {
            return 'N/A';
        }
    }

    // If text contains "In Stock" then mark true.
    private function isAvailable($availabilityText) {
        return str_contains($availabilityText, "In Stock");
    }

    // Helper function to convert capacity from a string in GB, to an int in MB.
    // Note we use (1 GB = 1000 MB) instead of (1 GB = 1024 MB) for cleaner numbers.
    private function convertCapacityToMB($capacity) {
        if ($capacity !== 'N/A') {
            // Remove any non-numeric characters (e.g., "GB")
            $numericValue = (float) preg_replace('/[^0-9.]/', '', $capacity);
            return $numericValue * 1000;
        } else {
            return 'N/A';
        }
    }

    // Convert from relative Url to full Url
    private function convertToFullUrl($relativeUrl) {
        // Replace '../' with an empty string to remove parent directory reference
        $baseUrl = 'https://www.magpiehq.com/developer-challenge/';
        $cleanedRelativeUrl = str_replace('../', '', $relativeUrl);
        $fullUrl = $baseUrl . $cleanedRelativeUrl;

        return $fullUrl;
    }

    // Function to remove currency signs
    private function cleanPrice($price) {
        $currencySigns = ['$', '€', '£'];
        $cleanedPrice = str_replace($currencySigns, '', $price);
        return floatval($cleanedPrice);
    }
}

$scrape = new Scrape();
$scrape->run();
