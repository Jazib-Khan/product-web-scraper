<?php

namespace App;

class Product
{
    public string $title;
    public float $price;
    public string $imageUrl;
    public int $capacityMB;
    public string $colour;
    public string $availabilityText;
    public bool $isAvailable;
    public string $shippingText;
    public ?string $shippingDate;

    public function __construct(
        string $title,
        float $price,
        string $imageUrl,
        int $capacityMB,
        string $colour,
        string $availabilityText,
        bool $isAvailable,
        string $shippingText,
        ?string $shippingDate
    ) {
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

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'price' => $this->price,
            'imageUrl' => $this->imageUrl,
            'capacityMB' => $this->capacityMB,
            'colour' => $this->colour,
            'availabilityText' => $this->availabilityText,
            'isAvailable' => $this->isAvailable,
            'shippingText' => $this->shippingText,
            'shippingDate' => $this->shippingDate
        ];
    }
}
