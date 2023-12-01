<?php

namespace App\Services;

use App\Exception\InvalidExchangeRateRequestException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class ExchangeRateService
 *
 * Service for fetching exchange rates between currencies.
 */
class ExchangeRateService
{
    /**
     * Constant representing the USD currency code.
     */
    public const USD_CURRENCY = "USD";

    /**
     * ExchangeRateService constructor.
     *
     * @param HttpClientInterface $httpClient HTTP client used for making requests.
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Get the exchange rate between two currencies.
     *
     * @param  string  $fromCurrency  The currency to convert from.
     * @param  string  $toCurrency  The currency to convert to.
     *
     * @return int|float The exchange rate.
     *
     * @throws InvalidArgumentException
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): int|float
    {
        $cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($fromCurrency, $toCurrency): int|float {
            $item->expiresAfter(3600);

            return $this->fetchExchangeRate($fromCurrency, $toCurrency);
        });
    }

    /**
     *  Fetch the exchange rate from an external API.
     *
     * @param  string  $fromCurrency  The currency to convert from.
     * @param  string  $toCurrency  The currency to convert to.
     *
     * @return int|float The fetched exchange rate.
     *
     * @throws TransportExceptionInterface
     * @throws InvalidExchangeRateRequestException
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function fetchExchangeRate(string $fromCurrency, string $toCurrency): int|float
    {
        $fromCurrency = strtolower($fromCurrency);
        $toCurrency = strtolower($toCurrency);

        $url = "https://cdn.jsdelivr.net/gh/fawazahmed0/currency-api@1/latest/currencies/{$fromCurrency}/{$toCurrency}.json";
        $response = $this->httpClient->request(Request::METHOD_GET, $url);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new InvalidExchangeRateRequestException(
                'Please double-check the data submitted for the currency conversion service.'
            );
        }

        $data = json_decode($response->getContent(), true);
        return $data[strtolower($toCurrency)];
    }
}