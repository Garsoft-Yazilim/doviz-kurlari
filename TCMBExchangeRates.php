<?php
/**
 * TCMB Döviz Kurları Sınıfı
 * 
 * Bu sınıf, Türkiye Cumhuriyet Merkez Bankası'ndan güncel döviz kurlarını çeker.
 * 
 * @author Claude
 * @version 1.1
 */
class TCMBExchangeRates {
    /**
     * TCMB XML URL'si
     * 
     * @var string
     */
    private $xmlUrl;
    
    /**
     * XML verisi
     * 
     * @var SimpleXMLElement|null
     */
    private $xml = null;
    
    /**
     * Hata mesajı
     * 
     * @var string|null
     */
    private $errorMessage = null;
    
    /**
     * Kurlar için tarih
     * 
     * @var string|null
     */
    private $date = null;
    
    /**
     * Yapılandırıcı
     * 
     * @param string $date Tarih (Format: gün-ay-yıl, Örnek: 25-01-2023)
     */
    public function __construct($date = null) {
        if ($date === null) {
            // Bugünün tarihi için
            $this->xmlUrl = 'https://www.tcmb.gov.tr/kurlar/today.xml';
        } else {
            // Belirli bir tarih için
            $dateObj = DateTime::createFromFormat('d-m-Y', $date);
            if ($dateObj === false) {
                $this->errorMessage = 'Geçersiz tarih formatı. Format: gün-ay-yıl (Örnek: 25-01-2023)';
                return;
            }
            
            $year = $dateObj->format('Y');
            $month = $dateObj->format('m');
            $day = $dateObj->format('d');
            
            $this->xmlUrl = "https://www.tcmb.gov.tr/kurlar/{$year}{$month}/{$day}{$month}{$year}.xml";
        }
        
        $this->date = $date;
        $this->loadXML();
    }
    
