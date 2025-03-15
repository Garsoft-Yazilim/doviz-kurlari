# TCMBExchangeRates

## Açıklama
TCMBExchangeRates sınıfı, Türkiye Cumhuriyet Merkez Bankası'ndan (TCMB) günlük döviz kurlarını çekmek ve kullanmak için geliştirilmiştir. PHP ile yazılmış olup XML verisini kullanarak döviz kurlarıyla ilgili çeşitli işlemler yapmanıza olanak tanır.

## Özellikler
- TCMB'nin günlük döviz kuru XML verisini çeker.
- Döviz kurları ile ilgili detaylı bilgileri döndürür.
- Belirli bir tarihe göre döviz kurlarını çekme desteği.
- Döviz alım/satım kurlarını alma.
- Döviz dönüştürme işlemleri.

## Gereksinimler
- PHP 7.4 veya üzeri
- `SimpleXMLElement` desteği
- TCMB'nin döviz kuru XML dosyasına erişim

## Kurulum
1. **Dosyayı projenize dahil edin:**
    ```php
    require_once 'TCMBExchangeRates.php';
    ```

2. **TCMBExchangeRates sınıfını başlatın:**
    ```php
    $exchange = new TCMBExchangeRates();
    ```

## Kullanım

### Tüm Döviz Kurlarını Almak
```php
$exchange = new TCMBExchangeRates();
$currencies = $exchange->getAllCurrencies();
print_r($currencies);
```

### Belirli Bir Döviz Kurunu Almak
```php
$usdRates = $exchange->getCurrency('USD');
print_r($usdRates);
```

### Döviz Alış/Satış Kurlarını Almak
```php
$usdSellingRate = $exchange->getSellingRate('USD');
$usdBuyingRate = $exchange->getBuyingRate('USD');
```

### Döviz Dönüştürme
```php
$amountInTRY = $exchange->convert(100, 'USD', 'TRY');
```

### Belirli Tarihteki Döviz Kurlarını Çekmek
```php
$exchange = new TCMBExchangeRates('25-01-2023');
$eurRates = $exchange->getCurrency('EUR');
```

## Hata Kontrolü
Sınıf içerisinde hata yönetimi sağlanmıştır. Eğer bir hata oluşursa aşağıdaki gibi kontrol edebilirsiniz:
```php
if ($exchange->hasError()) {
    echo "Hata: " . $exchange->getErrorMessage();
}
```

## Lisans
Bu proje açık kaynaklıdır ve MIT lisansı altındadır.

