<?php

namespace App;

class Product
{
    // keep these public for simplicity in this simple script
    public $title;
    public $price;
    public $imageUrl;
    public $capacityMB;
    public $colour;
    public $availabilityText;
    public $isAvailable;
    public $shippingText;
    public $shippingDate;

    public function __construct($title, $price, $imageUrl, $capacityMB, $colour, $availabilityText, $isAvailable, $shippingText, $shippingDate)
    {
        $this->title = $title;
        $this->price = $price;
        $this->imageUrl = $imageUrl;
        $this->capacityMB = $capacityMB;
        $this->colour = $colour;
        $this->availabilityText = $availabilityText;
        $this->isAvailable = $isAvailable;
        $this->shippingText = $shippingText;
        $this->shippingDate = $shippingDate;
    }
}