    /**
     * XML verilerini yükler
     * 
     * @return bool Başarılı olup olmadığı
     */
    private function loadXML() {
        try {
            $context = stream_context_create([
                'http' => [
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'timeout' => 10
                ]
            ]);
            
            $xmlContent = @file_get_contents($this->xmlUrl, false, $context);
            
            if ($xmlContent === false) {
                $this->errorMessage = 'TCMB XML verileri yüklenemedi. URL kontrol edilemedi: ' . $this->xmlUrl;
                return false;
            }
            
            $this->xml = new SimpleXMLElement($xmlContent);
            return true;
        } catch (Exception $e) {
            $this->errorMessage = 'XML ayrıştırma hatası: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Bir hata olup olmadığını kontrol eder
     * 
     * @return bool Hata var mı
     */
    public function hasError() {
        return $this->errorMessage !== null;
    }
    
    /**
     * Hata mesajını döndürür
     * 
     * @return string|null Hata mesajı
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    /**
     * Tüm döviz kurlarını döndürür
     * 
     * @return array Tüm döviz kurları
     */
    public function getAllCurrencies() {
        if ($this->hasError() || $this->xml === null) {
            return [];
        }
        
        $currencies = [];
        
        foreach ($this->xml->Currency as $currency) {
            $code = (string)$currency['CurrencyCode'];
            $currencies[$code] = [
                'code' => $code,
                'name' => (string)$currency->CurrencyName,
                'forex_buying' => (string)$currency->ForexBuying,
                'forex_selling' => (string)$currency->ForexSelling,
                'banknote_buying' => (string)$currency->BanknoteBuying,
                'banknote_selling' => (string)$currency->BanknoteSelling,
                'unit' => (int)$currency->Unit,
                'cross_rate' => (string)$currency->CrossRateUSD,
                'cross_rate_other' => (string)$currency->CrossRateOther
            ];
        }
        
        return $currencies;
    }
    
    /**
     * Belirli bir döviz kodu için kurları döndürür
     * 
     * @param string $currencyCode Döviz kodu (örn. USD, EUR)
     * @return array|null Döviz kuru bilgileri veya bulunamazsa null
     */
    public function getCurrency($currencyCode) {
        $currencies = $this->getAllCurrencies();
        
        return isset($currencies[strtoupper($currencyCode)]) 
            ? $currencies[strtoupper($currencyCode)] 
            : null;
    }
    
    /**
     * Döviz satış kurunu döndürür
     * 
     * @param string $currencyCode Döviz kodu (örn. USD, EUR)
     * @param string $type Kur tipi ('forex' veya 'banknote')
     * @return float|null Satış kuru veya bulunamazsa null
     */
    public function getSellingRate($currencyCode, $type = 'forex') {
        $currency = $this->getCurrency($currencyCode);
        
        if ($currency === null) {
            return null;
        }
        
        $key = $type . '_selling';
        return isset($currency[$key]) && $currency[$key] !== '' 
            ? (float)str_replace(',', '.', $currency[$key]) 
            : null;
    }
    
    /**
     * Döviz alış kurunu döndürür
     * 
     * @param string $currencyCode Döviz kodu (örn. USD, EUR)
     * @param string $type Kur tipi ('forex' veya 'banknote')
     * @return float|null Alış kuru veya bulunamazsa null
     */
    public function getBuyingRate($currencyCode, $type = 'forex') {
        $currency = $this->getCurrency($currencyCode);
        
        if ($currency === null) {
            return null;
        }
        
        $key = $type . '_buying';
        return isset($currency[$key]) && $currency[$key] !== '' 
            ? (float)str_replace(',', '.', $currency[$key]) 
            : null;
    }
    
    /**
     * Döviz çevirme işlemi yapar
     * 
     * @param float $amount Miktar
     * @param string $fromCurrency Kaynak döviz kodu
     * @param string $toCurrency Hedef döviz kodu
     * @param string $rateType Kur tipi ('forex' veya 'banknote')
     * @return float|null Çevrilen miktar veya hata durumunda null
     */
    public function convert($amount, $fromCurrency, $toCurrency, $rateType = 'forex') {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        
        // TL'den dövize çevirme
        if ($fromCurrency === 'TRY') {
            $rate = $this->getBuyingRate($toCurrency, $rateType);
            if ($rate === null) {
                return null;
            }
            return $amount / $rate;
        }
        
        // Dövizden TL'ye çevirme
        if ($toCurrency === 'TRY') {
            $rate = $this->getSellingRate($fromCurrency, $rateType);
            if ($rate === null) {
                return null;
            }
            return $amount * $rate;
        }
        
        // Dövizden dövize çevirme
        $fromRate = $this->getSellingRate($fromCurrency, $rateType);
        $toRate = $this->getBuyingRate($toCurrency, $rateType);
        
        if ($fromRate === null || $toRate === null) {
            return null;
        }
        
        $amountInTRY = $amount * $fromRate;
        return $amountInTRY / $toRate;
    }
    
    /**
     * Kurların çekildiği tarihi döndürür
     * 
     * @return string|null XML'deki tarih bilgisi veya bugünün tarihi
     */
    public function getDate() {
        if ($this->hasError() || $this->xml === null) {
            return null;
        }
        
        // XML'den tarih bilgisini al
        if (isset($this->xml['Date'])) {
            return (string)$this->xml['Date'];
        }
        
        // Belirtilen tarih varsa onu döndür, yoksa bugünü
        return $this->date ?? date('d.m.Y');
    }
    
    /**
     * Belirli bir para birimi listesi için kurları döndürür
     * 
     * @param array $currencyCodes İstenen döviz kodları listesi (örn. ['USD', 'EUR', 'GBP'])
     * @return array İstenen döviz kurları
     */
    public function getSelectedCurrencies(array $currencyCodes) {
        $result = [];
        foreach ($currencyCodes as $code) {
            $currency = $this->getCurrency($code);
            if ($currency !== null) {
                $result[$code] = $currency;
            }
        }
        return $result;
    }
}
?> 