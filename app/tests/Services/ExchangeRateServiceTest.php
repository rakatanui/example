<?php

namespace App\Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ExchangeRateService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Exception\InvalidExchangeRateRequestException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ExchangeRateServiceTest extends TestCase
{
    public function testGetExchangeRateWithValidCache()
    {
        // Arrange
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockCache = $this->createMock(CacheInterface::class);

        $exchangeRateService = new ExchangeRateService($mockHttpClient, $mockCache);

        $cacheKey = 'exchange_rate_USD_EUR';
        $expectedExchangeRate = 1.2;

        $mockCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($expectedExchangeRate);

        // Act
        $exchangeRate = $exchangeRateService->getExchangeRate('USD', 'EUR');

        // Assert
        $this->assertEquals($expectedExchangeRate, $exchangeRate);
    }

    public function testGetExchangeRateWithInvalidCache()
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $cache = $this->createMock(CacheInterface::class);

        $exchangeRateService = new ExchangeRateService($httpClient, $cache);

        $cacheKey = 'exchange_rate_USD_EUR';

        $cache->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'))
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600);
                return $callback($item);
            });

        $httpClientResponse = $this->createMock(ResponseInterface::class);
        $httpClientResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $httpClientResponse->expects($this->once())
            ->method('getContent')
            ->willReturn('{"eur": 1.2}');
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://cdn.jsdelivr.net/gh/fawazahmed0/currency-api@1/latest/currencies/usd/eur.json')
            ->willReturn($httpClientResponse);

        // Act
        $exchangeRate = $exchangeRateService->getExchangeRate('USD', 'EUR');

        // Assert
        $this->assertEquals(1.2, $exchangeRate);
    }

    public function testGetExchangeRateWithInvalidResponse()
    {
        // Arrange
        $httpClient = $this->createMock(HttpClientInterface::class);
        $cache = $this->createMock(CacheInterface::class);

        $exchangeRateService = new ExchangeRateService($httpClient, $cache);

        $cacheKey = 'exchange_rate_XYZ_EUR';

        $cache->expects($this->once())
            ->method('get')
            ->with($cacheKey, $this->isType('callable'))
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600);
                return $callback($item);
            });

        $httpClientResponse = $this->createMock(ResponseInterface::class);
        $httpClientResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(404);
        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://cdn.jsdelivr.net/gh/fawazahmed0/currency-api@1/latest/currencies/xyz/eur.json')
            ->willReturn($httpClientResponse);

        // Assert
        $this->expectException(InvalidExchangeRateRequestException::class);

        // Act
        $exchangeRateService->getExchangeRate('XYZ', 'EUR');
    }
}
