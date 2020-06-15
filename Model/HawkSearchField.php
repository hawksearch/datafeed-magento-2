<?php
/**
 *  Copyright (c) 2020 Hawksearch (www.hawksearch.com) - All Rights Reserved
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *  FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *  IN THE SOFTWARE.
 */
declare(strict_types=1);

namespace HawkSearch\Datafeed\Model;

use HawkSearch\Datafeed\Api\Data\HawkSearchFieldInterface;
use Magento\Framework\DataObject;

class HawkSearchField extends DataObject implements HawkSearchFieldInterface
{
    public function __construct(
        array $data = [
            "FieldType" => "keyword",
            "Type" => "String",
            "Boost" => 1,
            "FacetHandler" => 0,
            "IsPrimaryKey" => false,
            "IsOutput" => false,
            "IsShingle" => false,
            "IsBestFragment" => false,
            "IsDictionary" => false,
            "IsSort" => false,
            "IsPrefix" => false,
            "IsHidden" => false,
            "IsCompare" => false,
            "PartialQuery" => "",
            "IsKeywordText" => true,
            "IsQuery" => false,
            "IsQueryText" => false,
            "SkipCustom" => false,
            "StripHtml" => false,
            "MinNGramAnalyzer" => 2,
            "MaxNGramAnalyzer" => 15,
            "CoordinateType" => 0,
            "OmitNorms" => false,
            "ItemMapping" => "",
            "DefaultValue" => "",
            "UseForPrediction" => false,
            "CopyTo" => "",
            "Analyzer" => "",
            "DoNotStore" => true,
            "Tags" => "",
            "Iterations" => [
                1
            ],
            "AnalyzerLanguage" => null,
            "PreviewMapping" => null,
            "OmitTfAndPos" => false
        ]
    ) {
        parent::__construct($data);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getData(static::NAME);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setName(string $value)
    {
        return $this->setData(static::NAME, $value);
    }

    /**
     * @return string
     */
    public function getFieldType(): string
    {
        return $this->getData(static::FIELD_TYPE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setFieldType(string $value)
    {
        return $this->setData(static::FIELD_TYPE, $value);
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->getData(static::LABEL);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setLabel(string $value)
    {
        return $this->setData(static::LABEL, $value);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->getData(static::TYPE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setType(string $value)
    {
        return $this->setData(static::TYPE, $value);
    }

    /**
     * @return int
     */
    public function getBoost(): int
    {
        return $this->getData(static::BOOST);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setBoost(int $value)
    {
        return $this->setData(static::BOOST, $value);
    }

    /**
     * @return int
     */
    public function getFacetHandler(): int
    {
        return $this->getData(static::FACET_HANDLER);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setFacetHandler(int $value)
    {
        return $this->setData(static::FACET_HANDLER, $value);
    }

    /**
     * @return bool
     */
    public function getIsPrimaryKey(): bool
    {
        return $this->getData(static::IS_PRIMARY_KEY);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsPrimaryKey(bool $value)
    {
        return $this->setData(static::IS_PRIMARY_KEY, $value);
    }

    /**
     * @return bool
     */
    public function getIsOutput(): bool
    {
        return $this->getData(static::IS_OUTPUT);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsOutput(bool $value)
    {
        return $this->setData(static::IS_OUTPUT, $value);
    }

    /**
     * @return bool
     */
    public function getIsShingle(): bool
    {
        return $this->getData(static::IS_SHINGLE);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsShingle(bool $value)
    {
        return $this->setData(static::IS_SHINGLE, $value);
    }

    /**
     * @return bool
     */
    public function getIsBestFragment(): bool
    {
        return $this->getData(static::IS_BEST_FRAGMENT);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsBestFragment(bool $value)
    {
        return $this->setData(static::IS_BEST_FRAGMENT, $value);
    }

    /**
     * @return bool
     */
    public function getIsDictionary(): bool
    {
        return $this->getData(static::IS_DICTIONARY);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsDictionary(bool $value)
    {
        return $this->setData(static::IS_DICTIONARY, $value);
    }

    /**
     * @return bool
     */
    public function getIsSort(): bool
    {
        return $this->getData(static::IS_SORT);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsSort(bool $value)
    {
        return $this->setData(static::IS_SORT, $value);
    }

    /**
     * @return bool
     */
    public function getIsPrefix(): bool
    {
        return $this->getData(static::IS_PREFIX);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsPrefix(bool $value)
    {
        return $this->setData(static::IS_PREFIX, $value);
    }

    /**
     * @return bool
     */
    public function getIsHidden(): bool
    {
        return $this->getData(static::IS_HIDDEN);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsHidden(bool $value)
    {
        return $this->setData(static::IS_HIDDEN, $value);
    }

    /**
     * @return bool
     */
    public function getIsCompare(): bool
    {
        return $this->getData(static::IS_COMPARE);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsCompare(bool $value)
    {
        return $this->setData(static::IS_COMPARE, $value);
    }

    /**
     * @return string
     */
    public function getPartialQuery(): string
    {
        return $this->getData(static::PARTIAL_QUERY);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPartialQuery(string $value)
    {
        return $this->setData(static::PARTIAL_QUERY, $value);
    }

    /**
     * @return bool
     */
    public function getIsKeywordText(): bool
    {
        return $this->getData(static::IS_KEYWORD_TEXT);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsKeywordText(bool $value)
    {
        return $this->setData(static::IS_KEYWORD_TEXT, $value);
    }

    /**
     * @return bool
     */
    public function getIsQuery(): bool
    {
        return $this->getData(static::IS_QUERY);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsQuery(bool $value)
    {
        return $this->setData(static::IS_QUERY, $value);
    }

    /**
     * @return bool
     */
    public function getIsQueryText(): bool
    {
        return $this->getData(static::IS_QUERY_TEXT);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setIsQueryText(bool $value)
    {
        return $this->setData(static::IS_QUERY_TEXT, $value);
    }

    /**
     * @return bool
     */
    public function getSkipCustom(): bool
    {
        return $this->getData(static::SKIP_CUSTOM);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setSkipCustom(bool $value)
    {
        return $this->setData(static::SKIP_CUSTOM, $value);
    }

    /**
     * @return bool
     */
    public function getStripHtml(): bool
    {
        return $this->getData(static::STRIP_HTML);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setStripHtml(bool $value)
    {
        return $this->setData(static::STRIP_HTML, $value);
    }

    /**
     * @return int
     */
    public function getMinNGramAnalyzer(): int
    {
        return $this->getData(static::MIN_N_GRAM_ANALYZER);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setMinNGramAnalyzer(int $value)
    {
        return $this->setData(static::MIN_N_GRAM_ANALYZER, $value);
    }

    /**
     * @return int
     */
    public function getMaxNGramAnalyzer(): int
    {
        return $this->getData(static::MAX_N_GRAM_ANALYZER);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setMaxNGramAnalyzer(int $value)
    {
        return $this->setData(static::MAX_N_GRAM_ANALYZER, $value);
    }

    /**
     * @return int
     */
    public function getCoordinateType(): int
    {
        return $this->getData(static::COORDINATE_TYPE);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setCoordinateType(int $value)
    {
        return $this->setData(static::COORDINATE_TYPE, $value);
    }

    /**
     * @return bool
     */
    public function getOmitNorms(): bool
    {
        return $this->getData(static::OMIT_NORMS);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setOmitNorms(bool $value)
    {
        return $this->setData(static::OMIT_NORMS, $value);
    }

    /**
     * @return string
     */
    public function getItemMapping(): string
    {
        return $this->getData(static::ITEM_MAPPING);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setItemMapping(string $value)
    {
        return $this->setData(static::ITEM_MAPPING, $value);
    }

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {
        return $this->getData(static::DEFAULT_VALUE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDefaultValue(string $value)
    {
        return $this->setData(static::DEFAULT_VALUE, $value);
    }

    /**
     * @return bool
     */
    public function getUseForPrediction(): bool
    {
        return $this->getData(static::USE_FOR_PREDICTION);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setUseForPrediction(bool $value)
    {
        return $this->setData(static::USE_FOR_PREDICTION, $value);
    }

    /**
     * @return string
     */
    public function getCopyTo(): string
    {
        return $this->getData(static::COPY_TO);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setCopyTo(string $value)
    {
        return $this->setData(static::COPY_TO, $value);
    }

    /**
     * @return string
     */
    public function getAnalyzer(): string
    {
        return $this->getData(static::ANALYZER);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setAnalyzer(string $value)
    {
        return $this->setData(static::ANALYZER, $value);
    }

    /**
     * @return bool
     */
    public function getDoNotStore(): bool
    {
        return $this->getData(static::DO_NOT_STORE);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setDoNotStore(bool $value)
    {
        return $this->setData(static::DO_NOT_STORE, $value);
    }

    /**
     * @return string
     */
    public function getTags(): string
    {
        return $this->getData(static::TAGS);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setTags(string $value)
    {
        return $this->setData(static::TAGS, $value);
    }

    /**
     * @return array
     */
    public function getIterations(): array
    {
        return $this->getData(static::ITERATIONS);
    }

    /**
     * @param array $value
     * @return $this
     */
    public function setIterations(array $value)
    {
        return $this->setData(static::ITERATIONS, $value);
    }

    /**
     * @return mixed
     */
    public function getAnalyzerLanguage()
    {
        return $this->getData(static::ANALYZER_LANGUAGE);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setAnalyzerLanguage($value)
    {
        return $this->setData(static::ANALYZER_LANGUAGE, $value);
    }

    /**
     * @return mixed
     */
    public function getPreviewMapping()
    {
        return $this->getData(static::PREVIEW_MAPPING);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setPreviewMapping($value)
    {
        return $this->setData(static::PREVIEW_MAPPING, $value);
    }

    /**
     * @return bool
     */
    public function getOmitTfAndPos(): bool
    {
        return $this->getData(static::OMIT_TF_ADN_POS);
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setOmitTfAndPos(bool $value)
    {
        return $this->setData(static::OMIT_TF_ADN_POS, $value);
    }

    /**
     * @return string
     */
    public function getCreateDate(): string
    {
        return $this->getData(static::CREATE_DATE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setCreateDate(string $value)
    {
        return $this->setData(static::CREATE_DATE, $value);
    }

    /**
     * @return string
     */
    public function getModifyDate(): string
    {
        return $this->getData(static::MODIFY_DATE);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setModifyDate(string $value)
    {
        return $this->setData(static::MODIFY_DATE, $value);
    }
}
