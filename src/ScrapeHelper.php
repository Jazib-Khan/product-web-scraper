<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeHelper
{
    public static function fetchDocument(string $url): Crawler
    {
        $client = new Client();

        $response = $client->get($url);

        return new Crawler($response->getBody()->getContents(), $url);
    }

    public static function extractCapacityInMB(string $title): int
    {
        if (preg_match('/(\d+)\s*(TB|GB)/i', $title, $matches)) {
            $value = (int)$matches[1];
            $unit = strtoupper($matches[2]);

            return $unit === 'TB' ? $value * 1000000 : $value * 1000;
        }

        return 0;
    }

    public static function extractShippingDate(string $shippingText): ?string
    {
        if (preg_match('/(\d+)(st|nd|rd|th)\s+([A-Za-z]+)/', $shippingText, $matches)) {
            $day = $matches[1];
            $month = $matches[3];
            $year = date('Y');

            $date = \DateTime::createFromFormat('j F Y', "$day $month $year");
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
