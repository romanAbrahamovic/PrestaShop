<?php

/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\Localization\Currency\DataLayer;

use Exception;
use PrestaShop\PrestaShop\Adapter\Currency\CurrencyDataProvider;
use PrestaShop\PrestaShop\Core\Data\Layer\AbstractDataLayer;
use PrestaShop\PrestaShop\Core\Data\Layer\DataLayerException;
use PrestaShop\PrestaShop\Core\Localization\CLDR\CurrencyDataLayerInterface as CldrCurrencyDataLayerInterface;
use PrestaShop\PrestaShop\Core\Localization\Currency\CurrencyData;
use PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException;

/**
 * CLDR Currency Database (Doctrine) data layer
 *
 * Provides and persists currency data from/into database
 */
class CurrencyDatabase extends AbstractDataLayer implements CldrCurrencyDataLayerInterface
{

    protected $dataProvider;
    protected $localeCode;

    public function __construct(CurrencyDataProvider $dataProvider, $localeCode)
    {
        $this->dataProvider = $dataProvider;
        $this->localeCode   = $localeCode;
    }

    /**
     * Set the lower layer.
     * When reading data, if nothing is found then it will try to read in the lower data layer
     * When writing data, the data will also be written in the lower data layer
     *
     * @param CldrCurrencyDataLayerInterface $lowerLayer
     *  The lower data layer.
     *
     * @return self
     */
    public function setLowerLayer(CldrCurrencyDataLayerInterface $lowerLayer)
    {
        $this->lowerDataLayer = $lowerLayer;

        return $this;
    }

    /**
     * Actually read a data object into the current layer
     *
     * Data is read into database
     *
     * @param string $currencyCode
     *  The CurrencyData object identifier
     *
     * @return CurrencyData|null
     *  The wanted CurrencyData object (null if not found)
     */
    protected function doRead($currencyCode)
    {
        $currencyEntity = $this->dataProvider->getCurrencyByIsoCode($currencyCode);

        if (null === $currencyEntity) {
            return null;
        }

        $currencyData = new CurrencyData();

        $currencyData->isoCode                    = $currencyEntity->iso_code;
        $currencyData->names[$this->localeCode]   = $currencyEntity->name;
        $currencyData->numericIsoCode             = $currencyEntity->numeric_iso_code;
        $currencyData->symbols[$this->localeCode] = $currencyEntity->symbol;
        $currencyData->precision                  = $currencyEntity->precision;

        return $currencyData;
    }

    /**
     * @inheritDoc
     */
    public function write($id, $data)
    {
        if (!($data instanceof CurrencyData)) {
            throw new LocalizationException(
                '$data must be an instance of ' . CurrencyData::class
            );
        }

        return parent::write($id, $data);
    }

    /**
     * Actually write a data object into the current layer
     * Here, this is a DB insert/update...
     *
     * @param string $currencyCode
     *  The currency ISO 4217 code
     *
     * @param CurrencyData $currencyData
     *  The data object to be written
     *
     * @return void
     *
     * @throws DataLayerException
     *  If something goes wrong when trying to write into DB
     */
    protected function doWrite($currencyCode, $currencyData)
    {
        $currencyEntity = $this->dataProvider->getCurrencyByIsoCodeOrCreate($currencyCode);

        $currencyEntity->iso_code         = $currencyData->isoCode;
        $currencyEntity->name             = $currencyData->names[$this->localeCode];
        $currencyEntity->numeric_iso_code = $currencyData->numericIsoCode;
        $currencyEntity->symbol           = $currencyData->symbols[$this->localeCode];
        $currencyEntity->precision        = $currencyData->precision;

        try {
            $this->dataProvider->saveCurrency($currencyEntity);
        } catch (Exception $e) {
            throw new DataLayerException('Unable to persist data in DB data layer', 0, $e);
        }
    }
}
